<?php

namespace App\Domain\Subscription\Models;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'plan_id',
        'status',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'grace_period_end',
        'degraded_period_end',
        'trial_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_period_end' => 'datetime',
            'degraded_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(SubscriptionAuditLog::class);
    }
}
