<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\EnglishLevel;
use App\Models\Revision;
use Database\Seeders\RolesAndPermissionsSeeder;

class StudentLevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function getAdminUser()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function getCoachUser()
    {
        $coach = User::factory()->create();
        $coach->assignRole('coach');
        return $coach;
    }

    private function getStudentUser()
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        return $student;
    }

    public function test_admin_can_view_level_edit_page(): void
    {
        $student = $this->getStudentUser();

        $response = $this->actingAs($this->getAdminUser())->get(route('students.level.edit', $student));

        $response->assertStatus(200);
        $response->assertSee('Attribuer le niveau initial');
        // Seul l'admin voit la case de correction. assertSee echapperait
        // l'apostrophe (&#039;) alors que le texte est ecrit en dur, non
        // echappe, dans la vue : on desactive l'echappement du besoin.
        $response->assertSee("correction d'une erreur de saisie", false);
    }

    public function test_coach_can_view_level_edit_page_without_correction_option(): void
    {
        $student = $this->getStudentUser();

        $response = $this->actingAs($this->getCoachUser())->get(route('students.level.edit', $student));

        $response->assertStatus(200);
        $response->assertDontSee('correction d\'une erreur de saisie');
    }

    public function test_normal_progression_keeps_previous_level_visible_in_history(): void
    {
        $student = $this->getStudentUser();
        $a2 = EnglishLevel::where('code', 'A2-')->firstOrFail();
        $b1 = EnglishLevel::where('code', 'B1')->firstOrFail();

        $admin = $this->getAdminUser();

        $this->actingAs($admin)->put(route('students.level.update', $student), [
            'english_level_id' => $a2->id,
        ]);

        $this->actingAs($admin)->put(route('students.level.update', $student), [
            'english_level_id' => $b1->id,
        ]);

        $response = $this->actingAs($admin)->get(route('students.level.edit', $student));

        $response->assertStatus(200);
        $response->assertSee('Niveau initial :');
        // La valeur precedente (A2-) reste visible comme etape "avant" de la
        // progression normale.
        $response->assertSee('font-medium">A2-</span>', false);
        $this->assertSame('B1', $student->fresh()->englishLevel->code);
    }

    public function test_admin_correction_hides_erroneous_level_from_history(): void
    {
        $student = $this->getStudentUser();
        $b1 = EnglishLevel::where('code', 'B1')->firstOrFail();
        $a2 = EnglishLevel::where('code', 'A2-')->firstOrFail();

        $admin = $this->getAdminUser();

        // Saisie erronee : B1 au lieu de A2-.
        $this->actingAs($admin)->put(route('students.level.update', $student), [
            'english_level_id' => $b1->id,
        ]);

        // Correction explicite par l'admin.
        $this->actingAs($admin)->put(route('students.level.update', $student), [
            'english_level_id' => $a2->id,
            'correction'       => '1',
        ]);

        $response = $this->actingAs($admin)->get(route('students.level.edit', $student));

        $response->assertStatus(200);
        $response->assertSee('Niveau initial (corrigé)');
        // "B1" apparait forcement dans la liste deroulante des paliers : on
        // verifie precisement l'absence de B1 comme valeur d'une etape
        // d'historique (dans un <span>), pas l'absence totale du mot sur la page.
        $response->assertDontSee('font-bold text-indigo-700">B1<', false);
        $response->assertDontSee('font-medium">B1<', false);
        $this->assertSame('A2-', $student->fresh()->englishLevel->code);

        // Le journal, lui, garde la trace complete : rien n'est supprime en base.
        $this->assertSame(2, Revision::where('revisable_type', User::class)
            ->where('revisable_id', $student->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_coach_cannot_flag_a_correction(): void
    {
        $student = $this->getStudentUser();
        $b1 = EnglishLevel::where('code', 'B1')->firstOrFail();
        $a2 = EnglishLevel::where('code', 'A2-')->firstOrFail();

        $coach = $this->getCoachUser();

        $this->actingAs($coach)->put(route('students.level.update', $student), [
            'english_level_id' => $b1->id,
        ]);

        // Un coach qui forgerait le champ "correction" ne doit pas pouvoir
        // effacer une etape de l'historique : le controle est cote serveur.
        $this->actingAs($coach)->put(route('students.level.update', $student), [
            'english_level_id' => $a2->id,
            'correction'       => '1',
        ]);

        $response = $this->actingAs($this->getAdminUser())->get(route('students.level.edit', $student));

        $response->assertStatus(200);
        // La tentative de correction du coach a ete ignoree cote serveur :
        // l'ancien niveau reste une etape normale de l'historique, visible
        // comme valeur "avant" de la progression B1 -> A2-.
        $response->assertSee('font-medium">B1</span>', false);
        $response->assertDontSee('Niveau initial (corrigé)');
    }
}
