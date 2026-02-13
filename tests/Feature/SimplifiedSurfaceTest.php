<?php

namespace Tests\Feature;

use Tests\TestCase;

class SimplifiedSurfaceTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_root_redirects_to_quotations_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect('/dashboard/quotations');
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_quotations_dashboard_exists_and_portfolio_dashboard_is_removed(): void
    {
        $this->get('/dashboard/quotations')->assertOk();
        $this->get('/dashboard/portfolio')->assertNotFound();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_dashboard_alias_redirects_to_quotations_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/dashboard/quotations');
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_removed_alert_and_portfolio_api_endpoints_return_not_found(): void
    {
        $this->getJson('/api/alerts')->assertNotFound();
        $this->postJson('/api/alerts', [])->assertNotFound();
        $this->getJson('/api/portfolios')->assertNotFound();
        $this->postJson('/api/portfolios', [])->assertNotFound();
    }
}
