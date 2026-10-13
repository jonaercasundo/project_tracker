<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingTransitionException;
use App\Exceptions\ExpiredBillingQrCodeException;
use App\Exceptions\InvalidBillingQrCodeException;
use App\Models\DeliveryBillingRecord;
use App\Models\User;
use App\Services\DeliveryBillingQrCodeService;
use App\Services\DeliveryBillingService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingScannerController extends Controller
{
    public function __construct(
        private DeliveryBillingQrCodeService $qrCodes,
        private DeliveryBillingService $billing
    ) {}

    public function index(): View
    {
        return view('billing.scanner');
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:2048'],
            'source' => ['required', 'in:qr,manual'],
        ]);

        if ($validated['source'] === 'qr') {
            try {
                $deliveryId = $this->qrCodes->resolveDeliveryId($validated['q']);
            } catch (ExpiredBillingQrCodeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 410);
            } catch (InvalidBillingQrCodeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            if ($deliveryId === null) {
                return response()->json([
                    'message' => 'This QR code does not identify a delivery. Scan a delivery label or search by DR number.',
                ], 422);
            }

            $rows = $this->deliveryQuery()->where('d.delivery_id', $deliveryId)->limit(1)->get();
        } else {
            $rows = $this->deliveryQuery()
                ->where('d.dr_no', trim($validated['q']))
                ->orderBy('d.delivery_id')
                ->limit(50)
                ->get();
        }

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'No delivery record matches that QR code or DR number.'], 404);
        }

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json([
            'records' => $rows->map(fn (object $delivery): array => $this->presentDelivery($delivery, $user))->values(),
            'message' => $rows->count() > 1
                ? 'This DR number has multiple delivery records. Select the correct school and project.'
                : 'Delivery record found.',
        ]);
    }

    public function markForBilling(Request $request, int $delivery): JsonResponse
    {
        $actor = $this->actor($request);

        return $this->runTransition(
            fn () => $this->billing->markForBilling($delivery, $actor),
            $delivery,
            'Marked For Billing.'
        );
    }

    public function confirmBilled(Request $request, int $delivery): JsonResponse
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'billing_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'billing_reference' => ['nullable', 'string', 'max:255'],
            'billing_remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $actor = $this->actor($request);

        return $this->runTransition(
            fn () => $this->billing->confirmBilled($delivery, $validated, $actor),
            $delivery,
            'Confirmed Billed. Payment status was not changed.'
        );
    }

    public function placeOnHold(Request $request, int $delivery): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);
        $actor = $this->actor($request);

        return $this->runTransition(
            fn () => $this->billing->placeOnHold($delivery, $validated['remarks'], $actor),
            $delivery,
            'Delivery placed On Hold.'
        );
    }

    public function releaseHold(Request $request, int $delivery): JsonResponse
    {
        $actor = $this->actor($request);

        return $this->runTransition(
            fn () => $this->billing->releaseHold($delivery, $actor),
            $delivery,
            'Billing hold released.'
        );
    }

    private function runTransition(callable $operation, int $deliveryId, string $message): JsonResponse
    {
        try {
            $operation();
        } catch (BillingTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->httpStatus);
        }

        $user = request()->user();
        abort_unless($user instanceof User, 401);
        $delivery = $this->deliveryQuery()->where('d.delivery_id', $deliveryId)->firstOrFail();

        return response()->json([
            'message' => $message,
            'record' => $this->presentDelivery($delivery, $user),
        ]);
    }

    private function deliveryQuery(): Builder
    {
        return DB::table('deliveries as d')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'billing.invoice_id')
            ->select([
                'd.delivery_id',
                'd.dr_no',
                'd.project_id',
                'p.project_name',
                'd.school_id',
                's.school_name',
                'd.delivery_date',
                'd.status as delivery_status',
                'billing.invoice_id',
                'billing.billing_amount',
                'billing.billing_reference',
                'billing.billing_remarks',
                'invoice.invoice_no',
                'invoice.invoice_date',
                'invoice.amount as invoice_amount',
                'invoice.status as invoice_status',
            ])
            ->selectRaw("COALESCE(billing.status, 'pending_billing') as billing_status");
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDelivery(object $delivery, User $user): array
    {
        $delivery->delivery_id = (int) $delivery->delivery_id;
        $delivery->billing_status = (string) $delivery->billing_status;
        $eligible = in_array($delivery->delivery_status, ['delivered', 'accepted'], true);
        $nextAction = match ($delivery->billing_status) {
            DeliveryBillingRecord::PENDING_BILLING => $eligible && $user->can('billing.for-billing.mark')
                ? 'mark_for_billing'
                : ($eligible ? 'awaiting_authorized_user' : 'ineligible'),
            DeliveryBillingRecord::FOR_BILLING => ! $eligible
                ? 'ineligible'
                : ($user->can('billing.billed.confirm') ? 'confirm_billed' : 'awaiting_authorized_user'),
            DeliveryBillingRecord::ON_HOLD => $user->can('billing.exceptions.manage')
                ? 'release_hold'
                : 'on_hold',
            DeliveryBillingRecord::BILLED => 'billed',
            default => 'unknown',
        };

        return [
            'delivery_id' => $delivery->delivery_id,
            'dr_no' => $delivery->dr_no,
            'project_id' => (int) $delivery->project_id,
            'project' => $delivery->project_name,
            'school_id' => $delivery->school_id,
            'school' => $delivery->school_name,
            'delivery_date' => $delivery->delivery_date,
            'delivery_status' => $delivery->delivery_status,
            'billing_status' => $delivery->billing_status,
            'invoice_id' => $delivery->invoice_id,
            'invoice_no' => $delivery->invoice_no,
            'invoice_date' => $delivery->invoice_date,
            'invoice_amount' => $delivery->invoice_amount,
            'invoice_status' => $delivery->invoice_status,
            'billing_amount' => $delivery->billing_amount,
            'billing_reference' => $delivery->billing_reference,
            'billing_remarks' => $delivery->billing_remarks,
            'eligible' => $eligible,
            'next_action' => $nextAction,
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
