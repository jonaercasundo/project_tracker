<?php

use App\Models\MmcPplImportBatch;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\ProjectFinancialPlanCalculator;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function mmcPplImportWorkbook(array $formOverrides = [], float $contractAmount = 112, array $factoryOverrides = []): string
{
    $spreadsheet = new Spreadsheet;
    $form = $spreadsheet->getActiveSheet();
    $form->setTitle('1. FORM');
    $formHeaders = [
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
    foreach ($formHeaders as $column => $header) {
        $form->setCellValue($column.'2', $header);
    }
    $formValues = [
        'A3' => 'FIXTURE-001', 'B3' => 'Lot 1', 'C3' => 'Synthetic import fixture', 'D3' => 'BID-FIXTURE',
        'M3' => 200, 'N3' => 0, 'O3' => 10, 'S3' => 0.01, 'T3' => 0,
        'V3' => 0, 'W3' => 0, 'Y3' => 0, 'Z3' => 0, 'AA3' => 0, 'AB3' => 0,
        'AC3' => 0, 'AD3' => 0, 'AE3' => 0, 'AF3' => 0, 'AG3' => 0, 'AH3' => 0,
        'AI3' => 0, 'AJ3' => 0.1, 'AK3' => 0, 'AL3' => 0, 'AM3' => 0, 'AN3' => 0.13,
        'AO3' => 0.12, 'AX3' => 2026,
    ];
    $formValues = array_replace($formValues, $formOverrides);
    $projectCode = (string) $formValues['A3'];
    $lotNumber = (string) $formValues['B3'];
    foreach ($formValues as $coordinate => $value) {
        $form->setCellValue($coordinate, $value);
    }

    $factory = $spreadsheet->createSheet();
    $factory->setTitle('2. FACTORY COST');
    foreach (['A' => 'PROJECT CODE', 'B' => 'LOT #', 'D' => 'ITEM #', 'E' => 'ITEM DESCRIPTION', 'G' => 'FACTORY', 'I' => 'QTY', 'J' => 'Unit Cost (usd)', 'K' => 'Forex', 'L' => 'Amount in Php'] as $column => $header) {
        $factory->setCellValue($column.'2', $header);
    }
    $factoryValues = array_replace(['A3' => 'FIXTURE-001', 'B3' => 'Lot 1', 'D3' => '1', 'E3' => 'Desk', 'G3' => 'Fixture factory', 'I3' => 1, 'J3' => 10, 'K3' => 10, 'L3' => 100], $factoryOverrides);
    $factoryValues['A3'] = $projectCode;
    $factoryValues['B3'] = $lotNumber;
    foreach ($factoryValues as $coordinate => $value) {
        $factory->setCellValue($coordinate, $value);
    }

    $municipal = $spreadsheet->createSheet();
    $municipal->setTitle('3. MUNICIPAL TAX & INSURANCE');
    $municipal->setCellValue('A4', 'COMPONENT');
    $municipal->setCellValue('B4', 'RATE');
    $municipal->setCellValue('C4', 'BASE');
    $assumptions = app(ProjectFinancialPlanCalculator::class)->defaultAssumptions();
    $assumptionCells = [
        'bondable_rate' => 'B6', 'bond_premium_rate' => 'B7', 'bond_dst_rate' => 'B8', 'bond_lgt_rate' => 'B9',
        'bond_notarial_fee' => 'B10', 'bond_service_fee' => 'B11', 'marine_premium_rate' => 'B15',
        'marine_dst_rate' => 'B16', 'marine_lgt_rate' => 'B17', 'fire_premium_rate' => 'B21',
        'fire_dst_rate' => 'B22', 'fire_tax_rate' => 'B23', 'fire_lgt_rate' => 'B24',
        'inland_premium_rate' => 'B28', 'inland_dst_rate' => 'B29', 'inland_lgt_rate' => 'B30',
        'municipal_first_rate' => 'B34', 'municipal_excess_rate' => 'B35', 'rrdm_rate' => 'B36',
    ];
    foreach ($assumptionCells as $key => $coordinate) {
        $municipal->setCellValue($coordinate, $assumptions[$key]);
    }

    $ppl = $spreadsheet->createSheet();
    $ppl->setTitle('PPL');
    $pplHeaders = ['A' => 'PROJECT CODE', 'B' => 'LOT', 'G' => 'CONTRACT AMOUNT', 'H' => 'NET SALES', 'I' => 'FACTORY COST', 'AB' => 'TOTAL COST OF SALES', 'AC' => 'GROSS PROFIT', 'AD' => 'GPM', 'AE' => 'OPEX', 'AJ' => 'PPL', 'AK' => 'NPM', 'AM' => 'WORKING CAPITAL NEEDED'];
    foreach ($pplHeaders as $column => $header) {
        $ppl->setCellValue($column.'2', $header);
    }
    $inputs = [
        'contract_amount' => $contractAmount, 'abc' => (float) ($formValues['M3'] ?? 0), 'factory_cost' => (float) ($factoryValues['L3'] ?? 0), 'pf1_rate' => 0.01,
        'pf2_rate' => 0, 'pf3_rate' => 0, 'll_commission_rate' => 0, 'interest_dst_rate' => 0,
        'shipping_containers' => 0, 'rate_per_container' => 0, 'logistics_rate' => 0,
        'warehouse_area' => 0, 'warehouse_rent_per_sqm' => 0, 'warehouse_months' => 0,
        'other_expenses_rate' => 0, 'manpower_rate' => 0, 'assembly_rate' => 0, 'rd_rate' => 0,
        'facilitation_rate' => 0, 'tax_lawyer_rate' => 0.1, 'opex_rate' => 0, 'income_tax_rate' => 0,
        'incentive_rate' => 0, 'importation_vat_rate' => 0.13, 'sales_vat_rate' => 0.12,
    ];
    $formInputColumns = [
        'pf1_rate' => 'S3', 'pf2_rate' => 'T3', 'pf3_rate' => 'U3', 'll_commission_rate' => 'V3',
        'interest_dst_rate' => 'W3', 'shipping_containers' => 'Y3', 'rate_per_container' => 'Z3',
        'logistics_rate' => 'AA3', 'warehouse_area' => 'AB3', 'warehouse_rent_per_sqm' => 'AC3',
        'warehouse_months' => 'AD3', 'other_expenses_rate' => 'AE3', 'manpower_rate' => 'AF3',
        'assembly_rate' => 'AG3', 'rd_rate' => 'AH3', 'facilitation_rate' => 'AI3',
        'tax_lawyer_rate' => 'AJ3', 'opex_rate' => 'AK3', 'income_tax_rate' => 'AL3',
        'incentive_rate' => 'AM3', 'importation_vat_rate' => 'AN3', 'sales_vat_rate' => 'AO3',
    ];
    foreach ($formInputColumns as $input => $coordinate) {
        if (array_key_exists($coordinate, $formValues)) {
            $value = $formValues[$coordinate];
            $inputs[$input] = is_string($value) && str_starts_with($value, '=') ? (float) substr($value, 1) : $value;
        }
    }
    $results = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', $inputs, $assumptions);
    $resultColumns = [
        'net_sales' => 'H', 'factory_cost' => 'I', 'costs.pf1' => 'K', 'costs.pf2' => 'L', 'costs.pf3' => 'M',
        'costs.ll_commission' => 'N', 'costs.interest_dst_working_capital' => 'O', 'costs.shipping_brokerage' => 'P',
        'costs.logistics' => 'Q', 'costs.warehouse_rental' => 'R', 'costs.other_expenses' => 'S', 'costs.manpower' => 'T',
        'costs.assembly_service_center' => 'U', 'costs.research_and_development' => 'V', 'costs.facilitation' => 'W',
        'costs.tax_lawyer_allowance' => 'X', 'costs.municipal_tax' => 'Y', 'costs.performance_bond' => 'Z',
        'costs.goods_insurance' => 'AA', 'total_cost_of_sales' => 'AB', 'gross_profit' => 'AC', 'gpm' => 'AD',
        'opex' => 'AE', 'net_profit_before_tax' => 'AF', 'income_tax_provision' => 'AG', 'net_profit_after_tax' => 'AH',
        'incentive' => 'AI', 'ppl' => 'AJ', 'npm' => 'AK', 'importation_vat' => 'AL', 'working_capital_needed' => 'AM',
    ];
    $ppl->setCellValue('A3', $projectCode);
    $ppl->setCellValue('B3', $lotNumber);
    $ppl->setCellValue('G3', $contractAmount);
    foreach ($resultColumns as $metric => $column) {
        $ppl->setCellValue($column.'3', data_get($results, $metric));
    }

    $path = tempnam(sys_get_temp_dir(), 'mmc-ppl-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    if (is_string($formValues['W3'] ?? null) && str_starts_with($formValues['W3'], '=')) {
        $archive = new ZipArchive;
        $archive->open($path);
        $sheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
        $cachedRate = substr($formValues['W3'], 1);
        $updatedSheetXml = preg_replace_callback(
            '/(<c\b(?=[^>]*\br="W3")[^>]*>).*?(<\/c>)/s',
            fn (array $matches): string => $matches[1].'<f>'.htmlspecialchars($cachedRate, ENT_XML1).'</f><v>'.htmlspecialchars($cachedRate, ENT_XML1).'</v>'.$matches[2],
            $sheetXml,
        );
        $archive->addFromString('xl/worksheets/sheet1.xml', $updatedSheetXml);
        $archive->close();
    }

    return $path;
}

function mmcPplImportUpload(string $path): UploadedFile
{
    return new UploadedFile($path, 'synthetic-ppl-fixture.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('previews and confirms a workbook into a new versioned plan without changing its matched PPL Form', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $form = PplForm::query()->create(['project_code' => 'FIXTURE-001', 'lot_number' => 'Lot 1', 'project_title' => 'Existing historical form', 'abc' => 200]);
    $path = mmcPplImportWorkbook();

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])
            ->assertRedirect();
        $firstBatchId = MmcPplImportBatch::query()->value('id');
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])
            ->assertRedirect(route('finance.ppl-import.show', $firstBatchId));
        $this->assertDatabaseCount('mmc_ppl_import_batches', 1);
        $batch = MmcPplImportBatch::query()->firstOrFail();
        expect($batch->rows)->toHaveCount(1)
            ->and($batch->rows->first()->status)->toBe('warning')
            ->and($batch->rows->first()->normalized_data['plan']['ppl_form_id'])->toBe($form->id)
            ->and($batch->rows->first()->normalized_data['plan']['inputs']['pf3_rate'])->toBe('0')
            ->and($batch->rows->first()->normalized_data['plan']['inputs']['tax_lawyer_rate'])->toBe('0.1')
            ->and($batch->rows->first()->normalized_data['plan']['project_id'])->toBeNull()
            ->and($batch->rows->first()->normalized_data['plan']['project_information_id'])->toBeNull();

        $this->from(route('finance.ppl-import.show', $batch))
            ->post(route('finance.ppl-import.confirm', $batch))
            ->assertSessionHasErrors('acknowledge_warnings');
        $this->assertDatabaseCount('project_financial_plans', 0);

        $this->post(route('finance.ppl-import.confirm', $batch), ['acknowledge_warnings' => '1'])
            ->assertRedirect(route('finance.ppl-import.show', $batch));

        $plan = ProjectFinancialPlan::query()->with('versions')->firstOrFail();
        expect($plan->company_id)->toBe($user->currentCompany()->company_id)
            ->and($plan->ppl_form_id)->toBe($form->id)
            ->and($plan->revenue_year)->toBe(2026)
            ->and($plan->versions)->toHaveCount(1)
            ->and($plan->latestVersion->calculation_results['costs']['tax_lawyer_allowance'])->toBe('20.00')
            ->and($plan->latestVersion->input_data['workbook_import']['source_sheet'])->toBe('1. FORM')
            ->and($plan->latestVersion->input_data['workbook_import']['source_mapping']['import_confirmed_by'])->toBe($user->getAuthIdentifier())
            ->and($batch->rows()->firstOrFail()->normalized_data['source_mapping']['import_confirmed_by'])->toBe($user->getAuthIdentifier());
        $this->assertDatabaseHas('project_financial_plan_cost_items', [
            'project_financial_plan_version_id' => $plan->latestVersion->id,
            'category' => 'factory_cost_detail',
            'amount' => 100,
        ]);
        $this->assertDatabaseHas('ppl_forms', ['id' => $form->id, 'project_title' => 'Existing historical form', 'abc' => 200]);
        $this->assertDatabaseCount('ppl_forms', 1);
        $this->assertDatabaseCount('mmc_ppl_import_source_mappings', 1);
        $this->get(route('finance.ppl.dashboard', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Synthetic import fixture');
    } finally {
        @unlink($path);
    }
});

