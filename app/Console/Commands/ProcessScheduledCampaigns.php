<?php

namespace App\Console\Commands;

use App\Models\SurveyInviteCampaign;
use App\Jobs\SendInviteCampaignJob;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and dispatch scheduled survey invite campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaigns = SurveyInviteCampaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns to process.');
            return 0;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Dispatching campaign: {$campaign->name} (ID: {$campaign->id})");
            SendInviteCampaignJob::dispatch($campaign);
        }

        return 0;
    }
}
