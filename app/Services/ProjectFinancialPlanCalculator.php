<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class ProjectFinancialPlanCalculator
{
    /** @var array<string, string> */
    private const WORKBOOK_ASSUMPTIONS = [
        'municipal_first_rate' => '0.021',
        'municipal_excess_rate' => '0.0055',
        'rrdm_rate' => '0.10',
        'bondable_rate' => '0.30',
        'bond_premium_rate' => '0.0055',
        'bond_dst_rate' => '0.125',
        'bond_lgt_rate' => '0.002',
        'bond_notarial_fee' => '700',
        'bond_service_fee' => '1150',
        'marine_premium_rate' => '0.00125',
        'marine_dst_rate' => '0.125',
        'marine_lgt_rate' => '0.002',
        'fire_premium_rate' => '0.0008',
        'fire_dst_rate' => '0.125',
        'fire_tax_rate' => '0.02',
        'fire_lgt_rate' => '0.002',
        'inland_premium_rate' => '0.0015',
        'inland_dst_rate' => '0.125',
        'inland_lgt_rate' => '0.002',
    ];

    /** @return array<string, string> */
    public function defaultAssumptions(): array
    {
        return self::WORKBOOK_ASSUMPTIONS;
    }

    /** @return array<string, string> */
    public function formulaManifest(): array
    {
        return [
            'net_sales' => 'contract_amount / (1 + sales_vat_rate)',
            'factory_cost' => 'sum of project lot item factory costs, sourced from the entered value or an MI factory-cost snapshot',
            'pf1' => 'pf1_rate * contract_amount',
            'pf2' => 'pf2_rate * contract_amount',
            'pf3' => 'pf3_rate * contract_amount',
            'll_commission' => 'll_commission_rate * factory_cost',
            'interest_dst_working_capital' => 'interest_dst_rate * net_sales',
            'shipping_brokerage' => 'container_count * rate_per_container',
            'logistics' => 'logistics_rate * ABC',
            'warehouse_rental' => 'warehouse_area_sqm * rental_per_sqm * rental_months',
            'other_expenses' => 'other_expenses_rate * contract_amount',
            'manpower' => 'manpower_rate * ABC',
            'assembly_service_center' => 'assembly_rate * ABC',
            'research_and_development' => 'rd_rate * ABC',
            'facilitation' => 'facilitation_rate * ABC',
            'tax_lawyer_allowance' => 'tax_lawyer_rate * ABC',
            'municipal_tax' => '((min(net_sales, 400000) * first_tier_rate) + (max(net_sales - 400000, 0) * excess_rate)) * (1 + RRDM_rate)',
            'performance_bond' => '(contract_amount * bondable_rate * premium_rate * (1 + DST_rate + LGT_rate)) + notarial_fee + service_fee',
            'goods_insurance' => 'factory_cost * (marine_rate + fire_rate + inland_marine_rate)',
            'total_cost_of_sales' => 'factory_cost + PF1 + PF2 + PF3 + LL_commission + interest_DST + shipping + logistics + warehouse + other_costs + municipal_tax + bond + goods_insurance',
            'gross_profit' => 'net_sales - total_cost_of_sales',
            'gpm' => 'gross_profit / net_sales; null when net_sales is zero',
            'net_profit_before_tax' => 'gross_profit - OPEX',
            'income_tax_provision' => 'if net_profit_before_tax > 0 then income_tax_rate * net_sales else 0',
            'net_profit_after_tax' => 'net_profit_before_tax - income_tax_provision',
            'incentive' => 'max(net_profit_after_tax, 0) * incentive_rate',
            'PPL' => 'net_profit_after_tax - incentive',
            'NPM' => 'PPL / net_sales; null when net_sales is zero',
            'importation_vat' => 'factory_cost * importation_vat_rate',
            'working_capital_needed' => 'total_cost_of_sales - municipal_tax',
            'additional_costs' => 'sum of individually recorded additional cost lines',
        ];
    }

    /**
     * Calculate project profitability using the workbook's PPL summary formulas.
     * Currency outputs are rounded to cents; margins are ratios and remain null when sales are zero.
     *
     * @param  array<string, string|int|float|null>  $inputs
     * @param  array<string, string|int|float|null>  $assumptions
     * @param  list<array{label: string, amount: string|int|float}>  $additionalCosts
     * @return array<string, string|null>
     */
    public function calculate(array $inputs, array $assumptions = [], array $additionalCosts = []): array
    {
        $rates = array_replace(self::WORKBOOK_ASSUMPTIONS, $assumptions);
        $contractAmount = $this->decimal($inputs['contract_amount'] ?? 0);
        $abc = $this->decimal($inputs['abc'] ?? 0);
        $factoryCost = $this->decimal($inputs['factory_cost'] ?? 0);
        $netSales = $contractAmount->dividedBy(BigDecimal::of('1')->plus($this->decimal($inputs['sales_vat_rate'] ?? '0.12')), 8, RoundingMode::HALF_UP);

        $costs = [
            'factory_cost' => $factoryCost,
            'pf1' => $this->multiply($contractAmount, $inputs['pf1_rate'] ?? 0),
            'pf2' => $this->multiply($contractAmount, $inputs['pf2_rate'] ?? 0),
            'pf3' => $this->multiply($contractAmount, $inputs['pf3_rate'] ?? 0),
            'll_commission' => $this->multiply($factoryCost, $inputs['ll_commission_rate'] ?? 0),
            'interest_dst_working_capital' => $this->multiply($netSales, $inputs['interest_dst_rate'] ?? 0),
            'shipping_brokerage' => $this->decimal($inputs['shipping_containers'] ?? 0)->multipliedBy($this->decimal($inputs['rate_per_container'] ?? 0)),
            'logistics' => $this->multiply($abc, $inputs['logistics_rate'] ?? 0),
            'warehouse_rental' => $this->decimal($inputs['warehouse_area'] ?? 0)
                ->multipliedBy($this->decimal($inputs['warehouse_rent_per_sqm'] ?? 0))
                ->multipliedBy($this->decimal($inputs['warehouse_months'] ?? 0)),
            'other_expenses' => $this->multiply($contractAmount, $inputs['other_expenses_rate'] ?? 0),
            'manpower' => $this->multiply($abc, $inputs['manpower_rate'] ?? 0),
            'assembly_service_center' => $this->multiply($abc, $inputs['assembly_rate'] ?? 0),
            'research_and_development' => $this->multiply($abc, $inputs['rd_rate'] ?? 0),
            'facilitation' => $this->multiply($abc, $inputs['facilitation_rate'] ?? 0),
            'tax_lawyer_allowance' => $this->multiply($abc, $inputs['tax_lawyer_rate'] ?? 0),
            'municipal_tax' => $this->municipalTax($netSales, $rates),
            'performance_bond' => $this->performanceBond($contractAmount, $rates),
            'goods_insurance' => $this->goodsInsurance($factoryCost, $rates),
        ];

        $additionalCostTotal = BigDecimal::zero();
        foreach ($additionalCosts as $cost) {
            $additionalCostTotal = $additionalCostTotal->plus($this->decimal($cost['amount']));
        }
        $costs['additional_costs'] = $additionalCostTotal;

        $totalCostOfSales = array_reduce($costs, fn (BigDecimal $sum, BigDecimal $cost): BigDecimal => $sum->plus($cost), BigDecimal::zero());
        $grossProfit = $netSales->minus($totalCostOfSales);
        $operatingExpenses = $this->multiply($netSales, $inputs['opex_rate'] ?? 0);
        $netProfitBeforeTax = $grossProfit->minus($operatingExpenses);
        $incomeTax = $netProfitBeforeTax->isNegative() ? BigDecimal::zero() : $this->multiply($netSales, $inputs['income_tax_rate'] ?? 0);
        $netProfitAfterTax = $netProfitBeforeTax->minus($incomeTax);
        $incentive = $netProfitAfterTax->isNegative() ? BigDecimal::zero() : $this->multiply($netProfitAfterTax, $inputs['incentive_rate'] ?? 0);
        $ppl = $netProfitAfterTax->minus($incentive);
        $importationVat = $this->multiply($factoryCost, $inputs['importation_vat_rate'] ?? '0.13');

        $results = [
            'contract_amount' => $this->money($contractAmount),
            'abc' => $this->money($abc),
            'net_sales' => $this->money($netSales),
            'factory_cost' => $this->money($costs['factory_cost']),
            'costs' => $this->moneyMap($costs),
            'total_cost_of_sales' => $this->money($totalCostOfSales),
            'gross_profit' => $this->money($grossProfit),
            'gpm' => $this->ratio($grossProfit, $netSales),
            'opex' => $this->money($operatingExpenses),
            'net_profit_before_tax' => $this->money($netProfitBeforeTax),
            'income_tax_provision' => $this->money($incomeTax),
            'net_profit_after_tax' => $this->money($netProfitAfterTax),
            'incentive' => $this->money($incentive),
            'ppl' => $this->money($ppl),
            'npm' => $this->ratio($ppl, $netSales),
            'importation_vat' => $this->money($importationVat),
            'working_capital_needed' => $this->money($totalCostOfSales->minus($costs['municipal_tax'])),
        ];

        return $results;
    }

    /** @param array<string, BigDecimal> $rates */
    private function municipalTax(BigDecimal $netSales, array $rates): BigDecimal
    {
        $taxableFirstTier = $netSales->isGreaterThan('400000') ? BigDecimal::of('400000') : $netSales;
        $excess = $netSales->isGreaterThan('400000') ? $netSales->minus('400000') : BigDecimal::zero();
        $basicTax = $taxableFirstTier->multipliedBy($this->decimal($rates['municipal_first_rate']))
            ->plus($excess->multipliedBy($this->decimal($rates['municipal_excess_rate'])));

        return $basicTax->multipliedBy(BigDecimal::one()->plus($this->decimal($rates['rrdm_rate'])));
    }

    /** @param array<string, BigDecimal> $rates */
    private function performanceBond(BigDecimal $contractAmount, array $rates): BigDecimal
    {
        return $contractAmount
            ->multipliedBy($this->decimal($rates['bondable_rate']))
            ->multipliedBy($this->decimal($rates['bond_premium_rate']))
            ->multipliedBy(BigDecimal::one()->plus($this->decimal($rates['bond_dst_rate']))->plus($this->decimal($rates['bond_lgt_rate'])))
            ->plus($this->decimal($rates['bond_notarial_fee']))
            ->plus($this->decimal($rates['bond_service_fee']));
    }

    /** @param array<string, BigDecimal> $rates */
    private function goodsInsurance(BigDecimal $factoryCost, array $rates): BigDecimal
    {
        $marine = $this->insuranceRate($rates['marine_premium_rate'], $rates['marine_dst_rate'], $rates['marine_lgt_rate']);
        $fire = $this->insuranceRate($rates['fire_premium_rate'], $rates['fire_dst_rate'], $rates['fire_lgt_rate'], $rates['fire_tax_rate']);
        $inland = $this->insuranceRate($rates['inland_premium_rate'], $rates['inland_dst_rate'], $rates['inland_lgt_rate']);

        return $factoryCost->multipliedBy($marine->plus($fire)->plus($inland));
    }

    private function insuranceRate(string|int|float $premium, string|int|float $stampTax, string|int|float $localTax, string|int|float $additionalTax = 0): BigDecimal
    {
        return $this->decimal($premium)->multipliedBy(
            BigDecimal::one()
                ->plus($this->decimal($stampTax))
                ->plus($this->decimal($localTax))
                ->plus($this->decimal($additionalTax)),
        );
    }

    private function multiply(BigDecimal $amount, string|int|float $rate): BigDecimal
    {
        return $amount->multipliedBy($this->decimal($rate));
    }

    private function decimal(string|int|float|null $value): BigDecimal
    {
        return BigDecimal::of($value === null || $value === '' ? '0' : (string) $value);
    }

    private function money(BigDecimal $value): string
    {
        return (string) $value->toScale(2, RoundingMode::HALF_UP);
    }

    /** @param array<string, BigDecimal> $values
     * @return array<string, string>
     */
    private function moneyMap(array $values): array
    {
        $formatted = [];
        foreach ($values as $key => $value) {
            $formatted[$key] = $this->money($value);
        }

        return $formatted;
    }

    private function ratio(BigDecimal $numerator, BigDecimal $denominator): ?string
    {
        if ($denominator->isZero()) {
            return null;
        }

        return (string) $numerator->dividedBy($denominator, 8, RoundingMode::HALF_UP);
    }
}
