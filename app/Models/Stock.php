<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'date',
        'stock_5kg',
        'stock_20kg',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
