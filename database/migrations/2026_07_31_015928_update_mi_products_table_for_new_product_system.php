<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mi_products', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Remove Old Columns
            |--------------------------------------------------------------------------
            */

            $table->dropColumn([
                'item_no',
                'main_category',
                'sub_category',
                'sub_sub_category',
                'collection',
                'sample_type',
                'photo_reference',
                'file_id',
                'gdrive_link',
                'material',
                'dimension',
                'qty',
                'sample_dev_status',
                'present_in_showroom',
                'borrowed',
                'remarks',
            ]);

            /*
            |--------------------------------------------------------------------------
            | SKU
            |--------------------------------------------------------------------------
            */

            $table->string('sku')->nullable()->after('product_id');
            $table->string('item_code')->nullable()->after('sku');

            /*
            |--------------------------------------------------------------------------
            | Taxonomy
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('category_id')->after('description');
            $table->unsignedBigInteger('sub_category_id')->after('category_id');
            $table->unsignedBigInteger('product_type_id')->nullable()->after('sub_category_id');
            $table->unsignedBigInteger('collection_id')->nullable()->after('product_type_id');

            /*
            |--------------------------------------------------------------------------
            | Product Information
            |--------------------------------------------------------------------------
            */

            $table->string('type_of_sample')->after('collection_id');
            $table->string('classification')->nullable()->after('type_of_sample');
            $table->string('designed_by')->nullable()->after('classification');

            /*
            |--------------------------------------------------------------------------
            | Attributes
            |--------------------------------------------------------------------------
            */

            $table->json('materials')->nullable()->after('designed_by');
            $table->string('type')->nullable()->after('materials');
            $table->json('color')->nullable()->after('type');

            /*
            |--------------------------------------------------------------------------
            | Product Dimensions
            |--------------------------------------------------------------------------
            */

            $table->decimal('product_height',10,2)->nullable();
            $table->decimal('product_width',10,2)->nullable();
            $table->decimal('product_length',10,2)->nullable();
            $table->decimal('product_depth',10,2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Carton Dimensions
            |--------------------------------------------------------------------------
            */

            $table->decimal('carton_height',10,2)->nullable();
            $table->decimal('carton_width',10,2)->nullable();
            $table->decimal('carton_length',10,2)->nullable();
            $table->decimal('carton_depth',10,2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */

            $table->decimal('purchase_cost',12,2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Images
            |--------------------------------------------------------------------------
            */

            $table->text('image_link')->nullable();
            $table->string('product_file')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->string('status')->default('Active');

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('category_id')
                ->references('id')
                ->on('mi_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('sub_category_id')
                ->references('id')
                ->on('mi_sub_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('product_type_id')
                ->references('id')
                ->on('mi_product_types')
                ->nullOnDelete();

            $table->foreign('collection_id')
                ->references('id')
                ->on('mi_collections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mi_products', function (Blueprint $table) {

            $table->dropForeign(['category_id']);
            $table->dropForeign(['sub_category_id']);
            $table->dropForeign(['product_type_id']);
            $table->dropForeign(['collection_id']);

            $table->dropColumn([
                'sku',
                'item_code',

                'category_id',
                'sub_category_id',
                'product_type_id',
                'collection_id',

                'type_of_sample',
                'classification',
                'designed_by',

                'materials',
                'type',
                'color',

                'product_height',
                'product_width',
                'product_length',
                'product_depth',

                'carton_height',
                'carton_width',
                'carton_length',
                'carton_depth',

                'purchase_cost',

                'image_link',
                'product_file',

                'status',
            ]);

            $table->integer('item_no')->nullable();

            $table->string('main_category');
            $table->string('sub_category')->nullable();
            $table->string('sub_sub_category')->nullable();
            $table->string('collection')->nullable();

            $table->string('sample_type')->nullable();

            $table->string('photo_reference')->nullable();
            $table->string('file_id')->nullable();
            $table->text('gdrive_link')->nullable();

            $table->text('material')->nullable();
            $table->string('dimension')->nullable();

            $table->integer('qty')->default(1);

            $table->enum('sample_dev_status', [
                'For Development',
                'On-going',
                'Completed',
                'Cancelled',
                'returned'
            ])->default('For Development');

            $table->boolean('present_in_showroom')->default(false);
            $table->boolean('borrowed')->default(false);

            $table->text('remarks')->nullable();
        });
    }
};