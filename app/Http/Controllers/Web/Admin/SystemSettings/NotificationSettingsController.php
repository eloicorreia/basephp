<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantNotificationSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantNotificationSettingsRequest;
use Illuminate\Http\RedirectResponse;

final class NotificationSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantNotificationSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string { return 'admin.system-settings.notifications'; }

    protected function routeName(): string { return 'admin.system-settings.notifications.edit'; }

    public function update(UpdateTenantNotificationSettingsRequest $request): RedirectResponse { return $this->updateSetting($request); }
}
