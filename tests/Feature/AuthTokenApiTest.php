<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuthTokenApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_create_api_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'postman',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Token created successfully.')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));

        $activity = Activity::query()
            ->where('event', 'token.created')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('audit', $activity->log_name);
        $this->assertSame('API token created', $activity->description);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame('postman', $activity->getExtraProperty('token_name'));
        $this->assertSame('POST', $activity->getExtraProperty('method'));
        $this->assertSame('api/auth/token', $activity->getExtraProperty('path'));
        $this->assertNotEmpty($activity->getExtraProperty('request_id'));
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_rejects_invalid_credentials_when_creating_api_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_error');
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_revoke_current_api_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('postman')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/auth/token');

        $response->assertOk()
            ->assertJsonPath('message', 'Token revoked successfully.');

        $this->assertCount(0, $user->fresh()->tokens);

        $activity = Activity::query()
            ->where('event', 'token.revoked')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('audit', $activity->log_name);
        $this->assertSame('API token revoked', $activity->description);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame('postman', $activity->getExtraProperty('token_name'));
        $this->assertSame('DELETE', $activity->getExtraProperty('method'));
        $this->assertSame('api/auth/token', $activity->getExtraProperty('path'));
        $this->assertNotEmpty($activity->getExtraProperty('request_id'));
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_authenticated_user_profile_includes_permissions_payload(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('profile')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.is_admin', true)
            ->assertJsonPath('data.permissions.delete_quotations', true);
    }
}
