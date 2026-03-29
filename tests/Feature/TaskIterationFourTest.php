<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskIterationFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_endpoints_require_authentication(): void
    {
        $this->getJson('/api/projects/1/tasks')->assertUnauthorized();
        $this->postJson('/api/projects/1/tasks', [
            'title' => 'Unauthorized task',
        ])->assertUnauthorized();
    }

    public function test_owner_can_create_show_update_and_delete_task(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Tasks Project',
            'description' => null,
        ]);

        $createResponse = $this->actingAs($owner, 'api')
            ->postJson('/api/projects/'.$project->id.'/tasks', [
                'title' => 'Iteration 4 Task',
                'description' => 'Task description',
                'status' => 'TODO',
                'priority' => 'HIGH',
                'assignedUserId' => $owner->id,
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task.title', 'Iteration 4 Task')
            ->assertJsonPath('data.task.projectId', $project->id)
            ->assertJsonPath('data.task.assignedUserId', $owner->id);

        $taskId = (int) $createResponse->json('data.task.id');

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks/'.$taskId)
            ->assertOk()
            ->assertJsonPath('data.task.id', $taskId);

        $this->actingAs($owner, 'api')
            ->patchJson('/api/projects/'.$project->id.'/tasks/'.$taskId, [
                'status' => 'IN_PROGRESS',
                'priority' => 'MEDIUM',
            ])
            ->assertOk()
            ->assertJsonPath('data.task.status', 'IN_PROGRESS')
            ->assertJsonPath('data.task.priority', 'MEDIUM');

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/projects/'.$project->id.'/tasks/'.$taskId)
            ->assertOk()
            ->assertJsonPath('data.message', 'Task deleted successfully.');

        $this->assertDatabaseMissing('tasks', [
            'id' => $taskId,
        ]);
    }

    public function test_member_can_access_project_tasks_but_foreign_user_cannot(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $foreignUser = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Shared Project',
            'description' => null,
        ]);
        $project->members()->attach($member->id);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Shared Task',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'LOW',
            'assigned_user_id' => $member->id,
        ]);

        $this->actingAs($member, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($member, 'api')
            ->patchJson('/api/projects/'.$project->id.'/tasks/'.$task->id, [
                'status' => 'DONE',
            ])
            ->assertOk()
            ->assertJsonPath('data.task.status', 'DONE');

        $this->actingAs($foreignUser, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks')
            ->assertForbidden();

        $this->actingAs($foreignUser, 'api')
            ->postJson('/api/projects/'.$project->id.'/tasks', [
                'title' => 'No access',
            ])
            ->assertForbidden();
    }

    public function test_task_listing_supports_filters_and_pagination(): void
    {
        $owner = User::factory()->create();
        $assigneeA = User::factory()->create();
        $assigneeB = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Filter Project',
            'description' => null,
        ]);
        $project->members()->attach([$assigneeA->id, $assigneeB->id]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'A-1',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assigned_user_id' => $assigneeA->id,
        ]);
        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'A-2',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assigned_user_id' => $assigneeA->id,
        ]);
        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'B-1',
            'description' => null,
            'status' => 'DONE',
            'priority' => 'LOW',
            'assigned_user_id' => $assigneeB->id,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?status=TODO&priority=HIGH&assigneeId='.$assigneeA->id.'&page=1&limit=1');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.tasks')
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonPath('data.pagination.limit', 1)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.tasks.0.status', 'TODO')
            ->assertJsonPath('data.tasks.0.priority', 'HIGH')
            ->assertJsonPath('data.tasks.0.assignedUserId', $assigneeA->id);
    }

    public function test_assignee_must_belong_to_project(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Assignment Rules',
            'description' => null,
        ]);

        $this->actingAs($owner, 'api')
            ->postJson('/api/projects/'.$project->id.'/tasks', [
                'title' => 'Invalid assignee',
                'assignedUserId' => $outsider->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Assigned user must be the project owner or a project member.');
    }
}