it('preserves workbook decimal precision and reconciles PPL formulas through downstream totals', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $rate = '0.07923580699278363';
    $path = mmcPplImportWorkbook([
        'A3' => 'SME 2025 2, 9, 11', 'B3' => '2', 'C3' => 'SME 2025 project', 'D3' => 'unmatched-bid',
        'M3' => 157751301.52, 'N3' => 0.101, 'O3' => 60, 'S3' => 0.015, 'T3' => 0.06,
        'V3' => 0.0075, 'W3' => '='.$rate, 'Y3' => 12, 'Z3' => 195000, 'AA3' => 0.08,
        'AB3' => 1500, 'AC3' => 145.59, 'AD3' => 6, 'AE3' => 0.01, 'AF3' => 0.0025,
        'AG3' => 0.0025, 'AH3' => 0.0025, 'AI3' => 0.0025, 'AJ3' => 0,
        'AK3' => 0.05, 'AL3' => 0.02, 'AM3' => 0.15, 'AN3' => 0.13, 'AO3' => 0.12,
        'AX3' => 2026,
    ], 141821425.55, ['L3' => 76983415.20]);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $row = MmcPplImportBatch::query()->firstOrFail()->rows()->firstOrFail();
        $planInputs = $row->normalized_data['plan']['inputs'];
        $calculated = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', $planInputs, $row->normalized_data['plan']['assumptions']);

        expect($planInputs['interest_dst_rate'])->toBe('0.0792358069927836')
            ->and($planInputs['pf3_rate'])->toBe('0')
            ->and($calculated['costs']['interest_dst_working_capital'])->toBe('10033334.91')
            ->and($calculated['total_cost_of_sales'])->toBe('118844587.85')
            ->and($calculated['gross_profit'])->toBe('7781684.96')
            ->and($calculated['net_profit_before_tax'])->toBe('1450371.32')
            ->and($calculated['income_tax_provision'])->toBe('2532525.46')
            ->and($calculated['net_profit_after_tax'])->toBe('-1082154.13')
            ->and($calculated['ppl'])->toBe('-1082154.13')
            ->and($calculated['working_capital_needed'])->toBe('118071678.90')
            ->and($row->normalized_data['reconciliation']['differences'])->toBe([]);
    } finally {
        @unlink($path);
    }
});

