<?php

namespace App\Services;

use App\Models\PplForm;
use App\Models\ProjectFinancialPlan;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;

class PplFormFinancialSummary
{
    /** @var list<string> */
    public const AMOUNTS = [
        'abc', 'contract_amount', 'net_sales', 'factory_cost', 'pf1', 'pf2', 'pf3',
        'interest_dst_working_capital', 'total_cost_of_sales', 'gross_profit', 'opex',
        'net_profit_before_tax', 'income_tax_provision', 'net_profit_after_tax',
        'ppl', 'working_capital_needed',
    ];

    /**
     * Sum one active, versioned MMC plan per matching form. Forms with multiple
     * plans need Finance review before their amounts can enter the totals.
     *
     * @param  Builder<PplForm>  $forms
     * @return array{form_abc: string, amounts: array<string, string>, gpm: ?string, npm: ?string, plan_count: int, ambiguous_count: int}
     */
    public function forFilteredForms(Builder $forms, int $companyId): array
    {
        $formAbc = BigDecimal::of((string) ((clone $forms)->sum('abc') ?? '0'));
        $plansByForm = [];
        ProjectFinancialPlan::query()
            ->forCompany($companyId)
            ->whereNotNull('ppl_form_id')
            ->whereIn('ppl_form_id', (clone $forms)->reorder()->select('ppl_forms.id'))
            ->with('latestVersion')
            ->lazyById(200)
            ->each(function (ProjectFinancialPlan $plan) use (&$plansByForm): void {
                $formId = (int) $plan->ppl_form_id;
                if (! array_key_exists($formId, $plansByForm)) {
                    $plansByForm[$formId] = $plan->latestVersion?->calculation_results;

                    return;
                }

                $plansByForm[$formId] = null;
            });

        $amounts = array_fill_keys(self::AMOUNTS, BigDecimal::zero());
        $planCount = 0;
        $ambiguousCount = 0;
        foreach ($plansByForm as $results) {
            if ($results === null) {
                $ambiguousCount++;

                continue;
            }

            $planCount++;
            foreach (self::AMOUNTS as $key) {
                $value = match ($key) {
                    'pf1', 'pf2', 'pf3', 'interest_dst_working_capital' => data_get($results, 'costs.'.$key),
                    default => $results[$key] ?? null,
                };
                if ($value !== null && is_numeric($value)) {
                    $amounts[$key] = $amounts[$key]->plus((string) $value);
                }
            }
        }

        $netSales = $amounts['net_sales'];

        return [
            'form_abc' => (string) $formAbc->toScale(2, RoundingMode::HALF_UP),
            'amounts' => array_map(fn (BigDecimal $amount): string => (string) $amount->toScale(2, RoundingMode::HALF_UP), $amounts),
            'gpm' => $netSales->isZero() ? null : (string) $amounts['gross_profit']->dividedBy($netSales, 6, RoundingMode::HALF_UP),
            'npm' => $netSales->isZero() ? null : (string) $amounts['ppl']->dividedBy($netSales, 6, RoundingMode::HALF_UP),
            'plan_count' => $planCount,
            'ambiguous_count' => $ambiguousCount,
        ];
    }
}
