<?php

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Vehicle\Database\Factories\VehicleTypeFactory;

class VehicleType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'price_per_kilo',
        'image',
    ];

    // protected static function newFactory(): VehicleTypeFactory
    // {
    //     // return VehicleTypeFactory::new();
    // }
}