it('allows authorized users to explicitly map an unmatched workbook row to verified MMC records', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $pplForm = PplForm::query()->create(['project_code' => 'UNMATCHED-CODE', 'lot_number' => '2', 'project_title' => 'Existing PPL source', 'abc' => 200]);
    $project = Project::query()->create(['ref_no' => 'REF-MAP-1', 'project_name' => 'Verified project choice', 'project_code' => 'OTHER-CODE', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Ongoing', 'ABC' => 200]);
    $bidding = ProjectInformation::query()->create(['project_id' => (string) $project->project_id, 'project_name' => 'Verified bidding choice', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    $bidding->forceFill(['project_id_no' => 'OTHER-IDENTIFIER'])->save();
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => '2']);
    $path = mmcPplImportWorkbook(['A3' => 'UNMATCHED-CODE', 'B3' => '2', 'C3' => 'Workbook project title', 'D3' => 'NO-IDENTIFIER']);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $batch = MmcPplImportBatch::query()->firstOrFail();
        $row = $batch->rows()->firstOrFail();
        $this->get(route('finance.ppl-import.show', $batch))
            ->assertOk()
            ->assertSee('Verified project choice')
            ->assertSee('OTHER-IDENTIFIER')
            ->assertSee('Lot 2')
            ->assertSee('No bidding link (PPL Form can remain the source)');

        $this->patch(route('finance.ppl-import.rows.mapping', [$batch, $row]), [
            'project_id' => $project->project_id,
            'project_information_id' => $bidding->id,
            'lot_id' => $lot->id,
        ])->assertRedirect(route('finance.ppl-import.show', $batch));

        $row->refresh();
        expect($row->normalized_data['plan']['project_id'])->toBe($project->project_id)
            ->and($row->normalized_data['plan']['project_information_id'])->toBe($bidding->id)
            ->and($row->normalized_data['plan']['lot_id'])->toBe($lot->id)
            ->and($row->normalized_data['plan']['ppl_form_id'])->toBe($pplForm->id)
            ->and($row->normalized_data['plan']['inputs']['abc'])->toBe('200')
            ->and($row->normalized_data['source_mapping']['origin'])->toBe('manual')
            ->and($row->normalized_data['source_mapping']['confirmed_by'])->toBe($user->getAuthIdentifier())
            ->and($row->normalized_data['manual_mapping']['selected_by'])->toBe($user->getAuthIdentifier());

        $this->post(route('finance.ppl-import.confirm', $batch), ['acknowledge_warnings' => '1'])->assertRedirect();
        $plan = ProjectFinancialPlan::query()->firstOrFail();
        expect($plan->project_id)->toBe($project->project_id)
            ->and($plan->project_information_id)->toBe($bidding->id)
            ->and($plan->lot_id)->toBe($lot->id)
            ->and($plan->ppl_form_id)->toBe($pplForm->id)
            ->and($plan->latestVersion->input_data['workbook_import']['manual_mapping']['selected_by'])->toBe($user->getAuthIdentifier())
            ->and($plan->mmc_ppl_source_link_id)->toBe(MmcPplSourceLink::query()->sole()->getKey())
            ->and(MmcPplSourceLink::query()->sole()->match_origin)->toBe('manual')
            ->and(MmcPplSourceLink::query()->sole()->ppl_form_id)->toBe($pplForm->getKey())
            ->and((int) $project->fresh()->project_information_id)->toBe((int) $bidding->getKey());
    } finally {
        @unlink($path);
    }
});

