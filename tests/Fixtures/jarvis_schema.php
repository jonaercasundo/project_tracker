<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SQLite fixture of the inspected legacy operations schema, absent from migrations. */
Schema::create('projects', function (Blueprint $table): void {
    $table->increments('project_id');
    $table->string('project_code')->nullable();
    $table->string('ref_no')->nullable();
    $table->string('project_name')->nullable();
    $table->string('agency')->nullable();
    $table->decimal('contract_amount')->nullable();
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->string('status')->nullable();
    $table->dateTime('created_at')->nullable();
    $table->string('keystage')->nullable();
    $table->decimal('ABC')->nullable();
});

Schema::create('deliveries', function (Blueprint $table): void {
    $table->increments('delivery_id');
    $table->integer('project_id')->nullable();
    $table->string('school_id')->nullable();
    $table->integer('keystage_id')->nullable();
    $table->integer('lot_id')->nullable();
    $table->string('package_type')->nullable();
    $table->string('dr_no')->nullable();
    $table->date('delivery_date')->nullable();
    $table->string('status')->nullable();
    $table->dateTime('created_at')->nullable();
    $table->date('delivered_date')->nullable();
    $table->integer('logistics_location_id')->nullable();
    $table->date('accepted_date')->nullable();
    $table->integer('package_qty')->nullable();
    $table->string('qty_teachers_manual')->nullable();
    $table->integer('received_qty')->nullable();
});

Schema::create('school', function (Blueprint $table): void {
    $table->string('school_id')->primary();
    $table->integer('project_id')->nullable();
    $table->string('project_no')->nullable();
    $table->string('project')->nullable();
    $table->decimal('total_contract_price')->nullable();
    $table->string('school_name')->nullable();
    $table->string('address')->nullable();
    $table->string('contact_person')->nullable();
    $table->string('contact')->nullable();
    $table->string('municipality')->nullable();
    $table->string('division')->nullable();
    $table->string('region')->nullable();
    $table->decimal('latitude')->nullable();
    $table->decimal('longitude')->nullable();
});

Schema::create('logistics_location', function (Blueprint $table): void {
    $table->increments('logistics_location_id');
    $table->integer('logistics_id')->nullable();
    $table->integer('warehouse_id')->nullable();
    $table->string('region')->nullable();
    $table->decimal('latitude')->nullable();
    $table->decimal('longitude')->nullable();
});

Schema::create('warehouse', function (Blueprint $table): void {
    $table->increments('warehouse_id');
    $table->string('warehouse_name')->nullable();
    $table->string('warehouse_address')->nullable();
    $table->string('contact_info')->nullable();
});

Schema::create('inventory', function (Blueprint $table): void {
    $table->integer('item_id')->nullable();
    $table->integer('warehouse_id')->nullable();
    $table->increments('inventory_id');
    $table->integer('qty')->nullable();
    $table->string('inventory_status')->nullable();
    $table->dateTime('created_at')->nullable();
});

Schema::create('item', function (Blueprint $table): void {
    $table->increments('item_id');
    $table->string('item_name')->nullable();
    $table->string('unit')->nullable();
    $table->integer('project_id')->nullable();
    $table->decimal('price')->nullable();
    $table->decimal('supplier_price')->nullable();
});

Schema::create('inventory_history', function (Blueprint $table): void {
    $table->increments('history_id');
    $table->integer('inventory_id')->nullable();
    $table->integer('item_id')->nullable();
    $table->integer('warehouse_id')->nullable();
    $table->integer('old_qty')->nullable();
    $table->integer('new_qty')->nullable();
    $table->string('change_type')->nullable();
    $table->string('batch_no')->nullable();
    $table->dateTime('changed_at')->nullable();
});

Schema::create('lot', function (Blueprint $table): void {
    $table->increments('lot_id');
    $table->string('lot_name')->nullable();
    $table->integer('project_id')->nullable();
    $table->string('contract_no')->nullable();
});

Schema::create('keystage', function (Blueprint $table): void {
    $table->increments('keystage_id');
    $table->integer('keystage_num')->nullable();
    $table->integer('lot_id')->nullable();
    $table->string('description')->nullable();
});

Schema::create('package', function (Blueprint $table): void {
    $table->increments('package_id');
    $table->integer('package_num')->nullable();
    $table->integer('keystage_id')->nullable();
    $table->integer('lot_id')->nullable();
    $table->decimal('length')->nullable();
    $table->decimal('width')->nullable();
    $table->decimal('height')->nullable();
});

Schema::create('package_status', function (Blueprint $table): void {
    $table->increments('package_status_id');
    $table->integer('delivery_id')->nullable();
    $table->integer('package_id')->nullable();
    $table->string('status')->nullable();
    $table->string('remarks')->nullable();
    $table->dateTime('delivered_at')->nullable();
    $table->integer('delivered_by')->nullable();
    $table->string('receiver_name')->nullable();
    $table->decimal('latitude')->nullable();
    $table->decimal('longitude')->nullable();
    $table->integer('accuracy')->nullable();
});

Schema::create('package_content', function (Blueprint $table): void {
    $table->integer('package_id')->nullable();
    $table->integer('item_id')->nullable();
    $table->integer('qty')->nullable();
    $table->string('qty_teachers_manual')->nullable();
});

Schema::create('billing_grouped', function (Blueprint $table): void {
    $table->increments('id');
    $table->string('dr_no', 100);
    $table->dateTime('created_at')->nullable();
    $table->integer('group_id')->nullable();
});

Schema::create('grouping', function (Blueprint $table): void {
    $table->increments('group_id');
    $table->string('status')->nullable();
    $table->dateTime('created_at')->nullable();
    $table->string('group_name')->nullable();
    $table->date('paid_at')->nullable();
});

Schema::create('items', function (Blueprint $table): void {
    $table->increments('id');
    $table->string('item_id')->nullable();
    $table->string('code_prefix')->nullable();
    $table->string('item_name')->nullable();
    $table->string('description')->nullable();
    $table->integer('delivery_address_id')->nullable();
    $table->integer('lot_id')->nullable();
    $table->integer('keystage_id')->nullable();
    $table->string('project_id')->nullable();
    $table->string('unit')->nullable();
    $table->decimal('price')->nullable();
    $table->decimal('supplier_price')->nullable();
    $table->integer('active')->nullable();
    $table->dateTime('created_at')->nullable();
    $table->dateTime('updated_at')->nullable();
});
