<?php

namespace App\Domains\Core\Services\Notification;

use App\Domains\Core\Services\Notification\Contracts\NotificationChannelInterface;
use App\Domains\Core\Services\Notification\Contracts\NotificationDispatcherInterface;
use App\Domains\Core\Services\Notification\Contracts\NotificationStrategyInterface;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Notification Dispatcher Service
 *
 * Orchestrates the entire notification system using composition pattern.
 * Coordinates strategies and channels to deliver notifications efficiently.
 *
 * This is a perfect example of composition over inheritance:
 * - Composes multiple strategies rather than inheriting behavior
 * - Composes multiple channels rather than being tied to one
 * - Easy to add new strategies and channels without modifying existing code
 */
class NotificationDispatcher implements NotificationDispatcherInterface
{
    /**
     * Registered notification strategies.
     */
    protected Collection $strategies;

    /**
     * Registered notification channels.
     */
    protected Collection $channels;

    /**
     * Notification statistics.
     */
    protected array $statistics = [
        'sent' => 0,
        'failed' => 0,
        'queued' => 0,
        'skipped' => 0,
    ];

    public function __construct()
    {
        $this->strategies = collect();
        $this->channels = collect();

        // Auto-register default channels and strategies
        $this->registerDefaultChannels();
        $this->registerDefaultStrategies();
    }

