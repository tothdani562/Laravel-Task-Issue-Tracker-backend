<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\ListProjectTasksRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $project = $this->findAuthorizedProject($request, $projectId);

        if ($project instanceof JsonResponse) {
            return $project;
        }

        $validated = $request->validated();
        $assignedUserId = $validated['assignedUserId'] ?? null;

        if (! $this->isAssignableUser($project, $assignedUserId)) {
            return ApiResponse::error('Assigned user must be the project owner or a project member.', 422);
        }

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'TODO',
            'priority' => $validated['priority'] ?? 'MEDIUM',
            'assigned_user_id' => $assignedUserId,
            'due_date' => $validated['dueDate'] ?? null,
        ]);

        return ApiResponse::success([
            'task' => $this->serializeTask($task->load('assignee')),
        ], 201);
    }

    public function index(ListProjectTasksRequest $request, int $projectId): JsonResponse
    {
        $project = $this->findAuthorizedProject($request, $projectId);

        if ($project instanceof JsonResponse) {
            return $project;
        }

        $validated = $request->validated();
        $page = isset($validated['page']) ? (int) $validated['page'] : 1;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 15;

        $query = Task::query()
            ->where('project_id', $project->id)
            ->with('assignee')
            ->orderByDesc('created_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        if (isset($validated['assigneeId'])) {
            $query->where('assigned_user_id', (int) $validated['assigneeId']);
        }

        $tasks = $query->paginate($limit, ['*'], 'page', $page);

        return ApiResponse::success([
            'tasks' => $tasks->getCollection()
                ->map(fn (Task $task): array => $this->serializeTask($task))
                ->values(),
            'pagination' => [
                'page' => $tasks->currentPage(),
                'limit' => $tasks->perPage(),
                'total' => $tasks->total(),
                'lastPage' => $tasks->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $project = $this->findAuthorizedProject($request, $projectId);

        if ($project instanceof JsonResponse) {
            return $project;
        }

        $task = Task::query()
            ->where('project_id', $project->id)
            ->with('assignee')
            ->find($taskId);

        if ($task === null) {
            return ApiResponse::error('Task not found.', 404);
        }

        return ApiResponse::success([
            'task' => $this->serializeTask($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, int $projectId, int $taskId): JsonResponse
    {
        $project = $this->findAuthorizedProject($request, $projectId);

        if ($project instanceof JsonResponse) {
            return $project;
        }

        $task = Task::query()->where('project_id', $project->id)->find($taskId);

        if ($task === null) {
            return ApiResponse::error('Task not found.', 404);
        }

        $validated = $request->validated();

        if (array_key_exists('assignedUserId', $validated) && ! $this->isAssignableUser($project, $validated['assignedUserId'])) {
            return ApiResponse::error('Assigned user must be the project owner or a project member.', 422);
        }

        if (array_key_exists('title', $validated)) {
            $task->title = $validated['title'];
        }

        if (array_key_exists('description', $validated)) {
            $task->description = $validated['description'];
        }

        if (array_key_exists('status', $validated)) {
            $task->status = $validated['status'];
        }

        if (array_key_exists('priority', $validated)) {
            $task->priority = $validated['priority'];
        }

        if (array_key_exists('assignedUserId', $validated)) {
            $task->assigned_user_id = $validated['assignedUserId'];
        }

        if (array_key_exists('dueDate', $validated)) {
            $task->due_date = $validated['dueDate'];
        }

        $task->save();

        return ApiResponse::success([
            'task' => $this->serializeTask($task->load('assignee')),
        ]);
    }

    public function destroy(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $project = $this->findAuthorizedProject($request, $projectId);

        if ($project instanceof JsonResponse) {
            return $project;
        }

        $task = Task::query()->where('project_id', $project->id)->find($taskId);

        if ($task === null) {
            return ApiResponse::error('Task not found.', 404);
        }

        $task->delete();

        return ApiResponse::success([
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function findAuthorizedProject(Request $request, int $projectId): Project|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $project = Project::query()->find($projectId);

        if ($project === null) {
            return ApiResponse::error('Project not found.', 404);
        }

        $this->authorize('view', $project);

        return $project;
    }

    private function isAssignableUser(Project $project, int|string|null $assignedUserId): bool
    {
        if ($assignedUserId === null) {
            return true;
        }

        $assignedUserId = (int) $assignedUserId;

        return $project->owner_id === $assignedUserId
            || $project->members()->whereKey($assignedUserId)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'projectId' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'assignedUserId' => $task->assigned_user_id,
            'assignee' => $task->assignee === null ? null : [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'email' => $task->assignee->email,
            ],
            'dueDate' => $task->due_date?->toISOString(),
            'createdAt' => $task->created_at?->toISOString(),
            'updatedAt' => $task->updated_at?->toISOString(),
        ];
    }
}
