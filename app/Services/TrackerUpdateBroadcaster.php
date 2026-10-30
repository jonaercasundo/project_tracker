<?php

namespace App\Services;

use App\Events\TrackerModulesUpdated;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrackerUpdateBroadcaster
{
    /** @param array<int, string> $modules */
    public function forCompany(int $companyId, array $modules): void
    {
        try {
            event(new TrackerModulesUpdated($companyId, array_values(array_unique($modules))));
        } catch (Throwable $exception) {
            Log::warning('Tracker live update broadcast failed.', [
                'company_id' => $companyId,
                'modules' => $modules,
                'exception' => $exception::class,
            ]);
        }
    }

    public function companyIdForProject(?int $projectId): ?int
    {
        if ($projectId === null) {
            return null;
        }

        try {
            $companyId = Project::query()
                ->where('project_id', $projectId)
                ->value('company_id');
        } catch (Throwable $exception) {
            Log::warning('Could not resolve company for tracker live update.', [
                'project_id' => $projectId,
                'exception' => $exception::class,
            ]);

            return null;
        }

        return $companyId === null ? null : (int) $companyId;
    }

    /** @param array<int, string> $modules */
    public function forCompanyForDelivery(?int $deliveryId, array $modules): void
    {
        if ($deliveryId === null) {
            return;
        }

        try {
            $companyId = DB::table('deliveries')
                ->join('projects', 'projects.project_id', '=', 'deliveries.project_id')
                ->where('deliveries.delivery_id', $deliveryId)
                ->value('projects.company_id');
        } catch (Throwable $exception) {
            Log::warning('Could not resolve company for delivery live update.', [
                'delivery_id' => $deliveryId,
                'exception' => $exception::class,
            ]);

            return;
        }

        if ($companyId !== null) {
            $this->forCompany((int) $companyId, $modules);
        }
    }
}
