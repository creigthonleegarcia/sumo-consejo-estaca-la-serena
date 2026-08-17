<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\User;
use App\Notifications\AssignmentCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Assignment::with(['creator:id,name', 'assignee:id,name'])
            ->withCount('reports');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Si es sumo_consejo, solo ver las propias
        if ($request->user()->isSumoConsejo()) {
            $query->where('assigned_to', $request->user()->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($assignments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date|after:today',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'pending';

        $assignment = Assignment::create($validated);
        $assignment->load(['creator:id,name', 'assignee:id,name']);

        // Notificar al asignado
        $assignee = User::find($validated['assigned_to']);
        $assignee?->notify(new AssignmentCreated($assignment));

        return response()->json([
            'message' => 'Asignación creada correctamente.',
            'data' => $assignment,
        ], 201);
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $assignment->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'reports' => fn($q) => $q->orderByDesc('period_end'),
            'reports.user:id,name',
        ]);

        return response()->json(['data' => $assignment]);
    }

    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'assigned_to' => 'sometimes|exists:users,id',
            'due_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
        ]);

        $assignment->update($validated);

        return response()->json([
            'message' => 'Asignación actualizada.',
            'data' => $assignment->fresh(['creator:id,name', 'assignee:id,name']),
        ]);
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $assignment->delete();

        return response()->json(['message' => 'Asignación eliminada.']);
    }
}
