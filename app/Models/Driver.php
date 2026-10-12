<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

        ];
    }
}