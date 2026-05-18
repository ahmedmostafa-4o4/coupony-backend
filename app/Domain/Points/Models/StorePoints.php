<?php

namespace App\Domain\Points\Models;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePoints extends Model
{
    protected $table = 'store_points';

    protected $fillable = [
        'store_id',
        'current_balance',
        'lifetime_earned',
        'lifetime_spent',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'integer',
            'lifetime_earned' => 'integer',
            'lifetime_spent' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
