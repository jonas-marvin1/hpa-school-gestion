<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Level;
use App\Models\Program;

class LevelController extends Controller
{
    public function index(Request $request)
    {
        $query = Level::with('program');
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        $levels = $query->paginate(15)->appends($request->all());
        return view('admin.levels.index', compact('levels'));
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
