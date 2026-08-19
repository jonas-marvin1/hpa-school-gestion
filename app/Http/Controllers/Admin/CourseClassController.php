<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CourseClass;
use App\Models\Level;

class CourseClassController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseClass::with('level.program');
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        $classes = $query->paginate(15)->appends($request->all());
        return view('admin.classes.index', compact('classes'));
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
