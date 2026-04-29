<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiInstruction;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class AiInstructionController extends Controller
{
    public function index()
    {
        $instructions = AiInstruction::latest()->paginate(20);

        return Inertia::render('Admin/Instructions/Index', [
            'instructions' => $instructions,
        ]);
    }

    public function edit(AiInstruction $instruction)
    {
        return Inertia::render('Admin/Instructions/Edit', [
            'instruction' => $instruction,
        ]);
    }

    public function update(AiInstruction $instruction)
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $instruction->update($validated);

        return Redirect::route('admin.instructions.index')->with('success', 'Instruction updated successfully.');
    }
}
