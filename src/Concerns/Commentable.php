<?php

namespace XLaravel\Commentable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;
use XLaravel\Commentable\Contracts\CanComment;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Support\CommentableConfig;

/**
 * @mixin Model
 */
trait Commentable
{
    public static function bootCommentable(): void
    {
        static::deleted(function (Model $model) {
            /** @var Model&self $model */
            $model->forgetCommentsWhenGone();
        });
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(CommentableConfig::commentModel(), 'commentable');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function rootComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function addComment(string $body, (Model&CanComment)|null $commenter = null): Comment
    {
        return $this->comments()->create([
            'body' => $body,
            'commenter_id' => $commenter?->getKey(),
            'commenter_type' => $commenter?->getMorphClass(),
        ]);
    }

    public function addReply(Comment $parent, string $body, (Model&CanComment)|null $commenter = null): Comment
    {
        if (! $parent->isOn($this)) {
            throw new InvalidArgumentException('Parent comment does not belong to this commentable.');
        }

        return $this->comments()->create([
            'body' => $body,
            'parent_id' => $parent->getKey(),
            'commenter_id' => $commenter?->getKey(),
            'commenter_type' => $commenter?->getMorphClass(),
        ]);
    }

    protected function forgetCommentsWhenGone(): void
    {
        if ($this->exists) {
            return;
        }

        $this->comments()->forceDelete();
    }
}
