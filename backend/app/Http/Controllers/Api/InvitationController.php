<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Crear una nueva invitación (solo Presidencia).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:presidencia,secretario,sumo_consejo',
        ]);

        $invitation = Invitation::create([
            'email' => $request->email,
            'role' => $request->role,
            'token' => Str::random(64),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $invitationUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'))
            . '/invitation/' . $invitation->token;

        return response()->json([
            'message' => 'Invitación creada exitosamente.',
            'data' => $invitation,
            'invitation_url' => $invitationUrl,
        ], 201);
    }

    /**
     * Verificar token de invitación.
     */
    public function show(string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return response()->json([
                'message' => 'Esta invitación ha expirado.',
            ], 410);
        }

        if ($invitation->isAccepted()) {
            return response()->json([
                'message' => 'Esta invitación ya fue utilizada.',
            ], 410);
        }

        $roleLabels = [
            'presidencia' => 'Presidencia de Estaca',
            'secretario' => 'Secretario de Estaca',
            'sumo_consejo' => 'Sumo Consejo',
        ];

        return response()->json([
            'data' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'role_label' => $roleLabels[$invitation->role],
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }

    /**
     * Aceptar invitación y crear cuenta.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired() || $invitation->isAccepted()) {
            return response()->json([
                'message' => 'Esta invitación ya no es válida.',
            ], 410);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $invitation->role,
            'email_verified_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'message' => 'Cuenta creada exitosamente. Ya puedes iniciar sesión.',
            'data' => $user,
        ], 201);
    }
}
