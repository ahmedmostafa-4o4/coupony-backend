<?php

namespace App\Domain\Product\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductComment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_VISIBLE = 'visible';

    public const STATUS_HIDDEN = 'hidden';

    protected $table = 'product_comments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'parent_id',
        'review_user_id',
        'rating',
        'body',
        'status',
        'hidden_at',
        'hidden_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'hidden_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductComment $comment) {
            if (blank($comment->id)) {
                $comment->id = (string) Str::uuid();
            }

            $comment->status ??= self::STATUS_VISIBLE;

            if ($comment->parent_id === null) {
                $comment->review_user_id ??= $comment->user_id;
            } else {
                $comment->review_user_id = null;
                $comment->rating = null;
            }
        });

        static::deleting(function (ProductComment $comment) {
            if (! $comment->isForceDeleting() && $comment->isTopLevelReview()) {
                $comment->updateQuietly(['review_user_id' => null]);
            }
        });

        static::saved(fn (ProductComment $comment) => $comment->refreshProductRating());
        static::deleted(fn (ProductComment $comment) => $comment->refreshProductRating());
        static::restored(fn (ProductComment $comment) => $comment->refreshProductRating());
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function visibleReplies()
    {
        return $this->replies()
            ->visible()
            ->with(['user.profile', 'visibleReplies'])
            ->withCount('likes');
    }

    public function likes()
    {
        return $this->hasMany(ProductCommentLike::class, 'comment_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VISIBLE);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isTopLevelReview(): bool
    {
        return $this->parent_id === null;
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_VISIBLE;
    }

    public function refreshProductRating(): void
    {
        if (! $this->product_id) {
            return;
        }

        $summary = self::query()
            ->where('product_id', $this->product_id)
            ->topLevel()
            ->visible()
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as rating_count, COALESCE(AVG(rating), 0) as rating_avg')
            ->first();

        Product::query()
            ->whereKey($this->product_id)
            ->update([
                'rating_avg' => round((float) ($summary?->rating_avg ?? 0), 2),
                'rating_count' => (int) ($summary?->rating_count ?? 0),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }
}
