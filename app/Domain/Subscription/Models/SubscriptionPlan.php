<?php

namespace App\Domain\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPlanFactory> */
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\SubscriptionPlanFactory::new();
    }

    protected $table = 'subscription_plans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_products',
        'max_employees',
        'max_branches',
        'max_ai_messages_per_day',
        'features',
        'grace_period_days',
        'degraded_period_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_products' => 'integer',
            'max_employees' => 'integer',
            'max_branches' => 'integer',
            'max_ai_messages_per_day' => 'integer',
            'features' => 'array',
            'grace_period_days' => 'integer',
            'degraded_period_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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

    /**
     * Get the price for the given billing cycle.
     */
    public function getPriceForCycle(string $billingCycle): float
    {
        return match ($billingCycle) {
            'yearly' => (float) $this->price_yearly,
            default => (float) $this->price_monthly,
        };
    }
}
