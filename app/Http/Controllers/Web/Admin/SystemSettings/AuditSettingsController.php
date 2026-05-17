<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantAuditSettingsRequest;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantAuditSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Http\RedirectResponse;

final class AuditSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantAuditSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string
    {
        return 'admin.system-settings.audit';
    }

    protected function routeName(): string
    {
        return 'admin.system-settings.audit.edit';
    }

    public function update(UpdateTenantAuditSettingsRequest $request): RedirectResponse
    {
        return $this->updateSetting($request);
    }
}
