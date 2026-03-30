<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentIterationSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_and_list_comments_with_pagination(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Iteration 6 Project',
            'description' => null,
        ]);

        $project->members()->attach($member->id);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'assigned_user_id' => null,
            'title' => 'Task with comments',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'MEDIUM',
            'due_date' => null,
        ]);

        $this->actingAs($member, 'api')
            ->postJson('/api/tasks/'.$task->id.'/comments', [
                'content' => 'First comment',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.comment.taskId', $task->id)
            ->assertJsonPath('data.comment.authorId', $member->id);

        $createdResponse = $this->actingAs($member, 'api')
            ->postJson('/api/tasks/'.$task->id.'/comments', [
                'content' => 'Second comment',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $commentId = (int) $createdResponse->json('data.comment.id');

        $this->actingAs($owner, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments?page=1&limit=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.content', 'Second comment')
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonPath('data.pagination.limit', 1)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.pagination.lastPage', 2);

        $this->actingAs($owner, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments/'.$commentId)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.comment.id', $commentId)
            ->assertJsonPath('data.comment.content', 'Second comment');
    }

    public function test_only_author_or_project_owner_can_update_or_delete_comment(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $otherMember = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Permissions Project',
            'description' => null,
        ]);

        $project->members()->attach([$author->id, $otherMember->id]);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'assigned_user_id' => null,
            'title' => 'Permission task',
            'description' => null,
            'status' => 'TODO',
            'priority' => 'LOW',
            'due_date' => null,
        ]);

        $comment = Comment::query()->create([
            'task_id' => $task->id,
            'author_id' => $author->id,
            'content' => 'Author comment',
        ]);

        $this->actingAs($otherMember, 'api')
            ->patchJson('/api/tasks/'.$task->id.'/comments/'.$comment->id, [
                'content' => 'Invalid update',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->patchJson('/api/tasks/'.$task->id.'/comments/'.$comment->id, [
                'content' => 'Owner update',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.comment.content', 'Owner update');

        $this->actingAs($otherMember, 'api')
            ->deleteJson('/api/tasks/'.$task->id.'/comments/'.$comment->id)
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($author, 'api')
            ->deleteJson('/api/tasks/'.$task->id.'/comments/'.$comment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Comment deleted successfully.');

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_foreign_user_cannot_access_task_comments(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $foreign = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Access Control Project',
            'description' => null,
        ]);

        $project->members()->attach($author->id);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'assigned_user_id' => null,
            'title' => 'Foreign access task',
            'description' => null,
            'status' => 'IN_PROGRESS',
            'priority' => 'HIGH',
            'due_date' => null,
        ]);

        $comment = Comment::query()->create([
            'task_id' => $task->id,
            'author_id' => $author->id,
            'content' => 'Restricted comment',
        ]);

        $this->actingAs($foreign, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($foreign, 'api')
            ->postJson('/api/tasks/'.$task->id.'/comments', [
                'content' => 'Should not be created',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($foreign, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments/'.$comment->id)
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_comment_validation_and_not_found_cases_are_handled(): void
    {
        $owner = User::factory()->create();

        $project = Project::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Validation Project',
            'description' => null,
        ]);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'assigned_user_id' => null,
            'title' => 'Validation task',
            'description' => null,
            'status' => 'DONE',
            'priority' => 'LOW',
            'due_date' => null,
        ]);

        $this->actingAs($owner, 'api')
            ->postJson('/api/tasks/'.$task->id.'/comments', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments?limit=101')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/tasks/'.$task->id.'/comments/99999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->actingAs($owner, 'api')
            ->getJson('/api/tasks/99999/comments')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
