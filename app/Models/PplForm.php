<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PplForm extends Model
{
    protected $table = 'ppl_forms';

    protected $fillable = [
        'company_id',
        'project_code',
        'lot_number',
        'project_title',
        'project_id_no',
        'region',
        'bid_opening',
        'noa_months',
        'ntp_months',
        'delivery_days_after_production',
        'production_lead_time',
        'collection_period',
        'delivery_period',
        'abc',
        'lcb_abc',
        'forex',
        'factory_downpayment',
        'factory_payment_terms',
        'full_payment_after_delivery',
        'pf1_contract_amt',
        'pf2_contract_amt',
        'pf3_contract_amt',
        'll_comm_factory_cost',
        'interest_rate_dst',
        'warehouse_location',
        'shipping_brokerage_containers',
        'rate_per_container',
        'logistics_abc',
        'warehouse_area_sqm',
        'warehouse_rental_per_sqm',
        'warehouse_rental_months',
        'other_expenses',
        'manpower_others',
        'assembly_service_center',
        'rd_expenses',
        'facilitation',
        'allowance_tax_lawyer',
        'opex',
        'income_tax',
        'incentive',
        'importation_vat',
        'output_vat',
        'interest_rate_dst_per_annum',
        'hold_out',
        'retention',
        'business_cycle',
        'ntp_date',
        'factory_delivery',
        'first_delivery_date',
        'collection_date',
        'year_of_revenue_recognition',
        'bidder',
        'authorized_signatory',
    ];

    public function sourceLink(): HasOne
    {
        return $this->hasOne(MmcPplSourceLink::class, 'ppl_form_id');
    }

    protected $casts = [
        'bid_opening' => 'date',
        'ntp_date' => 'date',
        'factory_delivery' => 'date',
        'first_delivery_date' => 'date',
        'collection_date' => 'date',
    ];
}
