<?php

namespace App\Domain\Banner\Models;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TravelBanner extends Model
{
    use HasFactory;

    protected $table = 'travel_banners';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'image_url',
        'cta_text',
        'save_percent',
        'priority',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TravelBanner $banner) {
            if (blank($banner->id)) {
                $banner->id = (string) Str::uuid();
            }

            $banner->priority ??= 0;
            $banner->is_active ??= true;
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
