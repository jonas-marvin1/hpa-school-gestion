<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use App\Models\CourseClass;
use App\Models\Level;

class CourseClassController extends Controller
{
    use ExportsCsv;

    /**
     * Construit la requete des classes filtree par les criteres de l'URL,
     * sans pagination : utilisee telle quelle par l'ecran et par l'export CSV.
     */
    private function filtrerClasses(Request $request): Builder
    {
        $query = CourseClass::with('level.program');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function index(Request $request)
    {
        $classes = $this->filtrerClasses($request)->paginate(15)->appends($request->all());
        return view('admin.classes.index', compact('classes'));
    }

    /**
     * Export CSV des classes correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $classes = $this->filtrerClasses($request)->get();

        $lignes = $classes->map(fn (CourseClass $class) => [
            optional(optional($class->level)->program)->name ?? 'N/A',
            optional($class->level)->name ?? 'Niveau supprimé',
            $class->name,
            $class->location ?? 'Non défini',
            \Carbon\Carbon::parse($class->start_date)->format('d/m/Y'),
            \Carbon\Carbon::parse($class->end_date)->format('d/m/Y'),
        ]);

        return $this->streamCsv('classes.csv', ['Programme', 'Niveau', 'Nom de la classe', 'Lieu', 'Début', 'Fin'], $lignes);
    }

    public function create()
    {
        $levels = Level::with('program')->get();
        return view('admin.classes.create', compact('levels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
        ]);

        CourseClass::create($validated);

        return redirect()->route('admin.classes.index')->with('status', 'Classe créée avec succès.');
    }

    public function show(string $id)
    {
        // Not used
    }

    public function edit(CourseClass $class)
    {
        $levels = Level::with('program')->get();
        // Since $class is bound by the route param {class}, but 'class' is a reserved keyword in PHP, 
        // the variable here must match the route binding name (usually courseClass if explicitly defined, or class if default)
        // Laravel allows using $class if not explicitly bound to something else, but to be safe:
        return view('admin.classes.edit', compact('class', 'levels'));
    }

    public function update(Request $request, CourseClass $class)
    {
        $validated = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
        ]);

        $class->update($validated);

        return redirect()->route('admin.classes.index')->with('status', 'Classe mise à jour.');
    }

    public function assign(CourseClass $class)
    {
        $students = \App\Models\User::role('student')->get();
        $coaches = \App\Models\User::role('coach')->get();
        $assignedUserIds = $class->users()->pluck('users.id')->toArray();

        return view('admin.classes.assign', [
            'courseClass' => $class,
            'students' => $students,
            'coaches' => $coaches,
            'assignedUserIds' => $assignedUserIds
        ]);
    }

    public function assignUpdate(Request $request, CourseClass $class)
    {
        $request->validate([
            'coach_id' => 'required|exists:users,id',
            'student_ids' => 'array',
            'student_ids.*' => 'exists:users,id'
        ]);

        $syncData = [];
        $syncData[$request->coach_id] = ['role' => 'coach'];
        foreach ($request->input('student_ids', []) as $studentId) {
            $syncData[$studentId] = ['role' => 'student'];
        }
        $class->users()->sync($syncData);

        return redirect()->route('admin.classes.index')->with('status', 'Affectations mises à jour avec succès.');
    }

    public function destroy(CourseClass $class)
    {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('status', 'Classe supprimée.');
    }
}
