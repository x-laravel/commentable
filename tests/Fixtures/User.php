<?php

namespace XLaravel\Commentable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use XLaravel\Commentable\Concerns\Commentable;
use XLaravel\Commentable\Concerns\Commenter;
use XLaravel\Commentable\Contracts\CanComment;
use XLaravel\Commentable\Contracts\HasComments;

/**
 * @property int $id
 * @property string $name
 */
class User extends Model implements CanComment, HasComments
{
    use Commentable;
    use Commenter;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
