<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ServiceLayerDependencyTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo services_depend_only_on_domain_ports_or_peer_services.
     */
    public function services_depend_only_on_domain_ports_or_peer_services(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Services'))
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);

            preg_match_all('/^use\s+(App\\\\[A-Za-z0-9_\\\\]+);/m', $contents, $matches);
            $dependencies = $matches[1] ?? [];

            foreach ($dependencies as $dependency) {
                $usesInfrastructureDirectly = str_starts_with($dependency, 'App\\Infrastructure\\');
                $isAllowed = str_starts_with($dependency, 'App\\Domain\\')
                    || str_starts_with($dependency, 'App\\Services\\');

                $this->assertFalse(
                    $usesInfrastructureDirectly,
                    sprintf(
                        'Service file [%s] must not depend directly on Infrastructure; found [%s].',
                        $fileInfo->getPathname(),
                        $dependency
                    )
                );

                $this->assertTrue(
                    $isAllowed,
                    sprintf(
                        'Service file [%s] must depend only on Domain ports/entities or peer Services; found [%s].',
                        $fileInfo->getPathname(),
                        $dependency
                    )
                );
            }
        }
    }
}