it('auto-matches exact project identifiers and the lot belonging to the linked bidding record', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = Project::query()->create(['ref_no' => 'SME-2025-2-9-11', 'project_name' => 'SME 2025 2, 9, 11', 'project_code' => 'SME 2025 2, 9, 11', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Draft', 'ABC' => 200]);
    $bidding = ProjectInformation::query()->create(['project_id' => (string) $project->project_id, 'project_name' => 'SME 2025 2, 9, 11', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    $bidding->forceFill(['project_id_no' => 'BID-SME-2025-002'])->save();
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => 'Lot 2']);
    $pplForm = PplForm::query()->create(['project_code' => 'SME 2025 2, 9, 11', 'lot_number' => '2', 'project_title' => 'SME 2025 2, 9, 11', 'project_id_no' => 'BID-SME-2025-002', 'abc' => 200]);
    $path = mmcPplImportWorkbook(['A3' => 'SME 2025 2, 9, 11', 'B3' => 'Lot 2', 'C3' => 'SME 2025 2, 9, 11', 'D3' => 'BID-SME-2025-002']);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $row = MmcPplImportBatch::query()->firstOrFail()->rows()->firstOrFail();

        expect($row->normalized_data['source_mapping']['status'])->toBe('auto_matched')
            ->and($row->normalized_data['source_mapping']['origin'])->toBe('automatic')
            ->and($row->normalized_data['source_mapping']['linked_record_ids'])->toBe([
                'project_id' => $project->project_id,
                'bidding_id' => $bidding->id,
                'lot_id' => $lot->id,
                'ppl_form_id' => $pplForm->id,
                'financial_plan_id' => null,
            ])
            ->and($row->normalized_data['plan']['project_id'])->toBe($project->project_id)
            ->and($row->normalized_data['plan']['project_information_id'])->toBe($bidding->id)
            ->and($row->normalized_data['plan']['lot_id'])->toBe($lot->id)
            ->and($row->normalized_data['plan']['ppl_form_id'])->toBe($pplForm->id);
    } finally {
        @unlink($path);
    }
});

