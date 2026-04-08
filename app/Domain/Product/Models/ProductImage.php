<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'image_url',
        'sort_order',
        'is_primary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
