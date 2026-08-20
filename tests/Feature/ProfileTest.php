<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_student_sees_name_as_locked_on_profile_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $student = User::factory()->create(['name' => 'Nom Officiel']);
        $student->assignRole('student');

        $response = $this->actingAs($student)->get('/profile');

        $response->assertOk();
        $response->assertSee('Nom Officiel');
        $response->assertSee('non modifiable');
        $response->assertDontSee('name="name"', false);
    }

    public function test_student_cannot_change_their_name(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $student = User::factory()->create(['name' => 'Nom Officiel']);
        $student->assignRole('student');

        $response = $this
            ->actingAs($student)
            ->patch('/profile', [
                // Un Student qui forgerait le champ "name" ne doit pas
                // pouvoir changer l'identifiant defini par l'Admin.
                'name'       => 'Nom Falsifié',
                'email'      => $student->email,
                'full_phone' => '+33612345678',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $student->refresh();

        $this->assertSame('Nom Officiel', $student->name);
        $this->assertSame('+33612345678', $student->phone);
    }

    public function test_non_student_can_still_change_their_name(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $coach = User::factory()->create(['name' => 'Ancien Nom']);
        $coach->assignRole('coach');

        $response = $this
            ->actingAs($coach)
            ->patch('/profile', [
                'name'  => 'Nouveau Nom',
                'email' => $coach->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Nouveau Nom', $coach->fresh()->name);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
