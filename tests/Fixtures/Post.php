<?php

namespace XLaravel\Commentable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use XLaravel\Commentable\Concerns\Commentable;
use XLaravel\Commentable\Contracts\HasComments;

/**
 * @property int $id
 * @property string $title
 */
class Post extends Model implements HasComments
{
    use Commentable;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
