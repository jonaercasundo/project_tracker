<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PinterestTrend extends Model
{
    protected $table = 'pinterest_trends';

    protected $fillable = [
        'keyword',
        'title',
        'image',
        'link',
        'author',
        'score',
        'scraped_at',
    ];
}