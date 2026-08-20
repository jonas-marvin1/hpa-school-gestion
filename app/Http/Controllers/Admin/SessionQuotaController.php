<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\SessionQuota;
use App\Services\AnneesDisponibles;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Suivi du quota de sessions par classe et par mois.
 *
 * Consultation et saisie des quotas : le blocage effectif de la creation de
 * session, lui, vit dans GuardsSessionQuota, partage avec les controleurs
 * coach et manager qui creent ou deplacent des sessions.
 */
class SessionQuotaController extends Controller
{
    /** Libelles des mois, ecrits ici plutot que delegues a l'extension intl. */
    public const MOIS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function index(Request $request)
    {
        $annee = (int) ($request->input('year') ?: now()->year);
        $mois = (int) ($request->input('month') ?: now()->month);

        // Liste blanche : un mois hors 1-12 revient au mois courant plutot
        // que de planter la page.
        if ($mois < 1 || $mois > 12) {
            $mois = now()->month;
        }

        $debut = Carbon::create($annee, $mois, 1)->startOfMonth();
        $fin = $debut->copy()->endOfMonth();

        // Comptage en PHP plutot qu'en SQL : coherent avec le reste de
        // l'application, et evite les fonctions de date specifiques a MySQL
        // qui n'existent pas sous SQLite (utilise par les tests).
        $sessions = ClassSession::whereBetween('start_time', [$debut, $fin])->get(['course_class_id', 'status']);

        $generees = [];
        $realisees = [];
        foreach ($sessions as $session) {
            if ($session->status !== 'cancelled') {
                $generees[$session->course_class_id] = ($generees[$session->course_class_id] ?? 0) + 1;
            }
            if ($session->status === 'completed') {
                $realisees[$session->course_class_id] = ($realisees[$session->course_class_id] ?? 0) + 1;
            }
        }

        $quotas = SessionQuota::where('year', $annee)->where('month', $mois)->pluck('quota', 'course_class_id');

        $lignes = CourseClass::with('level.program')->orderBy('name')->get()->map(function (CourseClass $classe) use ($generees, $realisees, $quotas) {
            $quota = $quotas[$classe->id] ?? null;
            $genere = $generees[$classe->id] ?? 0;

            return (object) [
                'classe'   => $classe,
                'quota'    => $quota,
                'genere'   => $genere,
                'realise'  => $realisees[$classe->id] ?? 0,
                'atteint'  => $quota !== null && $genere === $quota,
                'depasse'  => $quota !== null && $genere > $quota,
            ];
        });

        $years = AnneesDisponibles::depuisColonneDate(ClassSession::query(), 'start_time');

        return view('admin.session-quotas.index', [
            'lignes'    => $lignes,
            'annee'     => $annee,
            'mois'      => $mois,
            'moisListe' => self::MOIS,
            'years'     => $years,
        ]);
    }

    /**
     * Enregistre ou met a jour le quota d'une classe pour un mois donne.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'year'  => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'quota' => 'required|integer|min:1',
        ]);

        SessionQuota::updateOrCreate(
            [
                'course_class_id' => $validated['course_class_id'],
                'year'            => $validated['year'],
                'month'           => $validated['month'],
            ],
            ['quota' => $validated['quota']]
        );

        return redirect()
            ->route('admin.session-quotas.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('status', 'Quota enregistré.');
    }
}
