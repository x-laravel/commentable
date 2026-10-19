<?php

namespace XLaravel\Commentable\Tests;

use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use XLaravel\Commentable\Models\Comment;
use XLaravel\Commentable\Tests\Fixtures\Post;
use XLaravel\Commentable\Tests\Fixtures\User;

class PruningTest extends TestCase
{
    public function test_comments_of_models_that_no_longer_exist_are_pruned_with_their_replies(): void
    {
        $gone = Post::query()->create(['title' => 'Rock']);
        $kept = Post::query()->create(['title' => 'Jazz']);
        $trashed = Post::query()->create(['title' => 'Blues']);
        $gone->addReply($gone->addComment('First'), 'Reply');
        $kept->addComment('Second');
        $trashed->addComment('Third');
        DB::table('posts')->where('id', $gone->id)->delete();
        $trashed->delete();

        $this->prune();

        $this->assertSame(0, Comment::query()->whereMorphedTo('commentable', $gone)->count());
        $this->assertSame(1, $kept->comments()->count());
        $this->assertSame(1, $trashed->comments()->count());
    }

    public function test_comments_of_commenters_that_no_longer_exist_are_pruned_with_their_replies(): void
    {
        $gone = User::query()->create(['name' => 'Ada']);
        $kept = User::query()->create(['name' => 'Grace']);
        $post = Post::query()->create(['title' => 'Rock']);
        $comment = $gone->comment($post, 'First');
        $kept->reply($post, $comment, 'Reply');
        $kept->comment($post, 'Second');
        $post->addComment('Anonymous');
        DB::table('users')->where('id', $gone->id)->delete();

        $this->prune();

        $this->assertSame(['Second', 'Anonymous'], $post->comments()->orderBy('id')->pluck('body')->all());
    }

    public function test_soft_deleted_comments_are_pruned_too(): void
    {
        $post = Post::query()->create(['title' => 'Rock']);
        $post->addComment('First')->delete();
        DB::table('posts')->delete();

        $this->prune();

        $this->assertSame(0, Comment::withTrashed()->count());
    }

    public function test_unknown_types_are_left_alone(): void
    {
        Comment::query()->create(['body' => 'First', 'commentable_type' => 'missing', 'commentable_id' => 1]);

        $this->prune();

        $this->assertSame(1, Comment::query()->count());
    }

    public function test_pruning_is_scheduled_daily_in_chunks_of_a_thousand(): void
    {
        $event = $this->scheduledPruning();

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertStringContainsString('--chunk=1000', (string) $event->command);
    }

    public function test_the_chunk_size_can_be_configured(): void
    {
        config(['commentable.prune.chunk' => 250]);

        $this->assertStringContainsString('--chunk=250', (string) $this->scheduledPruning()?->command);
    }

    public function test_the_schedule_accepts_a_cron_expression(): void
    {
        config(['commentable.prune.schedule' => '30 3 * * *']);

        $this->assertSame('30 3 * * *', $this->scheduledPruning()?->expression);
    }

    public function test_the_schedule_accepts_a_frequency_with_arguments(): void
    {
        config(['commentable.prune.schedule' => 'weeklyOn:1,04:00']);

        $this->assertSame('0 4 * * 1', $this->scheduledPruning()?->expression);
    }

    public function test_an_unknown_frequency_is_refused(): void
    {
        config(['commentable.prune.schedule' => 'sometimes']);

        $this->expectException(InvalidArgumentException::class);

        $this->scheduledPruning();
    }

    public function test_the_schedule_can_be_turned_off(): void
    {
        config(['commentable.prune.enabled' => false]);

        $this->assertNull($this->scheduledPruning());
    }

    protected function prune(): void
    {
        $this->assertSame(0, Artisan::call('model:prune', ['--model' => [Comment::class]]));
    }

    protected function scheduledPruning(): ?ScheduledEvent
    {
        $this->app?->forgetInstance(Schedule::class);

        $events = $this->app?->make(Schedule::class)->events() ?? [];

        foreach ($events as $event) {
            if (str_contains((string) $event->command, 'model:prune') && str_contains((string) $event->command, 'Comment')) {
                return $event;
            }
        }

        return null;
    }
}
