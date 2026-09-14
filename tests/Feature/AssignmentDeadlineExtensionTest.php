<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentDeadlineExtension;
use App\Models\CourseClass;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Program;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche du 14/09/2026, point 3 : prolongation du delai d'une evaluation.
 */
class AssignmentDeadlineExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function creerClasse(): CourseClass
    {
        $program = Program::factory()->create(['name' => 'Programme Test']);
        $level = Level::factory()->create(['program_id' => $program->id, 'name' => 'Niveau Test']);

        return CourseClass::factory()->create(['level_id' => $level->id, 'name' => 'Classe Test']);
    }

    private function creerApprenant(CourseClass $classe): User
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $classe->users()->attach($student->id, ['role' => 'student']);

        return $student;
    }

    private function creerCoach(): User
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');

        return $coach;
    }

    private function creerDevoir(CourseClass $classe, User $coach, $dueDate, ?User $student = null): Assignment
    {
        return Assignment::create([
            'course_class_id' => $classe->id,
            'student_id' => $student?->id,
            'coach_id' => $coach->id,
            'title' => 'Devoir de test',
            'description' => 'Description',
            'due_date' => $dueDate,
            'type' => 'text',
        ]);
    }

    // --- Resolution de la date limite effective ---

    public function test_date_limite_sans_prolongation_est_celle_du_devoir(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(5));

        $this->assertTrue($devoir->dateLimitePour($eleve)->equalTo($devoir->due_date));
    }

    public function test_prolongation_individuelle_ne_change_que_cet_apprenant(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleveVise = $this->creerApprenant($classe);
        $autreEleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        AssignmentDeadlineExtension::create([
            'assignment_id' => $devoir->id,
            'student_id' => $eleveVise->id,
            'previous_due_date' => $devoir->due_date,
            'new_due_date' => now()->addDays(9),
            'motif' => 'Retard justifié',
            'extended_by' => $coach->id,
        ]);

        $this->assertTrue($devoir->dateLimitePour($eleveVise)->isSameDay(now()->addDays(9)));
        $this->assertTrue($devoir->dateLimitePour($autreEleve)->equalTo($devoir->due_date));
    }

    public function test_prolongation_collective_change_tous_les_apprenants_sans_rendu(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve1 = $this->creerApprenant($classe);
        $eleve2 = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        // Prolongation collective : historique student_id=null, due_date mise a jour directement.
        AssignmentDeadlineExtension::create([
            'assignment_id' => $devoir->id,
            'student_id' => null,
            'previous_due_date' => $devoir->due_date,
            'new_due_date' => now()->addDays(9),
            'motif' => 'Retard general',
            'extended_by' => $coach->id,
        ]);
        $devoir->update(['due_date' => now()->addDays(9)]);

        $this->assertTrue($devoir->dateLimitePour($eleve1)->isSameDay(now()->addDays(9)));
        $this->assertTrue($devoir->dateLimitePour($eleve2)->isSameDay(now()->addDays(9)));
    }

    public function test_deux_prolongations_successives_la_plus_recente_fait_foi(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        AssignmentDeadlineExtension::create([
            'assignment_id' => $devoir->id,
            'student_id' => $eleve->id,
            'previous_due_date' => $devoir->due_date,
            'new_due_date' => now()->addDays(5),
            'motif' => 'Premiere prolongation',
            'extended_by' => $coach->id,
        ]);

        AssignmentDeadlineExtension::create([
            'assignment_id' => $devoir->id,
            'student_id' => $eleve->id,
            'previous_due_date' => now()->addDays(5),
            'new_due_date' => now()->addDays(10),
            'motif' => 'Seconde prolongation',
            'extended_by' => $coach->id,
        ]);

        $this->assertTrue($devoir->dateLimitePour($eleve)->isSameDay(now()->addDays(10)));
        $this->assertSame(2, AssignmentDeadlineExtension::where('assignment_id', $devoir->id)->where('student_id', $eleve->id)->count());
    }

    public function test_apprenant_prolonge_peut_deposer_apres_lancienne_date_et_avant_la_nouvelle(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve = $this->creerApprenant($classe);
        // Echeance dans 1 minute : on la laisse expirer avant de tenter le depot.
        $devoir = $this->creerDevoir($classe, $coach, now()->addMinute());

        AssignmentDeadlineExtension::create([
            'assignment_id' => $devoir->id,
            'student_id' => $eleve->id,
            'previous_due_date' => $devoir->due_date,
            'new_due_date' => now()->addDays(3),
            'motif' => 'Retard justifié',
            'extended_by' => $coach->id,
        ]);

        $this->travel(2)->minutes();

        $reponse = $this->actingAs($eleve)->post(route('student.assignments.submit', $devoir), [
            'content' => 'Ma réponse',
        ]);

        $reponse->assertRedirect(route('student.assignments.show', $devoir));
        $this->assertDatabaseHas('submissions', ['assignment_id' => $devoir->id, 'student_id' => $eleve->id]);
    }

    public function test_depot_refuse_apres_la_date_limite_effective_sans_prolongation(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addMinute());

        $this->travel(2)->minutes();

        $reponse = $this->actingAs($eleve)->post(route('student.assignments.submit', $devoir), [
            'content' => 'Ma réponse',
        ]);

        $reponse->assertSessionHasErrors();
        $this->assertDatabaseMissing('submissions', ['assignment_id' => $devoir->id, 'student_id' => $eleve->id]);
    }
}