    /**
     * Dispatch notifications for a ticket event.
     */
    public function dispatch(string $eventType, Ticket $ticket, array $eventData = []): array
    {
        $results = [
            'event_type' => $eventType,
            'ticket_id' => $ticket->id,
            'strategies_executed' => 0,
            'notifications_sent' => 0,
            'notifications_failed' => 0,
            'channels_used' => [],
            'errors' => [],
            'timestamp' => now(),
        ];

        try {
            // Get strategies for this event type
            $strategies = $this->getStrategiesForEvent($eventType);

            if ($strategies->isEmpty()) {
                $results['skipped'] = true;
                $results['reason'] = "No strategies found for event type: {$eventType}";

                return $results;
            }

            // Execute each strategy
            foreach ($strategies as $strategy) {
                try {
                    $strategyResult = $strategy->execute($ticket, $eventData);

                    if (isset($strategyResult['skipped']) && $strategyResult['skipped']) {
                        Log::info('Notification strategy skipped', [
                            'strategy' => get_class($strategy),
                            'event_type' => $eventType,
                            'ticket_id' => $ticket->id,
                            'reason' => $strategyResult['reason'] ?? 'Unknown',
                        ]);

                        continue;
                    }

                    $results['strategies_executed']++;

                    // Send notifications through specified channels
                    $channelResults = $this->sendThroughChannels($strategyResult);

                    // Aggregate results
                    $results['notifications_sent'] += $channelResults['sent'];
                    $results['notifications_failed'] += $channelResults['failed'];
                    $results['channels_used'] = array_unique(array_merge(
                        $results['channels_used'],
                        $channelResults['channels_used']
                    ));
                    $results['errors'] = array_merge($results['errors'], $channelResults['errors']);

                } catch (\Exception $e) {
                    $results['notifications_failed']++;
                    $results['errors'][] = [
                        'strategy' => get_class($strategy),
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Notification strategy failed', [
                        'strategy' => get_class($strategy),
                        'event_type' => $eventType,
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update statistics
            $this->updateStatistics($results);

            Log::info('Notification dispatch completed', [
                'event_type' => $eventType,
                'ticket_id' => $ticket->id,
                'strategies_executed' => $results['strategies_executed'],
                'notifications_sent' => $results['notifications_sent'],
                'notifications_failed' => $results['notifications_failed'],
            ]);

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $results['notifications_failed']++;

            Log::error('Notification dispatch failed', [
                'event_type' => $eventType,
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Register a notification strategy.
     */
    public function registerStrategy(NotificationStrategyInterface $strategy): self
    {
        $this->strategies->put($strategy->getEventType(), $strategy);

        return $this;
    }

    /**
     * Register a notification channel.
     */
    public function registerChannel(NotificationChannelInterface $channel): self
    {
        $this->channels->put($channel->getName(), $channel);

        return $this;
    }

    /**
     * Get all registered strategies for an event type.
     */
    public function getStrategiesForEvent(string $eventType): Collection
    {
        return $this->strategies->filter(function ($strategy) use ($eventType) {
            return $strategy->getEventType() === $eventType;
        });
    }

    /**
     * Get a specific notification channel by name.
     */
    public function getChannel(string $channelName): ?NotificationChannelInterface
    {
        return $this->channels->get($channelName);
    }

    /**
     * Get all available notification channels.
     */
    public function getAvailableChannels(): Collection
    {
        return $this->channels->filter(function ($channel) {
            return $channel->isAvailable();
        });
    }

    /**
     * Send bulk notifications for multiple tickets.
     */
    public function dispatchBulk(string $eventType, Collection $tickets, array $eventData = []): array
    {
        $results = [
            'event_type' => $eventType,
            'tickets_processed' => 0,
            'total_notifications_sent' => 0,
            'total_notifications_failed' => 0,
            'individual_results' => [],
            'errors' => [],
        ];

        foreach ($tickets as $ticket) {
            try {
                $ticketResult = $this->dispatch($eventType, $ticket, $eventData);

                $results['tickets_processed']++;
                $results['total_notifications_sent'] += $ticketResult['notifications_sent'] ?? 0;
                $results['total_notifications_failed'] += $ticketResult['notifications_failed'] ?? 0;
                $results['individual_results'][] = $ticketResult;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Queue notification for later delivery.
     */
    public function queueNotification(string $eventType, Ticket $ticket, array $eventData = [], int $delayInMinutes = 0): bool
    {
        try {
            $job = new \App\Jobs\ProcessNotificationJob($eventType, $ticket->id, $eventData);

            if ($delayInMinutes > 0) {
                Queue::later(now()->addMinutes($delayInMinutes), $job);
            } else {
                Queue::push($job);
            }

            $this->statistics['queued']++;

            Log::info('Notification queued', [
                'event_type' => $eventType,
                'ticket_id' => $ticket->id,
                'delay_minutes' => $delayInMinutes,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to queue notification', [
                'event_type' => $eventType,
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        $stats = $this->statistics;

        // Add channel and strategy information
        $stats['available_channels'] = $this->getAvailableChannels()->keys()->toArray();
        $stats['registered_strategies'] = $this->strategies->keys()->toArray();
        $stats['total_channels'] = $this->channels->count();
        $stats['total_strategies'] = $this->strategies->count();

        return $stats;
    }

    /**
     * Send notifications through specified channels.
     */
    protected function sendThroughChannels(array $strategyResult): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'channels_used' => [],
            'errors' => [],
        ];

        $channels = $strategyResult['channels'] ?? ['email'];
        $recipients = $strategyResult['recipients'] ?? [];
        $subject = $strategyResult['subject'] ?? '';
        $message = $strategyResult['message'] ?? '';
        $data = $strategyResult['data'] ?? [];

        foreach ($channels as $channelName) {
            $channel = $this->getChannel($channelName);

            if (! $channel) {
                $results['errors'][] = "Channel not found: {$channelName}";

                continue;
            }

            if (! $channel->isAvailable()) {
                $results['errors'][] = "Channel not available: {$channelName}";

                continue;
            }

            $channelRecipients = $recipients[$channelName] ?? [];

            if (empty($channelRecipients)) {
                continue; // No recipients for this channel
            }

            try {
                $channelResult = $channel->send($channelRecipients, $subject, $message, $data);

                $results['sent'] += $channelResult['sent'] ?? 0;
                $results['failed'] += $channelResult['failed'] ?? 0;
                $results['channels_used'][] = $channelName;

                if (! empty($channelResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $channelResult['errors']);
                }

            } catch (\Exception $e) {
                $results['failed'] += count($channelRecipients);
                $results['errors'][] = [
                    'channel' => $channelName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Register default notification channels.
     */
    protected function registerDefaultChannels(): void
    {
        $this->registerChannel(new \App\Domains\Core\Services\Notification\Channels\EmailChannel);
        $this->registerChannel(new \App\Domains\Core\Services\Notification\Channels\SmsChannel);
        $this->registerChannel(new \App\Domains\Core\Services\Notification\Channels\SlackChannel);
    }

    /**
     * Register default notification strategies.
     */
    protected function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new \App\Domains\Core\Services\Notification\Strategies\TicketCreatedStrategy);
        $this->registerStrategy(new \App\Domains\Core\Services\Notification\Strategies\TicketStatusChangedStrategy);
        $this->registerStrategy(new \App\Domains\Core\Services\Notification\Strategies\SlaBreachStrategy);
    }

    /**
     * Update internal statistics.
     */
    protected function updateStatistics(array $results): void
    {
        $this->statistics['sent'] += $results['notifications_sent'] ?? 0;
        $this->statistics['failed'] += $results['notifications_failed'] ?? 0;

        if (isset($results['skipped']) && $results['skipped']) {
            $this->statistics['skipped']++;
        }
    }

    /**
     * Reset statistics.
     */
    public function resetStatistics(): void
    {
        $this->statistics = [
            'sent' => 0,
            'failed' => 0,
            'queued' => 0,
            'skipped' => 0,
        ];
    }

    /**
     * Get detailed channel information.
     */
    public function getChannelInfo(): array
    {
        $info = [];

        foreach ($this->channels as $name => $channel) {
            $info[$name] = [
                'name' => $channel->getName(),
                'available' => $channel->isAvailable(),
                'required_config' => $channel->getRequiredConfig(),
                'class' => get_class($channel),
            ];
        }

        return $info;
    }

    /**
     * Get detailed strategy information.
     */
    public function getStrategyInfo(): array
    {
        $info = [];

        foreach ($this->strategies as $eventType => $strategy) {
            $info[$eventType] = [
                'event_type' => $strategy->getEventType(),
                'priority' => $strategy->getPriority(),
                'class' => get_class($strategy),
            ];
        }

        return $info;
    }
}
