<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentPayment;
use App\Services\AnneesDisponibles;
use Illuminate\Http\Request;

/**
 * Vue d'ensemble, tous apprenants confondus, des paiements attendus pour un
 * mois donne.
 *
 * Le dashboard admin ne montre que les echeances du jour et en retard ; cette
 * vue donne la projection complete du mois, y compris ce qui n'est pas
 * encore arrive a echeance.
 */
class StudentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $mois = (int) ($request->input('month') ?: now()->month);
        $annee = (int) ($request->input('year') ?: now()->year);

        // Liste blanche : un mois hors 1-12 revient au mois courant plutot
        // que de planter la page.
        if ($mois < 1 || $mois > 12) {
            $mois = now()->month;
        }

        $base = StudentPayment::whereMonth('due_date', $mois)->whereYear('due_date', $annee);

        if ($request->filled('search')) {
            $recherche = $request->search;
            $base->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$recherche}%"));
        }

        // Totaux calcules sur l'ensemble du mois filtre (hors filtre de
        // statut, qui ne sert qu'a affiner le tableau) : la vision
        // previsionnelle doit rester complete meme quand on affiche une
        // seule categorie.
        $echeancesDuMois = (clone $base)->get(['status', 'due_date', 'amount']);
        $totalAttendu = (float) $echeancesDuMois->sum('amount');
        $totalRegle = (float) $echeancesDuMois->where('status', 'paid')->sum('amount');
        $totalEnRetard = (float) $echeancesDuMois
            ->filter(fn ($e) => $e->status !== 'paid' && $e->due_date->isPast())
            ->sum('amount');
        $totalAVenir = max(0, $totalAttendu - $totalRegle - $totalEnRetard);

        $liste = (clone $base)->with(['student', 'program', 'paymentPlan']);

        // Le statut "en retard" n'est jamais stocke en base : comme dans
        // l'espace apprenant, il se deduit d'une echeance non payee dont la
        // date est depassee.
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $liste->where('status', 'pending')->whereDate('due_date', '<', today());
            } elseif ($request->status === 'pending') {
                $liste->where('status', 'pending')->whereDate('due_date', '>=', today());
            } else {
                $liste->where('status', $request->status);
            }
        }

        $paiements = $liste->orderBy('due_date')->paginate(20)->appends($request->all());

        $years = AnneesDisponibles::depuisColonneDate(StudentPayment::query(), 'due_date');

        return view('admin.student-payments.index', [
            'paiements'     => $paiements,
            'mois'          => $mois,
            'annee'         => $annee,
            'years'         => $years,
            'totalAttendu'  => $totalAttendu,
            'totalRegle'    => $totalRegle,
            'totalAVenir'   => $totalAVenir,
            'totalEnRetard' => $totalEnRetard,
        ]);
    }
}
