# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-19

### Added

- `Contracts\CanComment` and `Concerns\Commenter` for the model that posts comments,
  `Contracts\HasComments` and `Concerns\Commentable` for the models that receive them; one model can
  use both.
- Comments with ULID keys, replies nested to any depth and anonymous comments.
- `comment()`, `reply()`, `addComment()` and `addReply()`; a parent comment of another model is
  refused.
- `rootComments()`, `replies()`, `authoredComments()` and `isOn()` for listing and checks.
- Soft deletes; force deleting a comment deletes its replies through the foreign key.
- Cleanup of comments when their model or commenter is deleted, and pruning of left-behind comments
  through `model:prune`, scheduled by the package with a configurable frequency and chunk size.
- `Events\Commented`, `Events\Replied` and `Events\CommentDeleted`, dispatched after commit.
- Configurable connection, table and model.

[1.0.0]: https://github.com/x-laravel/commentable/releases/tag/v1.0.0
