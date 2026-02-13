<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ActionConventionTest extends TestCase
{
    #[Test]
    /**
     * Executa a rotina principal do metodo action_files_and_classes_use_action_suffix.
     */
    public function action_files_and_classes_use_action_suffix(): void
    {
        foreach ($this->actionFiles() as $fileInfo) {
            $filename = $fileInfo->getFilename();

            $this->assertStringEndsWith(
                'Action.php',
                $filename,
                sprintf('Action file [%s] must use the *Action.php suffix.', $fileInfo->getPathname())
            );

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);

            preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)\b/', $contents, $matches);
            $className = $matches[1] ?? null;

            $this->assertNotNull(
                $className,
                sprintf('Action file [%s] must declare a class.', $fileInfo->getPathname())
            );

            $expectedClassName = pathinfo($filename, PATHINFO_FILENAME);

            $this->assertSame(
                $expectedClassName,
                $className,
                sprintf('Action class in [%s] must match its filename.', $fileInfo->getPathname())
            );
            $this->assertStringEndsWith(
                'Action',
                $className,
                sprintf('Action class [%s] must use the *Action suffix.', $className)
            );
        }
    }

    #[Test]
    /**
     * Executa a rotina principal do metodo actions_expose_only_invoke_as_public_method.
     */
    public function actions_expose_only_invoke_as_public_method(): void
    {
        foreach ($this->actionFiles() as $fileInfo) {
            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertIsString($contents);

            $publicMethods = $this->publicMethodNames($contents);
            $publicMethods = array_values(array_filter(
                $publicMethods,
                static fn (string $methodName): bool => $methodName !== '__construct'
            ));

            $this->assertSame(
                ['__invoke'],
                $publicMethods,
                sprintf(
                    'Action [%s] must expose only __invoke as public method; found [%s].',
                    $fileInfo->getPathname(),
                    implode(', ', $publicMethods)
                )
            );
        }
    }

    /**
     * Executa a rotina principal do metodo actionFiles.
     */
    /**
     * @return array<int, SplFileInfo>
     */
    private function actionFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path('Actions'))
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
     * Executa a rotina principal do metodo publicMethodNames.
     */
    /**
     * @return array<int, string>
     */
    private function publicMethodNames(string $contents): array
    {
        preg_match_all('/public\s+(?:static\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $contents, $matches);

        return $matches[1] ?? [];
    }
}
