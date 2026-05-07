<?php

namespace App\Domain\Store\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreComment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_VISIBLE = 'visible';
    public const STATUS_HIDDEN = 'hidden';

    protected $table = 'store_comments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
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
        static::creating(function (StoreComment $comment) {
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

        static::deleting(function (StoreComment $comment) {
            if (! $comment->isForceDeleting() && $comment->isTopLevelReview()) {
                $comment->updateQuietly(['review_user_id' => null]);
            }
        });

        static::saved(fn(StoreComment $comment) => $comment->refreshStoreRating());
        static::deleted(fn(StoreComment $comment) => $comment->refreshStoreRating());
        static::restored(fn(StoreComment $comment) => $comment->refreshStoreRating());
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
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
        return $this->hasMany(StoreCommentLike::class, 'comment_id');
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

    public function refreshStoreRating(): void
    {
        if (! $this->store_id) {
            return;
        }

        $summary = self::query()
            ->where('store_id', $this->store_id)
            ->topLevel()
            ->visible()
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as rating_count, COALESCE(AVG(rating), 0) as rating_avg')
            ->first();

        Store::query()
            ->whereKey($this->store_id)
            ->update([
                'rating_avg' => round((float) ($summary?->rating_avg ?? 0), 2),
                'rating_count' => (int) ($summary?->rating_count ?? 0),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }
}
