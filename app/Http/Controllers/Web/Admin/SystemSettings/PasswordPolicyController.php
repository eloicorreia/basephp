<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\SystemSettings\UpdatePasswordPolicyRequest;
use App\Models\User;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantPasswordPolicyService;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PasswordPolicyController extends Controller
{
    public function __construct(
        private readonly TenantSelectionService $tenantSelectionService,
        private readonly TenantExecutionManager $tenantExecutionManager,
        private readonly TenantPasswordPolicyService $passwordPolicyService,
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

        $policy = $tenant !== null
            ? $this->tenantExecutionManager->run($tenant, fn () => $this->passwordPolicyService->getOrCreateDefault((int) $user->id))
            : null;

        return view('admin.system-settings.password-policy', [
            'tenants' => $tenants,
            'selectedTenant' => $tenant,
            'policy' => $policy,
        ]);
    }

    public function update(UpdatePasswordPolicyRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');
        $data = $request->validated();
        $tenant = $this->tenantSelectionService->resolveForUser($user, (string) $data['tenant']);

        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        $this->tenantExecutionManager->run(
            $tenant,
            fn () => $this->passwordPolicyService->update($data)
        );

        return redirect()
            ->route('admin.system-settings.password-policy.edit', ['tenant' => $tenant->code])
            ->with('status', 'Política de senhas atualizada com sucesso.');
    }
}