it('normalizes project-name punctuation and capitalization while retaining a verified project relationship', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = Project::query()->create(['ref_no' => 'NORMALIZED-REF', 'project_name' => '(SME 2)  Mass-Production: FY2025', 'project_code' => 'UNRELATED-CODE', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Draft', 'ABC' => 200]);
    $bidding = ProjectInformation::query()->create(['project_id' => (string) $project->project_id, 'project_name' => 'sme 2 mass production fy2025', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => '2']);
    $path = mmcPplImportWorkbook(['A3' => 'OTHER-CODE', 'B3' => 'Lot #2', 'C3' => 'SME 2 Mass Production FY2025', 'D3' => 'NO-BID-ID']);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $row = MmcPplImportBatch::query()->firstOrFail()->rows()->firstOrFail();

        expect($row->normalized_data['source_mapping']['status'])->toBe('auto_matched')
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['project_id'])->toBe($project->project_id)
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['bidding_id'])->toBe($bidding->id)
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['lot_id'])->toBe($lot->id);
    } finally {
        @unlink($path);
    }
});

it('marks ambiguous project and bidding matches for review without preselecting a candidate', function () {
    $this->signInBiddingUser('finance', 'MMC');
    foreach ([1, 2] as $index) {
        $project = Project::query()->create(['ref_no' => 'AMBIGUOUS-'.$index, 'project_name' => 'Ambiguous SME Project', 'project_code' => 'AMBIGUOUS-CODE', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Draft', 'ABC' => 200]);
        ProjectInformation::query()->create(['project_id' => (string) $project->project_id, 'project_name' => 'Ambiguous SME Project', 'project_code' => 'AMBIGUOUS-CODE', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    }
    $path = mmcPplImportWorkbook(['A3' => 'AMBIGUOUS-CODE', 'B3' => '2', 'C3' => 'Ambiguous SME Project', 'D3' => 'NO-IDENTIFIER']);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $row = MmcPplImportBatch::query()->firstOrFail()->rows()->firstOrFail();
        $this->get(route('finance.ppl-import.show', $row->batch))->assertSee('Review matching candidates');

        expect($row->normalized_data['source_mapping']['status'])->toBe('needs_review')
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['project_id'])->toBeNull()
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['bidding_id'])->toBeNull()
            ->and($row->normalized_data['source_mapping']['candidates']['projects'])->toHaveCount(2)
            ->and($row->normalized_data['source_mapping']['candidates']['biddings'])->toHaveCount(2);
    } finally {
        @unlink($path);
    }
});

it('does not auto-select or allow manual links between unrelated project and bidding records', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = Project::query()->create(['ref_no' => 'UNRELATED-PAIR-PROJECT', 'project_name' => 'Unrelated pair project', 'project_code' => 'PAIR-CODE', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Draft', 'ABC' => 200]);
    $bidding = ProjectInformation::query()->create(['project_id' => 'NOT-'.$project->project_id, 'project_name' => 'Unrelated pair project', 'project_code' => 'PAIR-CODE', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => '2']);
    $path = mmcPplImportWorkbook(['A3' => 'PAIR-CODE', 'B3' => '2', 'C3' => 'Unrelated pair project', 'D3' => 'NO-BID-ID']);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $batch = MmcPplImportBatch::query()->firstOrFail();
        $row = $batch->rows()->firstOrFail();

        expect($row->normalized_data['source_mapping']['status'])->toBe('needs_review')
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['project_id'])->toBeNull()
            ->and($row->normalized_data['source_mapping']['linked_record_ids']['bidding_id'])->toBeNull()
            ->and($row->normalized_data['source_mapping']['candidates']['projects'])->toHaveCount(1)
            ->and($row->normalized_data['source_mapping']['candidates']['biddings'])->toHaveCount(1);

        $this->patch(route('finance.ppl-import.rows.mapping', [$batch, $row]), [
            'project_id' => $project->project_id,
            'project_information_id' => $bidding->id,
            'lot_id' => $lot->id,
        ])->assertSessionHasErrors('project_information_id');

        expect($row->fresh()->normalized_data['plan']['project_id'])->toBeNull()
            ->and($row->fresh()->normalized_data['plan']['project_information_id'])->toBeNull()
            ->and($row->fresh()->normalized_data['plan']['lot_id'])->toBeNull();
    } finally {
        @unlink($path);
    }
});

