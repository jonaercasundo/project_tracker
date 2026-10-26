<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAuditLogger
{
    /** @param array<string, scalar|null> $details */
    public function record(
        ?User $actor,
        string $event,
        string $subjectType,
        string|int|null $subjectId,
        string $summary,
        ?int $companyId = null,
        array $details = [],
    ): void {
        if (! Schema::hasTable('admin_activity_logs')) {
            return;
        }

        DB::table('admin_activity_logs')->insert([
            'actor_user_id' => $actor?->getAuthIdentifier(),
            'company_id' => $companyId,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'summary' => $summary,
            'details' => $details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
