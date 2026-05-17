<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\BaseTenantSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

abstract class BaseTenantSettingController extends Controller
{
    public function __construct(
        protected readonly TenantSelectionService $tenantSelectionService,
        protected readonly TenantExecutionManager $tenantExecutionManager,
        protected readonly BaseTenantSettingService $settingService,
        protected readonly LogPersistenceService $logPersistenceService,
    ) {}

    /**
     * @return view-string
     */
    abstract protected function viewName(): string;

    abstract protected function routeName(): string;

    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user('web');
        $tenants = $this->tenantSelectionService->availableTenants($user);
        $tenant = $this->tenantSelectionService->resolveForUser(
            user: $user,
            tenantCode: $request->filled('tenant') ? $request->string('tenant')->toString() : null,
        );
        $setting = null;
        $loadError = null;

        if ($tenant !== null) {
            try {
                $setting = $this->tenantExecutionManager->run(
                    $tenant,
                    fn () => $this->settingService->getOrCreateDefault((int) $user->id)
                );
            } catch (Throwable $throwable) {
                $this->logPersistenceService->logSystemError(
                    throwable: $throwable,
                    category: 'tenant-settings',
                    operation: $this->routeName(),
                    userId: $user->id,
                );

                $loadError = 'Não foi possível carregar as configurações deste tenant. Verifique se o tenant está provisionado e se as migrations foram executadas.';
            }
        }

        return view($this->viewName(), [
            'tenants' => $tenants,
            'selectedTenant' => $tenant,
            'setting' => $setting,
            'loadError' => $loadError,
        ]);
    }

    protected function updateSetting(FormRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $tenant = $this->tenantSelectionService->resolveForUser($user, (string) $data['tenant']);

        if ($tenant === null) {
            abort(404);
        }

        try {
            $this->tenantExecutionManager->run(
                $tenant,
                fn () => $this->settingService->update($data)
            );
        } catch (Throwable $throwable) {
            $this->logPersistenceService->logSystemError(
                throwable: $throwable,
                category: 'tenant-settings',
                operation: $this->routeName(),
                userId: $user->id,
            );

            return back()
                ->withErrors(['settings' => 'Não foi possível salvar as configurações deste tenant. Verifique se o tenant está provisionado e se as migrations foram executadas.'])
                ->withInput();
        }

        return redirect()
            ->route($this->routeName(), ['tenant' => $tenant->code])
            ->with('status', 'Configurações atualizadas com sucesso.');
    }
}
