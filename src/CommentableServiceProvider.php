<?php

namespace XLaravel\Commentable;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use XLaravel\Commentable\Support\CommentableConfig;

class CommentableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/commentable.php', 'commentable');
    }

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, $this->schedulePruning(...));

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/commentable.php' => $this->app->configPath('commentable.php'),
        ], 'commentable-config');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'commentable-migrations');
    }

    private function schedulePruning(Schedule $schedule): void
    {
        if (! Config::boolean('commentable.prune.enabled', true)) {
            return;
        }

        $frequency = Config::string('commentable.prune.schedule', 'daily');

        $event = $schedule->command('model:prune', [
            '--model' => [CommentableConfig::commentModel()],
            '--chunk' => Config::integer('commentable.prune.chunk', 1000),
        ]);

        if (str_contains($frequency, ' ')) {
            $event->cron($frequency);

            return;
        }

        [$method, $arguments] = array_pad(explode(':', $frequency, 2), 2, '');

        if ($method === '' || ! method_exists($event, $method)) {
            throw new InvalidArgumentException("[{$frequency}] is not a schedule frequency.");
        }

        $event->{$method}(...($arguments === '' ? [] : explode(',', $arguments)));
    }
}
