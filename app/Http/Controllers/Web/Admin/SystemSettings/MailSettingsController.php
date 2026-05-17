<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\SystemSettings\TestTenantMailSettingsRequest;
use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantMailSettingsRequest;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantMailSettingService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class MailSettingsController extends Controller
{
    public function __construct(
        private readonly TenantSelectionService $tenantSelectionService,
        private readonly TenantExecutionManager $tenantExecutionManager,
        private readonly TenantMailSettingService $mailSettingService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {}

    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user('web');
        $tenants = $this->tenantSelectionService->availableTenants($user);
        $tenant = $this->tenantSelectionService->resolveForUser(
            user: $user,
            tenantCode: $request->filled('tenant') ? $request->string('tenant')->toString() : null,
        );

        $mailConfig = null;
        $loadError = null;

        if ($tenant !== null) {
            try {
                $mailConfig = $this->tenantExecutionManager->run($tenant, fn () => $this->mailSettingService->defaultConfig());
            } catch (Throwable $throwable) {
                $this->logPersistenceService->logSystemError($throwable, 'tenant-settings', 'mail.edit', $user->id);
                $loadError = 'Não foi possível carregar as configurações deste tenant. Verifique se o tenant está provisionado e se as migrations foram executadas.';
            }
        }

        return view('admin.system-settings.mail', [
            'tenants' => $tenants,
            'selectedTenant' => $tenant,
            'mailConfig' => $mailConfig,
            'loadError' => $loadError,
        ]);
    }

    public function update(UpdateTenantMailSettingsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        $data = $request->validated();
        $tenant = $this->tenantSelectionService->resolveForUser($user, (string) $data['tenant']);

        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        try {
            $this->tenantExecutionManager->run(
                $tenant,
                fn () => $this->mailSettingService->updateDefault($data)
            );
        } catch (Throwable $throwable) {
            $this->logPersistenceService->logSystemError($throwable, 'tenant-settings', 'mail.update', $user->id);

            return back()
                ->withErrors(['mail' => 'Não foi possível conectar ao servidor de e-mail com as configurações informadas.'])
                ->withInput($request->except('password'));
        }

        return redirect()
            ->route('admin.system-settings.mail.edit', ['tenant' => $tenant->code])
            ->with('status', 'Configuração de e-mail atualizada com sucesso.');
    }

    public function test(TestTenantMailSettingsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        $data = $request->validated();
        $tenant = $this->tenantSelectionService->resolveForUser($user, (string) $data['tenant']);

        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        try {
            $this->tenantExecutionManager->run(
                $tenant,
                fn () => $this->mailSettingService->sendTest((string) $data['to'])
            );
        } catch (Throwable $throwable) {
            $this->logPersistenceService->logSystemError($throwable, 'tenant-settings', 'mail.test', $user->id);

            return back()
                ->withErrors(['mail_test' => 'Falha ao enviar e-mail de teste. Verifique as configurações informadas.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.system-settings.mail.edit', ['tenant' => $tenant->code])
            ->with('status', 'E-mail de teste enviado com sucesso.');
    }
}
