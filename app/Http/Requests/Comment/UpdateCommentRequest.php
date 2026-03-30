<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\ApiFormRequest;

class UpdateCommentRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
        ];
    }
}
