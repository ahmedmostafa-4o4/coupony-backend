<?php

namespace App\Domain\Product\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    use HasFactory;

    protected $table = 'product_views';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'ip_address',
        'user_agent',
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
        static::creating(function (ProductView $view) {
            if (blank($view->id)) {
                $view->id = (string) \Illuminate\Support\Str::uuid();
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
