<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceDueForRenewal;
use App\Notifications\ServiceRenewalDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends renewal reminders to clients and account managers
 * 
 * Notifies at 30, 14, and 7 days before renewal.
 */
class NotifyServiceRenewalDue implements ShouldQueue
{
    public function handle(ServiceDueForRenewal $event): void
    {
        $service = $event->service->load('client', 'technician');

        Log::info('Service renewal reminder - sending notifications', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'client_id' => $service->client_id,
            'days_until_renewal' => $event->daysUntilRenewal,
            'renewal_date' => $service->renewal_date?->toDateString(),
        ]);

        $notification = new ServiceRenewalDueNotification($service, $event->daysUntilRenewal);

        // Notify primary technician/account manager
        if ($service->technician) {
            $service->technician->notify($notification);
        }

        // Notify company admins
        $companyAdmins = \App\Domains\Core\Models\User::where('company_id', $service->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        foreach ($companyAdmins as $admin) {
            $admin->notify($notification);
        }

        Log::info('Service renewal reminder notifications sent', [
            'service_id' => $service->id,
            'days_until_renewal' => $event->daysUntilRenewal,
        ]);
    }
}
