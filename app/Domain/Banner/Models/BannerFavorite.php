<?php

namespace App\Domain\Banner\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BannerFavorite extends Model
{
    use HasFactory;

    protected $table = 'banner_favorites';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'banner_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BannerFavorite $favorite) {
            if (blank($favorite->id)) {
                $favorite->id = (string) Str::uuid();
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
