<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function improveText(Request $request, AiTextService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|min:10|max:5000',
            'context' => 'nullable|string|max:200',
        ]);

        $result = $aiService->improveReport(
            $validated['text'],
            $validated['context'] ?? 'informe de mayordomía'
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'data' => [
                'improved_text' => $result['text'],
            ],
        ]);
    }
}
