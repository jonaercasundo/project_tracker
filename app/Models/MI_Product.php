<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MI_Product extends Model
{
    use HasFactory;

    // Explicitly declare the table name 
    protected $table = 'mi_products';

    // Explicitly declare the primary key (based on your earlier schema)
    protected $primaryKey = 'product_id';

    // Allow these fields to be mass-assigned via your form request
    protected $fillable = [
        // General Info
        'item_name',
        'category',
        'collection',
        'type_of_sample',
        'classification',
        'designed_by',
        
        // Attributes & Dimensions
        'materials',
        'type',
        'color',
        'product_height',
        'product_width',
        'product_length',
        'product_depth',
        
        // Packaging & Cost
        'carton_height',
        'carton_width',
        'carton_length',
        'carton_depth',
        'purchase_cost',
        
        // File / Media (This will store the path/filename in the DB)
        'product_file', 
    ];
}