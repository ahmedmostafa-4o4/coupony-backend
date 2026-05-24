<?php

namespace App\Domain\Subscription\Models;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionAuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'subscription_audit_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'subscription_id',
        'event_type',
        'previous_status',
        'new_status',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
