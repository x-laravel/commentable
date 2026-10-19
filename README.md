# Commentable for Laravel

<p align="center">
<a href="https://github.com/X-Laravel/commentable/actions/workflows/tests.yml"><img src="https://github.com/X-Laravel/commentable/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
<a href="https://packagist.org/packages/x-laravel/commentable" rel="nofollow"><img src="https://img.shields.io/packagist/v/x-laravel/commentable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/x-laravel/commentable" rel="nofollow"><img src="https://img.shields.io/packagist/php-v/x-laravel/commentable" alt="PHP Version"></a>
<a href="https://packagist.org/packages/x-laravel/commentable" rel="nofollow"><img src="https://img.shields.io/packagist/dt/x-laravel/commentable" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/x-laravel/commentable" rel="nofollow"><img src="https://img.shields.io/packagist/l/x-laravel/commentable" alt="License"></a>
</p>

Polymorphic, infinitely nested comments for Eloquent. Any model can receive comments, any model can
post them, and commenters are optional, so anonymous comments work too. Replies are loaded only
when asked for.

## Requirements

| Package version | PHP | Laravel |
|-----------------|-----|---------|
| 1.x | 8.4+ | 13 |

## Installation

```bash
composer require x-laravel/commentable
php artisan vendor:publish --tag=commentable-migrations
php artisan migrate
```

Publish the config to change the defaults:

```bash
php artisan vendor:publish --tag=commentable-config
```

## Preparing the Models

Implement `CanComment` and use `Commenter` on the model that posts comments. Implement `HasComments`
and use `Commentable` on every model that can receive them; only those models are accepted:

```php
use XLaravel\Commentable\Concerns\Commentable;
use XLaravel\Commentable\Concerns\Commenter;
use XLaravel\Commentable\Contracts\CanComment;
use XLaravel\Commentable\Contracts\HasComments;

class User extends Authenticatable implements CanComment
{
    use Commenter;
}

class Post extends Model implements HasComments
{
    use Commentable;
}
```

The `commentable_id` and `commenter_id` columns follow Laravel's morph key type, so set
`Schema::defaultMorphKeyType('uuid')` or `'ulid'` before migrating if your models use them.

## Configuration

| Key | Default | Meaning |
|-----|---------|---------|
| `connection` | `null` | Connection of the comment table |
| `comment.table` | `'comments'` | Name of the comment table |
| `comment.model` | `Comment::class` | Comment model; must extend the package's |
| `prune.enabled` | `true` | Schedule the pruning of left-behind comments |
| `prune.schedule` | `'daily'` | A cron expression, or a frequency method with its arguments: `'dailyAt:03:30'`, `'twiceDaily:1,13'`, `'weeklyOn:1,04:00'` |
| `prune.chunk` | `1000` | Rows deleted by each pruning query |

## Commenting

```php
$post->addComment('Great post!');                  // anonymous
$post->addComment('Great post!', $user);
$user->comment($post, 'Great post!');

$post->addReply($comment, 'I agree!', $user);
$user->reply($post, $comment, 'I agree!');
```

Replies can be added to any comment or reply, with no depth limit. A parent comment that belongs to
another model is refused with `InvalidArgumentException`.

## Listing

```php
$post->rootComments()->withCount('replies')->with('commenter')->paginate(20);
$comment->replies()->withCount('replies')->with('commenter')->paginate(10);

$post->comments;         // every comment on the post, replies included
$user->authoredComments; // every comment the user posted

$comment->parent;
$comment->commentable;
$comment->commenter;
$comment->isReply();
$comment->isOn($post);
```

The relations load nothing extra by default; chain counts and relations as each screen needs them.

Comment keys are ULIDs, so they can appear in URLs without revealing how many comments exist.

## Deleting and Pruning

```php
$comment->delete();       // soft delete
$comment->restore();
$comment->forceDelete();  // deletes the replies with it through the foreign key
```

Soft-deleting a comment leaves its replies in place. Add a `deleting` listener in your application
if they should go with it.

When a commented model or a commenter is deleted, its comments are permanently deleted with it,
soft-deleted ones included, and the foreign key takes their replies along, whoever wrote them.
Soft-deleted models keep their comments until they are force deleted.

Deletes that skip model events, such as bulk queries, database cascades or raw SQL, leave comments
behind. The package schedules `model:prune`, daily by default, to remove comments whose commented
model or commenter no longer exists. Anonymous comments are kept. Run it by hand with:

```bash
php artisan model:prune --model="XLaravel\Commentable\Models\Comment"
```

Pruning skips types that no longer resolve to a model and models on another connection.

## Reacting to Comments

`XLaravel\Commentable\Events\Commented` is dispatched for a new comment and `Replied` for a new reply;
`$event->comment->parent` is the comment that was replied to. `CommentDeleted` is dispatched when a
comment is soft or force deleted. All three carry the `Comment` and are dispatched after the
surrounding transaction commits.

Comments removed together with their model or commenter, by the foreign key or by pruning dispatch
nothing.

## Testing

```bash
composer install
composer test
composer analyse
```

## License

Open source software licensed under the [MIT license](LICENSE).
