<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\ListTaskCommentsRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $task = $this->findAuthorizedTask($request, $taskId);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $comment = Comment::query()->create([
            'task_id' => (int) $task->getKey(),
            'author_id' => (int) $user->getAuthIdentifier(),
            'content' => $request->validated('content'),
        ]);

        return ApiResponse::success([
            'comment' => $this->serializeComment($comment->load('author')),
        ], 201);
    }

    public function index(ListTaskCommentsRequest $request, int $taskId): JsonResponse
    {
        $task = $this->findAuthorizedTask($request, $taskId);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $validated = $request->validated();
        $page = isset($validated['page']) ? (int) $validated['page'] : 1;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 15;

        $comments = Comment::query()
            ->where('task_id', $task->getKey())
            ->with('author')
            ->orderByDesc('created_at')
            ->orderBy('id', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return ApiResponse::success([
            'comments' => $comments->getCollection()
                ->map(fn (Comment $comment): array => $this->serializeComment($comment))
                ->values(),
            'pagination' => [
                'page' => $comments->currentPage(),
                'limit' => $comments->perPage(),
                'total' => $comments->total(),
                'lastPage' => $comments->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $taskId, int $commentId): JsonResponse
    {
        $task = $this->findAuthorizedTask($request, $taskId);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $comment = Comment::query()
            ->where('task_id', $task->getKey())
            ->with('author')
            ->find($commentId);

        if ($comment === null) {
            return ApiResponse::error('Comment not found.', 404);
        }

        return ApiResponse::success([
            'comment' => $this->serializeComment($comment),
        ]);
    }

    public function update(UpdateCommentRequest $request, int $taskId, int $commentId): JsonResponse
    {
        $task = $this->findAuthorizedTask($request, $taskId);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $comment = Comment::query()->where('task_id', $task->getKey())->find($commentId);

        if ($comment === null) {
            return ApiResponse::error('Comment not found.', 404);
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if (! $this->canManageComment($user, $task, $comment)) {
            return ApiResponse::error('You are not allowed to modify this comment.', 403);
        }

        $validated = $request->validated();

        if (array_key_exists('content', $validated)) {
            $comment->setAttribute('content', $validated['content']);
        }

        $comment->save();

        return ApiResponse::success([
            'comment' => $this->serializeComment($comment->load('author')),
        ]);
    }

    public function destroy(Request $request, int $taskId, int $commentId): JsonResponse
    {
        $task = $this->findAuthorizedTask($request, $taskId);

        if ($task instanceof JsonResponse) {
            return $task;
        }

        $comment = Comment::query()->where('task_id', $task->getKey())->find($commentId);

        if ($comment === null) {
            return ApiResponse::error('Comment not found.', 404);
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if (! $this->canManageComment($user, $task, $comment)) {
            return ApiResponse::error('You are not allowed to delete this comment.', 403);
        }

        $comment->delete();

        return ApiResponse::success([
            'message' => 'Comment deleted successfully.',
        ]);
    }

    private function findAuthorizedTask(Request $request, int $taskId): Task|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $task = Task::query()->find($taskId);

        if ($task === null) {
            return ApiResponse::error('Task not found.', 404);
        }

        $project = $task->project()->first();

        if ($project === null) {
            return ApiResponse::error('Project not found.', 404);
        }

        $this->authorize('view', $project);

        return $task;
    }

    private function canManageComment(User $user, Task $task, Comment $comment): bool
    {
        $commentAuthorId = (int) $comment->getAttribute('author_id');
        $projectOwnerId = (int) $task->project()->value('owner_id');
        $userId = (int) $user->getAuthIdentifier();

        return $commentAuthorId === $userId
            || $projectOwnerId === $userId;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(Comment $comment): array
    {
        $author = $comment->author()->first();
        $createdAt = $comment->getAttribute('created_at');
        $updatedAt = $comment->getAttribute('updated_at');

        return [
            'id' => (int) $comment->getKey(),
            'taskId' => (int) $comment->getAttribute('task_id'),
            'authorId' => (int) $comment->getAttribute('author_id'),
            'author' => $author === null ? null : [
                'id' => (int) $author->getKey(),
                'name' => (string) $author->getAttribute('name'),
                'email' => (string) $author->getAttribute('email'),
            ],
            'content' => (string) $comment->getAttribute('content'),
            'createdAt' => $createdAt?->toISOString(),
            'updatedAt' => $updatedAt?->toISOString(),
        ];
    }
}
