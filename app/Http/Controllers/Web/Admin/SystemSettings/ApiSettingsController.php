<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantApiSettingsRequest;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantApiSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Http\RedirectResponse;

final class ApiSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantApiSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string
    {
        return 'admin.system-settings.api';
    }

    protected function routeName(): string
    {
        return 'admin.system-settings.api.edit';
    }

    public function update(UpdateTenantApiSettingsRequest $request): RedirectResponse
    {
        return $this->updateSetting($request);
    }
}
