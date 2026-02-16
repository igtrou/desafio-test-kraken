<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Define defaults estaveis de teste para evitar dependencia do .env local.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gateway.enforce_source' => false,
        ]);
    }
}
