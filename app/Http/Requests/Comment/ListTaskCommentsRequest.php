<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\ApiFormRequest;

class ListTaskCommentsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'limit' => ['sometimes', 'required', 'integer', 'between:1,100'],
        ];
    }
}
