<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsletterCampaign;
use Carbon\Carbon;

class SendScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send-scheduled-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled newsletter campaigns that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $dueCampaigns = NewsletterCampaign::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($dueCampaigns as $campaign) {
            // You may want to dispatch a job here for large lists
            app('App\\Http\\Controllers\\CampaignController')->send($campaign->id);
            $this->info("Sent campaign ID: {$campaign->id}");
        }
    }
}
