<?php

namespace App\Domain\Notification\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationBroadcast extends Model
{
    use HasFactory;

    protected $table = 'notification_broadcasts';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'channels',
        'target_roles',
        'target_user_ids',
        'status',
        'total_sent',
        'total_failed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'target_roles' => 'array',
            'target_user_ids' => 'array',
            'total_sent' => 'integer',
            'total_failed' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
