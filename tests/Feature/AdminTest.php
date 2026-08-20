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

    public function test_admin_can_export_users_csv(): void
    {
        $coach = User::factory()->create(['name' => 'Alice Export', 'email' => 'alice.export@example.com']);
        $coach->assignRole('coach');
        $student = User::factory()->create(['name' => 'Bob Autre', 'email' => 'bob.autre@example.com']);
        $student->assignRole('student');

        // Le filtre par role doit s'appliquer a l'export comme a l'ecran.
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.users.export', ['role' => 'coach']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Alice Export', $csv);
        $this->assertStringContainsString('alice.export@example.com', $csv);
        $this->assertStringNotContainsString('Bob Autre', $csv);
    }

    public function test_admin_can_view_classes(): void
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('admin.classes.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_export_classes_csv(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Export']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau Export']);
        CourseClass::factory()->create([
            'level_id' => $level->id,
            'name' => 'Classe Export',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ]);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.classes.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Classe Export', $csv);
        $this->assertStringContainsString('Programme Export', $csv);
    }

    public function test_admin_can_export_programs_csv(): void
    {
        Program::factory()->create(['name' => 'Programme CSV', 'description' => 'Une description']);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.programs.export'));

        $response->assertStatus(200);
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Programme CSV', $csv);
        $this->assertStringContainsString('Une description', $csv);
    }

    public function test_admin_can_export_levels_csv(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Parent']);
        Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau CSV', 'order' => 2]);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.levels.export'));

        $response->assertStatus(200);
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Niveau CSV', $csv);
        $this->assertStringContainsString('Programme Parent', $csv);
    }

    public function test_admin_can_export_submissions_csv(): void
    {
        $coach = User::factory()->create(['name' => 'Coach Devoir']);
        $coach->assignRole('coach');
        $student = User::factory()->create(['name' => 'Apprenant Devoir']);
        $student->assignRole('student');

        $program = Program::factory()->create(['name' => 'Programme Devoir']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau Devoir']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Devoir']);

        $assignment = \App\Models\Assignment::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir Export',
            'type' => 'text',
        ]);

        \App\Models\Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content_text' => 'Contenu du rendu',
        ]);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.submissions.export'));

        $response->assertStatus(200);
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Apprenant Devoir', $csv);
        $this->assertStringContainsString('Devoir Export', $csv);
        $this->assertStringContainsString('En attente', $csv);
    }

    public function test_admin_can_view_session_quotas(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Quota']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau Quota']);
        CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Quota']);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.session-quotas.index', ['year' => 2026, 'month' => 6]));

        $response->assertStatus(200);
        $response->assertSee('Classe Quota');
        $response->assertSee('Quota non défini');
    }

    public function test_admin_can_save_session_quota(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Quota']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau Quota']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Quota']);

        $response = $this->actingAs($this->getAdminUser())->post(route('admin.session-quotas.store'), [
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 8,
        ]);

        $response->assertRedirect(route('admin.session-quotas.index', ['year' => 2026, 'month' => 6]));
        $this->assertDatabaseHas('session_quotas', [
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 8,
        ]);

        // Une seconde saisie sur le meme couple classe/mois met a jour la
        // ligne existante plutot que d'en creer une seconde.
        $this->actingAs($this->getAdminUser())->post(route('admin.session-quotas.store'), [
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 10,
        ]);

        $this->assertSame(1, \App\Models\SessionQuota::where('course_class_id', $class->id)->count());
        $this->assertDatabaseHas('session_quotas', ['course_class_id' => $class->id, 'quota' => 10]);
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
