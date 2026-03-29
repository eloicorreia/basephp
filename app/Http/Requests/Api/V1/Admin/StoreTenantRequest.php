<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'schema_name' => ['required', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{2,62}$/'],
        ];
    }
}