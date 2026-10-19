<?php

namespace XLaravel\Commentable\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use XLaravel\Commentable\Models\Comment;

final readonly class Commented implements ShouldDispatchAfterCommit
{
    public function __construct(
        public Comment $comment,
    ) {
    }
}
