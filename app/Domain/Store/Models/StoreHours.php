<?php

namespace App\Domain\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreHours extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\StoreHoursFactory::new();
    }

    protected $fillable = [
        'store_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
