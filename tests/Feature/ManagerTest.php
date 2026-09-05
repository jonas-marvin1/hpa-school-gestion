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

    private function getAdminUser()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
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

    public function test_admin_can_delete_many_pending_and_cancelled_payments(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Prog Suppression']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Level Suppression']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Suppression']);
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $pending = Payment::factory()->create([
            'coach_id' => $coach->id, 'status' => 'pending',
            'month' => now()->month, 'year' => now()->year,
            'total_sessions' => 1, 'total_amount' => 500,
        ]);
        $cancelled = Payment::factory()->create([
            'coach_id' => $coach->id, 'status' => 'cancelled',
            'month' => now()->month, 'year' => now()->year,
            'total_sessions' => 1, 'total_amount' => 500,
        ]);

        $sessionPending = ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'payment_id' => $pending->id,
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'status' => 'validated',
            'intervention_type' => 'in_person',
            'amount' => 500,
        ]);
        $sessionCancelled = ClassSession::factory()->create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'payment_id' => $cancelled->id,
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'status' => 'validated',
            'intervention_type' => 'in_person',
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->getAdminUser())->post(route('manager.payments.destroyMany'), [
            'payment_ids' => [$pending->id, $cancelled->id],
        ]);

        $response->assertRedirect(route('manager.payments.index'));
        $this->assertDatabaseMissing('payments', ['id' => $pending->id]);
        $this->assertDatabaseMissing('payments', ['id' => $cancelled->id]);
        $this->assertNull($sessionPending->fresh()->payment_id);
        $this->assertNull($sessionCancelled->fresh()->payment_id);
    }

    public function test_admin_deleting_many_payments_ignores_paid_ones(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $pending = Payment::factory()->create([
            'coach_id' => $coach->id, 'status' => 'pending',
            'month' => now()->month, 'year' => now()->year,
            'total_sessions' => 1, 'total_amount' => 500,
        ]);
        $paid = Payment::factory()->create([
            'coach_id' => $coach->id, 'status' => 'paid',
            'month' => now()->month, 'year' => now()->year,
            'total_sessions' => 1, 'total_amount' => 500,
        ]);

        $response = $this->actingAs($this->getAdminUser())->post(route('manager.payments.destroyMany'), [
            'payment_ids' => [$pending->id, $paid->id],
        ]);

        $response->assertRedirect(route('manager.payments.index'));
        $response->assertSessionHas('status');
        $this->assertStringContainsString('payée', session('status'));
        $this->assertDatabaseMissing('payments', ['id' => $pending->id]);
        $this->assertDatabaseHas('payments', ['id' => $paid->id]);
    }

    public function test_manager_cannot_delete_many_payments(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');
        $payment = Payment::factory()->create([
            'coach_id' => $coach->id, 'status' => 'pending',
            'month' => now()->month, 'year' => now()->year,
            'total_sessions' => 1, 'total_amount' => 500,
        ]);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.payments.destroyMany'), [
            'payment_ids' => [$payment->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_delete_many_payments_requires_payment_ids(): void
    {
        $response = $this->actingAs($this->getAdminUser())->post(route('manager.payments.destroyMany'), []);

        $response->assertSessionHasErrors('payment_ids');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_manager_can_create_assignment_for_whole_class(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.assignments.store'), [
            'course_class_id' => $class->id,
            'title' => 'Devoir gestionnaire',
            'description' => 'Description',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'type' => 'text',
        ]);

        $response->assertRedirect(route('manager.assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'title' => 'Devoir gestionnaire',
            'student_id' => null,
        ]);
    }

    public function test_manager_can_create_individual_assignment(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $eleve = User::factory()->create();
        $eleve->assignRole('student');
        $class->users()->attach($eleve->id, ['role' => 'student']);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.assignments.store'), [
            'course_class_id' => $class->id,
            'student_id' => $eleve->id,
            'title' => 'Devoir individuel',
            'description' => 'Description',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'type' => 'text',
        ]);

        $response->assertRedirect(route('manager.assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'title' => 'Devoir individuel',
            'student_id' => $eleve->id,
        ]);
    }

    public function test_manager_cannot_attribute_assignment_to_student_of_another_class(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $autreClasse = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Autre Class']);
        $eleveHorsClasse = User::factory()->create();
        $eleveHorsClasse->assignRole('student');
        $autreClasse->users()->attach($eleveHorsClasse->id, ['role' => 'student']);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.assignments.store'), [
            'course_class_id' => $class->id,
            'student_id' => $eleveHorsClasse->id,
            'title' => 'Devoir invalide',
            'description' => 'Description',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'type' => 'text',
        ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertDatabaseMissing('assignments', ['title' => 'Devoir invalide']);
    }

    public function test_manager_can_edit_and_delete_a_coach_created_assignment(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);

        $assignment = \App\Models\Assignment::create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir du coach',
            'description' => 'Description',
            'due_date' => now()->addDays(5),
            'type' => 'text',
        ]);

        $manager = $this->getManagerUser();

        $this->actingAs($manager)->put(route('manager.assignments.update', $assignment), [
            'course_class_id' => $class->id,
            'title' => 'Devoir modifie par la gestionnaire',
            'description' => 'Description',
            'due_date' => now()->addDays(6)->format('Y-m-d'),
            'type' => 'text',
        ])->assertRedirect(route('manager.assignments.index'));

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Devoir modifie par la gestionnaire',
        ]);

        $this->actingAs($manager)->delete(route('manager.assignments.destroy', $assignment))
            ->assertRedirect(route('manager.assignments.index'));

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_manager_can_grade_a_submission(): void
    {
        $program = \App\Models\Program::factory()->create(['name' => 'Test Prog']);
        $level = \App\Models\Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $eleve = User::factory()->create();
        $eleve->assignRole('student');
        $class->users()->attach($eleve->id, ['role' => 'student']);

        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $assignment = \App\Models\Assignment::create([
            'course_class_id' => $class->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir a corriger',
            'description' => 'Description',
            'due_date' => now()->addDays(5),
            'type' => 'text',
        ]);

        $submission = \App\Models\Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $eleve->id,
            'content_text' => 'Ma reponse',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->getManagerUser())->post(route('manager.evaluations.store', $submission), [
            'score' => 15,
            'feedback' => 'Bon travail',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 15,
        ]);
    }
}
