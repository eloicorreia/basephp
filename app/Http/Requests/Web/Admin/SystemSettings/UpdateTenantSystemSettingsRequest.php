<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantSystemSettingsRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public static function localeOptions(): array
    {
        return [
            'pt_BR' => 'Português (Brasil)',
            'en_US' => 'Inglês (Estados Unidos)',
            'en_GB' => 'Inglês (Reino Unido)',
            'es_ES' => 'Espanhol (Espanha)',
            'es_MX' => 'Espanhol (México)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateFormatOptions(): array
    {
        return [
            'd/m/Y' => 'dd/mm/aaaa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function datetimeFormatOptions(): array
    {
        return [
            'd/m/Y H:i' => 'dd/mm/aaaa hh:mm',
            'd/m/Y H:i:s' => 'dd/mm/aaaa hh:mm:ss',
        ];
    }

    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:100'],
            'timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'locale' => ['required', Rule::in(array_keys(self::localeOptions()))],
            'date_format' => ['required', Rule::in(array_keys(self::dateFormatOptions()))],
            'datetime_format' => ['required', Rule::in(array_keys(self::datetimeFormatOptions()))],
            'default_items_per_page' => ['required', 'integer', 'min:1', 'max:500'],
            'max_items_per_page' => ['required', 'integer', 'min:1', 'max:1000', 'gte:default_items_per_page'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9\s().+-]+$/'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
