<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantPasswordPolicy;
use App\Models\User;
use App\Models\UserPasswordHistory;
use Illuminate\Support\Facades\Hash;

final class TenantPasswordValidatorService
{
    /**
     * @return list<string>
     */
    public function validate(string $password, TenantPasswordPolicy $policy, ?User $user = null): array
    {
        $errors = [];

        if (mb_strlen($password) < $policy->min_length) {
            $errors[] = sprintf('A senha deve ter pelo menos %d caracteres.', $policy->min_length);
        }

        if (mb_strlen($password) > $policy->max_length) {
            $errors[] = sprintf('A senha deve ter no máximo %d caracteres.', $policy->max_length);
        }

        if ($policy->require_uppercase && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter ao menos uma letra maiúscula.';
        }

        if ($policy->require_lowercase && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter ao menos uma letra minúscula.';
        }

        if ($policy->require_numbers && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter ao menos um número.';
        }

        if ($policy->require_symbols && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'A senha deve conter ao menos um símbolo.';
        }

        if ($policy->disallow_common_passwords && in_array(mb_strtolower($password), $this->commonPasswords(), true)) {
            $errors[] = 'A senha informada é muito comum.';
        }

        if ($user instanceof User && $policy->disallow_user_personal_data) {
            $normalized = mb_strtolower($password);
            foreach ($this->personalDataFragments($user) as $value) {
                if (mb_strlen((string) $value) >= 4 && str_contains($normalized, mb_strtolower((string) $value))) {
                    $errors[] = 'A senha não pode conter dados pessoais do usuário.';
                    break;
                }
            }
        }

        if ($user instanceof User && $policy->password_history_count > 0) {
            $histories = UserPasswordHistory::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit($policy->password_history_count)
                ->pluck('password_hash');

            foreach ($histories as $hash) {
                if (is_string($hash) && Hash::check($password, $hash)) {
                    $errors[] = sprintf('A senha não pode repetir uma das últimas %d senhas.', $policy->password_history_count);
                    break;
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return list<string>
     */
    private function commonPasswords(): array
    {
        return [
            '123456',
            '12345678',
            'password',
            'senha',
            'admin',
            'qwerty',
            'temporary-password',
        ];
    }

    /**
     * @return list<string>
     */
    private function personalDataFragments(User $user): array
    {
        $fragments = [];

        foreach (preg_split('/\s+/', (string) $user->name) ?: [] as $part) {
            $fragments[] = $part;
        }

        if (is_string($user->email) && str_contains($user->email, '@')) {
            [$localPart] = explode('@', $user->email, 2);
            $fragments[] = $localPart;
        }

        $fragments[] = $user->name;
        $fragments[] = $user->email;

        return array_values(array_filter(array_unique($fragments), static fn (string $value): bool => $value !== ''));
    }
}
