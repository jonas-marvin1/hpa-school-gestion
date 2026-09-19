<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\CourseClass;
use App\Models\Level;
use App\Models\PaymentPlan;
use App\Models\Program;
use App\Models\StudentPayment;
use App\Models\User;
use App\Notifications\AssignmentReminderNotification;
use App\Notifications\PaymentDueNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDueRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_nominative_assignment_reminder_only_notifies_the_targeted_student(): void
    {
        Notification::fake();

        $coach = User::factory()->create();
        $coach->assignRole('coach');

        $eleveVise = User::factory()->create();
        $eleveVise->assignRole('student');

        $autreEleve = User::factory()->create();
        $autreEleve->assignRole('student');

        $program = Program::factory()->create(['name' => 'Test Prog']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Test Level']);
        $class = CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Test Class']);
        $class->users()->attach($eleveVise->id, ['role' => 'student']);
        $class->users()->attach($autreEleve->id, ['role' => 'student']);

        // Echeance dans 3 jours : correspond au premier jalon de rappel.
        Assignment::create([
            'course_class_id' => $class->id,
            'student_id' => $eleveVise->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir individuel',
            'description' => 'Description',
            'due_date' => now()->addDays(3),
            'type' => 'text',
        ]);

        $this->artisan('reminders:send');

        Notification::assertSentTo($eleveVise, AssignmentReminderNotification::class);
        Notification::assertNotSentTo($autreEleve, AssignmentReminderNotification::class);
    }

    public function test_a_stopped_program_no_longer_sends_payment_reminders(): void
    {
        Notification::fake();

        $student = User::factory()->create();
        $student->assignRole('student');

        $plan = PaymentPlan::create([
            'student_id'     => $student->id,
            'total_amount'   => 40000,
            'advance_amount' => 0,
        ]);

        // Echeance en retard, mais annulee par l'arret de la formation
        // (point 6 du 19/09/2026) : ne doit plus jamais relancer l'apprenant.
        StudentPayment::create([
            'student_id'      => $student->id,
            'payment_plan_id' => $plan->id,
            'amount'          => 40000,
            'due_date'        => now()->subDays(10),
            'status'          => 'cancelled',
        ]);

        $this->artisan('reminders:send');

        Notification::assertNotSentTo($student, PaymentDueNotification::class);
    }
}
