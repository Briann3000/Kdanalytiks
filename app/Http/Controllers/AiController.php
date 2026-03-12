<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiService;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a survey schema from a text prompt.
     */
    public function generateSchema(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $schema = $this->aiService->generateSurveySchema($request->prompt);

        if (!$schema) {
            return response()->json([
                'success' => false,
                'message' => 'AI was unable to generate a schema. Please try a different prompt.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'schema' => json_encode($schema)
        ]);
    }
}
