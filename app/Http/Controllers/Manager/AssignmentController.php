<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Concerns\GereAttributionApprenant;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    use GereAttributionApprenant;

    public function index(Request $request)
    {
        // Perimetre de la gestionnaire : toutes les classes, comme partout
        // ailleurs dans son espace (fiche du 27/08/2026, point 1).
        $query = Assignment::with(['courseClass', 'coach.roles', 'student'])
            ->orderBy('due_date', 'desc');

        if ($request->has('search') && $request->search != '') {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $assignments = $query->paginate(10)->appends($request->all());

        return view('manager.assignments.index', compact('assignments'));
    }

    public function create()
    {
        $classes = $this->classesAvecApprenants();

        return view('manager.assignments.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'student_id' => ['nullable', 'exists:users,id', $this->regleApprenantDeLaClasse($request)],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:text,file,qcm,link,audio',
            'evaluation_link' => 'nullable|url|max:255',
            'attachment' => 'nullable|file|max:10240',
        ]);

        // Createur de l'evaluation : voir dette technique CLAUDE.md sur le
        // nommage de cette colonne, partagee avec le coach.
        $validated['coach_id'] = Auth::id();

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('assignments', 'public');
        }

        Assignment::create($validated);

        return redirect()->route('manager.assignments.index')->with('status', 'Devoir créé avec succès.');
    }

    public function edit(Assignment $assignment)
    {
        // La gestionnaire a le controle complet : aucune restriction sur le
        // createur de l'evaluation, contrairement au coach.
        $classes = $this->classesAvecApprenants();

        return view('manager.assignments.edit', compact('assignment', 'classes'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'student_id' => ['nullable', 'exists:users,id', $this->regleApprenantDeLaClasse($request)],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'type' => 'required|in:text,file,qcm,link,audio',
            'evaluation_link' => 'nullable|url|max:255',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('assignments', 'public');
        }

        $assignment->update($validated);

        return redirect()->route('manager.assignments.index')->with('status', 'Devoir mis à jour.');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();

        return redirect()->route('manager.assignments.index')->with('status', 'Devoir supprimé.');
    }
}
