<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:12',
                'max:100',
                'different:current_password',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatória.',
            'new_password.required' => 'A nova senha é obrigatória.',
            'new_password.min' => 'A nova senha deve possuir no mínimo 12 caracteres.',
            'new_password.confirmed' => 'A confirmação da nova senha não confere.',
            'new_password.different' => 'A nova senha deve ser diferente da senha atual.',
        ];
    }
}