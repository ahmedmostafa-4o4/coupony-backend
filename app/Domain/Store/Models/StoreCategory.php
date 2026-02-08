<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class StoreCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
            Store::class,
            'store_store_category'
        );
    }
}