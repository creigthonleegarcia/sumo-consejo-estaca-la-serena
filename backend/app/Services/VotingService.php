<?php

namespace App\Services;

use App\Models\Calling;
use App\Models\CallingVote;
use App\Models\User;

class VotingService
{
    /**
     * Registrar un voto en un llamamiento.
     */
    public function castVote(Calling $calling, User $user, string $vote, ?string $comment = null): array
    {
        // Verificar que la votación esté abierta
        if (!$calling->isVotingOpen()) {
            return ['success' => false, 'error' => 'La votación ya está cerrada.'];
        }

        // Verificar que no haya votado ya
        if ($calling->hasUserVoted($user->id)) {
            return ['success' => false, 'error' => 'Ya has votado en este llamamiento.'];
        }

        // Verificar que sea elegible (sumo_consejo)
        if (!$user->isSumoConsejo() && !$user->isPresidencia() && !$user->isAdmin()) {
            return ['success' => false, 'error' => 'No tienes permiso para votar.'];
        }

        // Registrar voto
        CallingVote::create([
            'calling_id' => $calling->id,
            'user_id' => $user->id,
            'vote' => $vote,
            'comment' => $comment,
        ]);

        // Verificar si se alcanzó quórum
        $eligibleVoters = User::where('role', 'sumo_consejo')
            ->where('is_active', true)
            ->count();

        $hasQuorum = $calling->fresh()->hasQuorum($eligibleVoters);
        $resolved = false;

        // Auto-resolver si hay quórum
        if ($hasQuorum && $calling->status === 'pending') {
            $calling->update(['status' => 'approved']);
            $resolved = true;
        }

        return [
            'success' => true,
            'message' => 'Voto registrado correctamente.',
            'has_quorum' => $hasQuorum,
            'resolved' => $resolved,
        ];
    }

    /**
     * Cerrar votaciones expiradas.
     */
    public function closeExpiredVotings(): int
    {
        $expired = Calling::where('status', 'pending')
            ->where('voting_deadline', '<', now())
            ->get();

        $closed = 0;
        $eligibleVoters = User::where('role', 'sumo_consejo')
            ->where('is_active', true)
            ->count();

        foreach ($expired as $calling) {
            $newStatus = $calling->hasQuorum($eligibleVoters) ? 'approved' : 'rejected';
            $calling->update(['status' => $newStatus]);
            $closed++;
        }

        return $closed;
    }
}
