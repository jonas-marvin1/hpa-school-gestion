<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $coach = Auth::user();

        // Find all classes this coach is assigned to (either directly or via sessions)
        $classIds = \App\Models\ClassSession::where('coach_id', $coach->id)->pluck('course_class_id')->unique();

        $query = Assignment::with('courseClass')
            ->whereIn('course_class_id', $classIds)
            ->orderBy('due_date', 'desc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $assignments = $query->paginate(10)->appends($request->all());

        return view('coach.assignments.index', compact('assignments'));
    }

    public function create()
    {
        $coach = Auth::user();
        $classIds = \App\Models\ClassSession::where('coach_id', $coach->id)->pluck('course_class_id')->unique();
        $classes = CourseClass::whereIn('id', $classIds)->get();

        return view('coach.assignments.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:text,file,qcm,link,audio',
            'evaluation_link' => 'nullable|url|max:255',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $validated['coach_id'] = Auth::id();

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('assignments', 'public');
        }

        Assignment::create($validated);

        return redirect()->route('coach.assignments.index')->with('status', 'Devoir créé avec succès.');
    }

    public function edit(Assignment $assignment)
    {
        $this->autoriserClasseDuCoach($assignment);

        $coach = Auth::user();
        $classIds = \App\Models\ClassSession::where('coach_id', $coach->id)->pluck('course_class_id')->unique();
        $classes = CourseClass::whereIn('id', $classIds)->get();

        return view('coach.assignments.edit', compact('assignment', 'classes'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $this->autoriserClasseDuCoach($assignment);

        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
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

        return redirect()->route('coach.assignments.index')->with('status', 'Devoir mis à jour.');
    }

    public function destroy(Assignment $assignment)
    {
        $this->autoriserClasseDuCoach($assignment);

        $assignment->delete();
        return redirect()->route('coach.assignments.index')->with('status', 'Devoir supprimé.');
    }

    /**
     * Sans ce controle, un coach pouvait modifier ou supprimer par URL
     * forgee un devoir d'une classe qui n'est pas la sienne : edit/update/
     * destroy ne verifiaient rien, contrairement a index()/create().
     */
    private function autoriserClasseDuCoach(Assignment $assignment): void
    {
        $classIds = \App\Models\ClassSession::where('coach_id', Auth::id())->pluck('course_class_id')->unique();

        abort_unless($classIds->contains($assignment->course_class_id), 403);
    }
}
