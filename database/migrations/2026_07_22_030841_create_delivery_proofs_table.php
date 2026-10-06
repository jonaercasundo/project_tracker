<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('package_status') || ! Schema::hasColumn('package_status', 'package_status_id')) {
            throw new RuntimeException('Restore the legacy package_status table with its package_status_id primary key before migrating delivery proofs.');
        }

        if (Schema::hasTable('delivery_proofs')) {
            if (! Schema::hasColumns('delivery_proofs', ['id', 'package_status_id', 'photo', 'created_at', 'updated_at'])) {
                throw new RuntimeException('The existing delivery_proofs schema is incomplete; reconcile it before migrating.');
            }
            foreach (Schema::getForeignKeys('delivery_proofs') as $foreignKey) {
                if ($foreignKey['columns'] === ['package_status_id']) {
                    if ($foreignKey['foreign_table'] !== 'package_status' || $foreignKey['foreign_columns'] !== ['package_status_id'] || strtolower($foreignKey['on_delete']) !== 'cascade') {
                        throw new RuntimeException('The existing delivery_proofs foreign key requires reconciliation.');
                    }

                    return;
                }
            }
            if (DB::table('delivery_proofs')->leftJoin('package_status', 'package_status.package_status_id', '=', 'delivery_proofs.package_status_id')->whereNull('package_status.package_status_id')->exists()) {
                throw new RuntimeException('Existing delivery proofs reference missing package statuses; reconcile them before adding the foreign key.');
            }
            Schema::table('delivery_proofs', function (Blueprint $table): void {
                $table->foreign('package_status_id')->references('package_status_id')->on('package_status')->cascadeOnDelete();
            });

            return;
        }

        Schema::create('delivery_proofs', function (Blueprint $table) {
            $table->id();

            // This creates the unsignedBigInteger and the constraint automatically
            $table->foreignId('package_status_id')
                ->constrained('package_status', 'package_status_id')
                ->onDelete('cascade');

            $table->string('photo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_proofs');
    }
};
