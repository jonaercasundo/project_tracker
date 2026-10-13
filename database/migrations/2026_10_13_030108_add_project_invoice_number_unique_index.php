<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            throw new RuntimeException('The legacy invoices table must exist before enforcing project invoice-number uniqueness.');
        }

        $duplicate = DB::table('invoices')
            ->select('project_id', 'invoice_no')
            ->groupBy('project_id', 'invoice_no')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicate) {
            throw new RuntimeException('Resolve duplicate invoice numbers within each project before installing delivery billing.');
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(['project_id', 'invoice_no'], 'invoices_project_invoice_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropUnique('invoices_project_invoice_unique');
            });
        }
    }
};
