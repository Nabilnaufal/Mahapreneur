<?php

namespace Tests\Feature;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_verify_email_and_login(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Alya Putri',
            'email' => 'Alya@Example.test',
            'password' => 'Rahasia123',
            'confirmPassword' => 'Rahasia123',
            'role' => 'CUSTOMER',
        ])->assertOk()->assertJsonPath('data.user.email', 'alya@example.test');

        $user = User::where('email', 'alya@example.test')->firstOrFail();
        $token = EmailVerificationToken::where('user_id', $user->id)->value('token');

        $this->postJson('/api/auth/verify-email', ['token' => $token])
            ->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'alya@example.test',
            'password' => 'Rahasia123',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'CUSTOMER')
            ->assertCookie(config('mahapreneur.auth_cookie'));
    }

    public function test_login_is_rejected_before_email_verification(): void
    {
        $user = User::create([
            'name' => 'Belum Verifikasi',
            'email' => 'pending@example.test',
            'password' => 'Rahasia123',
            'role' => 'CUSTOMER',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Rahasia123',
        ])->assertStatus(403)->assertJsonPath('errors.code', 'EMAIL_NOT_VERIFIED');
    }
}
