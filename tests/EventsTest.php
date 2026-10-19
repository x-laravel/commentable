<?php

namespace XLaravel\Commentable\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use XLaravel\Commentable\Events\CommentDeleted;
use XLaravel\Commentable\Events\Commented;
use XLaravel\Commentable\Events\Replied;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Tests\Fixtures\Post;
use XLaravel\Commentable\Tests\Fixtures\User;

class EventsTest extends TestCase
{
    #[Test]
    public function a_root_comment_dispatches_commented(): void
    {
        Event::fake([Commented::class, Replied::class]);

        $comment = User::create(['name' => 'Ali'])->comment(Post::create(['title' => 'Test Post']), 'Hello');

        Event::assertDispatched(Commented::class, fn (Commented $event) => $event->comment->is($comment));
        Event::assertNotDispatched(Replied::class);
    }

    #[Test]
    public function a_reply_dispatches_replied(): void
    {
        $user = User::create(['name' => 'Ali']);
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Hello');

        Event::fake([Commented::class, Replied::class]);

        $reply = $user->reply($post, $comment, 'Hi');

        Event::assertDispatched(Replied::class, fn (Replied $event) => $event->comment->is($reply));
        Event::assertNotDispatched(Commented::class);
    }

    #[Test]
    public function deleting_a_comment_dispatches_comment_deleted(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $trashed = $post->addComment('Trashed');
        $forced = $post->addComment('Forced');

        Event::fake([CommentDeleted::class]);

        $trashed->delete();
        $forced->forceDelete();

        Event::assertDispatchedTimes(CommentDeleted::class, 2);
    }

    #[Test]
    public function a_rolled_back_comment_is_not_announced(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $heard = [];
        Event::listen(Commented::class, function (Commented $event) use (&$heard) {
            $heard[] = $event->comment->body;
        });

        DB::transaction(fn () => $post->addComment('Kept'));

        try {
            DB::transaction(function () use ($post) {
                $post->addComment('Rolled back');

                throw new \RuntimeException();
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(['Kept'], $heard);
    }

    #[Test]
    public function pruning_dispatches_no_comment_deleted(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $post->addComment('Hello');
        DB::table('posts')->delete();

        Event::fake([CommentDeleted::class]);

        Artisan::call('model:prune', ['--model' => [Comment::class]]);

        $this->assertSame(0, Comment::withTrashed()->count());
        Event::assertNotDispatched(CommentDeleted::class);
    }
}
