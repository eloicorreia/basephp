<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Models\AdminMenuVersion;
use Illuminate\Support\Facades\DB;

final class AdminMenuVersionService
{
    public function currentVersion(): int
    {
        $version = AdminMenuVersion::query()
            ->whereKey(1)
            ->value('version');

        return is_numeric($version) ? (int) $version : 1;
    }

    public function increment(?int $userId = null): int
    {
        return DB::transaction(function () use ($userId): int {
            $menuVersion = AdminMenuVersion::query()
                ->whereKey(1)
                ->lockForUpdate()
                ->first();

            if (! $menuVersion instanceof AdminMenuVersion) {
                $menuVersion = AdminMenuVersion::query()->create([
                    'id' => 1,
                    'version' => 1,
                ]);
            }

            $menuVersion->forceFill([
                'version' => $menuVersion->version + 1,
                'changed_by' => $userId,
                'changed_at' => now(),
            ])->save();

            return $menuVersion->version;
        });
    }
}
