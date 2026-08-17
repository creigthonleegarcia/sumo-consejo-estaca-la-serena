<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calling;
use App\Models\User;
use App\Notifications\NewCallingProposed;
use App\Services\VotingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Calling::with(['proposer:id,name'])
            ->withCount('votes');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('member_name', 'like', "%{$search}%")
                  ->orWhere('calling_name', 'like', "%{$search}%")
                  ->orWhere('ward', 'like', "%{$search}%");
            });
        }

        $callings = $query->orderByDesc('created_at')->paginate(15);

        // Agregar info de votación a cada calling
        $callings->getCollection()->transform(function ($calling) use ($request) {
            $calling->approval_count = $calling->approvalCount();
            $calling->disapproval_count = $calling->disapprovalCount();
            $calling->has_user_voted = $calling->hasUserVoted($request->user()->id);
            $calling->is_voting_open = $calling->isVotingOpen();
            return $calling;
        });

        return response()->json($callings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_name' => 'required|string|max:255',
            'calling_name' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'voting_deadline' => 'required|date|after:now',
        ]);

        $validated['proposed_by'] = $request->user()->id;
        $validated['status'] = 'pending';

        $calling = Calling::create($validated);
        $calling->load('proposer:id,name');

        // Notificar a votantes elegibles
        User::where('role', 'sumo_consejo')->where('is_active', true)->get()
            ->each(fn($u) => $u->notify(new NewCallingProposed($calling)));

        return response()->json([
            'message' => 'Llamamiento propuesto correctamente.',
            'data' => $calling,
        ], 201);
    }

    public function show(Calling $calling, Request $request): JsonResponse
    {
        $calling->load(['proposer:id,name', 'votes.user:id,name']);

        $calling->approval_count = $calling->approvalCount();
        $calling->disapproval_count = $calling->disapprovalCount();
        $calling->total_votes = $calling->totalVotes();
        $calling->has_user_voted = $calling->hasUserVoted($request->user()->id);
        $calling->is_voting_open = $calling->isVotingOpen();

        // Total de votantes elegibles (sumo_consejo activos)
        $eligibleVoters = User::where('role', 'sumo_consejo')
            ->where('is_active', true)
            ->count();
        $calling->eligible_voters = $eligibleVoters;
        $calling->has_quorum = $calling->hasQuorum($eligibleVoters);

        return response()->json(['data' => $calling]);
    }

    public function update(Request $request, Calling $calling): JsonResponse
    {
        $validated = $request->validate([
            'member_name' => 'sometimes|string|max:255',
            'calling_name' => 'sometimes|string|max:255',
            'ward' => 'sometimes|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'voting_deadline' => 'sometimes|date',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
        ]);

        $calling->update($validated);

        return response()->json([
            'message' => 'Llamamiento actualizado.',
            'data' => $calling->fresh(['proposer:id,name']),
        ]);
    }

    public function destroy(Calling $calling): JsonResponse
    {
        $calling->delete();
        return response()->json(['message' => 'Llamamiento eliminado.']);
    }

    public function vote(Request $request, Calling $calling, VotingService $votingService): JsonResponse
    {
        $validated = $request->validate([
            'vote' => 'required|in:approve,disapprove',
            'comment' => 'nullable|string|max:500',
        ]);

        $result = $votingService->castVote($calling, $request->user(), $validated['vote'], $validated['comment'] ?? null);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'approval_count' => $calling->fresh()->approvalCount(),
                'disapproval_count' => $calling->fresh()->disapprovalCount(),
                'has_quorum' => $result['has_quorum'] ?? false,
                'resolved' => $result['resolved'] ?? false,
            ],
        ]);
    }
}
