<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'array', 'min:1', 'max:50'],
            'to.*' => ['required', 'email:rfc,dns', 'max:255'],
            'cc' => ['nullable', 'array', 'max:20'],
            'cc.*' => ['required', 'email:rfc,dns', 'max:255'],
            'bcc' => ['nullable', 'array', 'max:20'],
            'bcc.*' => ['required', 'email:rfc,dns', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_html' => ['nullable', 'boolean'],
            'queue' => ['nullable', 'string', 'in:default,notifications,integrations'],
            'external_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}