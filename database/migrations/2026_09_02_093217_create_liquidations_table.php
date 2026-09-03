<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Liquidation Header
        |--------------------------------------------------------------------------
        */

        Schema::create('mi_liquidations', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            $table->string('title');

            $table->date('date_prepared');

            /*
            | VND per 1 USD
            | Example: 26160.0000
            */
            $table->decimal(
                'exchange_rate',
                12,
                4
            );

            /*
            |--------------------------------------------------------------------------
            | Petty Cash Fund
            |--------------------------------------------------------------------------
            |
            | Amount originally given to the employee/requester.
            | Stored in VND.
            |
            */
            $table->decimal(
                'pcf_amount',
                14,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            |
            | companies.company_id is the PK.
            |
            */
            $table->unsignedBigInteger(
                'company_id'
            )->nullable();

            $table->foreign('company_id')
                ->references('company_id')
                ->on('companies')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Prepared By
            |--------------------------------------------------------------------------
            |
            | users.user_id is the PK and is signed integer.
            |
            */
            $table->integer(
                'prepared_by'
            )->nullable();

            $table->foreign('prepared_by')
                ->references('user_id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->string('status')
                ->default('Pending');

            /*
            |--------------------------------------------------------------------------
            | Timestamps / Soft Delete
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('status');

            $table->index('date_prepared');

            $table->index('company_id');

            $table->index('prepared_by');
        });


        /*
        |--------------------------------------------------------------------------
        | Liquidation Items
        |--------------------------------------------------------------------------
        */

        Schema::create('mi_liquidation_items', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Liquidation
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'liquidation_id'
            )
                ->constrained(
                    'mi_liquidations'
                )
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Expense Identification
            |--------------------------------------------------------------------------
            */

            /*
            | Example:
            | LF-20260903-001
            */
            $table->string(
                'ref_no',
                50
            );


            /*
            | Line number within the liquidation report.
            */
            $table->unsignedInteger(
                'line_no'
            );


            /*
            |--------------------------------------------------------------------------
            | Expense Date
            |--------------------------------------------------------------------------
            */

            $table->date(
                'item_date'
            );


            /*
            |--------------------------------------------------------------------------
            | Requested By
            |--------------------------------------------------------------------------
            |
            | users.user_id
            |
            */
            $table->integer(
                'requested_by'
            )->nullable();

            $table->foreign('requested_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Expense Classification
            |--------------------------------------------------------------------------
            */

            $table->string(
                'payee'
            );

            $table->string(
                'expense_type'
            );

            $table->string(
                'account_buyer'
            );


            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            |
            | Amount is stored only in VND.
            |
            | USD is calculated dynamically using:
            |
            | amount_vnd / liquidation.exchange_rate
            |
            */
            $table->decimal(
                'amount_vnd',
                14,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            $table->text(
                'remarks'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Receipt
            |--------------------------------------------------------------------------
            |
            | Stores the relative path, e.g.
            |
            | liquidations/receipts/abc123.jpg
            |
            */
            $table->string(
                'receipt_image'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                'liquidation_id'
            );

            $table->index(
                'ref_no'
            );

            $table->index(
                'item_date'
            );

            $table->index(
                'requested_by'
            );

            $table->index(
                'expense_type'
            );

            $table->index(
                'account_buyer'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'mi_liquidation_items'
        );

        Schema::dropIfExists(
            'mi_liquidations'
        );
    }
};