<?php

namespace App\Domain\PonyAI\Models;

use App\Domain\Product\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PonyImageEmbedding extends Model
{
    protected $table = 'pony_image_embeddings';

    protected $fillable = [
        'product_image_id',
        'embedding',
        'caption',
        'model_version',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function productImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }
}
