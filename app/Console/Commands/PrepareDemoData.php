<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\EnglishLevel;
use App\Models\PaymentPlan;
use App\Models\Program;
use App\Models\StudentPayment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Prepare un jeu de donnees pour la recette des nouvelles fonctionnalites.
 *
 * Pourquoi une commande plutot qu'un export SQL fige : le rappel de cours se
 * declenche 20 minutes avant une seance. Une seance figee dans un dump serait
 * deja passee au moment de l'import. Ici, les dates sont calculees a
 * l'execution, le jeu reste donc valable quel que soit le jour.
 *
 * Reexecutable : la commande nettoie ses propres traces avant de recreer.
 */
class PrepareDemoData extends Command
{
    protected $signature = 'demo:prepare {--clean : Supprime les donnees de demonstration sans les recreer}';

    protected $description = 'Prepare le jeu de donnees de recette des nouvelles fonctionnalites';

    /** Marqueur permettant de retrouver et de nettoyer les donnees generees. */
    private const MARQUEUR = '[RECETTE]';

    public function handle(): int
    {
        $this->nettoyer();

        if ($this->option('clean')) {
            $this->info('Données de recette supprimées.');

            return self::SUCCESS;
        }

        $etudiants = User::role('student')->orderBy('id')->take(3)->get();

        if ($etudiants->count() < 3) {
            $this->error('Il faut au moins 3 apprenants en base.');

            return self::FAILURE;
        }

        $this->preparerAffectations($etudiants);
        $this->preparerPlansDePaiement($etudiants);
        $this->preparerNiveaux($etudiants);
        $this->preparerDevoirAudio();
        $this->preparerSeanceImminente();

        $this->newLine();
        $this->info('Jeu de recette prêt.');
        $this->line('  Lancez ensuite : php artisan reminders:send');

        return self::SUCCESS;
    }

    /**
     * Rattache les comptes de recette a des classes reelles.
     *
     * Sans cela, l'apprenant de test n'a aucune classe : ni programme, ni
     * progression de cursus ne s'affichent, et la recette semble vide alors
     * que tout fonctionne.
     *
     * Le formateur de test est volontairement place sur des classes relevant
     * de DEUX programmes distincts : c'est le cas signale par le client, il
     * doit donc etre observable.
     */
    private function preparerAffectations($etudiants): void
    {
        // Classes rattachees a des programmes differents.
        $classesParProgramme = CourseClass::with('level.program')
            ->get()
            ->filter(fn ($c) => $c->level?->program)
            ->groupBy(fn ($c) => $c->level->program->id);

        if ($classesParProgramme->isEmpty()) {
            $this->warn('  affectations : aucune classe rattachée à un programme, étape ignorée.');

            return;
        }

        $deuxProgrammes = $classesParProgramme->take(2);

        // Les apprenants de recette rejoignent la premiere classe.
        $premiere = $deuxProgrammes->first()->first();

        foreach ($etudiants as $etudiant) {
            $etudiant->courseClasses()->syncWithoutDetaching([$premiere->id => ['role' => 'student']]);
        }

        $this->line(sprintf('  affectations     %-28s %d apprenant(s) de recette', $premiere->name, $etudiants->count()));

        // Le formateur de recette prend une seance sur une classe de chaque
        // programme, ce qui le rend « multi-programmes ».
        $coach = User::where('email', 'formateur@example.com')->first() ?? User::role('coach')->first();

        if (! $coach) {
            return;
        }

        $noms = [];

        foreach ($deuxProgrammes as $classes) {
            $classe = $classes->first();

            ClassSession::create([
                'course_class_id'   => $classe->id,
                'coach_id'          => $coach->id,
                'start_time'        => now()->subDays(2)->setTime(9, 0),
                'end_time'          => now()->subDays(2)->setTime(11, 0),
                'status'            => 'scheduled',
                'intervention_type' => self::MARQUEUR,
                'amount'            => 5000,
            ]);

            $noms[] = $classe->level->program->name;
        }

        $this->line(sprintf('  multi-programmes %-28s %s', $coach->name, implode(' + ', array_map(fn ($n) => mb_substr($n, 0, 22), $noms))));
    }

