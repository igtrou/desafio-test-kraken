<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ControllerLayerDependencyTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo controllers_do_not_depend_on_services_or_models_directly.
     */
    public function controllers_do_not_depend_on_services_or_models_directly(): void
    {
        foreach ($this->controllerFiles() as $fileInfo) {
            if ($this->isExcludedControllerFile($fileInfo)) {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);
            $this->assertStringNotContainsString(
                'use App\\Services\\',
                $contents,
                sprintf('Controller [%s] must use Actions instead of Services.', $fileInfo->getPathname())
            );
            $this->assertStringNotContainsString(
                'use App\\Models\\',
                $contents,
                sprintf('Controller [%s] must not depend directly on Models.', $fileInfo->getPathname())
            );
        }
    }

    #[Test]
    /**
     * Executa a rotina principal do metodo controllers_depend_on_actions_for_application_flow.
     */
    public function controllers_depend_on_actions_for_application_flow(): void
    {
        foreach ($this->controllerFiles() as $fileInfo) {
            if ($this->isExcludedControllerFile($fileInfo)) {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);
            $this->assertStringContainsString(
                'use App\\Actions\\',
                $contents,
                sprintf('Controller [%s] must depend on at least one Action.', $fileInfo->getPathname())
            );
        }
    }

    /**
     * Executa a rotina principal do metodo controllerFiles.
     */
    /**
     * @return array<int, SplFileInfo>
     */
    private function controllerFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Http/Controllers'))
        );

        $files = [];

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $files[] = $fileInfo;
        }

        return $files;
    }

    /**
     * Verifica o estado da condicao avaliada.
     */
    private function isExcludedControllerFile(SplFileInfo $fileInfo): bool
    {
        $path = str_replace('\\', '/', $fileInfo->getPathname());

        return $fileInfo->getFilename() === 'Controller.php'
            || str_contains($path, '/Http/Controllers/Concerns/');
    }
}
