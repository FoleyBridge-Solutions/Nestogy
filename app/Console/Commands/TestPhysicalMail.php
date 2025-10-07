<?php

namespace App\Console\Commands;

use App\Domains\PhysicalMail\Services\PhysicalMailService;
use App\Domains\PhysicalMail\Services\PostGridClient;
use Illuminate\Console\Command;

class TestPhysicalMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {--type=letter : Type of mail to send (letter, postcard, cheque)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test physical mail service with PostGrid';

    /**
     * Execute the console command.
     */
    public function handle(
        PhysicalMailService $mailService,
        PostGridClient $postgrid,
        \App\Domains\PhysicalMail\Services\PhysicalMailTemplateBuilder $templateBuilder
    ): int {
        $this->info('Testing PostGrid Physical Mail Service');
        $this->info('Mode: '.($postgrid->isTestMode() ? 'TEST' : 'LIVE'));

        $type = $this->option('type');
        $approach = $this->choice(
            'Which approach would you like to test?',
            ['safe-template', 'raw-html', 'unsafe-html'],
            0
        );

        $data = $this->buildTestData($type, $approach, $templateBuilder);

        try {
            $this->info('Sending test '.$type.' using '.$approach.' approach...');

            $order = $mailService->send($type, $data);

            $this->displayOrderInfo($order);
            
            sleep(3);
            $order->refresh();

            if ($order->postgrid_id) {
                $this->handleOrderProcessing($order, $mailService, $postgrid);
            } else {
                $this->warn('PostGrid ID not yet available. Check queue processing.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to send mail: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    private function buildTestData(string $type, string $approach, $templateBuilder): array
    {
        $data = [
            'type' => $type,
            'to' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'companyName' => 'Test Company Inc.',
                'addressLine1' => '123 Test Street',
                'addressLine2' => 'Suite 100',
                'city' => 'New York',
                'provinceOrState' => 'NY',
                'postalOrZip' => '10001',
                'country' => 'US',
            ],
            'from' => [
                'firstName' => 'Nestogy',
                'lastName' => 'Admin',
                'companyName' => 'Nestogy ERP',
                'addressLine1' => '456 Business Ave',
                'city' => 'San Francisco',
                'provinceOrState' => 'CA',
                'postalOrZip' => '94102',
                'country' => 'US',
            ],
            'content' => $this->getTestContent($approach, $templateBuilder),
            'color' => true,
            'double_sided' => false,
            'merge_variables' => [
                'date' => date('F j, Y'),
                'body' => '<p>This is a test letter to demonstrate our improved physical mail system with proper address zone handling.</p><p>The content now starts below the address zone, ensuring no cancellation from PostGrid.</p>',
                'to' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'companyName' => 'ABC Company',
                    'addressLine1' => '123 Main St',
                    'addressLine2' => 'Suite 100',
                    'city' => 'San Francisco',
                    'provinceOrState' => 'CA',
                    'postalOrZip' => '94105',
                ],
                'from' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'jobTitle' => 'Customer Success Manager',
                    'companyName' => 'Nestogy ERP',
                ],
            ],
        ];

        if ($approach === 'unsafe-html') {
            $data['address_placement'] = 'top_first_page';
            $this->warn('Using unsafe HTML - this may get cancelled by PostGrid!');
        }

        return $data;
    }

    private function displayOrderInfo($order): void
    {
        $this->info('✅ Mail queued successfully!');
        $this->info('Order ID: '.$order->id);
        $this->info('Status: '.$order->status);
        $this->info('Waiting for job to process...');
    }

    private function handleOrderProcessing($order, PhysicalMailService $mailService, PostGridClient $postgrid): void
    {
        $this->info('PostGrid ID: '.$order->postgrid_id);
        $this->info('PDF URL: '.$order->pdf_url);

        $tracking = $mailService->getTracking($order);
        $this->info('Tracking Status: '.json_encode($tracking));

        $this->checkCancellation($tracking, $order, $postgrid);
        $this->progressTestOrderIfNeeded($tracking, $order, $mailService, $postgrid);
    }

    private function checkCancellation(array $tracking, $order, PostGridClient $postgrid): void
    {
        if ($tracking['status'] === 'cancelled') {
            $this->error('❌ Letter was cancelled by PostGrid!');

            $letter = $postgrid->getLetter($order->postgrid_id);
            if (isset($letter['cancellation'])) {
                $this->error('Cancellation reason: '.$letter['cancellation']['reason']);
                $this->error('Cancellation note: '.$letter['cancellation']['note']);
            }
        } else {
            $this->info('✅ Letter accepted by PostGrid!');
        }
    }

    private function progressTestOrderIfNeeded(array $tracking, $order, PhysicalMailService $mailService, PostGridClient $postgrid): void
    {
        if ($postgrid->isTestMode() && $tracking['status'] !== 'cancelled') {
            $this->info('Progressing test order through statuses...');

            for ($i = 0; $i < 3; $i++) {
                try {
                    $mailService->progressTestOrder($order);
                    $order->refresh();
                    $this->info('  → Status: '.$order->status);
                } catch (\Exception $e) {
                    break;
                }
            }
        }
    }

    /**
     * Get test content based on approach
     */
    private function getTestContent(string $approach, $templateBuilder): string
    {
        switch ($approach) {
            case 'safe-template':
                // Use our safe template builder
                return $templateBuilder->generateBusinessLetter([
                    'primary_color' => '#1a56db',
                    'title' => 'Test Business Letter',
                    'body' => '{{body}}',
                ]);

            case 'raw-html':
                // Raw HTML that will be automatically made safe
                return '<h1>Test Letter</h1>
                <p>Date: {{date}}</p>
                <p>Dear {{to.firstName}} {{to.lastName}},</p>
                {{body}}
                <p>Sincerely,</p>
                <p>{{from.firstName}} {{from.lastName}}<br>
                {{from.companyName}}</p>';

            case 'unsafe-html':
                // Intentionally unsafe HTML to test cancellation
                return '<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .greeting {
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .signature {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <h1>Welcome to Nestogy ERP</h1>
    
    <div class="greeting">
        <p>Dear {{to.firstName}} {{to.lastName}},</p>
    </div>
    
    <div class="content">
        <p>Thank you for choosing Nestogy ERP for your business needs. This is a test letter to demonstrate our physical mail capabilities using PostGrid.</p>
        
        <p>With our integrated mail service, you can:</p>
        <ul>
            <li>Send invoices and statements by mail</li>
            <li>Mail contracts and legal documents</li>
            <li>Send marketing materials and newsletters</li>
            <li>Track delivery status in real-time</li>
        </ul>
        
        <p>All mail is sent securely through PostGrid\'s print and mail API, ensuring fast and reliable delivery.</p>
    </div>
    
    <div class="signature">
        <p>Best regards,</p>
        <p><strong>{{from.firstName}} {{from.lastName}}</strong><br>
        {{from.companyName}}<br>
        {{from.addressLine1}}<br>
        {{from.city}}, {{from.provinceOrState}} {{from.postalOrZip}}</p>
    </div>
</body>
</html>';

            default:
                return $this->getTestContent('safe-template', $templateBuilder);
        }
    }
}
