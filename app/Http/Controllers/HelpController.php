<?php

namespace App\Http\Controllers;

use App\Models\Survey;

class HelpController extends Controller
{
    /**
     * Display the Help page with searchable FAQs.
     */
    public function index()
    {
        $faqs = [
            [
                'question' => 'How do I create my first survey?',
                'answer' => 'Log in to your dashboard, click the "+ New Survey" button, enter a title, choose a category, and use our drag-and-drop builder to design your questionnaire.',
                'category' => 'Builder'
            ],
            [
                'question' => 'What are Analysis Groups?',
                'answer' => 'Analysis Groups allow you to split researchers or students into isolated groups. Members of a group can view the survey reports and use Socius AI to analyze results together, but they cannot see the conversations or threads of other groups.',
                'category' => 'Collaboration'
            ],
            [
                'question' => 'How can I add collaborators to my survey?',
                'answer' => 'Go to the survey\'s settings tab, scroll down to the "Collaborators" card, type the user\'s email, select their granular permissions (e.g. view submissions, edit form), and click "Save Collaborator".',
                'category' => 'Collaboration'
            ],
            [
                'question' => 'What is Socius AI?',
                'answer' => 'Socius is your AI analysis partner. Under the "Analyse" tab in reports, you can start a chat thread to ask questions about your survey responses, generate crosstabs, request executive summaries, or ask for data synthesis.',
                'category' => 'Socius'
            ],
            [
                'question' => 'How do rewarded surveys work?',
                'answer' => 'You can set a reward budget for paid surveys. Respondents receive a cash reward per completed response which goes directly to their local wallet. As an owner, you fund this budget when deploying the survey.',
                'category' => 'Billing'
            ],
            [
                'question' => 'Can I export my survey results?',
                'answer' => 'Yes. Under the "Data" and "Reports" tabs, you can export your response data and reports to PDF, Word (DOCX), Excel (XLSX), or JSON. Excel exports are available for Pro and Enterprise plans.',
                'category' => 'Analytics'
            ]
        ];

        return view('help.index', compact('faqs'));
    }


    /**
     * Launch a guided tour from the Help Center by redirecting to the right page.
     */
    public function launchTour(string $tour)
    {
        $user = auth()->user();

        abort_unless($user && $user->hasVerifiedEmail(), 403);

        if ($tour === 'builder') {
            return redirect()->route('surveys.create', ['start_tour' => 'builder']);
        }

        if ($tour === 'reports') {
            $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

            $query = Survey::query()->orderByDesc('updated_at');

            if ($role === 'organization') {
                $query->where('organization_id', $user->organization?->id);
            } elseif ($role === 'independent') {
                $query->where('independent_id', $user->independent?->id);
            } elseif ($role === 'admin') {
                // Admins can use any available survey report.
            } else {
                return redirect()->route('help')->with('error', 'This tour is only available for researcher and admin accounts.');
            }

            $survey = $query->first();

            if (!$survey) {
                return redirect()->route('help')->with('error', 'Create a survey first so we have a report page to guide you through.');
            }

            return redirect()->route('surveys.report', ['survey' => $survey, 'start_tour' => 'reports']);
        }

        return redirect()->route('help')->with('error', 'That tour could not be started.');
    }
}
