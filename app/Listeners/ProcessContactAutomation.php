<?php

namespace App\Listeners;

use App\Domains\Contract\Services\ContractAutomationService;
use App\Events\ContactCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessContactAutomation implements ShouldQueue
{
    use InteractsWithQueue;

    protected $automationService;

    /**
     * Create the event listener.
     */
    public function __construct(ContractAutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ContactCreated $event): void
    {
        try {
            $this->automationService->processNewContact($event->contact);
        } catch (\Exception $e) {
            Log::error('Failed to process contact automation', [
                'contact_id' => $event->contact->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
