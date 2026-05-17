<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantSelectionService;
use App\Services\TenantSettings\TenantWebhookSettingService;
use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantWebhookSettingsRequest;
use Illuminate\Http\RedirectResponse;

final class WebhookSettingsController extends BaseTenantSettingController
{
    public function __construct(TenantSelectionService $tenantSelectionService, TenantExecutionManager $tenantExecutionManager, TenantWebhookSettingService $settingService, LogPersistenceService $logPersistenceService)
    {
        parent::__construct($tenantSelectionService, $tenantExecutionManager, $settingService, $logPersistenceService);
    }

    protected function viewName(): string { return 'admin.system-settings.webhooks'; }

    protected function routeName(): string { return 'admin.system-settings.webhooks.edit'; }

    public function update(UpdateTenantWebhookSettingsRequest $request): RedirectResponse { return $this->updateSetting($request); }
}
