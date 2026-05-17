<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantIntegrationSettingsRequest;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantIntegrationSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Http\RedirectResponse;

final class IntegrationSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantIntegrationSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string
    {
        return 'admin.system-settings.integrations';
    }

    protected function routeName(): string
    {
        return 'admin.system-settings.integrations.edit';
    }

    public function update(UpdateTenantIntegrationSettingsRequest $request): RedirectResponse
    {
        return $this->updateSetting($request);
    }
}
