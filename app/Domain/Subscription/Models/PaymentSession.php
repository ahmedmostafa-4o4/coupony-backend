<?php

namespace App\Domain\Subscription\Models;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentSession extends Model
{
    protected $table = 'payment_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'plan_id',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'paymob_order_id',
        'paymob_transaction_id',
        'payment_url',
        'expires_at',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentSessionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
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

    /**
     * Check if the session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the session is still pending (not expired and status is pending).
     */
    public function isPending(): bool
    {
        return $this->status === PaymentSessionStatus::PENDING && ! $this->isExpired();
    }

    /**
     * Scope to filter pending, non-expired sessions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status', PaymentSessionStatus::PENDING)
            ->where('expires_at', '>', now());
    }
}
