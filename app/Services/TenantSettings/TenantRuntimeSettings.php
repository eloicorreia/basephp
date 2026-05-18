<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantSystemSetting;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class TenantRuntimeSettings
{
    private ?TenantSystemSetting $settings = null;

    private bool $loaded = false;

    public function settings(): TenantSystemSetting
    {
        if ($this->loaded && $this->settings instanceof TenantSystemSetting) {
            return $this->settings;
        }

        $this->loaded = true;

        try {
            if (! Schema::hasTable('tenant_system_settings')) {
                return $this->settings = $this->defaultSettings();
            }

            $setting = TenantSystemSetting::query()->orderBy('id')->first();

            return $this->settings = $setting instanceof TenantSystemSetting ? $setting : $this->defaultSettings();
        } catch (Throwable) {
            return $this->settings = $this->defaultSettings();
        }
    }

    public function defaultPerPage(): int
    {
        $settings = $this->settings();

        return max(1, min((int) $settings->default_items_per_page, $this->maxPerPage()));
    }

    public function maxPerPage(): int
    {
        return max(1, (int) $this->settings()->max_items_per_page);
    }

    public function dateFormat(): string
    {
        return (string) $this->settings()->date_format;
    }

    public function datetimeFormat(): string
    {
        return (string) $this->settings()->datetime_format;
    }

    public function timezone(): string
    {
        return (string) $this->settings()->timezone;
    }

    public function locale(): string
    {
        return (string) $this->settings()->locale;
    }

    public function formatDateTime(DateTimeInterface|int|string|null $value): ?string
    {
        return $this->format($value, $this->datetimeFormat());
    }

    public function formatDate(DateTimeInterface|int|string|null $value): ?string
    {
        return $this->format($value, $this->dateFormat());
    }

    private function format(DateTimeInterface|int|string|null $value, string $format): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = $value instanceof DateTimeInterface
                ? Carbon::instance($value)
                : (is_int($value) ? Carbon::createFromTimestamp($value) : Carbon::parse($value));

            return $date->timezone($this->timezone())->format($format);
        } catch (Throwable) {
            return is_string($value) ? $value : null;
        }
    }

    private function defaultSettings(): TenantSystemSetting
    {
        return new TenantSystemSetting([
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'date_format' => 'd/m/Y',
            'datetime_format' => 'd/m/Y H:i',
            'default_items_per_page' => 15,
            'max_items_per_page' => 100,
            'support_email' => null,
            'support_phone' => null,
            'maintenance_mode' => false,
            'maintenance_message' => null,
        ]);
    }
}
