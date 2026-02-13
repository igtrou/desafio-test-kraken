<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ActionModelDependencyTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo actions_do_not_depend_on_models_directly.
     */
    public function actions_do_not_depend_on_models_directly(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Actions'))
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);
            $this->assertStringNotContainsString(
                'use App\\Models\\',
                $contents,
                sprintf(
                    'Action [%s] must depend on Services instead of Models.',
                    $fileInfo->getPathname()
                )
            );
        }
    }
}
