<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Attendance;
use Illuminate\Http\Request;

class SessionFeedbackController extends Controller
{
    public function create(ClassSession $session)
    {
        // Vérifier que l'étudiant appartient à la classe de la session
        if (!$session->courseClass->users->contains(auth()->id())) {
            abort(403, 'Vous n\'êtes pas autorisé à évaluer cette session.');
        }

        // Vérifier que l'étudiant était présent
        $attendance = Attendance::where('class_session_id', $session->id)
                                ->where('student_id', auth()->id())
                                ->first();

        if (!$attendance || !$attendance->is_present) {
            return redirect()->route('student.dashboard')->with('error', 'Seuls les étudiants présents peuvent laisser un avis.');
        }

        // Vérifier si un avis a déjà été donné
        if ($attendance->feedback) {
            return redirect()->route('student.dashboard')->with('info', 'Vous avez déjà donné votre avis pour cette session.');
        }

        return view('student.sessions.feedback', compact('session'));
    }

    public function store(Request $request, ClassSession $session)
    {
        // Vérifier que l'étudiant appartient à la classe
        if (!$session->courseClass->users->contains(auth()->id())) {
            abort(403);
        }

        $attendance = Attendance::where('class_session_id', $session->id)
                                ->where('student_id', auth()->id())
                                ->first();

        if (!$attendance || !$attendance->is_present) {
            return redirect()->route('student.dashboard')->with('error', 'Seuls les étudiants présents peuvent laisser un avis.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string',
        ]);

        $attendance->update([
            'rating' => $validated['rating'],
            'feedback' => $validated['feedback'],
            'feedback_status' => 'pending'
        ]);

        return redirect()->route('student.dashboard')->with('success', 'Votre avis a été enregistré avec succès et est en attente de validation.');
    }
}
