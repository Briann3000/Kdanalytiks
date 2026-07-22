<?php

namespace App\Console\Commands;

use App\Services\IndexNowService;
use Illuminate\Console\Command;

class SubmitIndexNow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indexnow:submit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit all public KDAnalytiks URLs to IndexNow for Bing and DuckDuckGo instant indexing';

    /**
     * Execute the console command.
     */
    public function handle(IndexNowService $indexNowService): int
    {
        $this->info('Submitting public KDAnalytiks URLs to IndexNow...');

        $success = $indexNowService->submitAllPublicPages();

        if ($success) {
            $this->info('Successfully submitted URLs to IndexNow (Microsoft Bing, DuckDuckGo, Yandex).');
            return Command::SUCCESS;
        }

        $this->error('Failed to submit URLs to IndexNow. Check logs for details.');
        return Command::FAILURE;
    }
}
