<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CourseClass;
use App\Models\User;

class CourseClassAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseClass::with(['level.program']);
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            // La recherche ne portait que sur le nom de la classe, alors que la
            // liste affiche aussi le programme et le niveau. Le groupe where()
            // est indispensable : sans lui, un futur filtre ajoute apres serait
            // avale par le OR.
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('level', function ($l) use ($search) {
                      $l->where('name', 'like', "%{$search}%")
                        ->orWhereHas('program', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }
        
        $classes = $query->paginate(10)->appends($request->all());
        return view('manager.classes.index', compact('classes'));
    }

    public function edit(CourseClass $courseClass)
    {
        // Load all students and coaches
        $students = User::role('student')->get();
        $coaches = User::role('coach')->get();
        
        // Get currently assigned user IDs
        $assignedUserIds = $courseClass->users()->pluck('users.id')->toArray();

        return view('manager.classes.assign', compact('courseClass', 'students', 'coaches', 'assignedUserIds'));
    }

    public function update(Request $request, CourseClass $courseClass)
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
        $courseClass->users()->sync($syncData);

        return redirect()->route('manager.classes.index')->with('status', 'Affectations mises à jour avec succès.');
    }
}
