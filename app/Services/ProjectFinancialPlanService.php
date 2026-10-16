<?php

namespace App\Services;

use App\Models\Company;
use App\Models\MI_Product_Factory_Cost;
use App\Models\PplForm;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectFinancialPlanVersion;
use App\Models\ProjectLot;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectFinancialPlanService
{
    /** @var array<string, string> */
    private const INPUT_DEFAULTS = [
        'importation_vat_rate' => '0.13',
        'sales_vat_rate' => '0.12',
    ];

    /** @var array<string, string> */
    private const COST_LABELS = [
        'factory_cost' => 'Factory Cost',
        'pf1' => 'PF1',
        'pf2' => 'PF2',
        'pf3' => 'PF3',
        'll_commission' => 'LL Commission',
        'interest_dst_working_capital' => 'Interest & DST for Working Capital',
        'shipping_brokerage' => 'Shipping / Brokerage',
        'logistics' => 'Logistics (Door to Door)',
        'warehouse_rental' => 'Warehouse Rental',
        'other_expenses' => 'Other Expenses',
        'manpower' => 'Manpower / Others',
        'assembly_service_center' => 'Assembly / Service Center',
        'research_and_development' => 'R&D (Samples, Courier Cost)',
        'facilitation' => 'Facilitation',
        'tax_lawyer_allowance' => 'Allowance for Tax Lawyer',
        'municipal_tax' => 'Business Permit / Municipal Tax',
        'performance_bond' => 'Performance Bond',
        'goods_insurance' => 'Goods Insurance',
        'additional_costs' => 'Additional Costs',
    ];

    /** @var array<string, string> */
    private const WORKBOOK_REFERENCES = [
        'factory_cost' => 'PPL!I:I · FACTORY COST',
        'pf1' => 'PPL!K:K · PF1',
        'pf2' => 'PPL!L:L · PF2',
        'pf3' => 'PPL!M:M · PF3',
        'll_commission' => 'PPL!N:N · LL COM',
        'interest_dst_working_capital' => 'PPL!O:O · INTEREST & DST FOR WORKING CAPITAL',
        'shipping_brokerage' => 'PPL!P:P · SHIPPING / BROKERAGE',
        'logistics' => 'PPL!Q:Q · LOGISTICS (DOOR TO DOOR)',
        'warehouse_rental' => 'PPL!R:R · WAREHOUSE RENTAL',
        'other_expenses' => 'PPL!S:S · OTHER EXPENSES',
        'manpower' => 'PPL!T:T · MANPOWER / OTHERS',
        'assembly_service_center' => 'PPL!U:U · ASSEMBLY / SERVICE CENTER',
        'research_and_development' => 'PPL!V:V · R&D',
        'facilitation' => 'PPL!W:W · FACILITATION',
        'tax_lawyer_allowance' => 'PPL!X:X · ALLOWANCE FOR TAX LAWYER',
        'municipal_tax' => 'PPL!Y:Y · BUSINESS PERMIT / MUNICIPAL TAX',
        'performance_bond' => 'PPL!Z:Z · PERFORMANCE BOND',
        'goods_insurance' => 'PPL!AA:AA · GOODS INSURANCE',
    ];

    /** @var array<string, string> */
    private const COST_RATE_INPUTS = [
        'pf1' => 'pf1_rate', 'pf2' => 'pf2_rate', 'pf3' => 'pf3_rate',
        'll_commission' => 'll_commission_rate', 'interest_dst_working_capital' => 'interest_dst_rate',
        'logistics' => 'logistics_rate', 'other_expenses' => 'other_expenses_rate',
        'manpower' => 'manpower_rate', 'assembly_service_center' => 'assembly_rate',
        'research_and_development' => 'rd_rate', 'facilitation' => 'facilitation_rate',
        'tax_lawyer_allowance' => 'tax_lawyer_rate',
    ];

    public function __construct(private ProjectFinancialPlanCalculator $calculator) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $user, int $companyId): ProjectFinancialPlan
    {
        return DB::transaction(function () use ($data, $user, $companyId): ProjectFinancialPlan {
            $this->authorizeLinkedSources($data, $companyId);
            $this->resolveLotProjectInformation($data);
            $plan = ProjectFinancialPlan::query()->create([
                ...Arr::only($data, ['project_id', 'project_information_id', 'lot_id', 'ppl_form_id']),
                'company_id' => $companyId,
                'name' => $data['name'],
                'status' => 'draft',
                'created_by' => $user->getKey(),
                'updated_by' => $user->getKey(),
            ]);

            $this->createVersion($plan, $data, $user, 1);

            return $plan->load(['latestVersion', 'project', 'bidding', 'lot']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(ProjectFinancialPlan $plan, array $data, User $user, int $companyId): ProjectFinancialPlan
    {
        return DB::transaction(function () use ($plan, $data, $user, $companyId): ProjectFinancialPlan {
            $lockedPlan = ProjectFinancialPlan::query()
                ->forCompany($companyId)
                ->whereKey($plan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeLinkedSources($data, $companyId);
            $this->resolveLotProjectInformation($data);
            $lockedPlan->fill([
                ...Arr::only($data, ['project_id', 'project_information_id', 'lot_id', 'ppl_form_id']),
                'name' => $data['name'],
                'updated_by' => $user->getKey(),
            ])->save();

            $nextVersion = ((int) $lockedPlan->versions()->max('version_number')) + 1;
            $this->createVersion($lockedPlan, $data, $user, $nextVersion);

            return $lockedPlan->fresh()->load(['latestVersion', 'project', 'bidding', 'lot']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function createVersion(ProjectFinancialPlan $plan, array $data, User $user, int $versionNumber): ProjectFinancialPlanVersion
    {
        $inputs = array_replace(self::INPUT_DEFAULTS, Arr::except($data['inputs'], ['factory_cost_id']));
        $requiredInputs = [
            'contract_amount', 'abc', 'factory_cost', 'pf1_rate', 'pf2_rate', 'pf3_rate',
            'll_commission_rate', 'interest_dst_rate', 'shipping_containers', 'rate_per_container',
            'logistics_rate', 'warehouse_area', 'warehouse_rent_per_sqm', 'warehouse_months',
            'other_expenses_rate', 'manpower_rate', 'assembly_rate', 'rd_rate', 'facilitation_rate',
            'tax_lawyer_rate', 'opex_rate', 'income_tax_rate', 'incentive_rate',
            'importation_vat_rate', 'sales_vat_rate',
        ];
        $missingInputs = array_filter($requiredInputs, fn (string $key): bool => ! array_key_exists($key, $inputs) || $inputs[$key] === null || $inputs[$key] === '');
        if ($missingInputs !== []) {
            $errors = [];
            foreach ($missingInputs as $key) {
                $errors['inputs.'.$key] = 'Enter a value from this project’s PPL assumptions. Use 0 when Finance confirms the item does not apply.';
            }

            throw ValidationException::withMessages($errors);
        }
        $assumptions = array_replace($this->calculator->defaultAssumptions(), $data['assumptions'] ?? []);
        $additionalCosts = $data['cost_items'] ?? [];
        $results = $this->calculator->calculate($inputs, $assumptions, $additionalCosts);
        $pplForm = isset($data['ppl_form_id']) ? PplForm::query()->findOrFail($data['ppl_form_id']) : null;
        $factoryCostSnapshot = $this->factoryCostSnapshot($inputs, $data);

        $version = $plan->versions()->create([
            'version_number' => $versionNumber,
            'status' => 'draft',
            'change_summary' => $data['change_summary'] ?? ($versionNumber === 1 ? 'Initial financial draft' : 'Updated financial draft'),
            'input_data' => [
                'inputs' => $inputs,
                'additional_cost_items' => $additionalCosts,
                'source_ppl_form' => $pplForm?->getAttributes(),
            ],
            'calculation_results' => $results,
            'assumptions_snapshot' => [
                'workbook' => 'PPL MONITORING_24 (Autosaved).xlsx',
                'worksheet' => 'PPL / 1. FORM / 2. FACTORY COST / 3. MUNICIPAL TAX & INSURANCE',
                'rates' => $assumptions,
                'formula_manifest' => $this->calculator->formulaManifest(),
                'corrections' => [
                    'Municipal tax uses first-tier and excess rates only on their corresponding portions of net sales.',
                    'Zero net sales returns a null margin instead of a spreadsheet division error.',
                ],
            ],
            'factory_cost_snapshot' => $factoryCostSnapshot,
            'created_by' => $user->getKey(),
        ]);

        foreach ($results['costs'] as $category => $amount) {
            if ($category === 'additional_costs') {
                continue;
            }

            $rateInput = self::COST_RATE_INPUTS[$category] ?? null;
            $version->costItems()->create([
                'category' => $category,
                'label' => self::COST_LABELS[$category] ?? str($category)->replace('_', ' ')->title()->toString(),
                'amount' => $amount,
                'calculation_basis' => $category,
                'rate' => $rateInput ? ($inputs[$rateInput] ?? null) : null,
                'source_type' => 'workbook_formula',
                'source_reference' => self::WORKBOOK_REFERENCES[$category] ?? 'PPL worksheet financial summary',
                'source_snapshot' => ['formula' => $this->calculator->formulaManifest()[$category] ?? null],
            ]);
        }

        foreach ($additionalCosts as $cost) {
            $version->costItems()->create([
                'category' => 'additional_cost',
                'label' => $cost['label'],
                'amount' => $cost['amount'],
                'source_type' => $cost['source_type'] ?? null,
                'source_reference' => $cost['source_reference'] ?? null,
                'source_snapshot' => ['entry_type' => 'user_supplied'],
            ]);
        }

        return $version;
    }

    /** @param array<string, mixed> $data */
    private function resolveLotProjectInformation(array &$data): void
    {
        if (! empty($data['lot_id'])) {
            $lot = ProjectLot::query()->findOrFail($data['lot_id']);
            if (! empty($data['project_information_id']) && (int) $data['project_information_id'] !== (int) $lot->project_id) {
                throw ValidationException::withMessages(['lot_id' => 'The selected lot does not belong to the selected bidding document.']);
            }

            $data['project_information_id'] = $lot->project_id;
        }
    }

    /** @param array<string, mixed> $data */
    private function authorizeLinkedSources(array $data, int $companyId): void
    {
        $companyCode = Company::query()->whereKey($companyId)->value('code');
        if ($companyCode !== 'MMC' && (isset($data['project_id']) || isset($data['project_information_id']) || isset($data['lot_id']) || isset($data['ppl_form_id']))) {
            throw ValidationException::withMessages(['project_id' => 'Project, bidding, and legacy PPL records are currently owned by the MMC workspace.']);
        }

        if (! empty($data['inputs']['factory_cost_id']) && $companyCode !== 'MI') {
            throw ValidationException::withMessages(['inputs.factory_cost_id' => 'MI factory-cost snapshots are available only in the MI company workspace.']);
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function factoryCostSnapshot(array $inputs, array $data): array
    {
        $factoryCostId = $data['inputs']['factory_cost_id'] ?? null;
        if ($factoryCostId) {
            $factoryCost = MI_Product_Factory_Cost::query()->with('product')->findOrFail($factoryCostId);

            return [
                'source' => 'mi_product_factory_costs',
                'id' => $factoryCost->getKey(),
                'product_id' => $factoryCost->product_id,
                'product_name' => $factoryCost->product?->item_name,
                'factory_name' => $factoryCost->factory_name,
                'factory_cost' => $factoryCost->factory_cost,
                'currency' => $factoryCost->currency,
                'effective_date' => $factoryCost->effective_date?->toDateString(),
                'remarks' => $factoryCost->remarks,
                'plan_factory_cost_input' => $inputs['factory_cost'],
            ];
        }

        return [
            'source' => 'manual_project_financial_plan_input',
            'factory_cost' => $inputs['factory_cost'],
            'currency' => 'PHP',
        ];
    }
}
