<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Periodes de creation proposees dans le filtre.
     *
     * Les libelles sont definis ici plutot que dans la vue : le controleur
     * et la vue parlent ainsi des memes valeurs, et ajouter une periode ne
     * demande qu'une seule modification.
     */
    private const PERIODES = [
        'aujourdhui'     => "Aujourd'hui",
        'ce_mois'        => 'Ce mois-ci',
        'mois_dernier'   => 'Le mois dernier',
        'cette_annee'    => 'Cette année',
        'annee_derniere' => "L'année dernière",
        'personnalise'   => 'Période personnalisée',
    ];

    /** Granularites proposees pour la repartition des creations de comptes. */
    private const GRANULARITES = [
        'jour'  => 'Par jour',
        'mois'  => 'Par mois',
        'annee' => 'Par année',
    ];

    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre sur la date de creation du compte. Les valeurs venant de
        // l'URL sont filtrees par liste blanche : une valeur fantaisiste est
        // ignoree plutot que de renvoyer une erreur a l'administrateur.
        $periode = array_key_exists($request->input('periode'), self::PERIODES)
            ? $request->input('periode')
            : null;

        [$debut, $fin] = $this->intervalleCreation($periode, $request->input('du'), $request->input('au'));

        if ($debut) {
            $query->where('created_at', '>=', $debut);
        }
        if ($fin) {
            $query->where('created_at', '<=', $fin);
        }

        // Les comptes les plus recents en tete : c'est l'information la plus
        // souvent recherchee quand on ouvre cette page.
        $tri = $request->input('tri') === 'ancien' ? 'ancien' : 'recent';
        $query->orderBy('created_at', $tri === 'ancien' ? 'asc' : 'desc');

        $granularite = array_key_exists($request->input('granularite'), self::GRANULARITES)
            ? $request->input('granularite')
            : 'mois';

        // La repartition est calculee sur la requete filtree, avant la
        // pagination : elle decrit l'ensemble du resultat, pas la page affichee.
        $repartition = $this->repartitionCreations(clone $query, $granularite);

        // Comptes importes sans date de creation : signales a l'administrateur
        // pour qu'un resultat partiel ne passe pas pour un resultat complet.
        // Ce comptage precede la pagination, qui pose une limite sur la requete.
        $sansDate = (clone $query)->reorder()->whereNull('created_at')->count();

        $roles = Role::all();
        $users = $query->paginate(15)->appends($request->all());

        return view('admin.users.index', [
            'users'         => $users,
            'roles'         => $roles,
            'periodes'      => self::PERIODES,
            'granularites'  => self::GRANULARITES,
            'periode'       => $periode,
            'granularite'   => $granularite,
            'tri'           => $tri,
            'repartition'   => $repartition,
            'sansDate'      => $sansDate,
        ]);
    }

    /**
     * Traduit une periode choisie en intervalle de dates.
     *
     * @return array{0: ?Carbon, 1: ?Carbon} [debut, fin], null signifiant « pas de borne ».
     */
    private function intervalleCreation(?string $periode, ?string $du, ?string $au): array
    {
        return match ($periode) {
            'aujourdhui'     => [now()->startOfDay(), now()->endOfDay()],
            'ce_mois'        => [now()->startOfMonth(), now()->endOfMonth()],
            // subMonthNoOverflow evite qu'un 31 mars ne bascule au 2 mars.
            'mois_dernier'   => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'cette_annee'    => [now()->startOfYear(), now()->endOfYear()],
            'annee_derniere' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'personnalise'   => [
                $this->dateOuNull($du)?->startOfDay(),
                $this->dateOuNull($au)?->endOfDay(),
            ],
            default          => [null, null],
        };
    }

    /** Lit une date saisie sans jamais lever d'exception sur une valeur invalide. */
    private function dateOuNull(?string $valeur): ?Carbon
    {
        if (! $valeur) {
            return null;
        }

        try {
            return Carbon::parse($valeur);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Compte les creations de comptes par jour, par mois ou par annee.
     *
     * Le regroupement est fait en PHP plutot qu'en SQL : les fonctions de
     * date different entre MySQL et SQLite, et le volume d'utilisateurs d'une
     * ecole ne justifie pas d'ecrire une requete par moteur.
     *
     * @return array<int, array{cle: string, libelle: string, total: int, variation: ?int}>
     */
    private function repartitionCreations($query, string $granularite): array
    {
        $formats = ['jour' => 'Y-m-d', 'mois' => 'Y-m', 'annee' => 'Y'];
        $format = $formats[$granularite] ?? $formats['mois'];

        $comptes = $query->reorder()
            ->whereNotNull('created_at')
            ->pluck('created_at')
            ->groupBy(fn ($date) => Carbon::parse($date)->format($format))
            ->map->count()
            ->sortKeysDesc();

        $cles = $comptes->keys()->all();
        $lignes = [];

        foreach ($cles as $rang => $cle) {
            // La liste est triee du plus recent au plus ancien : la periode
            // precedente est donc la ligne suivante du tableau.
            $precedente = $cles[$rang + 1] ?? null;

            $lignes[] = [
                'cle'       => $cle,
                'libelle'   => $this->libellePeriode($cle, $granularite),
                'total'     => $comptes[$cle],
                'variation' => $precedente === null ? null : $comptes[$cle] - $comptes[$precedente],
            ];
        }

        return $lignes;
    }

    /** Met en forme la cle de regroupement pour un lecteur francophone. */
    private function libellePeriode(string $cle, string $granularite): string
    {
        if ($granularite === 'annee') {
            return $cle;
        }

        if ($granularite === 'jour') {
            return Carbon::parse($cle)->format('d/m/Y');
        }

        // Les noms de mois sont ecrits ici plutot que delegues a l'extension
        // intl, qui n'est pas garantie sur un hebergement mutualise.
        $mois = [
            1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];

        [$annee, $numero] = explode('-', $cle);

        return $mois[(int) $numero].' '.$annee;
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'admin')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name|not_in:admin',
            'status' => 'required|in:active,inactive',
            'full_phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['full_phone'] ?? null,
            'status' => $validated['status'],
            'avatar' => $avatarPath,
        ]);

        $user->assignRole($validated['role']);
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur créé avec succès.');
    }

    public function show(string $id)
    {
        // Not specifically needed for now
    }

    public function edit(User $user)
    {
        if (Auth::id() === $user->id) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'admin')->get();
        }
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $roleValidation = 'required|exists:roles,name';
        if (Auth::id() !== $user->id) {
            $roleValidation .= '|not_in:admin';
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => $roleValidation,
            'status' => 'required|in:active,inactive',
            'full_phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['full_phone'] ?? $user->phone,
            'status' => $validated['status'],
        ];

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($updateData);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $user->update(['password' => bcrypt($request->password)]);
        }

        $user->syncRoles([$validated['role']]);
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.users.index')->withErrors(['error' => 'Impossible de supprimer un compte administrateur.']);
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('status', 'Utilisateur supprimé.');
    }
}
