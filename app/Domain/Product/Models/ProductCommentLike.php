<?php

namespace App\Domain\Product\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCommentLike extends Model
{
    use HasFactory;

    protected $table = 'product_comment_likes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'comment_id',
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
        static::creating(function (ProductCommentLike $like) {
            if (blank($like->id)) {
                $like->id = (string) Str::uuid();
            }
        });
    }

    public function comment()
    {
        return $this->belongsTo(ProductComment::class, 'comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
