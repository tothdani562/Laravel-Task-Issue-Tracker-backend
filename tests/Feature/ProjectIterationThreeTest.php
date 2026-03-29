<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIterationThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_endpoints_require_authentication(): void
    {
        $this->getJson('/api/projects')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->postJson('/api/projects', [
            'name' => 'Unauth Project',
        ])->assertUnauthorized();
    }

    public function test_owner_can_create_and_view_project(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner, 'api')
            ->postJson('/api/projects', [
                'name' => 'Iteration 3 Project',
                'description' => 'Iteration 3 description',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.project.name', 'Iteration 3 Project')
            ->assertJsonPath('data.project.ownerId', $owner->id)
            ->assertJsonCount(0, 'data.project.members');

        $projectId = (int) $response->json('data.project.id');

        $this->actingAs($owner, 'api')
            ->getJson('/api/projects/'.$projectId)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.project.id', $projectId)
            ->assertJsonPath('data.project.ownerId', $owner->id);
    }

    public function test_project_listing_returns_owner_and_member_projects(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $ownedProject = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Owned',
            'description' => null,
        ]);

        $memberProject = Project::query()->create([
            'owner_id' => $member->id,
            'name' => 'Member',
            'description' => null,
        ]);
        $memberProject->members()->attach($owner->id);

        $otherProject = Project::query()->create([
            'owner_id' => $member->id,
            'name' => 'Other',
            'description' => null,
        ]);

        $response = $this->actingAs($owner, 'api')->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.projects');

        $projectIds = collect($response->json('data.projects'))
            ->pluck('id')
            ->all();

        $this->assertContains($ownedProject->id, $projectIds);
        $this->assertContains($memberProject->id, $projectIds);
        $this->assertNotContains($otherProject->id, $projectIds);
    }

    public function test_only_owner_can_update_or_delete_project(): void
    {
        $owner = User::factory()->create();
        $foreignUser = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Original',
            'description' => null,
        ]);

        $this->actingAs($foreignUser, 'api')
            ->patchJson('/api/projects/'.$project->id, [
                'name' => 'Updated by foreign user',
            ])
            ->assertForbidden();

        $this->actingAs($owner, 'api')
            ->patchJson('/api/projects/'.$project->id, [
                'name' => 'Updated by owner',
            ])
            ->assertOk()
            ->assertJsonPath('data.project.name', 'Updated by owner');

        $this->actingAs($foreignUser, 'api')
            ->deleteJson('/api/projects/'.$project->id)
            ->assertForbidden();

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/projects/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.message', 'Project deleted successfully.');

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_owner_can_add_and_remove_member_while_member_cannot_manage_membership(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $foreignUser = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Membership Project',
            'description' => null,
        ]);

        $this->actingAs($member, 'api')
            ->postJson('/api/projects/'.$project->id.'/members', [
                'memberUserId' => $foreignUser->id,
            ])
            ->assertForbidden();

        $this->actingAs($owner, 'api')
            ->postJson('/api/projects/'.$project->id.'/members', [
                'memberUserId' => $member->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);

        $this->actingAs($member, 'api')
            ->getJson('/api/projects/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.project.id', $project->id);

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/projects/'.$project->id.'/members/'.$member->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);

        $this->actingAs($member, 'api')
            ->getJson('/api/projects/'.$project->id)
            ->assertForbidden();
    }

    public function test_owner_cannot_add_or_remove_self_as_member(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Self Membership Rules',
            'description' => null,
        ]);

        $this->actingAs($owner, 'api')
            ->postJson('/api/projects/'.$project->id.'/members', [
                'memberUserId' => $owner->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Project owner cannot be added as a member.');

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/projects/'.$project->id.'/members/'.$owner->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Project owner cannot be removed from members.');
    }
}
