<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\CourseClass;
use App\Models\ClassSession;
use App\Models\Payment;
use Database\Seeders\RolesAndPermissionsSeeder;

class ManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function getManagerUser()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        return $manager;
    }

    public function test_manager_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->getManagerUser())->get(route('manager.dashboard'));
        $response->assertStatus(200);
    }

    public function test_manager_can_view_classes(): void
    {
        $response = $this->actingAs($this->getManagerUser())->get(route('manager.classes.index'));
        $response->assertStatus(200);
    }

    public function test_manager_can_view_sessions(): void
    {
        $response = $this->actingAs($this->getManagerUser())->get(route('manager.sessions.index'));
        $response->assertStatus(200);
    }

    public function test_manager_can_create_session(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $coach = User::factory()->create();
        $coach->assignRole('coach');
        
        $response = $this->actingAs($this->getManagerUser())->post(route('manager.sessions.store'), [
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '2026-06-10 10:00:00',
            'end_time' => '2026-06-10 12:00:00',
            'intervention_type' => 'in_person',
            'amount' => 100,
            'status' => 'scheduled'
        ]);

        $response->assertRedirect(route('manager.sessions.index'));
        $this->assertDatabaseHas('class_sessions', [
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'status' => 'scheduled'
        ]);
    }

    public function test_manager_cannot_create_session_when_quota_reached(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        \App\Models\SessionQuota::create([
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 1,
        ]);

        ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-05 10:00:00',
            'end_time' => '2026-06-05 12:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.sessions.store'), [
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-10 10:00:00',
            'end_time' => '2026-06-10 12:00:00',
            'intervention_type' => 'in_person',
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('course_class_id');
        $this->assertSame(1, ClassSession::where('course_class_id', $class->id)->count());
    }

    public function test_manager_cannot_move_session_into_full_month(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        \App\Models\SessionQuota::create([
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 1,
        ]);

        // Le mois de juin est deja plein avec cette session.
        ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-05 10:00:00',
            'end_time' => '2026-06-05 12:00:00',
            'status' => 'scheduled',
        ]);

        // Une autre session, programmee en juillet, que le manager tente de
        // deplacer vers juin : le quota doit bloquer le deplacement, pas
        // seulement la creation.
        $sessionADeplacer = ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-07-05 10:00:00',
            'end_time' => '2026-07-05 12:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->getManagerUser())->put(route('manager.sessions.update', $sessionADeplacer), [
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-20 10:00:00',
            'end_time' => '2026-06-20 12:00:00',
            'intervention_type' => 'in_person',
            'amount' => 100,
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('course_class_id');
        $this->assertSame('2026-07-05 10:00:00', $sessionADeplacer->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    public function test_manager_can_reschedule_session_without_self_blocking(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        \App\Models\SessionQuota::create([
            'course_class_id' => $class->id,
            'year' => 2026,
            'month' => 6,
            'quota' => 1,
        ]);

        // Seule session du mois : c'est elle-meme qu'on deplace. Sans
        // l'exclusion, elle se bloquerait en se comptant deux fois.
        $session = ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-05 10:00:00',
            'end_time' => '2026-06-05 12:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->getManagerUser())->put(route('manager.sessions.update', $session), [
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '2026-06-20 15:00:00',
            'end_time' => '2026-06-20 17:00:00',
            'intervention_type' => 'in_person',
            'amount' => 100,
            'status' => 'scheduled',
        ]);

        $response->assertRedirect(route('manager.sessions.index'));
        $this->assertSame('2026-06-20 15:00:00', $session->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    public function test_manager_can_view_payments(): void
    {
        $response = $this->actingAs($this->getManagerUser())->get(route('manager.payments.index'));
        $response->assertStatus(200);
    }

    public function test_manager_can_export_sessions_csv(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Export']);
        $coach = User::factory()->create(['name' => 'Coach Export']);
        $coach->assignRole('coach');

        ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'status' => 'scheduled',
            'intervention_type' => 'in_person',
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->getManagerUser())->get(route('manager.sessions.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Classe Export', $csv);
        $this->assertStringContainsString('Coach Export', $csv);
    }

    public function test_manager_can_export_payments_csv(): void
    {
        $coach = User::factory()->create(['name' => 'Coach Paie']);
        $coach->assignRole('coach');

        Payment::factory()->create([
            'coach_id' => $coach->id,
            'month' => now()->month,
            'year' => now()->year,
            'total_sessions' => 1,
            'total_amount' => 500,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->getManagerUser())->get(route('manager.payments.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Coach Paie', $csv);
        $this->assertStringContainsString('500,00', $csv);
    }

    public function test_manager_can_create_payment(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.payments.store'), [
            'coach_id' => $coach->id,
            'month' => now()->month,
            'year' => now()->year
        ]);

        $response->assertRedirect(route('manager.payments.index'));
    }
}
