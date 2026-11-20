<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DeliveryTrackingReport
{
    public const TYPES = [
        'dr-summary' => 'Delivery Summary by DR#',
        'school-summary' => 'Delivery Summary by School ID',
        'lot-summary' => 'Delivery Summary by Lot',
        'item-details' => 'Delivery Item Details',
        'complete' => 'Complete Delivery Report',
        'assigned-logistics' => 'Delivery Summary by Assigned Logistics',
    ];

    private const SHEETS = [
        'dr-summary' => ['DR Summary', ['DR #', 'Project', 'School ID', 'School Name', 'Address', 'Region', 'Division', 'Municipality', 'Assigned Logistics', 'Lots', 'Delivery Date', 'Delivered Date', 'Accepted Date', 'Status', 'Delivery Records', 'Package Quantity', 'Packages', 'Completed Packages']],
        'school-summary' => ['School Summary', ['School ID', 'School Name', 'Project', 'Address', 'Region', 'Division', 'Municipality', 'Total DRs', 'Delivered DRs', 'Remaining DRs', 'Delivery Progress (%)', 'Delivery Records', 'Package Quantity', 'Packages', 'Completed Packages', 'Last Delivered Date', 'Lots']],
        'lot-summary' => ['Lot Summary', ['Lot ID', 'Lot', 'Project', 'Schools', 'Total DRs', 'Delivered DRs', 'Remaining DRs', 'Delivery Progress (%)', 'Delivery Records', 'Package Quantity', 'Packages', 'Completed Packages', 'Last Delivered Date']],
        'assigned-logistics' => ['Logistics Assignment Summary', ['Assigned Logistics', 'Provider ID', 'Total DRs', 'Schools', 'Delivered DRs', 'Remaining DRs', 'Delivery Progress (%)', 'Delivery Records', 'Package Quantity', 'Packages', 'Completed Packages', 'Last Delivered Date']],
        'item-details' => ['Item Details', ['DR #', 'Delivery ID', 'Project', 'School ID', 'School Name', 'Region', 'Division', 'Municipality', 'Assigned Logistics', 'Lot', 'Keystage', 'Package ID', 'Package Status', 'Item', 'Quantity per Package', 'Package Quantity', 'Total Item Quantity', 'Delivery Status', 'Delivery Date', 'Delivered Date', 'Accepted Date']],
        'filters' => ['Applied Filters', ['Field', 'Value']],
    ];

    /** @param array<string, string|null> $filters */
    public function write(Builder $deliveries, Closure $withPackages, string $type, string $scope, array $filters, string $user, CarbonInterface $generatedAt): void
    {
        $workbook = new Spreadsheet;
        $workbook->removeSheetByIndex(0);

        try {
            $sheets = [];
            foreach (self::SHEETS as $key => [$title, $headings]) {
                if ($type === 'complete' || $key === $type || $key === 'filters') {
                    $sheet = $workbook->createSheet()->setTitle($title);
                    $this->row($sheet, 1, $headings);
                    $sheets[$key] = $sheet;
                }
            }

            $this->populate($deliveries, $withPackages, $sheets, $type, $scope, $filters, $user, $generatedAt);

            foreach ($sheets as $key => $sheet) {
                $headings = self::SHEETS[$key][1];
                $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:'.$lastColumn.$sheet->getHighestDataRow());
                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '263C59']],
                ]);
                foreach ($headings as $index => $heading) {
                    $width = match ($heading) {
                        'Project', 'School Name', 'Address', 'Item', 'Assigned Logistics', 'Lot', 'Lots' => 36,
                        'Value' => 65,
                        default => max(15, strlen($heading) + 4),
                    };
                    $sheet->getColumnDimensionByColumn($index + 1)->setWidth($width);
                }
            }

            $workbook->setActiveSheetIndex(0);
            (new Xlsx($workbook))->save('php://output');
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function populate(Builder $query, Closure $withPackages, array $sheets, string $type, string $scope, array $filters, string $user, CarbonInterface $generatedAt): void
    {
        $drs = [];
        $providerDrs = [];
        $lotDrs = [];
        $itemRow = 2;
        (clone $query)->select([
            'd.delivery_id', 'd.project_id', 'd.dr_no', 'd.school_id', 'd.status',
            'd.delivery_date', 'd.delivered_date', 'd.accepted_date', 'd.package_qty',
            's.school_name', 's.address', 's.region', 's.division', 's.municipality',
            'p.project_name', 'd.lot_id', 'l.lot_name', 'k.keystage_num',
            'provider.logistic_id as provider_id', 'provider.logistic_name as provider_name',
        ])->chunkById(250, function ($deliveries) use ($query, $withPackages, $sheets, &$drs, &$providerDrs, &$lotDrs, &$itemRow): void {
            $packages = $withPackages(
                (clone $query)->whereIntegerInRaw('d.delivery_id', $deliveries->pluck('delivery_id')->all()),
                isset($sheets['item-details']),
            );
            $columns = ['d.delivery_id', 'pk.package_id', 'ps.status as package_status'];
            if (isset($sheets['item-details'])) {
                array_push($columns, 'i.item_name', 'pc.qty as content_qty');
                $packages->orderBy('pc.item_id');
            }
            $packageRows = $packages->select($columns)->whereNotNull('pk.package_id')
                ->orderBy('pk.package_id')->get()->groupBy('delivery_id');

            foreach ($deliveries as $delivery) {
                $key = serialize([$delivery->project_id, (string) $delivery->school_id, (string) $delivery->dr_no]);
                $name = $delivery->provider_name ?? 'Unassigned';
                $provider = $delivery->provider_id ?? 'unassigned';
                $quantity = (int) ($delivery->package_qty ?? 1);
                $packagesById = [];
                foreach ($packageRows->get($delivery->delivery_id, []) as $package) {
                    $packagesById[$package->package_id] = $package->package_status ?: 'pending';
                    if (isset($sheets['item-details']) && $package->item_name !== null) {
                        $this->row($sheets['item-details'], $itemRow++, [
                            (string) $delivery->dr_no, $delivery->delivery_id, $delivery->project_name, (string) $delivery->school_id,
                            $delivery->school_name, $delivery->region, $delivery->division, $delivery->municipality,
                            $name, $delivery->lot_name, $delivery->keystage_num, $package->package_id,
                            $packagesById[$package->package_id], $package->item_name, (int) $package->content_qty,
                            $quantity, (int) $package->content_qty * $quantity, $delivery->status,
                            $this->date($delivery->delivery_date), $this->date($delivery->delivered_date), $this->date($delivery->accepted_date),
                        ]);
                    }
                }

                $drs[$key] ??= ['delivery' => $delivery, 'providers' => [], 'lots' => []];
                $drs[$key]['providers'][$provider] = $name;
                $drs[$key]['lots'][$delivery->lot_name] = $delivery->lot_name;
                $this->addDelivery($drs[$key], $delivery, $quantity, $packagesById);
                if (isset($sheets['lot-summary'])) {
                    $lotDrs[$delivery->lot_id][$key] ??= ['delivery' => $delivery];
                    $this->addDelivery($lotDrs[$delivery->lot_id][$key], $delivery, $quantity, $packagesById);
                }
                if (isset($sheets['assigned-logistics'])) {
                    $providerDrs[$provider][$key] ??= ['delivery' => $delivery];
                    $this->addDelivery($providerDrs[$provider][$key], $delivery, $quantity, $packagesById);
                }
            }
        }, 'd.delivery_id', 'delivery_id');

        $schools = [];
        $delivered = $assigned = 0;
        $row = 2;
        foreach ($drs as $dr) {
            $delivery = $dr['delivery'];
            $school = (string) $delivery->school_id;
            $schools[$school] ??= ['delivery' => $delivery, 'lots' => []];
            $schools[$school]['lots'] += $dr['lots'];
            $this->addDr($schools[$school], $dr);
            $delivered += (int) $this->isDelivered($dr);
            $assigned += (int) (array_keys($dr['providers']) !== ['unassigned']);

            if (isset($sheets['dr-summary'])) {
                $statuses = array_keys($dr['package_statuses'] ?? $dr['statuses']);
                $this->row($sheets['dr-summary'], $row++, [
                    (string) $delivery->dr_no, $delivery->project_name, $school, $delivery->school_name,
                    $delivery->address, $delivery->region, $delivery->division, $delivery->municipality,
                    implode(', ', $dr['providers']), implode(', ', $dr['lots']),
                    $this->date($dr['delivery_date']), $this->date($dr['delivered_date']), $this->date($dr['accepted_date']),
                    count($statuses) === 1 ? $statuses[0] : 'mixed',
                    $dr['records'], $dr['quantity'], $dr['packages'], $dr['completed_packages'],
                ]);
            }
        }

        if (isset($sheets['school-summary'])) {
            $row = 2;
            foreach ($schools as $school) {
                $delivery = $school['delivery'];
                $this->row($sheets['school-summary'], $row++, [
                    (string) $delivery->school_id, $delivery->school_name, $delivery->project_name,
                    $delivery->address, $delivery->region, $delivery->division, $delivery->municipality,
                    ...$this->totals($school), implode(', ', $school['lots']),
                ]);
            }
        }

        $row = 2;
        foreach ($lotDrs as $lotId => $receipts) {
            [$total, $schoolCount] = $this->summarizeReceipts($receipts);
            $delivery = reset($receipts)['delivery'];
            $this->row($sheets['lot-summary'], $row++, [
                $lotId, $delivery->lot_name, $delivery->project_name, $schoolCount, ...$this->totals($total),
            ]);
        }

        $row = 2;
        foreach ($providerDrs as $provider => $receipts) {
            [$total, $schoolCount] = $this->summarizeReceipts($receipts);
            $this->row($sheets['assigned-logistics'], $row++, [
                reset($receipts)['delivery']->provider_name ?? 'Unassigned',
                $provider === 'unassigned' ? null : $provider,
                $total['drs'], $schoolCount, ...array_slice($this->totals($total), 1),
            ]);
        }

        $metadata = [
            'Generated At ('.config('app.timezone').')' => Date::dateTimeToExcel($generatedAt),
            'Generated By' => $user,
            'Report Type' => self::TYPES[$type],
            'Export Scope' => $scope === 'selected' ? 'Selected records only' : 'All filtered records',
            'Project' => $filters['project'],
            'Lot' => $filters['lot'] ?? 'All Lots',
            'Region' => $filters['region'] ?? 'All Regions',
            'Division' => $filters['division'] ?? 'All Divisions',
            'Municipality' => $filters['municipality'] ?? 'All Municipalities',
            'Search' => $filters['search'] ?? '',
            'Delivery Status' => $filters['status'] ?? 'All',
            'Logistics Status' => $filters['logistics_status'] ?? 'All Logistics Statuses',
            'Assigned Logistics' => match ($filters['assigned_logistics'] ?? null) {
                null => 'All Assigned Logistics',
                'unassigned' => 'Unassigned',
                default => $filters['assigned_logistics'],
            },
            'Filtered DRs' => count($drs),
            'Filtered Schools' => count($schools),
            'Filtered Delivered DRs' => $delivered,
            'Assigned DRs' => $assigned,
            'Unassigned DRs' => count($drs) - $assigned,
        ];
        $row = 2;
        foreach ($metadata as $label => $value) {
            $this->row($sheets['filters'], $row++, [$label, $value]);
        }
        $sheets['filters']->getStyle('B2')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm:ss');
    }

    private function addDelivery(array &$dr, object $delivery, int $quantity, array $packageStatuses): void
    {
        $status = $delivery->status ?: 'pending';
        $dr['statuses'][$status] = true;
        $dr['records'] = ($dr['records'] ?? 0) + 1;
        $dr['quantity'] = ($dr['quantity'] ?? 0) + $quantity;
        $dr['completed_records'] = ($dr['completed_records'] ?? 0) + (int) in_array($status, ['delivered', 'accepted'], true);
        $dr['packages'] = ($dr['packages'] ?? 0) + count($packageStatuses);
        $dr['completed_packages'] = ($dr['completed_packages'] ?? 0) + count(array_intersect($packageStatuses, ['delivered', 'accepted']));
        foreach ($packageStatuses as $packageStatus) {
            $dr['package_statuses'][$packageStatus] = true;
        }
        $dr['delivery_date'] = min($dr['delivery_date'] ?? $delivery->delivery_date, $delivery->delivery_date);
        $dr['delivered_date'] = max($dr['delivered_date'] ?? null, $delivery->delivered_date);
        $dr['accepted_date'] = max($dr['accepted_date'] ?? null, $delivery->accepted_date);
    }

    private function isDelivered(array $dr): bool
    {
        return $dr['packages'] > 0
            ? $dr['completed_packages'] === $dr['packages']
            : $dr['completed_records'] === $dr['records'];
    }

    private function addDr(array &$group, array $dr): void
    {
        $group['drs'] = ($group['drs'] ?? 0) + 1;
        $group['delivered'] = ($group['delivered'] ?? 0) + (int) $this->isDelivered($dr);
        foreach (['records', 'quantity', 'packages', 'completed_packages'] as $metric) {
            $group[$metric] = ($group[$metric] ?? 0) + $dr[$metric];
        }
        $group['delivered_date'] = max($group['delivered_date'] ?? null, $dr['delivered_date']);
    }

    private function summarizeReceipts(array $receipts): array
    {
        $total = [];
        $schools = [];
        foreach ($receipts as $dr) {
            $this->addDr($total, $dr);
            $schools[$dr['delivery']->school_id] = true;
        }

        return [$total, count($schools)];
    }

    private function totals(array $group): array
    {
        return [
            $group['drs'], $group['delivered'], $group['drs'] - $group['delivered'],
            round(100 * $group['delivered'] / $group['drs'], 2),
            $group['records'], $group['quantity'], $group['packages'], $group['completed_packages'],
            $this->date($group['delivered_date']),
        ];
    }

    private function date(?string $date): ?Carbon
    {
        return $date && $date !== '0000-00-00' ? Carbon::parse($date) : null;
    }

    private function row(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $cell = Coordinate::stringFromColumnIndex($index + 1).$row;
            if ($value instanceof CarbonInterface) {
                $sheet->setCellValue($cell, Date::dateTimeToExcel($value));
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            } elseif (is_string($value)) {
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
            } elseif ($value !== null) {
                $sheet->setCellValue($cell, $value);
            }
        }
    }
}
