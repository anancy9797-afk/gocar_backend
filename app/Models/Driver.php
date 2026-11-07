<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Driver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone', // <--- أضف الحقل هنا لتسمح بالتعبئة الجماعية
        'email',
        'image',
        'vehicle_number',
        'vehicle_iamge',
        'is_active',
        'car_type_id',
        'latitude',
'longitude',
'is_available',
    ];

    protected $hidden = [
        'password',
        
    ];

        /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
                    'is_active' => 'boolean',
    'is_available' => 'boolean',

        ];
    }
    public function carType()
{
    return $this->belongsTo(
        \Modules\Vehicle\Models\VehicleType::class,
        'car_type_id'
    );
}



public function wallet(): MorphOne
{
    return $this->morphOne(Wallet::class, 'walletable');
}







}
