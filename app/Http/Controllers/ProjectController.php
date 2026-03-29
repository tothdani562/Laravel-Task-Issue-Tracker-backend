<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\AddProjectMemberRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $project = Project::query()->create([
            'owner_id' => $user->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return ApiResponse::success([
            'project' => $this->serializeProject($project->load(['owner', 'members'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $projects = Project::query()
            ->with(['owner', 'members'])
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->whereKey($user->id))
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success([
            'projects' => $projects->map(fn (Project $project): array => $this->serializeProject($project))->values(),
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ApiResponse::success([
            'project' => $this->serializeProject($project->load(['owner', 'members'])),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->fill($request->validated());
        $project->save();

        return ApiResponse::success([
            'project' => $this->serializeProject($project->load(['owner', 'members'])),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return ApiResponse::success([
            'message' => 'Project deleted successfully.',
        ]);
    }

    public function addMember(AddProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $memberUserId = (int) $request->validated('memberUserId');

        if ($memberUserId === (int) $project->owner_id) {
            return ApiResponse::error('Project owner cannot be added as a member.', 422);
        }

        $project->members()->syncWithoutDetaching([$memberUserId]);

        return ApiResponse::success([
            'project' => $this->serializeProject($project->load(['owner', 'members'])),
        ]);
    }

    public function removeMember(Project $project, int $memberUserId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if ($memberUserId === (int) $project->owner_id) {
            return ApiResponse::error('Project owner cannot be removed from members.', 422);
        }

        $memberExists = User::query()->whereKey($memberUserId)->exists();

        if (! $memberExists) {
            return ApiResponse::error('Member user not found.', 404);
        }

        $attached = $project->members()->whereKey($memberUserId)->exists();

        if (! $attached) {
            return ApiResponse::error('User is not a member of this project.', 404);
        }

        $project->members()->detach($memberUserId);

        return ApiResponse::success([
            'project' => $this->serializeProject($project->load(['owner', 'members'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'ownerId' => $project->owner_id,
            'owner' => [
                'id' => $project->owner?->id,
                'name' => $project->owner?->name,
                'email' => $project->owner?->email,
            ],
            'members' => $project->members
                ->map(static fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ])
                ->values(),
            'createdAt' => $project->created_at?->toISOString(),
            'updatedAt' => $project->updated_at?->toISOString(),
        ];
    }
}
