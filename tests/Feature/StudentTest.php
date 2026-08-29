<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function getStudentUser()
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        return $student;
    }

    public function test_student_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->getStudentUser())->get(route('student.dashboard'));
        $response->assertStatus(200);
    }

    public function test_student_can_view_assignments(): void
    {
        $response = $this->actingAs($this->getStudentUser())->get(route('student.assignments.index'));
        $response->assertStatus(200);
    }
    public function test_student_can_view_assignment_details(): void
    {
        $student = $this->getStudentUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Program 1']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Level 1']);
        $class = \App\Models\CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Class 1']);
        $class->users()->attach($student->id, ['role' => 'student']);
        
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $assignment = \App\Models\Assignment::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'title' => 'Test Assignment',
            'type' => 'text'
        ]);

        $response = $this->actingAs($student)->get(route('student.assignments.show', $assignment));
        $response->assertStatus(200);
    }

    public function test_student_only_sees_nominative_assignment_addressed_to_them(): void
    {
        $eleveVise = $this->getStudentUser();
        $autreEleve = $this->getStudentUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Program 1']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Level 1']);
        $class = \App\Models\CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Class 1']);
        $class->users()->attach($eleveVise->id, ['role' => 'student']);
        $class->users()->attach($autreEleve->id, ['role' => 'student']);

        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $assignment = \App\Models\Assignment::factory()->create([
            'course_class_id' => $class->id,
            'student_id' => $eleveVise->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir individuel',
            'type' => 'text',
        ]);

        $this->actingAs($eleveVise)->get(route('student.assignments.index'))->assertSee('Devoir individuel');
        $this->actingAs($eleveVise)->get(route('student.assignments.show', $assignment))->assertStatus(200);

        $this->actingAs($autreEleve)->get(route('student.assignments.index'))->assertDontSee('Devoir individuel');
        // Acces direct par URL : sans ce controle, l'autre apprenant y
        // accederait malgre l'absence de l'index (point de vigilance de la
        // fiche du 27/08/2026).
        $this->actingAs($autreEleve)->get(route('student.assignments.show', $assignment))->assertStatus(403);
    }

    public function test_student_can_submit_assignment(): void
    {
        $student = $this->getStudentUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Program 1']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Level 1']);
        $class = \App\Models\CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Class 1']);
        $class->users()->attach($student->id, ['role' => 'student']);
        
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $assignment = \App\Models\Assignment::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'title' => 'Test Assignment',
            'type' => 'text'
        ]);

        $response = $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
            'content' => 'My submission content'
        ]);

        $response->assertRedirect(route('student.assignments.show', $assignment));
        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content_text' => 'My submission content'
        ]);
    }

    public function test_student_can_submit_session_feedback(): void
    {
        $student = $this->getStudentUser();
        $program = \App\Models\Program::factory()->create(['name' => 'Program 1']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Level 1']);
        $class = \App\Models\CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Class 1']);
        $class->users()->attach($student->id, ['role' => 'student']);
        
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $session = \App\Models\ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed'
        ]);

        \App\Models\Attendance::factory()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'is_present' => true
        ]);

        $response = $this->actingAs($student)->post(route('student.sessions.feedback.store', $session), [
            'rating' => 5,
            'feedback' => 'Great session!'
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('attendances', [
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'rating' => 5,
            'feedback' => 'Great session!',
            'feedback_status' => 'pending'
        ]);
    }
}
