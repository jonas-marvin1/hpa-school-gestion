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

    public function test_coach_can_create_individual_assignment(): void
    {
        $coach = $this->getCoachUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($coach->id, ['role' => 'coach']);

        $eleve = User::factory()->create();
        $eleve->assignRole('student');
        $class->users()->attach($eleve->id, ['role' => 'student']);

        $response = $this->actingAs($coach)->post(route('coach.assignments.store'), [
            'course_class_id' => $class->id,
            'student_id' => $eleve->id,
            'title' => 'Devoir individuel',
            'description' => 'Test description',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'type' => 'text',
        ]);

        $response->assertRedirect(route('coach.assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'title' => 'Devoir individuel',
            'student_id' => $eleve->id,
        ]);
    }

    public function test_coach_cannot_edit_update_or_delete_an_assignment_from_another_class(): void
    {
        $coach = $this->getCoachUser();
        $autreCoach = $this->getCoachUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($autreCoach->id, ['role' => 'coach']);

        $assignment = \App\Models\Assignment::create([
            'course_class_id' => $class->id,
            'coach_id' => $autreCoach->id,
            'title' => 'Devoir hors classe',
            'description' => 'Description',
            'due_date' => now()->addDays(5),
            'type' => 'text',
        ]);

        $this->actingAs($coach)->get(route('coach.assignments.edit', $assignment))->assertStatus(403);

        $this->actingAs($coach)->put(route('coach.assignments.update', $assignment), [
            'course_class_id' => $class->id,
            'title' => 'Modifie',
            'description' => 'Description',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'type' => 'text',
        ])->assertStatus(403);

        $this->actingAs($coach)->delete(route('coach.assignments.destroy', $assignment))->assertStatus(403);

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'title' => 'Devoir hors classe']);
    }

    public function test_coach_cannot_view_evaluations_for_a_class_that_is_not_theirs(): void
    {
        $coach = $this->getCoachUser();
        $autreCoach = $this->getCoachUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($autreCoach->id, ['role' => 'coach']);

        ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $autreCoach->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
        ]);

        $assignment = \App\Models\Assignment::create([
            'course_class_id' => $class->id,
            'coach_id' => $autreCoach->id,
            'title' => 'Devoir hors classe',
            'description' => 'Description',
            'due_date' => now()->addDays(5),
            'type' => 'text',
        ]);

        $response = $this->actingAs($coach)->get(route('coach.evaluations.index', $assignment));

        $response->assertStatus(403);
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
