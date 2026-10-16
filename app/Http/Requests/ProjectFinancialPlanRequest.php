<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

abstract class ProjectFinancialPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'project_information_id' => ['nullable', 'integer', 'exists:project_information,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'ppl_form_id' => ['nullable', 'integer', 'exists:ppl_forms,id'],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'inputs' => ['required', 'array:contract_amount,abc,factory_cost,pf1_rate,pf2_rate,pf3_rate,ll_commission_rate,interest_dst_rate,shipping_containers,rate_per_container,logistics_rate,warehouse_area,warehouse_rent_per_sqm,warehouse_months,other_expenses_rate,manpower_rate,assembly_rate,rd_rate,facilitation_rate,tax_lawyer_rate,opex_rate,income_tax_rate,incentive_rate,importation_vat_rate,sales_vat_rate,factory_cost_id'],
            'inputs.contract_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'inputs.abc' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'inputs.factory_cost' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'inputs.pf1_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.pf2_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.pf3_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.ll_commission_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.interest_dst_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.shipping_containers' => ['required', 'integer', 'min:0', 'max:1000000'],
            'inputs.rate_per_container' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'inputs.logistics_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.warehouse_area' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'inputs.warehouse_rent_per_sqm' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'inputs.warehouse_months' => ['required', 'integer', 'min:0', 'max:1200'],
            'inputs.other_expenses_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.manpower_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.assembly_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.rd_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.facilitation_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.tax_lawyer_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.opex_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.income_tax_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.incentive_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.importation_vat_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.sales_vat_rate' => ['required', 'numeric', 'between:0,1'],
            'inputs.factory_cost_id' => ['nullable', 'integer', 'exists:mi_product_factory_costs,id'],
            'assumptions' => ['nullable', 'array:municipal_first_rate,municipal_excess_rate,rrdm_rate,bondable_rate,bond_premium_rate,bond_dst_rate,bond_lgt_rate,bond_notarial_fee,bond_service_fee,marine_premium_rate,marine_dst_rate,marine_lgt_rate,fire_premium_rate,fire_dst_rate,fire_tax_rate,fire_lgt_rate,inland_premium_rate,inland_dst_rate,inland_lgt_rate'],
            'assumptions.municipal_first_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.municipal_excess_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.rrdm_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.bondable_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.bond_premium_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.bond_dst_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.bond_lgt_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.bond_notarial_fee' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'assumptions.bond_service_fee' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'assumptions.marine_premium_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.marine_dst_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.marine_lgt_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.fire_premium_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.fire_dst_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.fire_tax_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.fire_lgt_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.inland_premium_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.inland_dst_rate' => ['nullable', 'numeric', 'between:0,1'],
            'assumptions.inland_lgt_rate' => ['nullable', 'numeric', 'between:0,1'],
            'cost_items' => ['nullable', 'array', 'max:100'],
            'cost_items.*.label' => ['required_with:cost_items', 'string', 'max:255'],
            'cost_items.*.amount' => ['required_with:cost_items', 'numeric', 'min:0', 'max:9999999999999.99'],
            'cost_items.*.source_type' => ['nullable', 'string', 'max:80'],
            'cost_items.*.source_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $lotId = $this->input('lot_id');
            $projectInformationId = $this->input('project_information_id');

            if ($lotId && $projectInformationId && ! DB::table('lots')->where('id', $lotId)->where('project_id', $projectInformationId)->exists()) {
                $validator->errors()->add('lot_id', 'The selected lot does not belong to the selected bidding document.');
            }
        }];
    }
}
