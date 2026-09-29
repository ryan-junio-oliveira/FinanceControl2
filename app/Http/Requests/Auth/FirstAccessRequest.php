<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class FirstAccessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', $this->strongPassword()],
        ];
    }

    public function messages(): array
    {
        return $this->strongPasswordMessages();
    }
}
