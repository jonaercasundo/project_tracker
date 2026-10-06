<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class MigrateMI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mi:migrate {--force : Run without the production confirmation} {--pretend : Show SQL without applying migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install MI catalog and financial migrations with company dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->error('Install the shared users schema before running MI migrations.');

            return self::FAILURE;
        }

        $options = [
            '--force' => (bool) $this->option('force'),
            '--pretend' => (bool) $this->option('pretend'),
            '--no-interaction' => ! $this->input->isInteractive(),
        ];

        /** The quotation migration predates its factory dependency. */
        $result = $this->call('migrate', $options + ['--path' => ['database/migrations/2026_07_27_074144_create_mi_factories_table.php']]);
        if ($result !== self::SUCCESS) {
            return $result;
        }

        return $this->call('migrate', $options + ['--path' => [
            'database/migrations/2026_07_27_073520_create_m_i__products_table.php',
            'database/migrations/2026_07_27_073852_create_mi_product_quotations_table.php',
            'database/migrations/2026_07_27_073909_create_mi_cost_assumptions_table.php',
            'database/migrations/2026_07_27_073942_create_mi_costing_analyses_table.php',
            'database/migrations/2026_07_29_075636_create_categories_table.php',
            'database/migrations/2026_07_29_075711_create_sub_categories_table.php',
            'database/migrations/2026_07_29_075746_create_product_types_table.php',
            'database/migrations/2026_07_29_075829_create_collections_table.php',
            'database/migrations/2026_07_30_085237_create_mi_materials_table.php',
            'database/migrations/2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
            'database/migrations/2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
            'database/migrations/2026_08_17_000000_add_price_to_products_table.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
            'database/migrations/2026_09_02_093217_create_liquidations_table.php',
            'database/migrations/2026_09_04_075822_change_requested_by_to_string_on_mi_liquidation_items_table.php',
            'database/migrations/2026_09_18_091109_create_budget_requests_table.php',
            'database/migrations/2026_09_18_091132_create_budget_requests_items_table.php',
            'database/migrations/2026_09_18_091159_create_liquidations_table.php',
            'database/migrations/2026_09_18_091209_create_liquidations_items_table.php',
            'database/migrations/2026_10_06_031105_add_mi_financial_attribution_and_audit_tables.php',
            'database/migrations/2026_10_06_040342_create_mi_product_images_table.php',
            'database/migrations/2026_10_06_085134_add_unique_item_code_to_mi_products_table.php',
            'database/migrations/2026_10_06_050731_change_users_role_to_string.php',
            'database/migrations/2026_10_06_061026_rename_mi_approver_role_to_executive.php',
            'database/migrations/2026_10_06_080510_enable_mi_executive_approval_workflow.php',
        ]]);
    }
}
