<?php

use App\Models\MmcPplImportBatch;
use App\Models\PplForm;
use App\Models\ProjectFinancialPlan;
use App\Services\ProjectFinancialPlanCalculator;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function mmcPplImportWorkbook(): string
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
        'M3' => 200, 'N3' => 0, 'O3' => 10, 'S3' => 0.01, 'T3' => 0, 'U3' => 0,
        'V3' => 0, 'W3' => 0, 'Y3' => 0, 'Z3' => 0, 'AA3' => 0, 'AB3' => 0,
        'AC3' => 0, 'AD3' => 0, 'AE3' => 0, 'AF3' => 0, 'AG3' => 0, 'AH3' => 0,
        'AI3' => 0, 'AJ3' => 0.1, 'AK3' => 0, 'AL3' => 0, 'AM3' => 0, 'AN3' => 0.13,
        'AO3' => 0.12, 'AX3' => 2026,
    ];
    foreach ($formValues as $coordinate => $value) {
        $form->setCellValue($coordinate, $value);
    }

    $factory = $spreadsheet->createSheet();
    $factory->setTitle('2. FACTORY COST');
    foreach (['A' => 'PROJECT CODE', 'B' => 'LOT #', 'D' => 'ITEM #', 'E' => 'ITEM DESCRIPTION', 'G' => 'FACTORY', 'I' => 'QTY', 'J' => 'Unit Cost (usd)', 'K' => 'Forex', 'L' => 'Amount in Php'] as $column => $header) {
        $factory->setCellValue($column.'2', $header);
    }
    foreach (['A3' => 'FIXTURE-001', 'B3' => 'Lot 1', 'D3' => '1', 'E3' => 'Desk', 'G3' => 'Fixture factory', 'I3' => 1, 'J3' => 10, 'K3' => 10, 'L3' => 100] as $coordinate => $value) {
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
        'contract_amount' => 112, 'abc' => 200, 'factory_cost' => 100, 'pf1_rate' => 0.01,
        'pf2_rate' => 0, 'pf3_rate' => 0, 'll_commission_rate' => 0, 'interest_dst_rate' => 0,
        'shipping_containers' => 0, 'rate_per_container' => 0, 'logistics_rate' => 0,
        'warehouse_area' => 0, 'warehouse_rent_per_sqm' => 0, 'warehouse_months' => 0,
        'other_expenses_rate' => 0, 'manpower_rate' => 0, 'assembly_rate' => 0, 'rd_rate' => 0,
        'facilitation_rate' => 0, 'tax_lawyer_rate' => 0.1, 'opex_rate' => 0, 'income_tax_rate' => 0,
        'incentive_rate' => 0, 'importation_vat_rate' => 0.13, 'sales_vat_rate' => 0.12,
    ];
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
    $ppl->setCellValue('A3', 'FIXTURE-001');
    $ppl->setCellValue('B3', 'Lot 1');
    $ppl->setCellValue('G3', 112);
    foreach ($resultColumns as $metric => $column) {
        $ppl->setCellValue($column.'3', data_get($results, $metric));
    }

    $path = tempnam(sys_get_temp_dir(), 'mmc-ppl-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

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
            ->and($batch->rows->first()->normalized_data['plan']['inputs']['tax_lawyer_rate'])->toBe('0.10000000');

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
            ->and($plan->latestVersion->input_data['workbook_import']['source_sheet'])->toBe('1. FORM');
        $this->assertDatabaseHas('project_financial_plan_cost_items', [
            'project_financial_plan_version_id' => $plan->latestVersion->id,
            'category' => 'factory_cost_detail',
            'amount' => 100,
        ]);
        $this->assertDatabaseHas('ppl_forms', ['id' => $form->id, 'project_title' => 'Existing historical form', 'abc' => 200]);
        $this->assertDatabaseCount('ppl_forms', 1);
        $this->assertDatabaseCount('mmc_ppl_import_source_mappings', 1);
        $this->get(route('finance.dashboard', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Synthetic import fixture');
    } finally {
        @unlink($path);
    }
});

it('restricts the workbook import routes to the MMC finance company context', function () {
    $this->signInBiddingUser('finance', 'MI');

    $this->get(route('finance.ppl-import.index'))->assertForbidden();
});
