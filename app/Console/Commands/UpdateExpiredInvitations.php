<?php

namespace App\Console\Commands;

use App\Domains\Client\Services\PortalInvitationService;
use Illuminate\Console\Command;

class UpdateExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:update-expired-invitations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expired portal invitations and change their status';

    /**
     * Execute the console command.
     */
    public function handle(PortalInvitationService $invitationService): int
    {
        $this->info('Checking for expired portal invitations...');
        
        $expired = $invitationService->updateExpiredInvitations();
        
        if ($expired > 0) {
            $this->info("Updated {$expired} expired invitation(s).");
        } else {
            $this->info('No expired invitations found.');
        }
        
        return Command::SUCCESS;
    }
}
