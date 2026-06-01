<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyInviteCampaign;
use App\Models\SurveyInviteRecipient;
use App\Jobs\SendInviteCampaignJob;
use App\Jobs\SendInviteReminderJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InviteCampaignController extends Controller
{
    /**
     * Show all campaigns for this survey.
     */
    public function index(Survey $survey)
    {
        Gate::authorize('view', $survey);

        $campaigns = $survey->campaigns()
            ->withCount('recipients')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('surveys.campaigns.index', compact('survey', 'campaigns'));
    }

    /**
     * Show the campaign creation form.
     */
    public function create(Survey $survey)
    {
        Gate::authorize('view', $survey);

        return view('surveys.campaigns.create', compact('survey'));
    }

    /**
     * Store and schedule/dispatch campaign.
     */
    public function store(Request $request, Survey $survey)
    {
        Gate::authorize('view', $survey);

        $request->validate([
            'name' => 'required|string|max:255',
            'scheduled_at' => 'nullable|date|after:now',
            'auto_reminders' => 'nullable|boolean',
            'reminder_interval_days' => 'nullable|integer|min:1|max:30',
            'emails' => 'nullable|string',
            'csv_file' => 'nullable|file|mimes:csv,txt|max:2048',
        ]);

        $recipientsData = [];

        if ($request->hasFile('csv_file')) {
            $path = $request->file('csv_file')->getRealPath();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);
            $emailIndex = -1;
            $nameIndex = -1;

            if ($header) {
                foreach ($header as $key => $col) {
                    $colClean = strtolower(trim($col));
                    if (str_contains($colClean, 'email')) {
                        $emailIndex = $key;
                    } elseif (str_contains($colClean, 'name')) {
                        $nameIndex = $key;
                    }
                }
            }

            if ($emailIndex === -1) {
                $emailIndex = 0;
                $nameIndex = 1;
                rewind($file);
            }

            while (($row = fgetcsv($file)) !== false) {
                $email = isset($row[$emailIndex]) ? trim($row[$emailIndex]) : '';
                $name = isset($row[$nameIndex]) ? trim($row[$nameIndex]) : null;

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipientsData[] = [
                        'email' => $email,
                        'name' => $name,
                    ];
                }
            }
            fclose($file);
        }

        if ($request->filled('emails')) {
            $emailString = str_replace([';', "\n", "\r"], ',', $request->input('emails'));
            $emailArray = array_map('trim', explode(',', $emailString));
            foreach ($emailArray as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipientsData[] = [
                        'email' => $email,
                        'name' => null,
                    ];
                }
            }
        }

        $uniqueRecipients = [];
        $seenEmails = [];
        foreach ($recipientsData as $recipient) {
            if (!in_array($recipient['email'], $seenEmails)) {
                $seenEmails[] = $recipient['email'];
                $uniqueRecipients[] = $recipient;
            }
        }

        if (empty($uniqueRecipients)) {
            return back()->withErrors(['emails' => __('No valid email addresses provided.')])->withInput();
        }

        $status = $request->filled('scheduled_at') ? 'scheduled' : 'sending';

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => $request->input('name'),
            'status' => $status,
            'auto_reminders' => $request->boolean('auto_reminders', false),
            'reminder_interval_days' => $request->input('reminder_interval_days', 3),
            'scheduled_at' => $request->input('scheduled_at'),
            'total_recipients' => count($uniqueRecipients),
            'created_by' => auth()->id(),
        ]);

        foreach ($uniqueRecipients as $recipient) {
            SurveyInviteRecipient::create([
                'campaign_id' => $campaign->id,
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'token' => Str::random(32),
                'status' => 'pending',
            ]);
        }

        if ($status === 'sending') {
            SendInviteCampaignJob::dispatch($campaign);
            $message = __('Campaign created and dispatching emails.');
        } else {
            $message = __('Campaign scheduled successfully.');
        }

        return redirect()->route('surveys.campaigns.index', $survey)->with('success', $message);
    }

    /**
     * Show campaign details and recipient list.
     */
    public function show(Survey $survey, SurveyInviteCampaign $campaign)
    {
        Gate::authorize('view', $survey);

        if ($campaign->survey_id !== $survey->id) {
            abort(404);
        }

        $recipients = $campaign->recipients()->paginate(25);

        return view('surveys.campaigns.show', compact('survey', 'campaign', 'recipients'));
    }

    /**
     * Send manual reminders to non-respondents.
     */
    public function sendReminders(Survey $survey, SurveyInviteCampaign $campaign)
    {
        Gate::authorize('view', $survey);

        if ($campaign->survey_id !== $survey->id) {
            abort(404);
        }

        $recipients = $campaign->recipients()
            ->where('status', 'sent')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', __('No non-respondents found to send reminders.'));
        }

        foreach ($recipients as $recipient) {
            SendInviteReminderJob::dispatch($recipient);
        }

        return back()->with('success', __('Reminders queued for :count non-respondents.', ['count' => $recipients->count()]));
    }

    /**
     * Cancel a scheduled/sending campaign.
     */
    public function cancel(Survey $survey, SurveyInviteCampaign $campaign)
    {
        Gate::authorize('view', $survey);

        if ($campaign->survey_id !== $survey->id) {
            abort(404);
        }

        if ($campaign->status === 'completed') {
            return back()->with('error', __('Cannot cancel a completed campaign.'));
        }

        $campaign->update(['status' => 'cancelled']);

        return back()->with('success', __('Campaign cancelled successfully.'));
    }
}
