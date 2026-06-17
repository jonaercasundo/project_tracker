<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppl_forms', function (Blueprint $table) {
            $table->id();

            // PROJECT INFO
            $table->string('project_code')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('project_title')->nullable();
            $table->string('project_id_no')->nullable();
            $table->string('region')->nullable();

            // TIMELINES
            $table->date('bid_opening')->nullable();
            $table->integer('noa_months')->nullable();
            $table->integer('ntp_months')->nullable();
            $table->integer('delivery_days_after_production')->nullable();
            $table->integer('production_lead_time')->nullable();
            $table->integer('collection_period')->nullable();
            $table->integer('delivery_period')->nullable();

            // FINANCIAL
            $table->decimal('abc', 15, 2)->nullable();
            $table->decimal('lcb_abc', 15, 2)->nullable();
            $table->decimal('forex', 15, 6)->nullable();

            $table->decimal('factory_downpayment', 15, 2)->nullable();
            $table->string('factory_payment_terms')->nullable();
            $table->decimal('full_payment_after_delivery', 15, 2)->nullable();

            $table->decimal('pf1_contract_amt', 15, 2)->nullable();
            $table->decimal('pf2_contract_amt', 15, 2)->nullable();
            $table->decimal('pf3_contract_amt', 15, 2)->nullable();

            $table->decimal('ll_comm_factory_cost', 15, 2)->nullable();
            $table->decimal('interest_rate_dst', 15, 4)->nullable();

            // LOGISTICS
            $table->string('warehouse_location')->nullable();
            $table->integer('shipping_brokerage_containers')->nullable();
            $table->decimal('rate_per_container', 15, 2)->nullable();
            $table->decimal('logistics_abc', 15, 2)->nullable();

            // WAREHOUSE
            $table->decimal('warehouse_area_sqm', 15, 2)->nullable();
            $table->decimal('warehouse_rental_per_sqm', 15, 2)->nullable();
            $table->integer('warehouse_rental_months')->nullable();

            // EXPENSES
            $table->decimal('other_expenses', 15, 2)->nullable();
            $table->decimal('manpower_others', 15, 2)->nullable();
            $table->decimal('assembly_service_center', 15, 2)->nullable();
            $table->decimal('rd_expenses', 15, 2)->nullable();
            $table->decimal('facilitation', 15, 2)->nullable();
            $table->decimal('allowance_tax_lawyer', 15, 2)->nullable();

            // TAX / FINANCE
            $table->decimal('opex', 15, 2)->nullable();
            $table->decimal('income_tax', 15, 2)->nullable();
            $table->decimal('incentive', 15, 2)->nullable();
            $table->decimal('importation_vat', 15, 2)->nullable();
            $table->decimal('output_vat', 15, 2)->nullable();

            $table->decimal('interest_rate_dst_per_annum', 15, 4)->nullable();
            $table->decimal('hold_out', 15, 2)->nullable();
            $table->decimal('retention', 15, 2)->nullable();

            // DATES
            $table->string('business_cycle')->nullable();
            $table->date('ntp_date')->nullable();
            $table->date('factory_delivery')->nullable();
            $table->date('first_delivery_date')->nullable();
            $table->date('collection_date')->nullable();
            $table->year('year_of_revenue_recognition')->nullable();

            // PARTY INFO
            $table->string('bidder')->nullable();
            $table->string('authorized_signatory')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppl_forms');
    }
};