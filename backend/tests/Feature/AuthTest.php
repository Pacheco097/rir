<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receives_token_and_permissions(): void
    {
        User::factory()->ti()->create(['email' => 'ti@empresa.com']);

        $this->postJson('/api/auth/login', ['email' => 'ti@empresa.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'role', 'permissions']])
            ->assertJsonPath('user.role.value', 'ti');
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'x@empresa.com']);

        $this->postJson('/api/auth/login', ['email' => 'x@empresa.com', 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create(['email' => 'inativo@empresa.com', 'active' => false]);

        $this->postJson('/api/auth/login', ['email' => 'inativo@empresa.com', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/gaps')->assertUnauthorized();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_token_works_for_authenticated_requests(): void
    {
        User::factory()->admin()->create(['email' => 'admin@empresa.com']);
        $token = $this->postJson('/api/auth/login', ['email' => 'admin@empresa.com', 'password' => 'password'])->json('token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('email', 'admin@empresa.com');
    }
}
