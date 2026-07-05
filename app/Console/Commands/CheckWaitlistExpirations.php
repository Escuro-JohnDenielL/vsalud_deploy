<?php

namespace App\Console\Commands;

use App\Services\WaitlistService;
use Illuminate\Console\Command;

class CheckWaitlistExpirations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:check-waitlist-expirations';

    /**
     * The console command description.
     */
    protected $description = 'Check for expired waitlist notifications (24-hour window) and rotate to next in line';

    /**
     * Execute the console command.
     */
    public function handle(WaitlistService $waitlistService)
    {
        $this->info('Checking for expired waitlist notifications...');

        $count = $waitlistService->processExpiredNotifications();

        $this->info("Processed {$count} expired notification(s).");

        return Command::SUCCESS;
    }
}
