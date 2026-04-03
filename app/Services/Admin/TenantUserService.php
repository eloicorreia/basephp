<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\CreateTenantUserDTO;
use App\Models\TenantUser;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Facades\DB;

class TenantUserService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {
    }

    public function createOrUpdate(CreateTenantUserDTO $dto): TenantUser
    {
        return DB::transaction(function () use ($dto): TenantUser {
            $tenantUser = TenantUser::query()->updateOrCreate(
                [
                    'tenant_id' => $dto->tenantId,
                    'user_id' => $dto->userId,
                ],
                [
                    'role_id' => $dto->roleId,
                    'is_active' => $dto->isActive,
                ]
            );

            $this->logPersistenceService->logAudit(
                action: 'tenant_user.saved',
                auditableType: TenantUser::class,
                auditableId: $tenantUser->id,
                beforeData: null,
                afterData: [
                    'tenant_id' => $tenantUser->tenant_id,
                    'user_id' => $tenantUser->user_id,
                    'role_id' => $tenantUser->role_id,
                    'is_active' => $tenantUser->is_active,
                ],
                userId: auth()->id(),
                userRole: auth()->user()?->role?->code,
            );

            return $tenantUser;
        });
    }
}