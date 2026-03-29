<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

class UpdateTaskRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', 'in:TODO,IN_PROGRESS,DONE'],
            'priority' => ['sometimes', 'required', 'string', 'in:LOW,MEDIUM,HIGH'],
            'assignedUserId' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'dueDate' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
