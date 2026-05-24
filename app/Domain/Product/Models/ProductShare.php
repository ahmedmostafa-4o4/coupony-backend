<?php

namespace App\Domain\Product\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductShare extends Model
{
    protected $table = 'product_shares';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'platform',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductShare $share) {
            if (blank($share->id)) {
                $share->id = (string) Str::uuid();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
