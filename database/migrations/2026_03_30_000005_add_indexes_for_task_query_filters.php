<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['project_id', 'status'], 'tasks_project_status_idx');
            $table->index(['project_id', 'priority'], 'tasks_project_priority_idx');
            $table->index(['project_id', 'assigned_user_id'], 'tasks_project_assignee_idx');
            $table->index(['project_id', 'due_date'], 'tasks_project_due_date_idx');
            $table->index(['project_id', 'created_at'], 'tasks_project_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_project_status_idx');
            $table->dropIndex('tasks_project_priority_idx');
            $table->dropIndex('tasks_project_assignee_idx');
            $table->dropIndex('tasks_project_due_date_idx');
            $table->dropIndex('tasks_project_created_at_idx');
        });
    }
};
