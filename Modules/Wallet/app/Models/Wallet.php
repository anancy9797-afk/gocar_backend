<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function walletable(): MorphTo
    {
        return $this->morphTo();
    }
}