<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

class StoreTaskRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', 'in:TODO,IN_PROGRESS,DONE'],
            'priority' => ['sometimes', 'required', 'string', 'in:LOW,MEDIUM,HIGH'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'dueDate' => ['nullable', 'date'],
        ];
    }
}
