<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_complete_profile_setup(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/setup', [
            'name' => 'demo',
            'gender' => 'female',
            'language' => 'English',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile setup completed successfully',
                'user' => [
                    'name' => 'demo',
                    'gender' => 'Female',
                    'language' => 'English',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'demo',
            'gender' => 'Female',
            'language' => 'English',
        ]);
    }

    public function test_profile_setup_route_exists_before_authentication(): void
    {
        $response = $this->postJson('/api/profile/setup', [
            'name' => 'demo',
            'gender' => 'female',
            'language' => 'English',
        ]);

        $response->assertUnauthorized();
    }
}
