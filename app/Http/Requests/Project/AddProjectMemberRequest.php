<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\ApiFormRequest;

class AddProjectMemberRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'memberUserId' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
