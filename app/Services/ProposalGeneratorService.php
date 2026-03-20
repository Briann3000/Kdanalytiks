<?php

namespace App\Services;

use App\Models\ResearchProposal;
use Illuminate\Support\Facades\Log;

class ProposalGeneratorService
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a formal research proposal based on user inputs.
     */
    public function generateProposal(ResearchProposal $proposal)
    {
        set_time_limit(300);

        $sections = [
            'Introduction' => "Provide a high-level overview of the research topic and its significance.",
            'Problem Statement' => "Clearly define the research problem, gaps in current knowledge, and the necessity of this study.",
            'Research Objectives' => "Detail the specific goals and intended outcomes of the research.",
            'Methodology' => "Write a detailed methodology section for this research.",
            'Expected Outcomes & Scope' => "Discuss the potential impact of the findings and the boundaries of the study.",
            'Timeline & Phases' => "Outline a logical progression of the research from start to completion."
        ];

        $generatedContent = [];

        foreach ($sections as $title => $instruction) {
            // 🔥 Modify instruction ONLY for Methodology
            if ($title === 'Methodology') {
                    $instruction .= "\n\nFORMAT:
                    Use subheadings:
                    1. Research Design
                    2. Population and Sampling
                    3. Data Collection Methods
                    4. Data Analysis Techniques
                    5. Ethical Considerations";
                }

            $prompt = "Draft the '{$title}' section of a formal academic research proposal.\n\n" .
                "USER INPUTS:\n" .
                "Title: {$proposal->title}\n" .
                "Research Question: {$proposal->research_question}\n" .
                "Objectives: {$proposal->objectives}\n" .
                "Methodology Type: {$proposal->methodology_type}\n" .
                "Target Population: {$proposal->target_population}\n" .
                "Scope: {$proposal->scope}\n\n" .
                "SECTION INSTRUCTION: {$instruction}\n\n" .
                "STYLE RULES:
                - Follow {$proposal->style} academic standards
                - Use formal, objective, and authoritative language
                - Use structured headings where appropriate
                - Avoid vague statements".
            $systemPrompt = "You are a professional academic consultant specializing in grant writing and research design. " .
                "Your goal is to transform sparse researcher inputs into high-quality, persuasive, and logically sound proposal drafts.";

            Log::info("Generating proposal section: {$title} for Research ID: {$proposal->id}");
            $content = $this->aiService->callGroq($prompt, $systemPrompt);
            if (!$content) {
                            sleep(2);
                            $content = $this->aiService->callGroq($prompt, $systemPrompt);}
            $generatedContent[$title] = $content ?? "[Generation failed]";
        }

        $proposal->update([
            'content' => $generatedContent,
            'status' => 'generated'
        ]);

        return $proposal;
    }
}
