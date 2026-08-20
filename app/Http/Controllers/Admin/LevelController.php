<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use App\Models\Level;
use App\Models\Program;

class LevelController extends Controller
{
    use ExportsCsv;

    /**
     * Construit la requete des niveaux filtree par les criteres de l'URL,
     * sans pagination : utilisee telle quelle par l'ecran et par l'export CSV.
     */
    private function filtrerNiveaux(Request $request): Builder
    {
        $query = Level::with('program');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function index(Request $request)
    {
        $levels = $this->filtrerNiveaux($request)->paginate(15)->appends($request->all());
        return view('admin.levels.index', compact('levels'));
    }

    /**
     * Export CSV des niveaux correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $levels = $this->filtrerNiveaux($request)->get();

        $lignes = $levels->map(fn (Level $level) => [
            optional($level->program)->name ?? 'Programme supprimé',
            $level->name,
            $level->order,
        ]);

        return $this->streamCsv('niveaux.csv', ['Programme', 'Nom du niveau', 'Ordre'], $lignes);
    }

    public function create()
    {
        $programs = Program::all();
        return view('admin.levels.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
        ]);

        Level::create($validated);

        return redirect()->route('admin.levels.index')->with('status', 'Niveau créé avec succès.');
    }

    public function show(string $id)
    {
        // Not used
    }

    public function edit(Level $level)
    {
        $programs = Program::all();
        return view('admin.levels.edit', compact('level', 'programs'));
    }

    public function update(Request $request, Level $level)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
        ]);

        $level->update($validated);

        return redirect()->route('admin.levels.index')->with('status', 'Niveau mis à jour.');
    }

    public function destroy(Level $level)
    {
        $level->delete();
        return redirect()->route('admin.levels.index')->with('status', 'Niveau supprimé.');
    }
}
