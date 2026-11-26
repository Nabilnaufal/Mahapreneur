<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentMethodsTest extends TestCase
{
    public function test_only_configured_doku_methods_are_offered(): void
    {
        config([
            'mahapreneur.doku.client_id' => 'BRN-TEST',
            'mahapreneur.doku.secret_key' => 'SK-TEST',
            'mahapreneur.doku.methods' => ['OVO', 'DANA'],
        ]);

        $this->getJson('/api/payments/methods')
            ->assertOk()
            ->assertJsonPath('data.dokuConfigured', true)
            ->assertJsonPath('data.methods.0.code', 'OVO')
            ->assertJsonPath('data.methods.1.code', 'DANA')
            ->assertJsonPath('data.methods.2.code', 'COD');
    }

    public function test_doku_methods_are_hidden_without_credentials(): void
    {
        config([
            'mahapreneur.doku.client_id' => null,
            'mahapreneur.doku.secret_key' => null,
        ]);

        $this->getJson('/api/payments/methods')
            ->assertOk()
            ->assertJsonPath('data.dokuConfigured', false)
            ->assertJsonCount(1, 'data.methods')
            ->assertJsonPath('data.methods.0.code', 'COD');
    }
}
