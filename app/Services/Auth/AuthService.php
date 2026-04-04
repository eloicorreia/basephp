<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Tentativa de troca de senha com senha atual inválida.',
                category: 'auth',
                operation: 'change_password_invalid_current',
                userId: $user->id,
                httpStatus: 422,
                processingStatus: 'denied',
            );

            throw ValidationException::withMessages([
                'current_password' => ['A senha atual informada é inválida.'],
            ]);
        }

        $before = [
            'must_change_password' => $user->must_change_password,
        ];

        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
        ]);

        $user->save();

        $this->logPersistenceService->logAudit(
            action: 'user.password.changed',
            auditableType: User::class,
            auditableId: $user->id,
            beforeData: $before,
            afterData: [
                'must_change_password' => false,
            ],
            userId: $user->id,
            userRole: $user->role?->code,
        );
    }
}