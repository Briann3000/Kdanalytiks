<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * Handle the KM Autonomous Agent Chat Proxy.
     */
    public function chat(Request $request)
    {
        // 1. Validation - Limit message history size and content length
        try {
            $request->validate([
                'messages' => 'required|array|max:20', // Limit history
                'messages.*.content' => 'required|string|max:2000', // Limit content length
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'action' => 'chat',
                'message' => 'Your request is too long or invalid. Please try a shorter message.'
            ], 422);
        }

        $messages = $request->input('messages');
        $user = Auth::user();

        // Use user object directly if it exists, otherwise use guest defaults
        $role = $user ? (is_object($user->role) ? $user->role->value : $user->role) : 'guest';
        $name = $user ? $user->name : 'Guest';

        try {
            // Include role-based routing map inside try block to catch route name errors
            $allPages = [
                'admin' => [
                    'admin-dashboard' => ['url' => route('admin.dashboard'), 'label' => 'Admin Dashboard', 'desc' => 'main admin home page, overview, system health, stats, home'],
                    'admin-users' => ['url' => route('admin.users.index'), 'label' => 'Manage Users', 'desc' => 'view, search, filter, delete all users accounts, member management, list users'],
                    'admin-surveys' => ['url' => route('admin.surveys.index'), 'label' => 'All Surveys', 'desc' => 'view all surveys in the system, moderation, global survey list'],
                    'admin-survey-builder' => ['url' => route('surveys.create'), 'label' => 'Create Public Survey', 'desc' => 'build, create new public survey, add questions, designer, constructor'],
                    'admin-reports' => ['url' => route('admin.reports.index'), 'label' => 'Reports & Analytics', 'desc' => 'view reports, analytics, statistics, charts, insights, results summary'],
                    'admin-analytics' => ['url' => route('admin.analytics.index'), 'label' => 'Analytics Dashboard', 'desc' => 'detailed system analytics and trends, usage stats'],
                    'public-list-surveys' => ['url' => route('surveys.public'), 'label' => 'Public Surveys', 'desc' => 'browse, view all public surveys, take a survey'],
                    'logout' => ['url' => route('logout'), 'label' => 'Logout', 'desc' => 'sign out, log out, exit, leave, terminate session'],
                ],
                'organization' => [
                    'org-dashboard' => ['url' => route('organization.dashboard'), 'label' => 'Dashboard', 'desc' => 'home, main overview, stats, welcome page'],
                    'org-surveys' => ['url' => route('surveys.index'), 'label' => 'My Surveys', 'desc' => 'list, view all my surveys, owned surveys, manage surveys'],
                    'org-create-survey' => ['url' => route('surveys.create'), 'label' => 'Create Survey', 'desc' => 'build, create new survey, add questions, form builder, start new research'],
                    'org-responses' => ['url' => route('organization.responses.index'), 'label' => 'Survey Responses', 'desc' => 'view responses, answers, submissions, results, data, what people said'],
                    'org-reports' => ['url' => route('organization.reports.index'), 'label' => 'Reports & Analytics', 'desc' => 'charts, graphs, analytics, statistics, export csv, visual reports'],
                    'public-list-surveys' => ['url' => route('surveys.public'), 'label' => 'Public Surveys', 'desc' => 'browse public surveys, take surveys'],
                    'logout' => ['url' => route('logout'), 'label' => 'Logout', 'desc' => 'sign out, log out, exit, leave'],
                ],
                'independent' => [
                    'ind-dashboard' => ['url' => route('independent.dashboard'), 'label' => 'Dashboard', 'desc' => 'home, main overview, stats, landing'],
                    'ind-surveys' => ['url' => route('surveys.index'), 'label' => 'My Surveys', 'desc' => 'list, view all my surveys, research list, manage research'],
                    'ind-create-survey' => ['url' => route('surveys.create'), 'label' => 'Create Survey', 'desc' => 'build, create new research survey, design questions, researcher tool'],
                    'ind-responses' => ['url' => route('independent.responses.index'), 'label' => 'Survey Responses', 'desc' => 'view responses, answers, submissions, results, raw data'],
                    'ind-reports' => ['url' => route('independent.reports.index'), 'label' => 'Reports & Analytics', 'desc' => 'charts, graphs, analytics, statistics, research report'],
                    'public-list-surveys' => ['url' => route('surveys.public'), 'label' => 'Public Surveys', 'desc' => 'browse public surveys'],
                    'logout' => ['url' => route('logout'), 'label' => 'Logout', 'desc' => 'sign out, log out, exit'],
                ],
                'respondent' => [
                    'res-dashboard' => ['url' => route('respondent.dashboard'), 'label' => 'Dashboard', 'desc' => 'home, main overview, respondent home'],
                    'res-surveys' => ['url' => route('surveys.public'), 'label' => 'Available Surveys', 'desc' => 'view, take, fill, available surveys, participate, surveys for me'],
                    'res-responses' => ['url' => route('respondent.history'), 'label' => 'My Responses', 'desc' => 'view my submitted answers, history, my participation, past responses'],
                    'res-reports' => ['url' => route('respondent.reports.index'), 'label' => 'Reports & Analytics', 'desc' => 'view your survey participation summaries, trends'],
                    'logout' => ['url' => route('logout'), 'label' => 'Logout', 'desc' => 'sign out, log out, exit'],
                ],
                'guest' => [
                    'home' => ['url' => route('home'), 'label' => 'Home', 'desc' => 'homepage, main landing page, portal'],
                    'admin-login' => ['url' => route('admin.login'), 'label' => 'Admin Login', 'desc' => 'login, sign in as admin, administrator portal'],
                    'org-login' => ['url' => route('organization.login'), 'label' => 'Organization Login', 'desc' => 'login, sign in as organization, company business login'],
                    'ind-login' => ['url' => route('independent.login'), 'label' => 'Researcher Login', 'desc' => 'login, sign in as researcher, independent phd, scholar login'],
                    'res-login' => ['url' => route('respondent.login'), 'label' => 'Respondent Login', 'desc' => 'login, sign in as respondent, participant, individual user login'],
                    'org-register' => ['url' => route('register', ['role' => 'organization']), 'label' => 'Register as Organization', 'desc' => 'sign up, register, create account organization, join as company'],
                    'ind-register' => ['url' => route('register', ['role' => 'independent']), 'label' => 'Register as Researcher', 'desc' => 'sign up, register, create account researcher, join as phd scholar'],
                    'res-register' => ['url' => route('register', ['role' => 'respondent']), 'label' => 'Register as Respondent', 'desc' => 'sign up, register, create account respondent, join as participant'],
                    'public-surveys' => ['url' => route('surveys.public'), 'label' => 'Browse Public Surveys', 'desc' => 'view, browse public surveys, take surveys without login'],
                ],
            ];

            $pages = $allPages[$role] ?? $allPages['guest'];
            $pageList = '';
            foreach ($pages as $key => $p) {
                $pageList .= "  - $key: \"{$p['label']}\" → Target: \"{$key}\" ({$p['desc']})\n";
            }

            // 3. System Prompt Definition
            $systemPrompt = "You are KD Agent — the autonomous AI agent for KDAnalytiks, a survey platform.\n\n" .
                "User: {$name} | Role: {$role}\n\n" .
                "AVAILABLE PAGES FOR YOUR CURRENT ROLE:\n" .
                $pageList . "\n\n" .
                "YOUR JOB: Decide the single best action based on the user's message. Respond ONLY with a single valid JSON object.\n\n" .
                "RULES:\n" .
                "1. If the user asks for something NOT available in the list above (e.g., Guest wants to 'create survey'), use the 'chat' action to explain they need to Login or Register first, or use 'navigate' to take them to the Login/Register page.\n" .
                "2. If multiple pages seem relevant, pick the most specific one.\n" .
                "3. Use 'navigate' for simple page jumps.\n" .
                "4. Use 'prefill' only when the user explicitly wants to start/create something (like a survey) and you have data for it.\n" .
                "5. CRITICAL: Never return an array of objects. Only return ONE object.\n\n" .
                "RESPONSE TYPES:\n\n" .
                "1. Navigate:\n" .
                "{\"action\":\"navigate\",\"page_key\":\"org-responses\",\"message\":\"Taking you to your responses...\"}\n\n" .
                "2. Prefill (AI generates survey content):\n" .
                "{\"action\":\"prefill\",\"page_key\":\"org-create-survey\",\"message\":\"I've designed a survey for you. Opening it now!\",\"data\":{\"title\":\"Topic Title\",\"description\":\"...\",\"questions\":[...]}}\n\n" .
                "3. Chat (fallback or answers):\n" .
                "{\"action\":\"chat\",\"message\":\"I'm sorry, you need to log in as an organization to create surveys. Would you like me to take you to the login page?\"}\n\n" .
                "DECISION RULES OVERRIDE:\n" .
                "- \"create a survey\" + User is Guest → Suggest Login/Register (action: chat or navigate to res-login/org-login).\n" .
                "- \"logout\" → navigate to logout.\n" .
                "- \"how are you\" → action: chat.\n" .
                "- ONLY return JSON. No markdown. No extra text.";

            // 4. API Request to Groq
            $groqApiKey = config('services.groq.api_key');
            $groqModel = config('services.groq.model', 'llama-3.1-8b-instant');

            if (!$groqApiKey) {
                return response()->json([
                    'action' => 'chat',
                    'message' => 'Agent configuration error: GROQ_API_KEY is missing.'
                ], 500);
            }

            $response = Http::withToken($groqApiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $groqModel,
                    'max_tokens' => 1200,
                    'temperature' => 0.3,
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $messages
                    ),
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->failed()) {
                Log::error('Groq API Error: ' . $response->body());
                return response()->json([
                    'action' => 'chat',
                    'message' => 'AI Service is temporarily unavailable. Please try again later.'
                ], 502);
            }

            $groqData = $response->json();
            $aiText = trim($groqData['choices'][0]['message']['content'] ?? '');

            // Strip any accidental markdown fences
            $aiText = preg_replace('/^```json\s*/i', '', $aiText);
            $aiText = preg_replace('/\s*```$/i', '', $aiText);

            $parsed = json_decode($aiText, true);

            if (!$parsed || !isset($parsed['action'])) {
                // AI didn't follow format — treat as chat
                return response()->json([
                    'action' => 'chat',
                    'message' => $aiText ?: 'The agent could not process your request.'
                ]);
            }

            // 5. Back-end Safety Layer: Ensure AI only navigates to pages allowed for this role
            if ($parsed['action'] === 'navigate' || $parsed['action'] === 'prefill') {
                if (!isset($parsed['page_key']) || !isset($pages[$parsed['page_key']])) {
                    // Unauthorized or invalid page key
                    if ($role === 'guest') {
                        return response()->json([
                            'action' => 'chat',
                            'message' => 'I\'m sorry, you need to sign in to access that feature. Would you like to log in?'
                        ]);
                    }
                    return response()->json([
                        'action' => 'chat',
                        'message' => 'I\'m sorry, I cannot access that page for you. Is there something else I can help with?'
                    ]);
                }

                // Resolve page_key to actual URL and Label
                $parsed['url'] = $pages[$parsed['page_key']]['url'];
                $parsed['label'] = $pages[$parsed['page_key']]['label'];
            }

            return response()->json($parsed);

        } catch (\Exception $e) {
            Log::error('Agent Controller Exception: ' . $e->getMessage());
            return response()->json([
                'action' => 'chat',
                'message' => 'A connection error occurred. Please try again.'
            ], 500);
        }
    }
}
