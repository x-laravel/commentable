<?php

namespace XLaravel\Commentable\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Tests\Fixtures\Post;
use XLaravel\Commentable\Tests\Fixtures\User;

class CommenterTest extends TestCase
{
    #[Test]
    public function it_can_comment_on_a_model(): void
    {
        $user = User::create(['name' => 'Ali']);
        $post = Post::create(['title' => 'Test Post']);

        $comment = $user->comment($post, 'Nice post!');

        $this->assertEquals('Nice post!', $comment->body);
        $this->assertEquals($user->id, $comment->commenter_id);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'commenter_id' => $user->id]);
    }

    #[Test]
    public function it_can_reply_to_a_comment(): void
    {
        $user = User::create(['name' => 'Ali']);
        $post = Post::create(['title' => 'Test Post']);
        $comment = $post->addComment('Original comment');

        $reply = $user->reply($post, $comment, 'My reply');

        $this->assertEquals($comment->id, $reply->parent_id);
        $this->assertEquals($user->id, $reply->commenter_id);
        $this->assertTrue($reply->isOn($post));
    }

    #[Test]
    public function it_throws_when_reply_parent_does_not_belong_to_commentable(): void
    {
        $user = User::create(['name' => 'Ali']);
        $post = Post::create(['title' => 'Post 1']);
        $comment = Post::create(['title' => 'Post 2'])->addComment('This belongs to another post');

        $this->expectException(InvalidArgumentException::class);

        $user->reply($post, $comment, 'This should fail');
    }

    #[Test]
    public function it_returns_all_comments_made_by_the_user(): void
    {
        $user = User::create(['name' => 'Ali']);
        $post1 = Post::create(['title' => 'Post 1']);
        $post2 = Post::create(['title' => 'Post 2']);

        $user->comment($post1, 'Comment 1');
        $user->comment($post2, 'Comment 2');

        $this->assertCount(2, $user->authoredComments()->get());
    }

    #[Test]
    public function deleting_the_user_deletes_their_comments_with_the_replies(): void
    {
        $user = User::create(['name' => 'Ali']);
        $other = User::create(['name' => 'Ayşe']);
        $post = Post::create(['title' => 'Test Post']);
        $other->reply($post, $user->comment($post, 'Root comment'), 'A reply');
        $other->comment($post, 'Kept comment');

        $user->forceDelete();

        $this->assertSame(['Kept comment'], Comment::withTrashed()->pluck('body')->all());
    }

    #[Test]
    public function soft_deleting_the_user_keeps_their_comments(): void
    {
        $user = User::create(['name' => 'Ali']);
        $user->comment(Post::create(['title' => 'Test Post']), 'Root comment');

        $user->delete();

        $this->assertSame(1, Comment::query()->count());
    }

    #[Test]
    public function a_model_can_both_post_and_receive_comments(): void
    {
        $author = User::create(['name' => 'Ali']);
        $profile = User::create(['name' => 'Ayşe']);

        $author->comment($profile, 'Hello');

        $this->assertSame(['Hello'], $author->authoredComments()->pluck('body')->all());
        $this->assertSame(['Hello'], $profile->comments()->pluck('body')->all());
        $this->assertSame(0, $author->comments()->count());
        $this->assertSame(0, $profile->authoredComments()->count());
    }
}
