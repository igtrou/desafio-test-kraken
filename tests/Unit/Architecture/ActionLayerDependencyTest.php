<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ActionLayerDependencyTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo actions_do_not_depend_on_domain_layer_directly.
     */
    public function actions_do_not_depend_on_domain_layer_directly(): void
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
                'use App\\Domain\\',
                $contents,
                sprintf(
                    'Action [%s] must depend on Services instead of Domain.',
                    $fileInfo->getPathname()
                )
            );
        }
    }

    #[Test]
    /**
     * Executa a rotina principal do metodo actions_do_not_depend_on_framework_classes_directly.
     */
    public function actions_do_not_depend_on_framework_classes_directly(): void
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
                'Illuminate\\',
                $contents,
                sprintf(
                    'Action [%s] must not depend on Illuminate framework classes.',
                    $fileInfo->getPathname()
                )
            );
            $this->assertStringNotContainsString(
                'Laravel\\',
                $contents,
                sprintf(
                    'Action [%s] must not depend on Laravel framework classes.',
                    $fileInfo->getPathname()
                )
            );
        }
    }
}
