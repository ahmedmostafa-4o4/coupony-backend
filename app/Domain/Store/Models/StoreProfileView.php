<?php

namespace App\Domain\Store\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreProfileView extends Model
{
    protected $table = 'store_profile_views';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'user_id',
        'ip_address',
    ];

    protected static function booted(): void
    {
        static::creating(function (StoreProfileView $view) {
            if (blank($view->id)) {
                $view->id = (string) Str::uuid();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
