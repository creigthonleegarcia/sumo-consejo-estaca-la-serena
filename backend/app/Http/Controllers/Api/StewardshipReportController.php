<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StewardshipReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StewardshipReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StewardshipReport::with(['user:id,name', 'assignment:id,title']);

        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        // Si es sumo_consejo, solo ver los propios
        if ($request->user()->isSumoConsejo()) {
            $query->where('user_id', $request->user()->id);
        }

        $reports = $query->orderByDesc('period_end')->paginate(15);

        return response()->json($reports);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'content' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['submitted_at'] = now();

        $report = StewardshipReport::create($validated);
        $report->load(['user:id,name', 'assignment:id,title']);

        return response()->json([
            'message' => 'Informe enviado correctamente.',
            'data' => $report,
        ], 201);
    }

    public function show(StewardshipReport $report): JsonResponse
    {
        $report->load(['user:id,name,email', 'assignment:id,title,description']);

        return response()->json(['data' => $report]);
    }
}
