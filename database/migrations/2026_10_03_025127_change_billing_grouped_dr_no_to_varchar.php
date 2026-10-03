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
        Schema::table('billing_grouped', function (Blueprint $table): void {
            $table->string('dr_no', 100)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('billing_grouped')->pluck('dr_no') as $receiptNumber) {
            $receiptNumber = (string) $receiptNumber;
            if ((string) (int) $receiptNumber !== $receiptNumber || (int) $receiptNumber < -2147483648 || (int) $receiptNumber > 2147483647) {
                throw new RuntimeException('Cannot restore billing_grouped.dr_no to INT: receipt identifiers would be changed or lost.');
            }
        }

        Schema::table('billing_grouped', function (Blueprint $table): void {
            $table->integer('dr_no')->nullable(false)->change();
        });
    }
};
