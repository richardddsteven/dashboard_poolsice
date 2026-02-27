<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IceType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'weight',
        'price',
        'is_active'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function getActiveTypes()
    {
        return static::where('is_active', true)->get();
    }
}
