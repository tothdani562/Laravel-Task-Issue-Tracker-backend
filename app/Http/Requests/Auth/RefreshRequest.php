<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RefreshRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'refreshToken' => ['required', 'string', 'max:4096'],
        ];
    }
}