    private function preparerPlansDePaiement($etudiants): void
    {
        $programme = Program::first();

        // Trois situations distinctes, pour couvrir les cas que le client
        // doit pouvoir observer : plan neuf, plan entame, plan en retard.
        $cas = [
            ['libelle' => 'plan neuf',      'total' => 450000, 'avance' => 150000, 'regle' => 0, 'premiereEcheance' => 5],
            ['libelle' => 'plan entamé',    'total' => 600000, 'avance' => 200000, 'regle' => 1, 'premiereEcheance' => 20],
            ['libelle' => 'plan en retard', 'total' => 300000, 'avance' => 100000, 'regle' => 0, 'premiereEcheance' => -4],
        ];

        foreach ($etudiants as $i => $etudiant) {
            $c = $cas[$i];

            $plan = PaymentPlan::create([
                'student_id'     => $etudiant->id,
                'program_id'     => $programme?->id,
                'total_amount'   => $c['total'],
                'advance_amount' => $c['avance'],
                'notes'          => self::MARQUEUR . ' ' . $c['libelle'],
            ]);

            $reste = $c['total'] - $c['avance'];
            $nb = 2;
            $montant = $reste / $nb;

            for ($n = 0; $n < $nb; $n++) {
                $echeance = StudentPayment::create([
                    'student_id'      => $etudiant->id,
                    'program_id'      => $programme?->id,
                    'payment_plan_id' => $plan->id,
                    'amount'          => $montant,
                    'due_date'        => now()->addDays($c['premiereEcheance'] + $n * 30),
                    'status'          => 'pending',
                ]);

                if ($n < $c['regle']) {
                    $echeance->update(['status' => 'paid', 'paid_date' => now()->subDays(3)]);
                }
            }

            $this->line(sprintf(
                '  %-16s %-28s total %s, reste %s',
                $c['libelle'],
                $etudiant->name,
                number_format($c['total'], 0, ',', ' '),
                number_format($plan->fresh()->soldeRestant(), 0, ',', ' ')
            ));
        }
    }

    private function preparerNiveaux($etudiants): void
    {
        // Un coach est authentifie le temps de l'operation pour que le journal
        // enregistre un auteur, comme ce sera le cas dans l'application.
        $coach = User::role('coach')->first();

        if ($coach) {
            Auth::login($coach);
        }

        $paliers = ['A1', 'A2+', 'B1'];

        foreach ($etudiants as $i => $etudiant) {
            $depart = EnglishLevel::where('code', 'A1')->first();
            $cible  = EnglishLevel::where('code', $paliers[$i])->first();

            if (! $depart || ! $cible) {
                continue;
            }

            // Deux etapes pour que l'historique de progression ne soit pas vide.
            $etudiant->changerNiveau($depart);

            if ($cible->id !== $depart->id) {
                $etudiant->changerNiveau($cible);
            }

            $this->line(sprintf('  niveau           %-28s %s', $etudiant->name, $cible->code));
        }

        Auth::logout();
    }

    private function preparerDevoirAudio(): void
    {
        $classe = CourseClass::whereHas('classSessions')->first();
        $coach  = User::find(ClassSession::where('course_class_id', $classe?->id)->value('coach_id'));
        $eleve  = $classe?->users()->wherePivot('role', 'student')->first();

        if (! $classe || ! $coach || ! $eleve) {
            $this->warn('  devoir audio : classe, coach ou apprenant introuvable, étape ignorée.');

            return;
        }

        $devoir = Assignment::create([
            'course_class_id' => $classe->id,
            'coach_id'        => $coach->id,
            'title'           => self::MARQUEUR . ' Lecture à voix haute',
            'description'     => "Enregistrez-vous en lisant le texte de la page 12, puis déposez l'audio.",
            'type'            => 'audio',
            'due_date'        => now()->addDays(4),
        ]);

        // MP3 minimal valide (silence) : suffisant pour verifier le lecteur.
        $trame = "\xFF\xFB\x90\x00" . str_repeat("\x00", 400);
        Storage::disk('public')->put('submissions/recette_audio.mp3', str_repeat($trame, 60));

        Submission::create([
            'assignment_id' => $devoir->id,
            'student_id'    => $eleve->id,
            'file_path'     => 'submissions/recette_audio.mp3',
            'submitted_at'  => now()->subHour(),
        ]);

        $this->line(sprintf('  devoir audio     %-28s classe %s', $eleve->name, $classe->name));
    }

    private function preparerSeanceImminente(): void
    {
        $classe = CourseClass::whereHas('users', fn ($q) => $q->where('role', 'student'))->first()
               ?? CourseClass::first();

        $coach = User::role('coach')->first();

        if (! $classe || ! $coach) {
            $this->warn('  séance imminente : classe ou coach introuvable, étape ignorée.');

            return;
        }

        $session = ClassSession::create([
            'course_class_id'   => $classe->id,
            'coach_id'          => $coach->id,
            'start_time'        => now()->addMinutes(15),
            'end_time'          => now()->addMinutes(75),
            'status'            => 'scheduled',
            'intervention_type' => self::MARQUEUR,
            'amount'            => 5000,
        ]);

        $this->line(sprintf(
            '  séance imminente %-28s à %s (rappel dans quelques instants)',
            $classe->name,
            $session->start_time->format('H:i')
        ));
    }

    /** Retire tout ce que la commande a pu creer lors d'un passage precedent. */
    private function nettoyer(): void
    {
        $devoirs = Assignment::where('title', 'like', self::MARQUEUR . '%')->pluck('id');
        Submission::whereIn('assignment_id', $devoirs)->delete();
        Assignment::whereIn('id', $devoirs)->delete();
        Storage::disk('public')->delete('submissions/recette_audio.mp3');

        ClassSession::where('intervention_type', self::MARQUEUR)->delete();

        $plans = PaymentPlan::where('notes', 'like', self::MARQUEUR . '%')->pluck('id');
        StudentPayment::whereIn('payment_plan_id', $plans)->delete();
        PaymentPlan::whereIn('id', $plans)->delete();
    }
}
