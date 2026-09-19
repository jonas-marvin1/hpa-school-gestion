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

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.users.edit', $admin));
        $response->assertSessionHasErrors('status');
        $this->assertSame('active', $admin->fresh()->status);
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

    public function test_admin_can_view_expected_payments_for_a_month(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Paiement']);
        $student = User::factory()->create(['name' => 'Apprenant Attendu']);
        $student->assignRole('student');

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'program_id'     => $program->id,
            'total_amount'   => 300000,
            'advance_amount' => 0,
        ]);

        // Echeance payee, dans le mois filtre.
        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'program_id'      => $program->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 100000,
            'due_date'        => '2026-06-05',
            'paid_date'       => '2026-06-03',
            'status'          => 'paid',
        ]);

        // Echeance en retard : non payee, date depassee.
        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'program_id'      => $program->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 50000,
            'due_date'        => '2026-06-10',
            'status'          => 'pending',
        ]);

        // Echeance hors mois filtre : ne doit pas apparaitre.
        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'program_id'      => $program->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 75000,
            'due_date'        => '2026-07-05',
            'status'          => 'pending',
        ]);

        $this->travelTo(\Carbon\Carbon::parse('2026-06-20'));

        $response = $this->actingAs($this->getAdminUser())
            ->get(route('admin.student-payments.index', ['month' => 6, 'year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Apprenant Attendu');
        $response->assertViewHas('totalAttendu', 150000.0);
        $response->assertViewHas('totalRegle', 100000.0);
        $response->assertViewHas('totalEnRetard', 50000.0);
        $response->assertViewHas('totalAVenir', 0.0);

        // L'echeance de juillet ne doit pas polluer le total de juin.
        $paiements = $response->viewData('paiements');
        $this->assertSame(2, $paiements->total());
    }

    public function test_admin_can_filter_expected_payments_by_student_name(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Paiement Filtre']);
        $studentA = User::factory()->create(['name' => 'Alice Paiement']);
        $studentA->assignRole('student');
        $studentB = User::factory()->create(['name' => 'Bob Paiement']);
        $studentB->assignRole('student');

        foreach ([$studentA, $studentB] as $student) {
            \App\Models\StudentPayment::create([
                'student_id' => $student->id,
                'program_id' => $program->id,
                'amount'     => 20000,
                'due_date'   => '2026-06-15',
                'status'     => 'pending',
            ]);
        }

        $response = $this->actingAs($this->getAdminUser())->get(
            route('admin.student-payments.index', ['month' => 6, 'year' => 2026, 'search' => 'Alice'])
        );

        $response->assertStatus(200);
        $response->assertSee('Alice Paiement');
        $response->assertDontSee('Bob Paiement');
    }

    public function test_admin_can_cancel_a_pending_installment_with_a_reason(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->addDays(10),
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->getAdminUser())->patch(
            route('admin.echeances.annuler', $echeance),
            ['motif' => 'Abandon de la formation']
        );

        $response->assertRedirect();
        $echeance->refresh();
        $this->assertSame('cancelled', $echeance->status);
        $this->assertStringContainsString('Abandon de la formation', $echeance->notes);
        $this->assertStringContainsString(now()->format('d/m/Y'), $echeance->notes);
    }

    public function test_payment_plan_page_renders_the_cancellation_confirmation_window(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 50000,
            'advance_amount' => 0,
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 50000,
            'due_date'        => now()->addDays(10),
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->getAdminUser())->get(route('admin.students.plan.edit', $student));

        $response->assertOk();
        $response->assertSee('x-data="{ openAnnuler: false, openReactiver: false, openSupprimer: false }"', false);
        $response->assertSee('Annuler l\'échéance', false);
        $response->assertSee('Motif (facultatif)', false);
        $response->assertSee('Confirmer l\'annulation', false);
        $response->assertDontSee('prompt(', false);
        $response->assertDontSee('confirm(', false);
    }

    public function test_cancelling_a_pending_installment_without_a_reason_succeeds(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->addDays(10),
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->getAdminUser())->patch(route('admin.echeances.annuler', $echeance), []);

        $response->assertRedirect();
        $echeance->refresh();
        $this->assertSame('cancelled', $echeance->status);
        $this->assertSame('['.now()->format('d/m/Y').'] Annulée', $echeance->notes);
    }

    public function test_cancelling_an_already_paid_installment_is_refused(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->subDays(5),
            'paid_date'  => now()->subDays(5),
            'status'     => 'paid',
        ]);

        $response = $this->actingAs($this->getAdminUser())->patch(
            route('admin.echeances.annuler', $echeance),
            ['motif' => 'Erreur de saisie']
        );

        $response->assertSessionHasErrors('echeance');
        $this->assertSame('paid', $echeance->fresh()->status);
    }

    public function test_manager_cannot_cancel_an_installment(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->addDays(10),
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($manager)->patch(
            route('admin.echeances.annuler', $echeance),
            ['motif' => 'Abandon de la formation']
        );

        $response->assertStatus(403);
        $this->assertSame('pending', $echeance->fresh()->status);
    }

    public function test_admin_can_reactivate_a_cancelled_installment(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->addDays(10),
            'status'     => 'cancelled',
            'notes'      => '[01/09/2026] Annulée : test',
        ]);

        $response = $this->actingAs($this->getAdminUser())->patch(route('admin.echeances.reactiver', $echeance));

        $response->assertRedirect();
        $this->assertSame('pending', $echeance->fresh()->status);
    }

    public function test_reactivating_a_pending_installment_is_refused(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 50000,
            'due_date'   => now()->addDays(10),
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->getAdminUser())->patch(route('admin.echeances.reactiver', $echeance));

        $response->assertSessionHasErrors('echeance');
        $this->assertSame('pending', $echeance->fresh()->status);
    }

    public function test_cancelled_installments_are_excluded_from_expected_and_overdue_totals(): void
    {
        $program = Program::factory()->create(['name' => 'Programme Annulation']);
        $student = User::factory()->create(['name' => 'Apprenant Annulation']);
        $student->assignRole('student');

        // En retard, non reglee : comptee.
        \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'program_id' => $program->id,
            'amount'     => 40000,
            'due_date'   => '2026-06-10',
            'status'     => 'pending',
        ]);

        // Annulee, elle aussi en retard sur sa date : ne doit compter dans
        // aucun des deux totaux, sinon les montants affiches sont faux.
        \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'program_id' => $program->id,
            'amount'     => 60000,
            'due_date'   => '2026-06-05',
            'status'     => 'cancelled',
            'notes'      => 'Annulée : test',
        ]);

        $this->travelTo(\Carbon\Carbon::parse('2026-06-20'));

        $response = $this->actingAs($this->getAdminUser())
            ->get(route('admin.student-payments.index', ['month' => 6, 'year' => 2026]));

        $response->assertStatus(200);
        $response->assertViewHas('totalAttendu', 40000.0);
        $response->assertViewHas('totalEnRetard', 40000.0);
    }

    public function test_cancelling_an_installment_does_not_reduce_the_student_balance(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 300000,
            'advance_amount' => 0,
        ]);

        $echeances = [];
        foreach ([100000, 100000, 100000] as $montant) {
            $echeances[] = \App\Models\StudentPayment::create([
                'student_id'      => $student->id,
                'payment_plan_id' => $plan->id,
                'amount'          => $montant,
                'due_date'        => now()->addDays(30),
                'status'          => 'pending',
            ]);
        }

        $soldeAvant = $this->actingAs($student)->get(route('student.dashboard'))
            ->viewData('kpis')['solde_du'];
        $this->assertEquals(300000, $soldeAvant);

        $this->actingAs($this->getAdminUser())->patch(
            route('admin.echeances.annuler', $echeances[0]),
            ['motif' => 'Report de paiement']
        );

        // La regle metier interdit qu'annuler une echeance fasse baisser la
        // dette : la somme reste due, elle sera replanifiee plus tard.
        $soldeApres = $this->actingAs($student)->get(route('student.dashboard'))
            ->viewData('kpis')['solde_du'];
        $this->assertEquals(300000, $soldeApres);
    }

    public function test_balance_does_not_double_after_cancellation_and_replanning(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 300000,
            'advance_amount' => 0,
        ]);

        $echeanceAnnulee = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 100000,
            'due_date'        => now()->addDays(10),
            'status'          => 'pending',
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 200000,
            'due_date'        => now()->addDays(40),
            'status'          => 'pending',
        ]);

        $this->actingAs($admin)->patch(
            route('admin.echeances.annuler', $echeanceAnnulee),
            ['motif' => 'Report de paiement']
        );

        // Replanification : l'administrateur ressaisit le plan, remplacant
        // l'echeance annulee par une nouvelle echeance en attente pour le
        // meme montant, a une nouvelle date.
        $this->actingAs($admin)->post(route('admin.students.plan.store', $student), [
            'total_amount'   => 300000,
            'advance_amount' => 0,
            'echeances'      => [
                ['amount' => 100000, 'due_date' => now()->addDays(60)->format('Y-m-d')],
                ['amount' => 200000, 'due_date' => now()->addDays(40)->format('Y-m-d')],
            ],
        ]);

        $solde = $this->actingAs($student)->get(route('student.dashboard'))
            ->viewData('kpis')['solde_du'];

        // Le solde ne double pas : l'ancienne echeance annulee et la
        // nouvelle echeance replanifiee ne representent qu'une seule dette.
        $this->assertEquals(300000, $solde);
    }

    public function test_cancelled_installment_survives_plan_resubmission(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 100000,
            'advance_amount' => 0,
        ]);

        $echeance = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 100000,
            'due_date'        => now()->addDays(10),
            'status'          => 'pending',
        ]);

        $this->actingAs($admin)->patch(
            route('admin.echeances.annuler', $echeance),
            ['motif' => 'Report de paiement']
        );

        $this->actingAs($admin)->post(route('admin.students.plan.store', $student), [
            'total_amount'   => 100000,
            'advance_amount' => 0,
            'echeances'      => [
                ['amount' => 100000, 'due_date' => now()->addDays(60)->format('Y-m-d')],
            ],
        ]);

        $echeance->refresh();
        $this->assertSame('cancelled', $echeance->status);
        $this->assertStringContainsString('Report de paiement', $echeance->notes);
    }

    public function test_student_and_admin_balances_match(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 250000,
            'advance_amount' => 50000,
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 100000,
            'due_date'        => now()->subDays(5),
            'paid_date'       => now()->subDays(5),
            'status'          => 'paid',
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 100000,
            'due_date'        => now()->addDays(20),
            'status'          => 'pending',
        ]);

        $adminResponse = $this->actingAs($admin)->get(route('admin.students.plan.edit', $student));
        $adminSolde = $adminResponse->viewData('plan')->soldeRestant();

        $studentResponse = $this->actingAs($student)->get(route('student.dashboard'));
        $studentSolde = $studentResponse->viewData('kpis')['solde_du'];

        $this->assertEquals(100000, $adminSolde);
        $this->assertEquals($adminSolde, $studentSolde);
    }

    public function test_lowering_total_amount_regenerates_upcoming_installments_without_touching_paid_or_cancelled(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        $reglee = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->subDays(20),
            'paid_date'       => now()->subDays(20),
            'status'          => 'paid',
        ]);

        $annulee = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->addDays(10),
            'status'          => 'cancelled',
            'notes'           => '[19/09/2026] Annulée',
        ]);

        $enAttente = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->addDays(40),
            'status'          => 'pending',
        ]);

        // Baisse du cout total : 100000 au lieu de 120000, donc 60000 a
        // repartir sur les echeances a venir (100000 - 40000 deja regle).
        $this->actingAs($admin)->post(route('admin.students.plan.store', $student), [
            'total_amount'   => 100000,
            'advance_amount' => 0,
            'echeances'      => [
                ['amount' => 60000, 'due_date' => now()->addDays(45)->format('Y-m-d')],
            ],
        ])->assertSessionHasNoErrors();

        $reglee->refresh();
        $annulee->refresh();
        $this->assertSame('paid', $reglee->status);
        $this->assertSame('cancelled', $annulee->status);
        $this->assertDatabaseMissing('student_payments', ['id' => $enAttente->id]);

        $plan->refresh();
        $this->assertEquals(100000, $plan->total_amount);
        $this->assertEquals(60000, (float) $plan->echeances()->where('status', 'pending')->sum('amount'));
    }

    public function test_total_amount_below_amount_already_paid_is_refused(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->subDays(5),
            'paid_date'       => now()->subDays(5),
            'status'          => 'paid',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.students.plan.store', $student), [
            'total_amount'   => 30000,
            'advance_amount' => 0,
            'echeances'      => [],
        ]);

        $response->assertSessionHasErrors('total_amount');
        $this->assertEquals(120000, $plan->fresh()->total_amount);
    }

    public function test_total_amount_equal_to_amount_paid_leaves_no_upcoming_installment_and_zero_balance(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->subDays(5),
            'paid_date'       => now()->subDays(5),
            'status'          => 'paid',
        ]);

        \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 80000,
            'due_date'        => now()->addDays(30),
            'status'          => 'pending',
        ]);

        $this->actingAs($admin)->post(route('admin.students.plan.store', $student), [
            'total_amount'   => 40000,
            'advance_amount' => 0,
            'echeances'      => [],
        ])->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertEquals(40000, $plan->total_amount);
        $this->assertEquals(0, $plan->echeances()->where('status', 'pending')->count());
        $this->assertEquals(0, $plan->soldeRestant());
    }

    public function test_deleting_an_upcoming_installment_removes_it_and_lowers_the_total_amount(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        $echeance = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->addDays(30),
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.echeances.supprimer', $echeance));

        $response->assertRedirect();
        $this->assertDatabaseMissing('student_payments', ['id' => $echeance->id]);

        $plan->refresh();
        $this->assertEquals(80000, $plan->total_amount);
        $this->assertStringContainsString('supprimée', $plan->notes);
        $this->assertStringContainsString(now()->format('d/m/Y'), $plan->notes);
    }

    public function test_a_paid_installment_cannot_be_deleted(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        $echeance = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->subDays(5),
            'paid_date'       => now()->subDays(5),
            'status'          => 'paid',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.echeances.supprimer', $echeance));

        $response->assertSessionHasErrors('echeance');
        $this->assertDatabaseHas('student_payments', ['id' => $echeance->id, 'status' => 'paid']);
        $this->assertEquals(120000, $plan->fresh()->total_amount);
    }

    public function test_a_cancelled_installment_cannot_be_deleted(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $admin = $this->getAdminUser();

        $plan = \App\Models\PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 120000,
            'advance_amount' => 0,
        ]);

        $echeance = \App\Models\StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->addDays(10),
            'status'          => 'cancelled',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.echeances.supprimer', $echeance));

        $response->assertSessionHasErrors('echeance');
        $this->assertDatabaseHas('student_payments', ['id' => $echeance->id, 'status' => 'cancelled']);
    }

    public function test_manager_cannot_delete_an_installment(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $student = User::factory()->create();
        $student->assignRole('student');

        $echeance = \App\Models\StudentPayment::create([
            'student_id' => $student->id,
            'amount'     => 40000,
            'due_date'   => now()->addDays(10),
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($manager)->delete(route('admin.echeances.supprimer', $echeance));

        $response->assertForbidden();
        $this->assertDatabaseHas('student_payments', ['id' => $echeance->id]);
    }
}
