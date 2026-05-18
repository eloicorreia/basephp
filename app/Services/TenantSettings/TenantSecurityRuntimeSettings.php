<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantSecuritySetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class TenantSecurityRuntimeSettings
{
    private ?TenantSecuritySetting $settings = null;

    private bool $loaded = false;

    public function settings(): TenantSecuritySetting
    {
        if ($this->loaded && $this->settings instanceof TenantSecuritySetting) {
            return $this->settings;
        }

        $this->loaded = true;

        try {
            if (! Schema::hasTable('tenant_security_settings')) {
                return $this->settings = $this->defaultSettings();
            }

            $setting = TenantSecuritySetting::query()->orderBy('id')->first();

            return $this->settings = $setting instanceof TenantSecuritySetting ? $setting : $this->defaultSettings();
        } catch (Throwable) {
            return $this->settings = $this->defaultSettings();
        }
    }

    private function defaultSettings(): TenantSecuritySetting
    {
        return new TenantSecuritySetting([
            'session_lifetime_minutes' => 120,
            'idle_timeout_minutes' => null,
            'force_single_session_per_user' => false,
            'logout_on_password_change' => true,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'unlock_requires_admin' => false,
            'notify_user_on_failed_login' => true,
            'notify_admin_on_lockout' => true,
            'allowed_ip_ranges' => null,
        ]);
    }
}
