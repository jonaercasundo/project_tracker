<?php

namespace App\Providers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\LogisticsIntegration;
use App\Models\MI_Liquidation;
use App\Models\MI_Product;
use App\Models\ProjectInformation;
use App\Policies\BudgetRequestPolicy;
use App\Policies\LogisticsIntegrationPolicy;
use App\Policies\MILiquidationPolicy;
use App\Policies\MIProductPolicy;
use App\Policies\ProjectInformationPolicy;
use App\Policies\TravelLiquidationPolicy;
use App\Services\AccountingWorkspace;
use App\Services\MIApprovalQueue;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(BudgetRequest::class, BudgetRequestPolicy::class);
        Gate::policy(Liquidation::class, TravelLiquidationPolicy::class);
        Gate::policy(MI_Liquidation::class, MILiquidationPolicy::class);
        Gate::policy(MI_Product::class, MIProductPolicy::class);
        Gate::policy(ProjectInformation::class, ProjectInformationPolicy::class);
        Gate::policy(LogisticsIntegration::class, LogisticsIntegrationPolicy::class);
        View::composer('components.accounting-sidebar', function ($view): void {
            $user = auth()->user();
            $view->with('accountingCounters', $user?->hasAccountingRole() && $user->currentCompany()?->code === 'MI'
                ? app(AccountingWorkspace::class)->counters($user) : []);
        });
        View::composer(['components.mi-sidebar', 'components.accounting-sidebar'], function ($view): void {
            $user = auth()->user();
            $view->with('miApprovalPendingCount', $user?->canAccessMIApprovals() ? app(MIApprovalQueue::class)->pendingCount($user) : 0);
        });
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME));
    }
}
