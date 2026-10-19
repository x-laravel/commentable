<?php

namespace XLaravel\Commentable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use XLaravel\Commentable\Events\CommentDeleted;
use XLaravel\Commentable\Events\Commented;
use XLaravel\Commentable\Events\Replied;
use XLaravel\Commentable\Support\CommentableConfig;
use XLaravel\Commentable\Support\MissingMorphTargets;

/**
 * @property string $id
 * @property string $commentable_type
 * @property int|string $commentable_id
 * @property string|null $commenter_type
 * @property int|string|null $commenter_id
 * @property string|null $parent_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Comment extends Model
{
    use HasUlids;
    use MassPrunable;
    use SoftDeletes;

    protected $fillable = [
        'body',
        'parent_id',
        'commenter_id',
        'commenter_type',
        'commentable_id',
        'commentable_type',
    ];

    protected static function booted(): void
    {
        static::created(function (Comment $comment) {
            Event::dispatch($comment->isReply() ? new Replied($comment) : new Commented($comment));
        });

        static::deleted(function (Comment $comment) {
            Event::dispatch(new CommentDeleted($comment));
        });
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where(fn (Builder $query) => MissingMorphTargets::where($query, 'commentable'))
            ->orWhere(fn (Builder $query) => MissingMorphTargets::where($query, 'commenter'));
    }

    public function getTable(): string
    {
        return CommentableConfig::commentTable();
    }

    public function getConnectionName(): ?string
    {
        return CommentableConfig::connection() ?? parent::getConnectionName();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commenter(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CommentableConfig::commentModel(), 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(CommentableConfig::commentModel(), 'parent_id');
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function isOn(Model $commentable): bool
    {
        return $this->commentable_id === $commentable->getKey() && $this->commentable_type === $commentable->getMorphClass();
    }
}
