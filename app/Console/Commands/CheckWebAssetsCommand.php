<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CheckWebAssetsCommand extends Command
{
    protected $signature = 'web:assets:check';

    protected $description = 'Validate the admin web static asset and Blade contract.';

    public function handle(): int
    {
        if (! (bool) config('admin_web.enabled', true)) {
            $this->components->info('Módulo web administrativo desabilitado.');

            return self::SUCCESS;
        }

        $assetPath = (string) config('admin_web.template.asset_path');
        $basePath = public_path($assetPath);
        $requiredAssets = config('admin_web.template.required_assets', []);

        if (! is_array($requiredAssets) || $requiredAssets === []) {
            $this->components->error('Nenhum asset obrigatório foi configurado em admin_web.template.required_assets.');

            return self::FAILURE;
        }

        if (! File::isDirectory($basePath)) {
            $this->components->error(sprintf('Diretório de assets web não encontrado: %s', $assetPath));

            return self::FAILURE;
        }

        foreach ($requiredAssets as $requiredAsset) {
            if (! is_string($requiredAsset) || $requiredAsset === '') {
                $this->components->error('Asset obrigatório inválido em admin_web.template.required_assets.');

                return self::FAILURE;
            }

            if (! File::exists($basePath.DIRECTORY_SEPARATOR.$requiredAsset)) {
                $this->components->error(sprintf('Asset web obrigatório não encontrado: %s/%s', $assetPath, $requiredAsset));

                return self::FAILURE;
            }
        }

        foreach ($this->adminBladeFiles() as $bladeFile) {
            $contents = File::get($bladeFile);

            if (str_contains($contents, '@vite')) {
                $this->components->error(sprintf(
                    'Views administrativas devem usar os assets estáticos do template: %s',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $bladeFile)
                ));

                return self::FAILURE;
            }
        }

        $this->components->info('Contrato de assets web administrativos validado.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function adminBladeFiles(): array
    {
        $files = [];

        foreach ([resource_path('views/admin')] as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                $files[] = $file->getPathname();
            }
        }

        foreach ([
            resource_path('views/layouts/admin.blade.php'),
            resource_path('views/layouts/admin-auth.blade.php'),
        ] as $file) {
            if (File::exists($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
