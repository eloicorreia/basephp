<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantSelectionService;
use App\Services\TenantSettings\TenantSystemSettingService;
use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantSystemSettingsRequest;
use Illuminate\Http\RedirectResponse;

final class GeneralSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantSystemSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string { return 'admin.system-settings.general'; }

    protected function routeName(): string { return 'admin.system-settings.general.edit'; }

    public function update(UpdateTenantSystemSettingsRequest $request): RedirectResponse { return $this->updateSetting($request); }
}
