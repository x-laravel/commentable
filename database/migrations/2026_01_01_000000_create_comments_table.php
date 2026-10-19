<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XLaravel\Commentable\Support\CommentableConfig;

return new class extends Migration
{
    public function up(): void
    {
        $comments = CommentableConfig::commentTable();

        Schema::connection($this->getConnection())->create($comments, function (Blueprint $table) use ($comments) {
            $table->ulid('id')->primary();
            $table->morphs('commentable');
            $table->nullableMorphs('commenter');
            $table->foreignUlid('parent_id')->nullable()->constrained($comments)->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(CommentableConfig::commentTable());
    }

    public function getConnection(): ?string
    {
        return CommentableConfig::connection();
    }
};
