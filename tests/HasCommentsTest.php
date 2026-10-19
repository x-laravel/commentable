<?php

namespace XLaravel\Commentable\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Tests\Fixtures\Post;
use XLaravel\Commentable\Tests\Fixtures\User;

class HasCommentsTest extends TestCase
{
    #[Test]
    public function it_can_add_a_comment_without_commenter(): void
    {
        $post = Post::create(['title' => 'Test Post']);

        $comment = $post->addComment('Great post!');

        $this->assertEquals('Great post!', $comment->body);
        $this->assertNull($comment->commenter_id);
        $this->assertNull($comment->commenter_type);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Great post!']);
    }

    #[Test]
    public function it_can_add_a_comment_with_commenter(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $user = User::create(['name' => 'Ali']);

        $comment = $post->addComment('Great post!', $user);

        $this->assertEquals($user->id, $comment->commenter_id);
        $this->assertEquals($user->getMorphClass(), $comment->commenter_type);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'commenter_id' => $user->id,
            'commenter_type' => $user->getMorphClass(),
        ]);
    }

    #[Test]
    public function it_can_add_a_reply_to_a_comment(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Great post!');

        $reply = $post->addReply($comment, 'I agree!');

        $this->assertEquals($comment->id, $reply->parent_id);
        $this->assertEquals('I agree!', $reply->body);
        $this->assertDatabaseHas('comments', ['id' => $reply->id, 'parent_id' => $comment->id]);
    }

    #[Test]
    public function it_throws_when_reply_parent_does_not_belong_to_commentable(): void
    {
        $post = Post::create(['title' => 'Post 1']);
        $otherPost = Post::create(['title' => 'Post 2']);
        $comment = $otherPost->addComment('This belongs to another post');

        $this->expectException(InvalidArgumentException::class);

        $post->addReply($comment, 'This should fail');
    }

    #[Test]
    public function root_comments_returns_only_top_level_comments(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Root comment');
        $post->addReply($comment, 'A reply');

        $roots = $post->rootComments()->get();

        $this->assertCount(1, $roots);
        $this->assertTrue($comment->is($roots->first()));
    }

    #[Test]
    public function comments_returns_all_comments_including_replies(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Root comment');
        $post->addReply($comment, 'A reply');

        $this->assertCount(2, $post->comments()->get());
    }

    #[Test]
    public function deleting_the_model_deletes_its_comments(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $other = Post::create(['title' => 'Other Post']);
        $post->addReply($post->addComment('Root comment'), 'A reply');
        $post->addComment('Trashed comment')->delete();
        $other->addComment('Kept comment');

        $post->forceDelete();

        $this->assertSame(['Kept comment'], Comment::withTrashed()->pluck('body')->all());
    }

    #[Test]
    public function soft_deleting_the_model_keeps_its_comments(): void
    {
        $post = Post::create(['title' => 'Test Post']);
        $post->addComment('Root comment');

        $post->delete();

        $this->assertSame(1, Comment::query()->count());
    }
}
