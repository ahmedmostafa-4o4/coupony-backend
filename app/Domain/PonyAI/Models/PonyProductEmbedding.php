<?php

namespace App\Domain\PonyAI\Models;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PonyProductEmbedding extends Model
{
    protected $table = 'pony_product_embeddings';

    protected $primaryKey = 'product_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'text_embedding',
        'image_embedding',
        'source_revision_no',
        'model_version',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'text_embedding' => 'array',
            'image_embedding' => 'array',
            'source_revision_no' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
