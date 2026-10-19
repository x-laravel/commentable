<?php

namespace XLaravel\Commentable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;
use LogicException;
use XLaravel\Commentable\Contracts\CanComment;
use XLaravel\Commentable\Contracts\HasComments;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Support\CommentableConfig;

/**
 * @mixin Model
 */
trait Commenter
{
    public static function bootCommenter(): void
    {
        static::deleted(function (Model $model) {
            /** @var Model&self $model */
            $model->forgetPostedCommentsWhenGone();
        });
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function authoredComments(): MorphMany
    {
        return $this->morphMany(CommentableConfig::commentModel(), 'commenter');
    }

    public function comment(Model&HasComments $commentable, string $body): Comment
    {
        $this->ensureCanComment();

        return $this->authoredComments()->create([
            'body' => $body,
            'commentable_id' => $commentable->getKey(),
            'commentable_type' => $commentable->getMorphClass(),
        ]);
    }

    public function reply(Model&HasComments $commentable, Comment $parent, string $body): Comment
    {
        $this->ensureCanComment();

        if (! $parent->isOn($commentable)) {
            throw new InvalidArgumentException('Parent comment does not belong to this commentable.');
        }

        return $this->authoredComments()->create([
            'body' => $body,
            'parent_id' => $parent->getKey(),
            'commentable_id' => $commentable->getKey(),
            'commentable_type' => $commentable->getMorphClass(),
        ]);
    }

    protected function ensureCanComment(): void
    {
        if (! $this instanceof CanComment) {
            throw new LogicException(sprintf('Implement %s on [%s].', CanComment::class, static::class));
        }
    }

    protected function forgetPostedCommentsWhenGone(): void
    {
        if ($this->exists) {
            return;
        }

        $this->authoredComments()->forceDelete();
    }
}
