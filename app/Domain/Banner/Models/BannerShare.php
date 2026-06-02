<?php

namespace App\Domain\Banner\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BannerShare extends Model
{
    protected $table = 'banner_shares';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'banner_id',
        'user_id',
        'platform',
    ];

    protected static function booted(): void
    {
        static::creating(function (BannerShare $share) {
            if (blank($share->id)) {
                $share->id = (string) Str::uuid();
            }
        });
    }

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
