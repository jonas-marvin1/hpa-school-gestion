<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\CourseClass;
use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AssignmentReminderNotification;
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
}
