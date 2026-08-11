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

    /**
     * Deactivate all knowledge base rules for the user.
     */
    public function deactivateAll(Request $request): JsonResponse
    {
        $request->user()->sociusKnowledgeBases()->update(['is_active' => false]);

        return response()->json([
            'message' => 'All knowledge base instructions deactivated.',
        ]);
    }

    /**
     * Delete all knowledge base rules for the user.
     */
    public function deleteAll(Request $request): JsonResponse
    {
        $request->user()->sociusKnowledgeBases()->delete();

        return response()->json([
            'message' => 'All knowledge base instructions deleted.',
        ]);
    }

    /**
     * Upload a full knowledge base document (PDF, DOCX, TXT) to extract text into KB rules.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,docx,doc,txt,md', 'max:20480'],
        ]);

        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $file->getClientOriginalName();
        $extractedText = '';

        try {
            if ($extension === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $extractedText = $pdf->getText();
            } elseif ($extension === 'docx') {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
                $fullText = [];
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $fullText[] = $element->getText();
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $fullText[] = $child->getText();
                                }
                            }
                        }
                    }
                }
                $extractedText = implode("\n", $fullText);
            } else {
                $extractedText = file_get_contents($file->getRealPath());
            }

            $extractedText = trim(preg_replace('/\s+/', ' ', $extractedText));

            if (empty($extractedText)) {
                return response()->json([
                    'message' => 'Unable to extract text from the uploaded document.',
                ], 422);
            }

            $chunks = str_split($extractedText, 4000);
            $createdRules = [];

            foreach ($chunks as $index => $chunk) {
                $ruleTitle = count($chunks) > 1
                    ? "[Book/Doc: {$filename} - Part " . ($index + 1) . "]: "
                    : "[Book/Doc: {$filename}]: ";

                $rule = $request->user()->sociusKnowledgeBases()->create([
                    'content' => $ruleTitle . $chunk,
                    'is_active' => true,
                ]);
                $createdRules[] = $rule;
            }

            return response()->json([
                'message' => "Document '{$filename}' uploaded and learned successfully (" . count($createdRules) . " module(s) created).",
                'rules' => $createdRules,
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Knowledge base document upload error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to parse document: ' . $e->getMessage(),
            ], 500);
        }
    }
}
