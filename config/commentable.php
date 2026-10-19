<?php

use XLaravel\Commentable\Models\Comment;

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | connection  Database connection of the comment table. Null uses the
    |             default connection.
    | table       Name of the comment table.
    | model       Eloquent model of the table. It must extend the package's
    |             own model: XLaravel\Commentable\Models\Comment.
    |
    */

    'connection' => null,

    'comment' => [
        'table' => 'comments',
        'model' => Comment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Comments whose commented model or commenter no longer exists are left
    | behind by deletes that skip model events, such as bulk queries and
    | database cascades. The package schedules model:prune to remove them,
    | together with their replies. Anonymous comments are kept.
    |
    | enabled   Schedule the pruning.
    | schedule  When the pruning runs, written in one of two ways:
    |           A cron expression:
    |             "30 3 * * *"        every day at 03:30
    |
    |           The name of a Laravel schedule frequency method, with its
    |           arguments after a colon, separated by commas:
    |             "daily"             every day at 00:00
    |             "dailyAt:03:30"     every day at 03:30
    |             "twiceDaily:1,13"   every day at 01:00 and 13:00
    |             "weeklyOn:1,04:00"  every Monday at 04:00
    | chunk     Rows deleted by each query.
    |
    */

    'prune' => [
        'enabled' => true,
        'schedule' => 'daily',
        'chunk' => 1000,
    ],

];
