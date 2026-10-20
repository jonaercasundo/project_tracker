<?php

namespace Tests;

class FinancialPlanTestCase extends BiddingTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrations = parent::migrateFreshUsing()['--path'];
        $migrations[] = 'database/migrations/2026_07_27_073520_create_m_i__products_table.php';
        $migrations[] = 'database/migrations/2026_10_12_055813_create_mi_product_factory_costs_table.php';
        $migrations[] = 'database/migrations/2026_10_20_082229_add_country_of_production_to_mi_products_table.php';
        $migrations[] = 'database/migrations/2026_06_17_040839_create_ppl_form_table.php';
        $migrations[] = 'database/migrations/2026_10_16_074412_create_project_financial_plan_tables.php';

        return ['--path' => $migrations];
    }
}
