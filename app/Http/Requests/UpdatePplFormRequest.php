<?php

namespace App\Http\Requests;

class UpdatePplFormRequest extends StorePplFormRequest
{
    public function rules(): array
    {
        $rules = array_diff_key(parent::rules(), array_flip([
            'source_project_id',
            'source_project_information_id',
            'source_bidding_lot_id',
            'source_legacy_lot_id',
        ]));

        foreach ([
            'lcb_abc', 'factory_downpayment', 'full_payment_after_delivery',
            'pf1_contract_amt', 'pf2_contract_amt', 'pf3_contract_amt',
            'll_comm_factory_cost', 'logistics_abc', 'other_expenses',
            'manpower_others', 'assembly_service_center', 'rd_expenses',
            'facilitation', 'allowance_tax_lawyer', 'opex', 'income_tax',
            'incentive', 'importation_vat', 'output_vat', 'hold_out', 'retention',
        ] as $rateField) {
            $rules[$rateField][] = 'decimal:0,2';
        }

        $rules['interest_rate_dst'][] = 'decimal:0,4';
        $rules['interest_rate_dst_per_annum'][] = 'decimal:0,4';
        $rules['forex'][] = 'decimal:0,6';

        return $rules;
    }
}
