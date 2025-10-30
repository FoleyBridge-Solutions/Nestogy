<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceActivated;
use App\Notifications\ServiceActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends notifications when service is activated
 * 
 * Notifies:
 * - Primary technician (if assigned)
 * - Backup technician (if assigned)
 * - Account manager (company admins)
 */
class NotifyServiceActivated implements ShouldQueue
{
    public function handle(ServiceActivated $event): void
    {
        $service = $event->service->load('client', 'technician', 'backupTechnician');

        Log::info('Service activated - sending notifications', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'client_id' => $service->client_id,
            'client_name' => $service->client->name,
        ]);

        // Notify primary technician
        if ($service->technician) {
            $service->technician->notify(new ServiceActivatedNotification($service));
            Log::debug('Notified primary technician', ['user_id' => $service->technician->id]);
        }

        // Notify backup technician
        if ($service->backupTechnician) {
            $service->backupTechnician->notify(new ServiceActivatedNotification($service));
            Log::debug('Notified backup technician', ['user_id' => $service->backupTechnician->id]);
        }

        // Notify company admins/account managers
        $companyAdmins = \App\Domains\Core\Models\User::where('company_id', $service->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        foreach ($companyAdmins as $admin) {
            $admin->notify(new ServiceActivatedNotification($service));
        }

        Log::info('Service activated notifications sent', [
            'service_id' => $service->id,
            'notifications_sent' => 1 + ($service->technician ? 1 : 0) + ($service->backupTechnician ? 1 : 0) + $companyAdmins->count(),
        ]);
    }
}
