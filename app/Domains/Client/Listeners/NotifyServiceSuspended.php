<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceSuspended;
use App\Notifications\ServiceSuspendedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends notifications when service is suspended
 * 
 * Notifies:
 * - Primary and backup technicians
 * - Account managers
 */
class NotifyServiceSuspended implements ShouldQueue
{
    public function handle(ServiceSuspended $event): void
    {
        $service = $event->service->load('client', 'technician', 'backupTechnician');

        Log::warning('Service suspended - sending notifications', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'client_id' => $service->client_id,
            'client_name' => $service->client->name,
            'reason' => $event->reason,
        ]);

        $notification = new ServiceSuspendedNotification($service, $event->reason);

        // Notify primary technician
        if ($service->technician) {
            $service->technician->notify($notification);
        }

        // Notify backup technician
        if ($service->backupTechnician) {
            $service->backupTechnician->notify($notification);
        }

        // Notify company admins/account managers
        $companyAdmins = \App\Domains\Core\Models\User::where('company_id', $service->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        foreach ($companyAdmins as $admin) {
            $admin->notify($notification);
        }

        Log::info('Service suspended notifications sent', [
            'service_id' => $service->id,
        ]);
    }
}
