<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_running_api(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
