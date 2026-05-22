<?php

namespace App\Http\Controllers;

use App\Models\SociusKnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SociusKnowledgeBaseController extends Controller
{
    /**
     * Display a listing of the user's knowledge base rules.
     */
    public function index(Request $request): JsonResponse
    {
        $rules = $request->user()
            ->sociusKnowledgeBases()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'rules' => $rules,
        ]);
    }

    /**
     * Store a newly created rule in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule = $request->user()->sociusKnowledgeBases()->create([
            'content' => $validated['content'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'rule' => $rule,
            'message' => 'Rule added successfully.',
        ], 201);
    }

    /**
     * Update the specified rule in storage.
     */
    public function update(Request $request, SociusKnowledgeBase $knowledgeBase): JsonResponse
    {
        if ($knowledgeBase->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        $knowledgeBase->update($validated);

        return response()->json([
            'rule' => $knowledgeBase,
            'message' => 'Rule updated successfully.',
        ]);
    }

    /**
     * Remove the specified rule from storage.
     */
    public function destroy(Request $request, SociusKnowledgeBase $knowledgeBase): JsonResponse
    {
        if ($knowledgeBase->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $knowledgeBase->delete();

        return response()->json([
            'message' => 'Rule deleted successfully.',
        ]);
    }
}
