<?php

namespace XLaravel\Commentable\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use XLaravel\Commentable\Models\Comment;

final readonly class CommentDeleted implements ShouldDispatchAfterCommit
{
    public function __construct(
        public Comment $comment,
    ) {
    }
}
