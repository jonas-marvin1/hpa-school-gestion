<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use App\Models\Program;

class ProgramController extends Controller
{
    use ExportsCsv;

    /**
     * Construit la requete des programmes filtree par les criteres de l'URL,
     * sans pagination : utilisee telle quelle par l'ecran et par l'export CSV.
     */
    private function filtrerProgrammes(Request $request): Builder
    {
        $query = Program::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function index(Request $request)
    {
        $programs = $this->filtrerProgrammes($request)->paginate(15)->appends($request->all());
        return view('admin.programs.index', compact('programs'));
    }

    /**
     * Export CSV des programmes correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $programs = $this->filtrerProgrammes($request)->get();

        $lignes = $programs->map(fn (Program $program) => [
            $program->name,
            $program->description ?? '',
        ]);

        return $this->streamCsv('programmes.csv', ['Nom', 'Description'], $lignes);
    }

    public function create()
    {
        return view('admin.programs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:programs',
            'description' => 'nullable|string',
        ]);

        Program::create($validated);

        return redirect()->route('admin.programs.index')->with('status', 'Programme créé avec succès.');
    }

    public function show(string $id)
    {
        // Not used
    }

    public function edit(Program $program)
    {
        return view('admin.programs.edit', compact('program'));
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:programs,name,'.$program->id,
            'description' => 'nullable|string',
        ]);

        $program->update($validated);

        return redirect()->route('admin.programs.index')->with('status', 'Programme mis à jour.');
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->route('admin.programs.index')->with('status', 'Programme supprimé.');
    }
}
