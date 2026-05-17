<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('web') !== null; }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:20'],
            'date_format' => ['required', 'string', 'max:30'],
            'datetime_format' => ['required', 'string', 'max:40'],
            'default_items_per_page' => ['required', 'integer', 'min:1', 'max:500'],
            'max_items_per_page' => ['required', 'integer', 'min:1', 'max:1000', 'gte:default_items_per_page'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
