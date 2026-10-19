<?php

namespace XLaravel\Commentable\Tests;

use PHPUnit\Framework\Attributes\Test;
use XLaravel\Commentable\Tests\Fixtures\Post;
use XLaravel\Commentable\Tests\Fixtures\User;

class CommentTest extends TestCase
{
    #[Test]
    public function it_knows_if_it_is_a_reply(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Root comment');
        $reply = $post->addReply($comment, 'A reply');

        $this->assertFalse($comment->isReply());
        $this->assertTrue($reply->isReply());
    }

    #[Test]
    public function it_returns_its_parent(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Root comment');
        $reply = $post->addReply($comment, 'A reply');

        $this->assertTrue($comment->is($reply->parent));
    }

    #[Test]
    public function it_returns_its_replies(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Root comment');
        $post->addReply($comment, 'Reply 1');
        $post->addReply($comment, 'Reply 2');

        $this->assertCount(2, $comment->replies()->get());
    }

    #[Test]
    public function it_returns_its_commentable(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Hello');

        $this->assertTrue($post->is($comment->commentable));
        $this->assertInstanceOf(Post::class, $comment->commentable);
    }

    #[Test]
    public function it_returns_its_commenter(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $user = User::create(['name' => 'Ali']);

        $comment = $post->addComment('Hello', $user);

        $this->assertTrue($user->is($comment->commenter));
        $this->assertInstanceOf(User::class, $comment->commenter);
    }

    #[Test]
    public function it_can_be_soft_deleted(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('To be deleted');

        $comment->delete();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertCount(0, $post->comments()->get());
    }
}
