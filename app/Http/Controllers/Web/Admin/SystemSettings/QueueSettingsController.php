<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantQueueSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantQueueSettingsRequest;
use Illuminate\Http\RedirectResponse;

final class QueueSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantQueueSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string { return 'admin.system-settings.queues'; }

    protected function routeName(): string { return 'admin.system-settings.queues.edit'; }

    public function update(UpdateTenantQueueSettingsRequest $request): RedirectResponse { return $this->updateSetting($request); }
}
