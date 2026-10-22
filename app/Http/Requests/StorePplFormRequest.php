<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePplFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'project_code' => ['required', 'string', 'max:255'],
            'lot_number' => ['required', 'string', 'max:50'],
            'project_title' => ['required', 'string', 'max:255'],
            'project_id_no' => ['nullable', 'string', 'max:255'],
            'source_project_id' => ['nullable', 'integer'],
            'source_project_information_id' => ['nullable', 'integer'],
            'source_bidding_lot_id' => ['nullable', 'integer'],
            'source_legacy_lot_id' => ['nullable', 'integer'],
            'region' => ['nullable', 'string', 'max:255'],
            'bid_opening' => ['nullable', 'date'],
            'noa_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'ntp_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'delivery_days_after_production' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'production_lead_time' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'collection_period' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'delivery_period' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'abc' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'lcb_abc' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'forex' => ['nullable', 'numeric', 'gt:0', 'max:999999999.999999'],
            'factory_downpayment' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'factory_payment_terms' => ['nullable', 'string', 'max:255'],
            'full_payment_after_delivery' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'pf1_contract_amt' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'pf2_contract_amt' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'pf3_contract_amt' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'll_comm_factory_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'interest_rate_dst' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'shipping_brokerage_containers' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'rate_per_container' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'logistics_abc' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'warehouse_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'warehouse_rental_per_sqm' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'warehouse_rental_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'other_expenses' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'manpower_others' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'assembly_service_center' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'rd_expenses' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'facilitation' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'allowance_tax_lawyer' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'opex' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'income_tax' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'incentive' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'importation_vat' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'output_vat' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'interest_rate_dst_per_annum' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'hold_out' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'retention' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'business_cycle' => ['nullable', 'string', 'max:255'],
            'ntp_date' => ['nullable', 'date'],
            'factory_delivery' => ['nullable', 'date'],
            'first_delivery_date' => ['nullable', 'date'],
            'collection_date' => ['nullable', 'date'],
            'year_of_revenue_recognition' => ['nullable', 'integer', 'between:1901,2155'],
            'bidder' => ['nullable', 'string', 'max:255'],
            'authorized_signatory' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $aliases = [
            'delivery_days' => 'delivery_days_after_production',
            'lcb' => 'lcb_abc',
            'pf1' => 'pf1_contract_amt',
            'pf2' => 'pf2_contract_amt',
            'pf3' => 'pf3_contract_amt',
            'll_commission_factory_cost' => 'll_comm_factory_cost',
            'shipping_container' => 'shipping_brokerage_containers',
            'warehouse_area' => 'warehouse_area_sqm',
            'warehouse_rental' => 'warehouse_rental_per_sqm',
            'warehouse_months' => 'warehouse_rental_months',
            'year_revenue_recognition' => 'year_of_revenue_recognition',
        ];

        $values = [];
        foreach ($aliases as $formField => $column) {
            if ($this->exists($formField)) {
                $values[$column] = $this->input($formField);
            }
        }

        $this->merge($values);
    }
}
