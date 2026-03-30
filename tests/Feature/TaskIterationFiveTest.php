<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskIterationFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_listing_supports_due_date_range_and_sorting(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Iteration 5 Project',
            'description' => null,
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'No due date',
            'description' => null,
            'status' => 'DONE',
            'priority' => 'LOW',
            'assigned_user_id' => null,
            'due_date' => null,
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Due March 15',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assigned_user_id' => null,
            'due_date' => '2026-03-15T10:00:00Z',
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Due March 20',
            'description' => null,
            'status' => 'IN_PROGRESS',
            'priority' => 'MEDIUM',
            'assigned_user_id' => null,
            'due_date' => '2026-03-20T10:00:00Z',
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Due March 25',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'LOW',
            'assigned_user_id' => null,
            'due_date' => '2026-03-25T10:00:00Z',
        ]);

        $response = $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?dueFrom=2026-03-18T00:00:00Z&dueTo=2026-03-31T23:59:59Z&sortBy=dueDate&sortOrder=asc');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.tasks')
            ->assertJsonPath('data.tasks.0.title', 'Due March 20')
            ->assertJsonPath('data.tasks.1.title', 'Due March 25')
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_task_listing_supports_custom_sorting_for_priority(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Priority Sort Project',
            'description' => null,
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Medium Task',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'MEDIUM',
            'assigned_user_id' => null,
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Low Task',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'LOW',
            'assigned_user_id' => null,
        ]);

        Task::query()->create([
            'project_id' => $project->id,
            'title' => 'High Task',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assigned_user_id' => null,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?sortBy=priority&sortOrder=asc');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tasks.0.title', 'Low Task')
            ->assertJsonPath('data.tasks.1.title', 'Medium Task')
            ->assertJsonPath('data.tasks.2.title', 'High Task');
    }

    public function test_invalid_advanced_query_parameters_return_validation_error(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Validation Project',
            'description' => null,
        ]);

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?sortBy=invalidField')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?sortOrder=upwards')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?limit=101')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$project->id.'/tasks?dueFrom=2026-03-30&dueTo=2026-03-01')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
