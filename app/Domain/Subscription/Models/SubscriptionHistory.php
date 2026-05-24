<?php

namespace App\Domain\Subscription\Models;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\HistoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionHistory extends Model
{
    protected $table = 'subscription_history';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'plan_id',
        'billing_cycle',
        'amount',
        'payment_method',
        'status',
        'period_start',
        'period_end',
        'payment_session_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => HistoryStatus::class,
            'billing_cycle' => BillingCycle::class,
            'amount' => 'decimal:2',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
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
}
