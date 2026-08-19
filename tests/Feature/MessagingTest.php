<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_coach_message_form_has_no_recipient_picker(): void
    {
        $coach = $this->makeUser('coach');

        $response = $this->actingAs($coach)->get(route('messages.create'));

        $response->assertStatus(200);
        $response->assertSee("ensemble des administrateurs et gestionnaires");
        $response->assertDontSee('Sélectionnez un destinataire');
    }

    public function test_coach_message_is_broadcast_to_every_admin_and_manager(): void
    {
        $coach = $this->makeUser('coach');
        $admin = $this->makeUser('admin');
        $manager1 = $this->makeUser('manager');
        $manager2 = $this->makeUser('manager');

        $response = $this->actingAs($coach)->post(route('messages.store'), [
            'subject' => 'Besoin d\'aide',
            'body' => 'Ceci est un test.',
        ]);

        $response->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('messages', ['sender_id' => $coach->id, 'receiver_id' => $admin->id, 'subject' => 'Besoin d\'aide']);
        $this->assertDatabaseHas('messages', ['sender_id' => $coach->id, 'receiver_id' => $manager1->id, 'subject' => 'Besoin d\'aide']);
        $this->assertDatabaseHas('messages', ['sender_id' => $coach->id, 'receiver_id' => $manager2->id, 'subject' => 'Besoin d\'aide']);
        $this->assertSame(3, Message::where('subject', 'Besoin d\'aide')->count());

        $this->actingAs($admin)->get(route('messages.index'))->assertSee('Besoin d\'aide');
        $this->actingAs($manager2)->get(route('messages.index'))->assertSee('Besoin d\'aide');
    }

    public function test_admin_message_goes_to_single_selected_recipient(): void
    {
        $admin = $this->makeUser('admin');
        $coach = $this->makeUser('coach');
        $manager = $this->makeUser('manager');

        $response = $this->actingAs($admin)->post(route('messages.store'), [
            'receiver_id' => $coach->id,
            'subject' => 'Info planning',
            'body' => 'Ceci est un test.',
        ]);

        $response->assertRedirect(route('messages.index'));
        $this->assertSame(1, Message::where('subject', 'Info planning')->count());
        $this->assertDatabaseHas('messages', ['sender_id' => $admin->id, 'receiver_id' => $coach->id]);
        $this->assertDatabaseMissing('messages', ['sender_id' => $admin->id, 'receiver_id' => $manager->id]);
    }
}
