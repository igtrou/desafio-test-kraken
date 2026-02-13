<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DomainLayerDependencyTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo domain_layer_does_not_depend_on_outer_application_layers.
     */
    public function domain_layer_does_not_depend_on_outer_application_layers(): void
    {
        $forbiddenDependencies = [
            'use App\\Actions\\',
            'use App\\Services\\',
            'use App\\Infrastructure\\',
            'use App\\Http\\',
            'use App\\Models\\',
            'use App\\Data\\',
            'use App\\Exceptions\\',
            'use App\\Enums\\',
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Domain'))
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);

            foreach ($forbiddenDependencies as $forbiddenDependency) {
                $this->assertStringNotContainsString(
                    $forbiddenDependency,
                    $contents,
                    sprintf(
                        'Domain file [%s] must not depend on [%s].',
                        $fileInfo->getPathname(),
                        $forbiddenDependency
                    )
                );
            }
        }
    }
}
