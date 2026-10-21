<?php

namespace Modules\Trip\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Driver;
use App\Models\User;
// use Modules\Trip\Database\Factories\TripFactory;

class Trip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $fillable = [
        'client_id',
        'driver_id',
        'car_type_id',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'distance',
        'price',
        'status',
        'requested_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'distance' => 'decimal:2',
        'price' => 'decimal:2',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
    public function carType()
    {
        return $this->belongsTo(
            \Modules\Vehicle\Models\VehicleType::class,
            'car_type_id'
        );
    }
    public function statusHistory(): HasMany
{
    return $this->hasMany(TripStatusHistory::class)
        ->orderBy('changed_at', 'asc');
}
}