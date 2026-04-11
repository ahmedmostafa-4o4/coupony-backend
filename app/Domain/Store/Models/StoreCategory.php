<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'name',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeInActive($query)
    {
        return $query->where('is_active', false);
    }


    public function stores()
    {
        return $this->belongsToMany(
            Store::class ,
            'store_store_category'
        );
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar'
            ? ($this->name_ar ?: $this->name_en)
            : ($this->name_en ?: $this->name_ar);
    }

    protected static function booted(): void
    {
        static::saving(function (StoreCategory $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name_en ?: $category->name_ar);
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\StoreCategoryFactory::new ();
    }
}
