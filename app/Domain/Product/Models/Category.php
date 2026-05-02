<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'slug',
        'description',
        'icon_url',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $appends = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute($value): string
    {
        $locale = app()->getLocale();
        $nameAr = $this->attributes['name_ar'] ?? null;
        $nameEn = $this->attributes['name_en'] ?? null;
        $fallback = is_string($value) ? $value : '';

        return $locale === 'ar'
            ? ($nameAr ?: $nameEn ?: $fallback)
            : ($nameEn ?: $nameAr ?: $fallback);
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            $category->name = $category->name_en ?: $category->name_ar ?: $category->getRawOriginal('name') ?: '';

            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name_en ?: $category->name_ar ?: $category->name);
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
