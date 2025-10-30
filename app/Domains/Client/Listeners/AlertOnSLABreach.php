<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceSLABreached;
use App\Notifications\ServiceSLABreachedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Creates alerts and sends notifications when SLA is breached
 * 
 * Actions:
 * - Sends urgent notifications to technicians and managers
 * - Logs the breach for tracking
 * - Future: Can create tickets automatically
 */
class AlertOnSLABreach implements ShouldQueue
{
    public function handle(ServiceSLABreached $event): void
    {
        $service = $event->service->load('client', 'technician', 'backupTechnician');

        Log::error('SLA breach detected - sending urgent alerts', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'client_id' => $service->client_id,
            'client_name' => $service->client->name,
            'breach_details' => $event->breachDetails,
            'total_breaches' => $service->sla_breaches_count,
            'severity' => $event->breachDetails['severity'] ?? 'medium',
        ]);

        $notification = new ServiceSLABreachedNotification($service, $event->breachDetails);

        // Notify primary technician with HIGH PRIORITY
        if ($service->technician) {
            $service->technician->notify($notification);
        }

        // Notify backup technician
        if ($service->backupTechnician) {
            $service->backupTechnician->notify($notification);
        }

        // Notify all company admins - SLA breaches are critical
        $companyAdmins = \App\Domains\Core\Models\User::where('company_id', $service->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        foreach ($companyAdmins as $admin) {
            $admin->notify($notification);
        }

        // TODO: Optionally create a ticket for the breach
        // if ($service->sla_breaches_count >= 3) {
        //     Ticket::create([
        //         'client_id' => $service->client_id,
        //         'subject' => "Critical: Multiple SLA Breaches - {$service->name}",
        //         'description' => "Service has {$service->sla_breaches_count} SLA breaches. Latest: " . json_encode($event->breachDetails),
        //         'priority' => 'critical',
        //         'assigned_to' => $service->assigned_technician,
        //     ]);
        // }

        Log::error('SLA breach alerts sent', [
            'service_id' => $service->id,
            'notifications_sent' => 1 + ($service->technician ? 1 : 0) + ($service->backupTechnician ? 1 : 0) + $companyAdmins->count(),
        ]);
    }
}
