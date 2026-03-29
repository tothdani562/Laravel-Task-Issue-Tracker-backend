<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

class ListProjectTasksRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', 'in:TODO,IN_PROGRESS,DONE'],
            'priority' => ['sometimes', 'required', 'string', 'in:LOW,MEDIUM,HIGH'],
            'assigneeId' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'limit' => ['sometimes', 'required', 'integer', 'between:1,100'],
        ];
    }
}
