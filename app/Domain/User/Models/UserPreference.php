<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\UserPreferenceFactory::new();
    }

    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id',
        'email_marketing',
        'email_order_updates',
        'sms_notifications',
        'push_notifications',
        'preferred_currency',
        'preferred_language',
        'preferred_payment_method',
        'enable_personalized_recommendations',
        'browsing_history_tracking',
        'show_profile_publicly',
        'allow_data_sharing_for_analytics',
    ];

    protected $casts = [
        'email_marketing' => 'boolean',
        'email_order_updates' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'enable_personalized_recommendations' => 'boolean',
        'browsing_history_tracking' => 'boolean',
        'show_profile_publicly' => 'boolean',
        'allow_data_sharing_for_analytics' => 'boolean',
    ];

    /**
     * Relationship: Preference belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