it('reuses a previously confirmed MMC mapping for a later upload of the same source identity', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = Project::query()->create(['ref_no' => 'REPEAT-REF', 'project_name' => 'Repeat project', 'project_code' => 'REPEAT-CODE', 'agency' => 'MMC', 'contract_amount' => 100, 'status' => 'Draft', 'ABC' => 200]);
    $bidding = ProjectInformation::query()->create(['project_id' => (string) $project->project_id, 'project_name' => 'Repeat project', 'project_code' => 'REPEAT-CODE', 'procuring_entity' => 'MMC', 'approved_budget_contract_abc' => 200, 'status' => 'Draft']);
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => '2']);
    $pplForm = PplForm::query()->create(['project_code' => 'REPEAT-CODE', 'lot_number' => '2', 'project_title' => 'Repeat project', 'abc' => 200]);
    $firstPath = mmcPplImportWorkbook(['A3' => 'REPEAT-CODE', 'B3' => '2', 'C3' => 'Repeat project', 'D3' => 'NO-BID-ID']);
    $secondPath = mmcPplImportWorkbook(['A3' => 'REPEAT-CODE', 'B3' => '2', 'C3' => 'Repeat project', 'D3' => 'NO-BID-ID'], 113);

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($firstPath)])->assertRedirect();
        $firstBatch = MmcPplImportBatch::query()->firstOrFail();
        $firstRow = $firstBatch->rows()->firstOrFail();
        $this->patch(route('finance.ppl-import.rows.mapping', [$firstBatch, $firstRow]), [
            'project_id' => $project->project_id,
            'project_information_id' => $bidding->id,
            'lot_id' => $lot->id,
        ])->assertRedirect();
        $this->post(route('finance.ppl-import.confirm', $firstBatch), ['acknowledge_warnings' => '1'])->assertRedirect();

        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($secondPath)])->assertRedirect();
        $secondRow = MmcPplImportBatch::query()->whereKeyNot($firstBatch->id)->firstOrFail()->rows()->firstOrFail();

        expect($secondRow->normalized_data['source_mapping']['origin'])->toBe('manual')
            ->and($secondRow->normalized_data['source_mapping']['reused_from_plan_id'])->not->toBeNull()
            ->and($secondRow->normalized_data['plan']['project_id'])->toBe($project->project_id)
            ->and($secondRow->normalized_data['plan']['project_information_id'])->toBe($bidding->id)
            ->and($secondRow->normalized_data['plan']['lot_id'])->toBe($lot->id)
            ->and($secondRow->normalized_data['plan']['ppl_form_id'])->toBe($pplForm->id);
    } finally {
        @unlink($firstPath);
        @unlink($secondPath);
    }
});

it('restricts the workbook import routes to the MMC finance company context', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $path = mmcPplImportWorkbook();

    try {
        $this->post(route('finance.ppl-import.store'), ['file' => mmcPplImportUpload($path)])->assertRedirect();
        $batch = MmcPplImportBatch::query()->firstOrFail();
        $row = $batch->rows()->firstOrFail();
        $this->signInBiddingUser('finance', 'MI');

        $this->get(route('finance.ppl-import.index'))->assertForbidden();
        $this->patch(route('finance.ppl-import.rows.mapping', [$batch, $row]), [
            'project_id' => null, 'project_information_id' => null, 'lot_id' => null,
        ])->assertForbidden();
    } finally {
        @unlink($path);
    }
});
