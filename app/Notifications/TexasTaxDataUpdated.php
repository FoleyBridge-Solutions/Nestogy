<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class TexasTaxDataUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $updateData;

    public function __construct(array $updateData)
    {
        $this->updateData = $updateData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (config('texas-tax.automation.slack_webhook')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $quarter = $this->updateData['quarter'];
        $taxRatesCount = $this->updateData['tax_rates_count'] ?? 0;
        $addressCounties = $this->updateData['address_counties'] ?? 0;
        $savings = $this->calculateMonthlySavings();

        return (new MailMessage)
            ->subject("✅ Texas Tax Data Updated - {$quarter}")
            ->greeting('Texas Comptroller Tax Data Updated')
            ->line("The official Texas tax jurisdiction data has been successfully updated for **{$quarter}**.")
            ->line('**Update Summary:**')
            ->line("• Tax jurisdiction rates imported: **{$taxRatesCount}**")
            ->line("• Counties with address data: **{$addressCounties}**")
            ->line('• Data source: **Texas Comptroller (Official & FREE)**')
            ->line("• Monthly savings vs. paid services: **\${$savings}**")
            ->line('This ensures your MSP has the most current official tax rates for accurate billing.')
            ->action('View Tax Configuration', url('/admin/settings/taxes'))
            ->line('The system will automatically use this data for all new quotes and invoices.')
            ->line('💰 **Cost Savings**: Using official government data instead of expensive third-party services saves your MSP hundreds of dollars monthly while providing the most accurate, legally compliant tax calculations.');
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $quarter = $this->updateData['quarter'];
        $taxRatesCount = $this->updateData['tax_rates_count'] ?? 0;
        $savings = $this->calculateMonthlySavings();

        return (new SlackMessage)
            ->success()
            ->content("🏛️ Texas Tax Data Updated - {$quarter}")
            ->attachment(function ($attachment) use ($taxRatesCount, $quarter, $savings) {
                $attachment->title('Official Texas Comptroller Data Import Complete')
                    ->fields([
                        'Quarter' => $quarter,
                        'Tax Rates Imported' => number_format($taxRatesCount),
                        'Data Source' => 'Texas Comptroller (FREE)',
                        'Monthly Savings' => '$'.number_format($savings),
                        'Status' => '✅ Active & Current',
                    ])
                    ->color('good');
            });
    }

    /**
     * Calculate monthly savings compared to paid services
     */
    protected function calculateMonthlySavings(): int
    {
        $alternatives = config('texas-tax.cost_tracking.alternative_costs', []);
        $averageCost = count($alternatives) > 0 ? array_sum($alternatives) / count($alternatives) : 800;

        return (int) $averageCost;
    }
}
