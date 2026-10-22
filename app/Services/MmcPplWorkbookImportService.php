<?php

namespace App\Services;

use App\Models\MmcPplImportBatch;
use App\Models\MmcPplImportRow;
use App\Models\MmcPplImportSourceMapping;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;
use ZipArchive;

class MmcPplWorkbookImportService
{
    private const REQUIRED_SHEETS = ['1. FORM', '2. FACTORY COST', '3. MUNICIPAL TAX & INSURANCE', 'PPL'];

    private const FORM_COLUMNS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'M', 'N', 'O', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO',
        'AP', 'AT', 'AU', 'AV', 'AW', 'AX', 'AZ', 'BA',
    ];

    private const FACTORY_COST_COLUMNS = ['A', 'B', 'C', 'D', 'E', 'G', 'I', 'J', 'K', 'L'];

    private const PPL_COLUMNS = [
        'A', 'B', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W',
        'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
        'AN', 'AO',
    ];

    private const REQUIRED_FORM_HEADERS = [
        'A' => 'PROJECT CODE', 'B' => 'LOT #', 'C' => 'PROJECT TITLE', 'D' => 'PROJECT ID NO.',
        'M' => 'ABC', 'N' => 'LCB (ABC)', 'O' => 'FOREX', 'S' => 'PF1 (CONTRACT AMT)',
        'T' => 'PF2 (CONTRACT AMT)', 'U' => 'PF3 (CONTRACT AMT)', 'V' => 'LL COM (FACTORY COST)',
        'W' => 'INTEREST RATE + DST', 'Y' => 'Shipping / Brokerage (# of container)',
        'Z' => 'RATE PER CONTAINER', 'AA' => 'LOGISTICS (ABC)', 'AB' => 'WAREHOUSE AREA (SQM)',
        'AC' => 'WAREHOUSE RENTAL (PER SQM)', 'AD' => 'WAREHOUSE RENTAL (# of months)',
        'AE' => 'Other Expenses (bid docs, notarial fee, office supplies', 'AF' => 'Manpower / others (ABC)',
        'AG' => 'Assembly / Service Center (ABC)', 'AH' => 'R&D (samples, courier cost) (ABC)',
        'AI' => 'Facilitation', 'AJ' => 'Allowance for Tax Lawyer (NET SALES)',
        'AK' => 'OPEX (NET SALES)', 'AL' => 'INCOME TAX (NET SALES)', 'AM' => 'INCENTIVE (NET PROFIT)',
        'AN' => 'IMPORTATION VAT (COGS)', 'AO' => 'OUTPUT VAT', 'AX' => 'YEAR OF REVENUE RECOGNITION',
    ];

    private const REQUIRED_FACTORY_HEADERS = [
        'A' => 'PROJECT CODE', 'B' => 'LOT #', 'D' => 'ITEM #', 'E' => 'ITEM DESCRIPTION',
        'G' => 'FACTORY', 'I' => 'QTY', 'J' => 'Unit Cost (usd)', 'K' => 'Forex', 'L' => 'Amount in Php',
    ];

    private const REQUIRED_PPL_HEADERS = [
        'A' => 'PROJECT CODE', 'B' => 'LOT', 'G' => 'CONTRACT AMOUNT', 'H' => 'NET SALES',
        'I' => 'FACTORY COST', 'AB' => 'TOTAL COST OF SALES', 'AC' => 'GROSS PROFIT',
        'AD' => 'GPM', 'AE' => 'OPEX', 'AJ' => 'PPL', 'AK' => 'NPM', 'AM' => 'WORKING CAPITAL NEEDED',
    ];

    private const INPUT_CELL_MAP = [
        'pf1_rate' => 'S', 'pf2_rate' => 'T', 'pf3_rate' => 'U', 'll_commission_rate' => 'V',
        'interest_dst_rate' => 'W', 'shipping_containers' => 'Y', 'rate_per_container' => 'Z',
        'logistics_rate' => 'AA', 'warehouse_area' => 'AB', 'warehouse_rent_per_sqm' => 'AC',
        'warehouse_months' => 'AD', 'other_expenses_rate' => 'AE', 'manpower_rate' => 'AF',
        'assembly_rate' => 'AG', 'rd_rate' => 'AH', 'facilitation_rate' => 'AI',
        'tax_lawyer_rate' => 'AJ', 'opex_rate' => 'AK', 'income_tax_rate' => 'AL',
        'incentive_rate' => 'AM', 'importation_vat_rate' => 'AN', 'sales_vat_rate' => 'AO',
    ];

    private const MUNICIPAL_CELLS = [
        'bondable_rate' => 'B6', 'bond_premium_rate' => 'B7', 'bond_dst_rate' => 'B8',
        'bond_lgt_rate' => 'B9', 'bond_notarial_fee' => 'B10', 'bond_service_fee' => 'B11',
        'marine_premium_rate' => 'B15', 'marine_dst_rate' => 'B16', 'marine_lgt_rate' => 'B17',
        'fire_premium_rate' => 'B21', 'fire_dst_rate' => 'B22', 'fire_tax_rate' => 'B23',
        'fire_lgt_rate' => 'B24', 'inland_premium_rate' => 'B28', 'inland_dst_rate' => 'B29',
        'inland_lgt_rate' => 'B30', 'municipal_first_rate' => 'B34',
        'municipal_excess_rate' => 'B35', 'rrdm_rate' => 'B36',
    ];

    private const PPL_RESULT_CELLS = [
        'net_sales' => 'H', 'factory_cost' => 'I', 'costs.pf1' => 'K', 'costs.pf2' => 'L',
        'costs.pf3' => 'M', 'costs.ll_commission' => 'N', 'costs.interest_dst_working_capital' => 'O',
        'costs.shipping_brokerage' => 'P', 'costs.logistics' => 'Q', 'costs.warehouse_rental' => 'R',
        'costs.other_expenses' => 'S', 'costs.manpower' => 'T', 'costs.assembly_service_center' => 'U',
        'costs.research_and_development' => 'V', 'costs.facilitation' => 'W',
        'costs.tax_lawyer_allowance' => 'X', 'costs.municipal_tax' => 'Y',
        'costs.performance_bond' => 'Z', 'costs.goods_insurance' => 'AA',
        'total_cost_of_sales' => 'AB', 'gross_profit' => 'AC', 'gpm' => 'AD', 'opex' => 'AE',
        'net_profit_before_tax' => 'AF', 'income_tax_provision' => 'AG',
        'net_profit_after_tax' => 'AH', 'incentive' => 'AI', 'ppl' => 'AJ', 'npm' => 'AK',
        'importation_vat' => 'AL', 'working_capital_needed' => 'AM',
    ];

    public function __construct(
        private ProjectFinancialPlanCalculator $calculator,
        private ProjectFinancialPlanService $financialPlanService,
    ) {}

    /** @return array{batch: MmcPplImportBatch, duplicate: bool} */
    public function stage(UploadedFile $upload, User $user, int $companyId): array
    {
        $path = $upload->getPathname();
        $fileSize = filesize($path);
        $hash = hash_file('sha256', $path);
        if ($fileSize === false || $hash === false || $fileSize > 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'The workbook could not be read or exceeds the 20 MB upload limit.']);
        }

        $existingBatch = MmcPplImportBatch::query()
            ->where('company_id', $companyId)
            ->where('file_sha256', $hash)
            ->first();
        if ($existingBatch !== null) {
            return ['batch' => $existingBatch, 'duplicate' => true];
        }

        $this->validateArchive($path);
        $workbookData = $this->readWorkbook($path);
        $references = $this->loadReferences($companyId, $workbookData['form_rows']);
        $sourceRows = [];
        foreach ($workbookData['form_rows'] as $formRow) {
            $sourceRows[] = $this->prepareImportRow($formRow, $workbookData, $references);
        }

        $storagePath = 'mmc-ppl-imports/'.$companyId.'/'.Str::uuid().'.xlsx';
        if (! Storage::disk('local')->putFileAs(dirname($storagePath), $upload, basename($storagePath))) {
            throw ValidationException::withMessages(['file' => 'The workbook could not be stored for import review.']);
        }

        try {
            $batch = DB::transaction(function () use ($companyId, $user, $upload, $hash, $fileSize, $storagePath, $workbookData, $sourceRows): MmcPplImportBatch {
                $summary = $this->summarize($sourceRows, $workbookData['sheet_names']);
                $batch = MmcPplImportBatch::query()->create([
                    'company_id' => $companyId,
                    'created_by' => $user->getKey(),
                    'original_file_name' => Str::limit(basename($upload->getClientOriginalName()), 255, ''),
                    'file_sha256' => $hash,
                    'storage_path' => $storagePath,
                    'file_size_bytes' => $fileSize,
                    'status' => 'previewed',
                    'summary' => $summary,
                    'previewed_at' => now(),
                ]);

                foreach ($sourceRows as $sourceRow) {
                    $batch->rows()->create($sourceRow);
                }

                return $batch;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storagePath);
            throw $exception;
        }

        return ['batch' => $batch, 'duplicate' => false];
    }

    /** @return array{batch: MmcPplImportBatch, imported: int, skipped: int, blocked: int} */
    public function confirm(MmcPplImportBatch $batch, User $user, int $companyId, bool $acknowledgeWarnings): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $blockedCount = 0;

        try {
            DB::transaction(function () use ($batch, $user, $companyId, $acknowledgeWarnings, &$importedCount, &$skippedCount, &$blockedCount): void {
                $lockedBatch = MmcPplImportBatch::query()
                    ->where('company_id', $companyId)
                    ->whereKey($batch->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedBatch->status, ['previewed', 'failed'], true)) {
                    throw ValidationException::withMessages(['batch' => 'This import batch is no longer available for confirmation.']);
                }

                $rows = $lockedBatch->rows()->orderBy('source_row')->lockForUpdate()->get();
                $rowsWithWarnings = $rows->contains(fn (MmcPplImportRow $row): bool => $row->status === 'warning');
                if ($rowsWithWarnings && ! $acknowledgeWarnings) {
                    throw ValidationException::withMessages(['acknowledge_warnings' => 'Review and acknowledge the listed duplicate or reconciliation warnings before import.']);
                }

                $candidates = $rows->whereIn('status', ['ready', 'warning']);
                if ($candidates->isEmpty()) {
                    throw ValidationException::withMessages(['batch' => 'No rows are ready to import. Resolve the blocking errors in the source workbook first.']);
                }

                $lockedBatch->forceFill([
                    'status' => 'importing',
                    'attempt_count' => $lockedBatch->attempt_count + 1,
                    'error_message' => null,
                ])->save();

                foreach ($candidates as $row) {
                    $existingMapping = MmcPplImportSourceMapping::query()
                        ->where('company_id', $companyId)
                        ->where('source_fingerprint', $row->source_fingerprint)
                        ->lockForUpdate()
                        ->first();
                    if ($existingMapping !== null) {
                        $row->forceFill(['status' => 'skipped', 'financial_plan_id' => $existingMapping->financial_plan_id])->save();
                        $skippedCount++;

                        continue;
                    }

                    $data = $row->normalized_data;
                    $plan = $this->financialPlanService->create(
                        $data['plan'],
                        $user,
                        $companyId,
                    );
                    if (Schema::hasColumn('project_financial_plans', 'revenue_year') && is_numeric($data['revenue_year'] ?? null)) {
                        $plan->forceFill(['revenue_year' => (int) $data['revenue_year']])->save();
                    }
                    $version = $plan->latestVersion;
                    $inputData = $version->input_data ?? [];
                    $inputData['workbook_import'] = [
                        'batch_id' => $lockedBatch->getKey(),
                        'import_row_id' => $row->getKey(),
                        'file_name' => $lockedBatch->original_file_name,
                        'file_sha256' => $lockedBatch->file_sha256,
                        'source_sheet' => $row->source_sheet,
                        'source_row' => $row->source_row,
                        'source_fingerprint' => $row->source_fingerprint,
                        'source_cells' => $row->source_data['source_cells'],
                        'revenue_year' => $data['revenue_year'],
                    ];
                    $version->input_data = $inputData;
                    $assumptions = $version->assumptions_snapshot ?? [];
                    $assumptions['workbook_import_reconciliation'] = $row->normalized_data['reconciliation'];
                    $assumptions['workbook_import_warnings'] = $row->warnings ?? [];
                    $version->assumptions_snapshot = $assumptions;
                    $version->save();

                    foreach ($row->normalized_data['factory_cost_items'] as $costItem) {
                        $version->costItems()->create([
                            'category' => 'factory_cost_detail',
                            'label' => Str::limit($costItem['description'] ?: 'Factory cost item '.$costItem['item_number'], 255, ''),
                            'amount' => $costItem['amount_php'],
                            'calculation_basis' => 'quantity × USD unit cost × forex',
                            'rate' => $costItem['forex'],
                            'source_type' => 'workbook_import',
                            'source_reference' => $costItem['amount_cell'],
                            'source_snapshot' => $costItem['source_cells'],
                        ]);
                    }

                    MmcPplImportSourceMapping::query()->create([
                        'company_id' => $companyId,
                        'source_fingerprint' => $row->source_fingerprint,
                        'mmc_ppl_import_batch_id' => $lockedBatch->getKey(),
                        'mmc_ppl_import_row_id' => $row->getKey(),
                        'financial_plan_id' => $plan->getKey(),
                    ]);
                    $row->forceFill(['status' => 'imported', 'financial_plan_id' => $plan->getKey()])->save();
                    $importedCount++;
                }

                $blockedCount = $rows->where('status', 'blocked')->count();
                $lockedBatch->forceFill([
                    'status' => $blockedCount > 0 || $skippedCount > 0 ? 'partial' : 'imported',
                    'summary' => array_replace($lockedBatch->summary ?? [], [
                        'imported' => $importedCount,
                        'skipped' => $skippedCount,
                        'blocked' => $blockedCount,
                    ]),
                    'imported_at' => now(),
                ])->save();
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            MmcPplImportBatch::query()
                ->where('company_id', $companyId)
                ->whereKey($batch->getKey())
                ->update([
                    'status' => 'failed',
                    'error_message' => 'The batch was rolled back because a row could not be saved. Correct the issue and retry this preview.',
                ]);

            throw ValidationException::withMessages(['batch' => 'No financial plans were saved because the import transaction rolled back. The batch can be reviewed and retried.']);
        }

        return [
            'batch' => $batch->fresh(),
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'blocked' => $blockedCount,
        ];
    }

    public function cancel(MmcPplImportBatch $batch, int $companyId): MmcPplImportBatch
    {
        return DB::transaction(function () use ($batch, $companyId): MmcPplImportBatch {
            $lockedBatch = MmcPplImportBatch::query()
                ->where('company_id', $companyId)
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedBatch->status, ['previewed', 'failed'], true)) {
                throw ValidationException::withMessages(['batch' => 'Only a preview or failed batch can be cancelled.']);
            }

            $lockedBatch->forceFill(['status' => 'cancelled'])->save();

            return $lockedBatch;
        });
    }

    /** @param list<array<string, mixed>> $formRows
     * @return array<string, mixed>
     */
    private function loadReferences(int $companyId, array $formRows): array
    {
        $projectCodes = collect($formRows)->pluck('project_code')->filter()->unique()->values();
        $projectIdentifiers = collect($formRows)->pluck('project_identifier')->filter()->unique()->values();
        $sourceKeys = collect($formRows)->map(fn (array $row): string => $this->sourceKey($row['project_code'], $row['lot_number']))->flip();

        $pplForms = PplForm::query()->whereIn('project_code', $projectCodes)->get(['id', 'project_code', 'lot_number', 'project_title', 'abc'])
            ->filter(fn (PplForm $form): bool => $sourceKeys->has($this->sourceKey((string) $form->project_code, (string) $form->lot_number)));
        $projects = collect();
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'project_code') && Schema::hasColumn('projects', 'project_id')) {
            $projects = Project::query()->whereIn('project_code', $projectCodes)->get(['project_id', 'project_code'])
                ->filter(fn (Project $project): bool => $projectCodes->containsStrict($project->project_code));
        }

        $biddings = collect();
        if (Schema::hasTable('project_information') && Schema::hasColumn('project_information', 'project_id_no')) {
            $biddingColumns = ['id', 'project_id_no'];
            foreach (['approved_budget_contract_abc', 'contract_amount', 'lot_no'] as $optionalColumn) {
                if (Schema::hasColumn('project_information', $optionalColumn)) {
                    $biddingColumns[] = $optionalColumn;
                }
            }
            $biddings = ProjectInformation::query()->whereIn('project_id_no', $projectIdentifiers)->get($biddingColumns)
                ->filter(fn (ProjectInformation $bidding): bool => $projectIdentifiers->containsStrict($bidding->project_id_no));
        }

        $lots = collect();
        if (Schema::hasTable('lots') && Schema::hasColumn('lots', 'id') && Schema::hasColumn('lots', 'project_id')
            && Schema::hasColumn('lots', 'lot_no') && $biddings->isNotEmpty()) {
            $lots = ProjectLot::query()
                ->whereIn('project_id', $biddings->pluck('id'))
                ->get(['id', 'project_id', 'lot_no']);
        }

        $companyPlans = ProjectFinancialPlan::query()->forCompany($companyId)
            ->get(['id', 'ppl_form_id', 'project_information_id', 'lot_id']);
        $fingerprints = collect($formRows)->map(fn (array $row): string => $this->sourceFingerprint($row['project_code'], $row['lot_number']))->unique();
        $existingMappings = MmcPplImportSourceMapping::query()
            ->where('company_id', $companyId)
            ->whereIn('source_fingerprint', $fingerprints)
            ->get(['source_fingerprint', 'financial_plan_id']);

        return [
            'ppl_forms' => $pplForms->groupBy(fn (PplForm $form): string => $this->sourceKey($form->project_code, $form->lot_number)),
            'projects' => $projects->groupBy('project_code'),
            'biddings' => $biddings->groupBy('project_id_no'),
            'lots' => $lots->groupBy(fn (ProjectLot $lot): string => $lot->project_id.'|'.$lot->lot_no),
            'plans' => $companyPlans,
            'mappings' => $existingMappings->keyBy('source_fingerprint'),
        ];
    }

    /** @param array<string, mixed> $workbookData
     * @param  array<string, mixed>  $references
     * @return array<string, mixed>
     */
    private function prepareImportRow(array $formRow, array $workbookData, array $references): array
    {
        $projectCode = $formRow['project_code'];
        $lotNumber = $formRow['lot_number'];
        $key = $this->sourceKey($projectCode, $lotNumber);
        $fingerprint = $this->sourceFingerprint($projectCode, $lotNumber);
        $errors = [];
        $warnings = [];
        $sourceCells = $formRow['source_cells'];
        $factoryItems = $workbookData['factory_costs'][$key] ?? [];
        $pplSource = $workbookData['ppl_rows'][$key] ?? null;

        if (($formRow['duplicate_key'] ?? false) === true) {
            $errors[] = 'This project-code and lot key appears more than once in 1. FORM. Each source key must be unique.';
        }

        if ($pplSource === null) {
            $errors[] = 'No PPL row matches this project code and lot, so contract amount and workbook reconciliation are unavailable.';
        } elseif (($pplSource['duplicate_rows'] ?? []) !== []) {
            $errors[] = 'More than one PPL summary row uses this project-code and lot key. Resolve the duplicate source rows before import.';
        }

        $pplMatches = $references['ppl_forms'][$key] ?? collect();
        $pplForm = $pplMatches->count() === 1 ? $pplMatches->first() : null;
        if ($pplMatches->count() > 1) {
            $errors[] = 'More than one existing PPL Form matches this project code and lot.';
        } elseif ($pplForm === null) {
            $warnings[] = 'No existing PPL Form matches exactly. The import will retain workbook provenance without creating or changing a PPL Form.';
        }

        $projectMatches = $references['projects'][$projectCode] ?? collect();
        $project = $projectMatches->count() === 1 ? $projectMatches->first() : null;
        if ($projectMatches->count() > 1) {
            $errors[] = 'More than one MMC project matches this project code.';
        } elseif ($project === null) {
            $warnings[] = 'No exact project-code match exists. The plan will remain linked only to verified bidding or PPL records.';
        }

        $identifier = $formRow['project_identifier'];
        $biddingMatches = $identifier === null ? collect() : ($references['biddings'][$identifier] ?? collect());
        $bidding = $biddingMatches->count() === 1 ? $biddingMatches->first() : null;
        if ($biddingMatches->count() > 1) {
            $errors[] = 'The workbook project identifier matches more than one bidding record.';
        } elseif ($identifier !== null && $bidding === null) {
            $warnings[] = 'No exact bidding identifier match exists. No bidding record will be linked.';
        }

        $lotMatches = $bidding === null ? collect() : ($references['lots'][$bidding->id.'|'.$lotNumber] ?? collect());
        $lot = $lotMatches->count() === 1 ? $lotMatches->first() : null;
        if ($lotMatches->count() > 1) {
            $errors[] = 'More than one database lot matches this bidding record and lot number.';
        } elseif ($bidding !== null && $lot === null) {
            $warnings[] = 'No exact database lot match exists. The bidding project may be linked, but no lot is linked.';
        }

        $formAbc = $formRow['values']['abc'];
        $selectedAbc = $formAbc;
        $biddingAbc = $bidding?->approved_budget_contract_abc;
        $biddingLot = $bidding !== null && isset($bidding->lot_no) ? trim((string) $bidding->lot_no) : '';
        if ($biddingAbc !== null && $biddingAbc !== '' && $biddingLot !== '' && $biddingLot === $lotNumber) {
            if ($this->amountDiffers($formAbc, $biddingAbc)) {
                $warnings[] = 'The lot-specific bidding ABC differs from the workbook ABC. The bidding ABC is used and the workbook variance requires acknowledgement.';
            }
            $selectedAbc = $biddingAbc;
        }
        if ($pplForm !== null && $this->amountDiffers($formAbc, $pplForm->abc)) {
            $warnings[] = 'The workbook ABC differs from the preserved PPL Form ABC. The PPL Form record will not be changed.';
        }

        $contractAmount = $pplSource === null ? null : $this->cellValue($pplSource['cells']['G']);
        if ($pplSource !== null && $pplSource['cells']['G']['formula'] !== null) {
            $errors[] = 'PPL contract amount is formula-derived; a non-formula contract source is required before import.';
        }
        if (! is_numeric($contractAmount)) {
            $errors[] = 'PPL contract amount is missing or is not numeric.';
            $contractAmount = null;
        }

        if ($bidding !== null && isset($bidding->contract_amount) && $bidding->contract_amount !== null && $contractAmount !== null
            && $this->amountDiffers($contractAmount, $bidding->contract_amount)) {
            $warnings[] = 'The current bidding contract amount differs from the workbook contract amount. The workbook input remains in the preview for explicit review.';
        }

        if ($factoryItems === []) {
            $errors[] = 'No factory-cost item rows match this exact project code and lot.';
        }
        $factoryCost = '0.00';
        try {
            $factoryCost = $this->sumMoney(array_column($factoryItems, 'amount_php'));
        } catch (Throwable) {
            $errors[] = 'At least one matched factory-cost amount is missing, invalid, or has no cached formula result.';
        }

        $pplFactoryCost = $pplSource === null ? null : $this->cellValue($pplSource['cells']['I']);
        if ($pplSource !== null && is_numeric($pplFactoryCost) && $this->amountDiffers($factoryCost, $pplFactoryCost)) {
            $warnings[] = 'The summed factory-cost items do not equal the PPL factory-cost reconciliation value.';
        }

        foreach ($factoryItems as $item) {
            if ((float) $item['unit_cost_usd'] > 0 && (float) $item['forex'] <= 0) {
                $warnings[] = 'A factory-cost item has a positive USD unit cost and zero or missing forex at '.$item['amount_cell'].'.';
            }
            $expectedAmount = (float) $item['quantity'] * (float) $item['unit_cost_usd'] * (float) $item['forex'];
            if (abs($expectedAmount - (float) $item['amount_php']) > 0.01) {
                $warnings[] = 'A cached factory-cost amount differs from quantity × USD unit cost × forex at '.$item['amount_cell'].'.';
            }
        }

        $inputs = [
            'contract_amount' => $contractAmount === null ? '' : $this->inputNumber($contractAmount),
            'abc' => $selectedAbc === null ? '' : $this->inputNumber($selectedAbc),
            'factory_cost' => $factoryCost,
        ];
        foreach (self::INPUT_CELL_MAP as $inputKey => $column) {
            $value = $formRow['values'][$inputKey] ?? null;
            if (! is_numeric($value)) {
                $errors[] = 'Required financial input '.$inputKey.' is blank or non-numeric at 1. FORM!'.$column.$formRow['source_row'].'.';
                $inputs[$inputKey] = '';

                continue;
            }
            $inputs[$inputKey] = $this->inputNumber($value);
        }

        $assumptions = [];
        foreach ($workbookData['assumptions'] as $keyName => $assumption) {
            if (! is_numeric($assumption['value'])) {
                $errors[] = 'Required workbook assumption '.$keyName.' is missing at 3. MUNICIPAL TAX & INSURANCE!'.$assumption['coordinate'].'.';

                continue;
            }
            $assumptions[$keyName] = $this->inputNumber($assumption['value']);
        }

        $reconciliation = ['workbook' => [], 'calculated' => [], 'differences' => []];
        if ($pplSource !== null && $contractAmount !== null && is_numeric($inputs['abc']) && $factoryItems !== []) {
            try {
                $calculated = $this->calculator->calculate('MMC', $inputs, $assumptions);
                foreach (self::PPL_RESULT_CELLS as $metric => $column) {
                    $cell = $pplSource['cells'][$column];
                    $expected = $this->cellValue($cell);
                    if (! is_numeric($expected)) {
                        $errors[] = 'The workbook cached PPL result is unavailable at PPL!'.$column.$pplSource['source_row'].'.';

                        continue;
                    }
                    $actual = data_get($calculated, $metric);
                    if (! is_numeric($actual)) {
                        continue;
                    }
                    $difference = (float) $actual - (float) $expected;
                    $reconciliation['workbook'][$metric] = $this->inputNumber($expected);
                    $reconciliation['calculated'][$metric] = $this->inputNumber($actual);
                    if (abs($difference) > 0.01) {
                        $reconciliation['differences'][$metric] = [
                            'workbook' => $this->inputNumber($expected),
                            'calculated' => $this->inputNumber($actual),
                            'difference' => $this->inputNumber($difference),
                            'cell' => 'PPL!'.$column.$pplSource['source_row'],
                        ];
                    }
                }
                if ($reconciliation['differences'] !== []) {
                    $warnings[] = 'One or more calculated results differ from workbook PPL cached values. Review the metric differences before confirming.';
                }
            } catch (Throwable $exception) {
                $errors[] = 'The financial calculation could not be reconciled: '.$exception->getMessage();
            }
        }

        $sourceCells['ppl'] = $pplSource['cells'] ?? [];
        $sourceCells['factory_cost_items'] = array_column($factoryItems, 'source_cells');
        $sourceCells['municipal_assumptions'] = $workbookData['assumptions'];
        $warnings = array_values(array_unique($warnings));
        $errors = array_values(array_unique($errors));
        $status = $errors !== [] ? 'blocked' : ($warnings !== [] ? 'warning' : 'ready');
        $planName = Str::limit(($formRow['project_title'] ?: $projectCode).' - Lot '.$lotNumber, 255, '');

        $duplicatePlan = $pplForm !== null && $references['plans']->contains(fn (ProjectFinancialPlan $plan): bool => (int) $plan->ppl_form_id === (int) $pplForm->id);
        if ($duplicatePlan) {
            $errors[] = 'A financial plan is already linked to this PPL Form. Existing plans are not overwritten.';
            $status = 'blocked';
        }
        if (isset($references['mappings'][$fingerprint])) {
            $warnings[] = 'This project-code and lot source was imported in an earlier batch and will be skipped to prevent a duplicate plan.';
            $status = $status === 'ready' ? 'warning' : $status;
        }

        $projectInformationId = $bidding?->getKey();
        $lotId = $lot?->getKey();
        if ($projectInformationId !== null && $lotId !== null
            && $references['plans']->contains(fn (ProjectFinancialPlan $plan): bool => (int) $plan->project_information_id === (int) $projectInformationId && (int) $plan->lot_id === (int) $lotId)) {
            $errors[] = 'A financial plan already exists for this bidding record and lot. Existing plans are not overwritten.';
            $status = 'blocked';
        }

        $normalizedData = [
            'plan' => [
                'name' => $planName,
                'project_id' => $project?->project_id,
                'project_information_id' => $projectInformationId,
                'lot_id' => $lotId,
                'ppl_form_id' => $pplForm?->getKey(),
                'change_summary' => 'Created from a confirmed MMC PPL workbook import.',
                'inputs' => $inputs,
                'assumptions' => $assumptions,
                'cost_items' => [],
            ],
            'factory_cost_items' => $factoryItems,
            'reconciliation' => $reconciliation,
            'project_title' => $formRow['project_title'],
            'project_identifier' => $identifier,
            'revenue_year' => $formRow['values']['revenue_year'] ?? null,
        ];

        return [
            'source_sheet' => '1. FORM',
            'source_row' => $formRow['source_row'],
            'project_code' => $projectCode,
            'lot_number' => $lotNumber,
            'source_fingerprint' => $fingerprint,
            'status' => $status,
            'source_data' => [
                'source_cells' => $sourceCells,
                'project_code' => $projectCode,
                'lot_number' => $lotNumber,
                'project_title' => $formRow['project_title'],
                'project_identifier' => $identifier,
            ],
            'normalized_data' => $normalizedData,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /** @return array{sheet_names: list<string>, form_rows: list<array<string, mixed>>, factory_costs: array<string, list<array<string, mixed>>>, ppl_rows: array<string, array<string, mixed>>, assumptions: array<string, array{coordinate: string, value: mixed, formula: ?string}>} */
    private function readWorkbook(string $path): array
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $sheetNames = $reader->listWorksheetNames($path);
            $missingSheets = array_values(array_diff(self::REQUIRED_SHEETS, $sheetNames));
            if ($missingSheets !== []) {
                throw ValidationException::withMessages(['file' => 'Missing required worksheet(s): '.implode(', ', $missingSheets).'.']);
            }
            if (count($sheetNames) > 100) {
                throw ValidationException::withMessages(['file' => 'The workbook contains more than 100 worksheets.']);
            }

            $reader->setLoadSheetsOnly(self::REQUIRED_SHEETS);
            $reader->setReadFilter(new MmcPplWorkbookReadFilter([
                '1. FORM' => array_fill_keys(self::FORM_COLUMNS, true),
                '2. FACTORY COST' => array_fill_keys(self::FACTORY_COST_COLUMNS, true),
                'PPL' => array_fill_keys(self::PPL_COLUMNS, true),
                '3. MUNICIPAL TAX & INSURANCE' => array_fill_keys(['A', 'B', 'C'], true),
            ]));
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($path);
            $this->validateHeaders($spreadsheet);
            $assumptions = $this->readMunicipalAssumptions($spreadsheet->getSheetByName('3. MUNICIPAL TAX & INSURANCE'));
            $factoryCosts = $this->readFactoryCosts($spreadsheet->getSheetByName('2. FACTORY COST'));
            $pplRows = $this->readPplRows($spreadsheet->getSheetByName('PPL'));
            $formRows = $this->readFormRows($spreadsheet->getSheetByName('1. FORM'));
            $spreadsheet->disconnectWorksheets();

            return [
                'sheet_names' => $sheetNames,
                'form_rows' => $formRows,
                'factory_costs' => $factoryCosts,
                'ppl_rows' => $pplRows,
                'assumptions' => $assumptions,
            ];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The workbook is malformed or could not be read. No data was imported.']);
        }
    }

    private function validateArchive(string $path): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is not a valid XLSX archive.']);
        }

        $expandedSize = 0;
        if ($archive->numFiles > 10000) {
            $archive->close();
            throw ValidationException::withMessages(['file' => 'The workbook archive contains too many entries.']);
        }

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if (str_starts_with($name, '/') || preg_match('~(^|/)\.\.(/|$)~', $name) === 1 || str_contains(strtolower($name), 'vbaproject.bin')) {
                $archive->close();
                throw ValidationException::withMessages(['file' => 'The workbook contains an unsafe archive entry or macro payload.']);
            }

            $entrySize = (int) ($stat['size'] ?? 0);
            $expandedSize += $entrySize;
            if ($entrySize > 64 * 1024 * 1024 || $expandedSize > 250 * 1024 * 1024) {
                $archive->close();
                throw ValidationException::withMessages(['file' => 'The workbook expands beyond the allowed 250 MB processing limit.']);
            }
        }

        if ($archive->locateName('[Content_Types].xml') === false || $archive->locateName('xl/workbook.xml') === false) {
            $archive->close();
            throw ValidationException::withMessages(['file' => 'The archive is not a valid Excel workbook.']);
        }
        $archive->close();
    }

    private function validateHeaders(Spreadsheet $spreadsheet): void
    {
        $checks = [
            '1. FORM' => self::REQUIRED_FORM_HEADERS,
            '2. FACTORY COST' => self::REQUIRED_FACTORY_HEADERS,
            'PPL' => self::REQUIRED_PPL_HEADERS,
        ];
        foreach ($checks as $sheetName => $requiredHeaders) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $headerRow = 2;
            foreach ($requiredHeaders as $column => $expected) {
                $actual = $this->cellValue($this->cellRecord($sheet, $column.$headerRow));
                if (! str_starts_with($this->normalizeHeader($actual), $this->normalizeHeader($expected))) {
                    throw ValidationException::withMessages(['file' => 'Unexpected or renamed header at '.$sheetName.'!'.$column.$headerRow.'. Expected "'.$expected.'".']);
                }
            }
        }

        $municipal = $spreadsheet->getSheetByName('3. MUNICIPAL TAX & INSURANCE');
        foreach (['A4' => 'COMPONENT', 'B4' => 'RATE', 'C4' => 'BASE'] as $coordinate => $expected) {
            if ($this->normalizeHeader($this->cellValue($this->cellRecord($municipal, $coordinate))) !== $expected) {
                throw ValidationException::withMessages(['file' => 'The municipal tax and insurance rate table headers are missing or renamed.']);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function readFormRows(Worksheet $sheet): array
    {
        if ($sheet->getHighestDataRow() > 10001) {
            throw ValidationException::withMessages(['file' => 'The 1. FORM worksheet exceeds the 10,000 project-row limit.']);
        }

        $rows = [];
        $seenKeys = [];
        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            $cells = $this->cellRecords($sheet, self::FORM_COLUMNS, $row);
            $projectCode = trim((string) ($cells['A']['value'] ?? ''));
            $lotNumber = trim((string) ($cells['B']['value'] ?? ''));
            if ($projectCode === '' && $lotNumber === '') {
                continue;
            }
            if ($projectCode === '' || $lotNumber === '') {
                continue;
            }

            $values = [];
            foreach (self::INPUT_CELL_MAP as $inputKey => $column) {
                $values[$inputKey] = $cells[$column]['value'] ?? null;
            }
            $values['abc'] = $cells['M']['value'] ?? null;
            $values['lcb_discount_rate'] = $cells['N']['value'] ?? null;
            $values['forex'] = $cells['O']['value'] ?? null;
            $values['revenue_year'] = $cells['AX']['value'] ?? null;

            $key = $this->sourceKey($projectCode, $lotNumber);
            $rows[] = [
                'source_row' => $row,
                'project_code' => $projectCode,
                'lot_number' => $lotNumber,
                'duplicate_key' => isset($seenKeys[$key]),
                'project_title' => trim((string) ($cells['C']['value'] ?? '')),
                'project_identifier' => trim((string) ($cells['D']['value'] ?? '')) ?: null,
                'values' => $values,
                'source_cells' => ['1. FORM' => $cells],
            ];
            $seenKeys[$key] = true;
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The 1. FORM worksheet contains no project and lot rows.']);
        }

        $keyCounts = collect($rows)->countBy(fn (array $row): string => $this->sourceKey($row['project_code'], $row['lot_number']));
        foreach ($rows as &$row) {
            $key = $this->sourceKey($row['project_code'], $row['lot_number']);
            $row['duplicate_key'] = $keyCounts->get($key, 0) > 1;
        }
        unset($row);

        return $rows;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function readFactoryCosts(Worksheet $sheet): array
    {
        if ($sheet->getHighestDataRow() > 50001) {
            throw ValidationException::withMessages(['file' => 'The 2. FACTORY COST worksheet exceeds the 50,000 item-row limit.']);
        }

        $itemsByKey = [];
        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            $cells = $this->cellRecords($sheet, self::FACTORY_COST_COLUMNS, $row);
            $projectCode = trim((string) ($cells['A']['value'] ?? ''));
            $lotNumber = trim((string) ($cells['B']['value'] ?? ''));
            if ($projectCode === '' || $lotNumber === '') {
                continue;
            }

            $key = $this->sourceKey($projectCode, $lotNumber);
            $itemsByKey[$key][] = [
                'source_row' => $row,
                'item_number' => (string) ($cells['D']['value'] ?? ''),
                'description' => (string) ($cells['E']['value'] ?? ''),
                'factory' => (string) ($cells['G']['value'] ?? ''),
                'quantity' => $cells['I']['value'],
                'unit_cost_usd' => $cells['J']['value'],
                'forex' => $cells['K']['value'],
                'amount_php' => $cells['L']['value'],
                'amount_cell' => '2. FACTORY COST!L'.$row,
                'source_cells' => $cells,
            ];
        }

        return $itemsByKey;
    }

    /** @return array<string, array<string, mixed>> */
    private function readPplRows(Worksheet $sheet): array
    {
        if ($sheet->getHighestDataRow() > 10001) {
            throw ValidationException::withMessages(['file' => 'The PPL worksheet exceeds the 10,000 summary-row limit.']);
        }

        $rows = [];
        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            $cells = $this->cellRecords($sheet, self::PPL_COLUMNS, $row);
            $projectCode = trim((string) ($cells['A']['value'] ?? ''));
            $lotNumber = trim((string) ($cells['B']['value'] ?? ''));
            if ($projectCode === '' || $lotNumber === '') {
                continue;
            }
            $key = $this->sourceKey($projectCode, $lotNumber);
            if (isset($rows[$key])) {
                $rows[$key]['duplicate_rows'][] = $row;

                continue;
            }
            $rows[$key] = ['source_row' => $row, 'cells' => $cells, 'duplicate_rows' => []];
        }

        return $rows;
    }

    /** @return array<string, array{coordinate: string, value: mixed, formula: ?string}> */
    private function readMunicipalAssumptions(Worksheet $sheet): array
    {
        $assumptions = [];
        foreach (self::MUNICIPAL_CELLS as $key => $coordinate) {
            $record = $this->cellRecord($sheet, $coordinate);
            $assumptions[$key] = [
                'coordinate' => $coordinate,
                'value' => $record['value'],
                'formula' => $record['formula'],
            ];
        }

        return $assumptions;
    }

    /** @param list<string> $columns
     * @return array<string, array{coordinate: string, value: mixed, formula: ?string, cached_value: mixed, data_type: string}>
     */
    private function cellRecords(Worksheet $sheet, array $columns, int $row): array
    {
        $records = [];
        foreach ($columns as $column) {
            $coordinate = $column.$row;
            $records[$column] = $this->cellRecord($sheet, $coordinate);
        }

        return $records;
    }

    /** @return array{coordinate: string, value: mixed, formula: ?string, cached_value: mixed, data_type: string} */
    private function cellRecord(Worksheet $sheet, string $coordinate): array
    {
        $cell = $sheet->getCell($coordinate);
        $rawValue = $cell->getValue();
        $isFormula = is_string($rawValue) && str_starts_with($rawValue, '=');
        $cachedValue = $isFormula ? $cell->getOldCalculatedValue() : null;
        $value = $isFormula ? $cachedValue : $rawValue;

        return [
            'coordinate' => $coordinate,
            'value' => $value instanceof RichText ? $value->getPlainText() : $value,
            'formula' => $isFormula ? $rawValue : null,
            'cached_value' => $cachedValue,
            'data_type' => $cell->getDataType(),
        ];
    }

    /** @param array<string, mixed> $cell */
    private function cellValue(array $cell): mixed
    {
        $value = $cell['value'] ?? null;
        if (is_string($value) && str_starts_with($value, '#')) {
            return null;
        }

        return $value;
    }

    private function normalizeHeader(mixed $header): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim((string) $header))) ?? '';
    }

    private function sourceKey(string $projectCode, string $lotNumber): string
    {
        return trim($projectCode).'|'.trim($lotNumber);
    }

    private function sourceFingerprint(string $projectCode, string $lotNumber): string
    {
        return hash('sha256', mb_strtolower(trim($projectCode))."\x1f".trim($lotNumber));
    }

    /** @param list<mixed> $values */
    private function sumMoney(array $values): string
    {
        $sum = BigDecimal::zero();
        foreach ($values as $value) {
            if (! is_numeric($value)) {
                throw new RuntimeException('Factory cost item amount is not numeric.');
            }
            $sum = $sum->plus((string) $value);
        }

        return (string) $sum->toScale(2, RoundingMode::HALF_UP);
    }

    private function inputNumber(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, 8, '.', '');
    }

    private function amountDiffers(mixed $first, mixed $second): bool
    {
        return is_numeric($first) && is_numeric($second) && abs((float) $first - (float) $second) > 0.01;
    }

    /** @param list<array<string, mixed>> $rows
     * @param  list<string>  $sheetNames
     * @return array<string, mixed>
     */
    private function summarize(array $rows, array $sheetNames): array
    {
        $counts = collect($rows)->countBy('status');

        return [
            'worksheet_count' => count($sheetNames),
            'required_worksheets' => self::REQUIRED_SHEETS,
            'form_rows' => count($rows),
            'ready' => $counts->get('ready', 0),
            'warnings' => $counts->get('warning', 0),
            'blocked' => $counts->get('blocked', 0),
        ];
    }
}
