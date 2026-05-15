<?php

namespace App\Console\Commands;

use App\Models\SurveyAiAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneSociusAttachments extends Command
{
    protected $signature = 'socius:prune-attachments';

    protected $description = 'Delete expired Socius attachment files and metadata.';

    public function handle(): int
    {
        $expired = SurveyAiAttachment::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $attachment) {
            Storage::disk('local')->delete($attachment->storage_path);
            $attachment->delete();
        }

        $this->info("Pruned {$expired->count()} Socius attachment(s).");

        return self::SUCCESS;
    }
}
