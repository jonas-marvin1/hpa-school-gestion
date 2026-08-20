<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    use ExportsCsv;

    /** Libelles des statuts de session, partages entre l'ecran et l'export CSV. */
    private const STATUTS = [
        'scheduled' => 'Prévue',
        'completed' => 'Effectuée',
        'validated' => 'Validée',
        'cancelled' => 'Annulée',
    ];

    /**
     * Construit la requete des sessions filtree par les criteres de l'URL,
     * sans pagination : utilisee telle quelle par l'ecran et par l'export CSV.
     */
    private function filtrerSessions(Request $request): Builder
    {
        $query = ClassSession::with(['courseClass', 'coach', 'sessionReport', 'attendances'])->orderBy('start_time', 'desc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            // Le OR doit rester enferme dans son propre groupe : sans ce
            // where(function(){...}), SQL applique AND avant OR et la condition
            // devient "(classe correspond) OR (coach correspond AND statut = X)".
            // Les sessions trouvees par le nom de classe ressortaient alors quel
            // que soit leur statut, d'ou le filtre qui paraissait partiel.
            $query->where(function($q) use ($search) {
                $q->whereHas('courseClass', function($c) use ($search) {
                    $c->where('name', 'like', "%{$search}%");
                })->orWhereHas('coach', function($c) use ($search) {
                    $c->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('coach_id') && $request->coach_id != '') {
            $query->where('coach_id', $request->coach_id);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $coaches = User::role('coach')->get();
        $sessions = $this->filtrerSessions($request)->paginate(15)->appends($request->all());
        return view('manager.sessions.index', compact('sessions', 'coaches'));
    }

    /**
     * Export CSV des sessions correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $sessions = $this->filtrerSessions($request)->get();

        $lignes = $sessions->map(fn (ClassSession $session) => [
            $session->start_time->format('d/m/Y'),
            $session->start_time->format('H:i').' - '.$session->end_time->format('H:i'),
            $session->courseClass->name ?? 'N/A',
            $session->coach->name ?? 'N/A',
            number_format((float) $session->amount, 2, ',', ''),
            self::STATUTS[$session->status] ?? $session->status,
        ]);

        return $this->streamCsv('planning.csv', ['Date', 'Horaires', 'Classe', 'Formateur', 'Montant', 'Statut'], $lignes);
    }

    public function show(ClassSession $session)
    {
        // Le rapport du coach et les avis des apprenants etaient consultables
        // a deux endroits differents (fiche de session et onglet Evaluations).
        // Ils sont desormais charges ensemble pour un seul ecran.
        $session->load([
            'courseClass.level.program',
            'coach',
            'sessionReport',
            'attendances.student',
        ]);

        return view('manager.sessions.show', compact('session'));
    }

    public function create()
    {
        $classes = CourseClass::pluck('name', 'id');
        $coaches = User::role('coach')->pluck('name', 'id');
        return view('manager.sessions.create', compact('classes', 'coaches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'coach_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'intervention_type' => 'required|in:online,in_person',
            'amount' => 'required|numeric|min:0',
            'online_link' => 'nullable|url|max:255',
        ]);

        ClassSession::create([
            'course_class_id' => $validated['course_class_id'],
            'coach_id' => $validated['coach_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'intervention_type' => $validated['intervention_type'],
            'amount' => $validated['amount'],
            'online_link' => $validated['online_link'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()->route('manager.sessions.index')->with('status', 'Session programmée avec succès.');
    }

    public function edit(ClassSession $session)
    {
        $classes = CourseClass::pluck('name', 'id');
        $coaches = User::role('coach')->pluck('name', 'id');
        return view('manager.sessions.edit', compact('session', 'classes', 'coaches'));
    }

    public function update(Request $request, ClassSession $session)
    {
        if ($session->payment_id) {
            return redirect()->route('manager.sessions.index')->with('error', 'Cette session est déjà facturée et ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'coach_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'intervention_type' => 'required|in:online,in_person',
            'amount' => 'required|numeric|min:0',
            'online_link' => 'nullable|url|max:255',
            'status' => 'required|string|in:scheduled,completed,validated,cancelled'
        ]);

        $session->update($validated);

        return redirect()->route('manager.sessions.index')->with('status', 'Session mise à jour avec succès.');
    }

    public function destroy(ClassSession $session)
    {
        $session->delete();
        return redirect()->route('manager.sessions.index')->with('status', 'Session supprimée.');
    }
}
