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

    private function creerManager(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        return $manager;
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

    // --- Prolongation collective (point 4) ---

    public function test_manager_peut_prolonger_collectivement_un_devoir_dun_coach(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $eleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        $nouvelleDate = now()->addDays(9)->format('Y-m-d\TH:i');

        $reponse = $this->actingAs($manager)->post(route('manager.assignments.prolonger', $devoir), [
            'new_due_date' => $nouvelleDate,
            'motif' => 'Retard general de la classe',
        ]);

        $reponse->assertRedirect();
        $reponse->assertSessionDoesntHaveErrors();
        $devoir->refresh();
        $this->assertTrue($devoir->dateLimitePour($eleve)->isSameMinute(now()->addDays(9)));
        $this->assertDatabaseHas('assignment_deadline_extensions', [
            'assignment_id' => $devoir->id,
            'student_id' => null,
            'motif' => 'Retard general de la classe',
        ]);
    }

    public function test_coach_peut_prolonger_son_propre_devoir(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        $reponse = $this->actingAs($coach)->post(route('coach.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $reponse->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('assignment_deadline_extensions', ['assignment_id' => $devoir->id, 'student_id' => null]);
    }

    public function test_coach_recoit_403_sur_le_devoir_dun_autre_coach(): void
    {
        $classe = $this->creerClasse();
        $auteur = $this->creerCoach();
        $autreCoach = $this->creerCoach();
        $devoir = $this->creerDevoir($classe, $auteur, now()->addDays(2));

        $reponse = $this->actingAs($autreCoach)->post(route('coach.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $reponse->assertStatus(403);
    }

    public function test_apprenant_recoit_403(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $eleve = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        // Aucune route "assignments.prolonger" n'existe cote apprenant, mais
        // la policy doit de toute facon refuser un appel direct.
        $this->assertFalse($eleve->can('prolongerDelai', $devoir));

        $reponse = $this->actingAs($eleve)->post(route('coach.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $reponse->assertStatus(403);
    }

    public function test_prolongation_collective_ne_modifie_pas_un_rendu_ou_une_note_existants(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $eleveARendu = $this->creerApprenant($classe);
        $eleveSansRendu = $this->creerApprenant($classe);
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        $submission = Submission::create([
            'assignment_id' => $devoir->id,
            'student_id' => $eleveARendu->id,
            'content_text' => 'Ma copie',
            'submitted_at' => now(),
        ]);
        $grade = Grade::create([
            'submission_id' => $submission->id,
            'coach_id' => $coach->id,
            'score' => 15,
            'feedback' => 'Bon travail',
        ]);

        $this->actingAs($manager)->post(route('manager.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'content_text' => 'Ma copie']);
        $this->assertDatabaseHas('grades', ['id' => $grade->id, 'score' => 15]);
        // L'apprenant qui a deja rendu n'est pas concerne par la prolongation.
        $devoir->refresh();
        $this->assertTrue($devoir->dateLimitePour($eleveSansRendu)->isSameMinute(now()->addDays(9)));
    }

    public function test_date_dans_le_passe_refusee_pour_une_prolongation_collective(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(2));

        $reponse = $this->actingAs($manager)->post(route('manager.assignments.prolonger', $devoir), [
            'new_due_date' => now()->subDay()->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $reponse->assertSessionHasErrors('new_due_date');
    }

    public function test_date_anterieure_ou_egale_a_la_date_limite_effective_refusee(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(5));

        $reponse = $this->actingAs($manager)->post(route('manager.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'motif' => 'Retard general',
        ]);

        $reponse->assertSessionHasErrors('new_due_date');
    }

    public function test_motif_vide_refuse_pour_une_prolongation_collective(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $devoir = $this->creerDevoir($classe, $coach, now()->addDays(5));

        $reponse = $this->actingAs($manager)->post(route('manager.assignments.prolonger', $devoir), [
            'new_due_date' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'motif' => '',
        ]);

        $reponse->assertSessionHasErrors('motif');
    }

    public function test_manager_assignments_index_affiche_le_bouton_prolonger(): void
    {
        $classe = $this->creerClasse();
        $coach = $this->creerCoach();
        $manager = $this->creerManager();
        $this->creerDevoir($classe, $coach, now()->addDays(2));

        $reponse = $this->actingAs($manager)->get(route('manager.assignments.index'));

        $reponse->assertStatus(200);
        $reponse->assertSee('Prolonger le délai');
    }

    public function test_coach_assignments_index_masque_le_bouton_prolonger_sur_le_devoir_dun_autre_coach(): void
    {
        $classe = $this->creerClasse();
        $auteur = $this->creerCoach();
        $autreCoach = $this->creerCoach();
        // Meme classe que l'auteur, pour que le devoir apparaisse dans la
        // liste de cet autre coach : sans seance dans la classe, il ne
        // verrait meme pas la ligne, et le test ne prouverait rien.
        \App\Models\ClassSession::create([
            'course_class_id' => $classe->id,
            'coach_id' => $autreCoach->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);
        $this->creerDevoir($classe, $auteur, now()->addDays(2));

        $reponse = $this->actingAs($autreCoach)->get(route('coach.assignments.index'));

        $reponse->assertStatus(200);
        $reponse->assertSee('Devoir de test');
        $reponse->assertDontSee('Prolonger le délai');
    }
}
