<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_inactive_account_cannot_authenticate(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_session_is_terminated_when_account_is_deactivated_mid_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');

        $premiereVisite = $this->actingAs($user)->get(route('student.dashboard'));
        $premiereVisite->assertStatus(200);

        // Le compte est desactive pendant que la session est deja ouverte :
        // la requete suivante doit couper l'acces sans attendre l'expiration
        // de la session.
        $user->update(['status' => 'inactive']);

        $secondeVisite = $this->get(route('student.dashboard'));

        $secondeVisite->assertRedirect(route('login'));
        $secondeVisite->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
