<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\CourseClass;
use App\Models\ClassSession;
use Database\Seeders\RolesAndPermissionsSeeder;

class CoachTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function getCoachUser()
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');
        return $coach;
    }

    public function test_coach_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->getCoachUser())->get(route('coach.dashboard'));
        $response->assertStatus(200);
    }

    public function test_coach_can_view_sessions(): void
    {
        $response = $this->actingAs($this->getCoachUser())->get(route('coach.sessions.index'));
        $response->assertStatus(200);
    }

    public function test_coach_can_view_assignments(): void
    {
        $response = $this->actingAs($this->getCoachUser())->get(route('coach.assignments.index'));
        $response->assertStatus(200);
    }

    public function test_coach_can_create_assignment(): void
    {
        $coach = $this->getCoachUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($coach->id, ['role' => 'coach']);

        $response = $this->actingAs($coach)->post(route('coach.assignments.store'), [
            'course_class_id' => $class->id,
            'title' => 'Test Assignment',
            'description' => 'Test description',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'type' => 'text'
        ]);

        $response->assertRedirect(route('coach.assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'title' => 'Test Assignment',
            'type' => 'text'
        ]);
    }

    public function test_coach_cannot_create_session_when_quota_reached(): void
    {
        $coach = $this->getCoachUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($coach->id, ['role' => 'coach']);

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
            'status' => 'completed',
        ]);

        $response = $this->actingAs($coach)->post(route('coach.sessions.store'), [
            'course_class_id' => $class->id,
            'start_time' => '2026-06-12 10:00:00',
            'end_time' => '2026-06-12 12:00:00',
            'progress' => 'Chapitre 2',
        ]);

        $response->assertSessionHasErrors('course_class_id');
        $this->assertSame(1, ClassSession::where('course_class_id', $class->id)->count());
    }

    public function test_coach_can_view_payments(): void
    {
        $response = $this->actingAs($this->getCoachUser())->get(route('coach.payments.index'));
        $response->assertStatus(200);
    }

    public function test_coach_can_view_reports_when_empty(): void
    {
        $response = $this->actingAs($this->getCoachUser())->get(route('coach.reports.index'));
        $response->assertStatus(200);
    }

    public function test_coach_can_view_reports_with_data(): void
    {
        $coach = $this->getCoachUser();
        $student = User::factory()->create();
        $student->assignRole('student');

        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class', 'location' => 'Salle Test']);
        $class->users()->attach($coach->id, ['role' => 'coach']);
        $class->users()->attach($student->id, ['role' => 'student']);

        $session = ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHours(2),
            'status' => 'completed',
            'intervention_type' => 'in_person',
        ]);

        \App\Models\SessionReport::factory()->create([
            'class_session_id' => $session->id,
            'progress' => 'Chapitre 1 terminé',
        ]);

        \App\Models\Attendance::factory()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'is_present' => true,
        ]);

        $response = $this->actingAs($coach)->get(route('coach.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Chapitre 1 terminé');
    }
}
