<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtp2goCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-smtp2go {email? : The email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP2GO email configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address', 'test@example.com');

        $this->info('Testing SMTP2GO email configuration...');
        $this->info('Sending test email to: '.$email);

        try {
            // Temporarily set the mailer to smtp2go
            config(['mail.default' => 'smtp2go']);

            // Send test email
            Mail::raw('This is a test email from Nestogy using SMTP2GO. If you receive this, your SMTP2GO configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Nestogy SMTP2GO Test Email - '.now()->format('Y-m-d H:i:s'));
            });

            $this->info('✅ Test email sent successfully!');
            $this->info('Please check the inbox for: '.$email);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email');
            $this->error('Error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            $this->newLine();
            $this->warn('Troubleshooting tips:');
            $this->line('1. Check that SMTP2GO_API_KEY is set in your .env file');
            $this->line('2. Verify the API key is valid in your SMTP2GO dashboard');
            $this->line('3. Check that the sender email is verified in SMTP2GO');
            $this->line('4. Run with -v flag for more details');

            return Command::FAILURE;
        }
    }
}
