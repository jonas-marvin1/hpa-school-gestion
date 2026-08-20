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
