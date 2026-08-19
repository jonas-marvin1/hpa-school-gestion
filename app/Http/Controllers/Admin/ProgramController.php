<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Program;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::query();
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        $programs = $query->paginate(15)->appends($request->all());
        return view('admin.programs.index', compact('programs'));
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
