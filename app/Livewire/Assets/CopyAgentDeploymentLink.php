<?php

namespace App\Livewire\Assets;

use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CopyAgentDeploymentLink extends Component
{
    public $loading = false;
    public $copied = false;
    public $error = false;
    public $deploymentUrl = '';

    public function getDeploymentLink()
    {
        $this->loading = true;
        $this->error = false;
        $this->deploymentUrl = '';

        try {
            // Get the active RMM integration
            $integration = RmmIntegration::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                throw new \Exception('No active RMM integration found');
            }

            // Get the RMM service
            $rmmService = app(RmmServiceFactory::class)->make($integration);

            // Get or create deployment link
            $result = $rmmService->getDefaultDeploymentLink();

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Failed to get deployment link');
            }

            Log::info('TacticalRMM deployment link retrieved', [
                'integration_id' => $integration->id,
                'download_url' => $result['download_url'],
            ]);

            // Set the URL which triggers Alpine to copy it
            $this->deploymentUrl = $result['download_url'];
            
            $this->copied = true;
            $this->loading = false;

            // Reset after 2 seconds using Livewire 3 syntax
            $this->js('setTimeout(() => $wire.resetButton(), 2000)');

        } catch (\Exception $e) {
            Log::error('Failed to get deployment link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = true;
            $this->loading = false;

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to get deployment link: ' . $e->getMessage(),
            ]);

            // Reset after 2 seconds
            $this->js('setTimeout(() => $wire.resetButton(), 2000)');
        }
    }

    public function resetButton()
    {
        $this->copied = false;
        $this->error = false;
        $this->loading = false;
        $this->deploymentUrl = '';
    }

    public function render()
    {
        return view('livewire.assets.copy-agent-deployment-link');
    }
}
