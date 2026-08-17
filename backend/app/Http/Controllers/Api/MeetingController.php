<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAudioJob;
use App\Models\Meeting;
use App\Models\MeetingInvitation;
use App\Models\User;
use App\Notifications\MeetingInvited;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Meeting::with(['creator:id,name'])
            ->withCount(['invitations', 'invitations as attending_count' => fn($q) => $q->where('response', 'attending')]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('upcoming')) {
            $query->where('scheduled_at', '>=', now());
        }

        $meetings = $query->orderByDesc('scheduled_at')->paginate(15);

        return response()->json($meetings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:presidencia,sumo_consejo',
            'modality' => 'required|in:presencial,virtual,hibrida',
            'location_or_url' => 'nullable|string|max:500',
            'platform' => 'nullable|string|max:100',
            'agenda' => 'nullable|string',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'scheduled';

        $meeting = Meeting::create($validated);
        $meeting->load('creator:id,name');

        return response()->json([
            'message' => 'Reunión creada correctamente.',
            'data' => $meeting,
        ], 201);
    }

    public function show(Meeting $meeting): JsonResponse
    {
        $meeting->load([
            'creator:id,name',
            'invitations.user:id,name,email,role',
            'minute',
        ]);

        return response()->json(['data' => $meeting]);
    }

    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:presidencia,sumo_consejo',
            'modality' => 'sometimes|in:presencial,virtual,hibrida',
            'location_or_url' => 'sometimes|nullable|string|max:500',
            'platform' => 'sometimes|nullable|string|max:100',
            'agenda' => 'sometimes|nullable|string',
            'scheduled_at' => 'sometimes|date',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
        ]);

        $meeting->update($validated);

        return response()->json([
            'message' => 'Reunión actualizada.',
            'data' => $meeting->fresh(['creator:id,name']),
        ]);
    }

    public function destroy(Meeting $meeting): JsonResponse
    {
        $meeting->delete();
        return response()->json(['message' => 'Reunión eliminada.']);
    }

    public function invite(Request $request, Meeting $meeting): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $created = 0;
        foreach ($validated['user_ids'] as $userId) {
            $exists = MeetingInvitation::where('meeting_id', $meeting->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                MeetingInvitation::create([
                    'meeting_id' => $meeting->id,
                    'user_id' => $userId,
                    'response' => 'pending',
                ]);
                // Notificar al invitado
                User::find($userId)?->notify(new MeetingInvited($meeting));
                $created++;
            }
        }

        return response()->json([
            'message' => "Se invitaron {$created} usuarios.",
        ]);
    }

    public function rsvp(Request $request, Meeting $meeting): JsonResponse
    {
        $validated = $request->validate([
            'response' => 'required|in:attending,declined,tentative',
        ]);

        $invitation = MeetingInvitation::where('meeting_id', $meeting->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'No tienes invitación para esta reunión.'], 404);
        }

        $invitation->update(['response' => $validated['response']]);

        return response()->json([
            'message' => 'Respuesta registrada.',
            'data' => $invitation,
        ]);
    }

    public function uploadAudio(Request $request, Meeting $meeting): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg,webm|max:102400',
        ]);

        $path = $request->file('audio')->store("meetings/{$meeting->id}", 'local');

        ProcessAudioJob::dispatch($meeting, $path);

        return response()->json([
            'message' => 'Audio recibido. La transcripción se procesará en segundo plano.',
            'data' => ['path' => $path],
        ]);
    }
}
