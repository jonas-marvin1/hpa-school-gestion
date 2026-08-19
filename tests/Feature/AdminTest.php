<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Program;
use App\Models\Level;
use App\Models\CourseClass;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function getAdminUser()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_users(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->getAdminUser())->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'coach',
            'status' => 'active'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'testuser@example.com']);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = $this->actingAs($this->getAdminUser())->put(route('admin.users.update', $user->id), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'coach',
            'status' => 'active'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'updated@example.com', 'name' => 'Updated Name']);
        $this->assertTrue($user->fresh()->hasRole('coach'));
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = $this->actingAs($this->getAdminUser())->delete(route('admin.users.destroy', $user->id));
        
        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_view_programs(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.programs.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_program(): void
    {
        $response = $this->actingAs($this->getAdminUser())->post(route('admin.programs.store'), [
            'name' => 'Test Program',
            'description' => 'A program description'
        ]);

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('programs', ['name' => 'Test Program']);
    }

    public function test_admin_can_view_levels(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.levels.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_level(): void
    {
        $program = Program::factory()->create(['name' => 'Test Program']);
        
        $response = $this->actingAs($this->getAdminUser())->post(route('admin.levels.store'), [
            'program_id' => $program->id,
            'name' => 'Level 1',
            'order' => 1
        ]);

        $response->assertRedirect(route('admin.levels.index'));
        $this->assertDatabaseHas('levels', ['name' => 'Level 1', 'program_id' => $program->id]);
    }

    public function test_admin_can_view_classes(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.classes.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_class(): void
    {
        $program = Program::factory()->create(['name' => 'Test Program']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Level 1']);

        $response = $this->actingAs($this->getAdminUser())->post(route('admin.classes.store'), [
            'level_id' => $level->id,
            'name' => 'Class A',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d')
        ]);

        $response->assertRedirect(route('admin.classes.index'));
        $this->assertDatabaseHas('course_classes', ['name' => 'Class A']);
    }
}
