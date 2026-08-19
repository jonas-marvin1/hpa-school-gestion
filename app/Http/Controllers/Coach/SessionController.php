<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\SessionReport;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = ClassSession::where('coach_id', Auth::id())
            ->with(['courseClass', 'sessionReport'])
            ->latest('start_time')
            ->get();
            
        return view('coach.sessions.index', compact('sessions'));
    }

    public function create()
    {
        // Coach can only report for classes they are assigned to
        $classes = Auth::user()->courseClasses;
        return view('coach.sessions.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'progress' => 'required|string',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'attendances' => 'array',
        ]);

        // Verify coach is assigned to class
        $class = CourseClass::findOrFail($validated['course_class_id']);
        if (!$class->users->contains(Auth::id())) {
            abort(403, 'Vous n\'êtes pas assigné à cette classe.');
        }

        // Create Session
        $session = ClassSession::create([
            'course_class_id' => $validated['course_class_id'],
            'coach_id' => Auth::id(),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'completed',
        ]);

        // Create Report
        SessionReport::create([
            'class_session_id' => $session->id,
            'progress' => $validated['progress'],
            'observations' => $validated['observations'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
        ]);

        // Process Attendances
        $students = $class->users()->role('student')->get();
        foreach ($students as $student) {
            $isPresent = isset($validated['attendances'][$student->id]) && $validated['attendances'][$student->id];
            Attendance::create([
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'is_present' => $isPresent,
            ]);
        }

        return redirect()->route('coach.sessions.index')->with('status', 'Session déclarée avec succès !');
    }

    public function show(ClassSession $session)
    {
        if ($session->coach_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $session->load([
            'courseClass.level.program',
            'sessionReport',
            'attendances.student',
        ]);

        return view('coach.sessions.show', compact('session'));
    }

    public function cancel(ClassSession $session)
    {
        if ($session->coach_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($session->status, ['scheduled', 'completed'])) {
            return redirect()->route('coach.sessions.index')->with('error', 'Cette session ne peut plus être annulée.');
        }

        $session->update(['status' => 'cancelled']);

        return redirect()->route('coach.sessions.index')->with('status', 'Session annulée.');
    }
}
