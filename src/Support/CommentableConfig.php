<?php

namespace XLaravel\Commentable\Support;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use XLaravel\Commentable\Models\Comment;

/**
 * @internal
 */
final class CommentableConfig
{
    /**
     * @return class-string<Comment>
     */
    public static function commentModel(): string
    {
        $model = Config::get('commentable.comment.model') ?? Comment::class;

        if (! is_string($model) || ! is_a($model, Comment::class, true)) {
            throw new InvalidArgumentException('Set commentable.comment.model to a model that extends ' . Comment::class . '.');
        }

        return $model;
    }

    public static function commentTable(): string
    {
        return self::string('comment.table') ?: 'comments';
    }

    public static function connection(): ?string
    {
        return self::string('connection') ?: null;
    }

    protected static function string(string $key): string
    {
        $value = Config::get("commentable.{$key}");

        return is_string($value) ? trim($value) : '';
    }
}
