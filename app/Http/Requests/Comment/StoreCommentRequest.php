<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\ApiFormRequest;

class StoreCommentRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
