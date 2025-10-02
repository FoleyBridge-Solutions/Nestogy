<?php

namespace App\Traits;

trait HasFluxToasts
{
    public function success(string $message, int $duration = 3000): void
    {
        $this->dispatch('flux-toast', [
            'text' => $message,
            'variant' => 'success',
            'duration' => $duration,
        ]);
    }

    public function error(string $message, int $duration = 5000): void
    {
        $this->dispatch('flux-toast', [
            'text' => $message,
            'variant' => 'danger',
            'duration' => $duration,
        ]);
    }

    public function warning(string $message, int $duration = 4000): void
    {
        $this->dispatch('flux-toast', [
            'text' => $message,
            'variant' => 'warning',
            'duration' => $duration,
        ]);
    }

    public function info(string $message, int $duration = 3000): void
    {
        $this->dispatch('flux-toast', [
            'text' => $message,
            'variant' => 'info',
            'duration' => $duration,
        ]);
    }

    public function toast(string $message, string $variant = 'info', int $duration = 3000): void
    {
        $this->dispatch('flux-toast', [
            'text' => $message,
            'variant' => $variant,
            'duration' => $duration,
        ]);
    }
}
