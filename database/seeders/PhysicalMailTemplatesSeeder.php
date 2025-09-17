<?php

namespace Database\Seeders;

use App\Domains\PhysicalMail\Models\PhysicalMailTemplate;
use App\Domains\PhysicalMail\Services\PhysicalMailTemplateBuilder;
use Illuminate\Database\Seeder;

class PhysicalMailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $builder = new PhysicalMailTemplateBuilder();
        
        // Standard Business Letter Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Standard Business Letter'],
            [
                'type' => 'letter',
                'content' => $builder->generateBusinessLetter([
                    'primary_color' => '#1a56db',
                    'title' => 'Business Letter'
                ]),
                'description' => 'Professional business letter with proper margins for PostGrid address placement',
                'variables' => ['date', 'body', 'to', 'from'],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        // Invoice Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Invoice Template'],
            [
                'type' => 'letter',
                'content' => $builder->generateInvoiceTemplate([
                    'primary_color' => '#1a56db',
                    'title' => 'Invoice'
                ]),
                'description' => 'Invoice template with itemized billing and payment instructions',
                'variables' => [
                    'invoice_number', 'invoice_date', 'due_date', 'amount_due',
                    'line_items', 'total_amount', 'payment_instructions', 'to', 'from'
                ],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        // Statement Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Account Statement'],
            [
                'type' => 'letter',
                'content' => $this->getStatementTemplate($builder),
                'description' => 'Monthly account statement with transaction history',
                'variables' => [
                    'statement_date', 'account_number', 'balance', 'transactions', 'to', 'from'
                ],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        // Past Due Notice Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Past Due Notice'],
            [
                'type' => 'letter',
                'content' => $this->getPastDueTemplate($builder),
                'description' => 'Past due payment reminder notice',
                'variables' => [
                    'invoice_number', 'amount_due', 'days_overdue', 'due_date', 'to', 'from'
                ],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        // Welcome Letter Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Welcome Letter'],
            [
                'type' => 'letter',
                'content' => $this->getWelcomeTemplate($builder),
                'description' => 'New customer welcome letter',
                'variables' => [
                    'customer_name', 'account_number', 'services', 'to', 'from'
                ],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        // Contract Template
        PhysicalMailTemplate::updateOrCreate(
            ['name' => 'Service Contract'],
            [
                'type' => 'letter',
                'content' => $this->getContractTemplate($builder),
                'description' => 'Service agreement contract',
                'variables' => [
                    'contract_number', 'start_date', 'end_date', 'terms', 'to', 'from'
                ],
                'is_active' => true,
                'metadata' => [
                    'safe_for_postgrid' => true,
                    'margin_top_inches' => 4,
                    'template_version' => '1.0'
                ]
            ]
        );
        
        $this->command->info('Physical mail templates seeded successfully.');
    }
    
    private function getStatementTemplate(PhysicalMailTemplateBuilder $builder): string
    {
        $content = '
        <h1>Account Statement</h1>
        
        <div class="mb-2">
            <p><strong>Statement Date:</strong> {{statement_date}}</p>
            <p><strong>Account Number:</strong> {{account_number}}</p>
            <p><strong>Current Balance:</strong> {{balance}}</p>
        </div>
        
        <h2>Transaction History</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                {{#each transactions}}
                <tr>
                    <td>{{date}}</td>
                    <td>{{description}}</td>
                    <td>{{amount}}</td>
                    <td>{{balance}}</td>
                </tr>
                {{/each}}
            </tbody>
        </table>
        
        <div class="footer">
            <p class="text-center">Thank you for your business!</p>
        </div>
        ';
        
        return $builder->buildTemplate($content, 'statement');
    }
    
    private function getPastDueTemplate(PhysicalMailTemplateBuilder $builder): string
    {
        $content = '
        <h1 style="color: #dc2626;">PAST DUE NOTICE</h1>
        
        <p>Dear {{to.firstName}} {{to.lastName}},</p>
        
        <p>This is a reminder that your account is past due. Please remit payment immediately to avoid service interruption.</p>
        
        <div class="avoid-break" style="background: #fef2f2; padding: 20px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
            <p><strong>Original Due Date:</strong> {{due_date}}</p>
            <p><strong>Days Overdue:</strong> {{days_overdue}}</p>
            <p style="font-size: 16pt; font-weight: bold; color: #dc2626;">Amount Due: {{amount_due}}</p>
        </div>
        
        <p>To make a payment, please:</p>
        <ul>
            <li>Pay online at our website</li>
            <li>Call our billing department</li>
            <li>Mail a check to the address below</li>
        </ul>
        
        <p>If you have already made this payment, please disregard this notice. If you have any questions, please contact us immediately.</p>
        
        <div class="signature">
            <p>Sincerely,</p>
            <p class="signature-name">Billing Department</p>
            <p class="signature-title">{{from.companyName}}</p>
        </div>
        ';
        
        return $builder->buildTemplate($content, 'notice', ['primary_color' => '#dc2626']);
    }
    
    private function getWelcomeTemplate(PhysicalMailTemplateBuilder $builder): string
    {
        $content = '
        <h1>Welcome to {{from.companyName}}!</h1>
        
        <p>Dear {{customer_name}},</p>
        
        <p>Thank you for choosing {{from.companyName}} for your business needs. We are excited to have you as our valued customer and look forward to serving you.</p>
        
        <div class="avoid-break" style="background: #eff6ff; padding: 20px; margin: 20px 0;">
            <h3>Your Account Information</h3>
            <p><strong>Account Number:</strong> {{account_number}}</p>
            <p><strong>Services Activated:</strong></p>
            <ul>
                {{#each services}}
                <li>{{name}} - {{description}}</li>
                {{/each}}
            </ul>
        </div>
        
        <h2>Getting Started</h2>
        <p>To help you get the most from our services:</p>
        <ol>
            <li>Log in to your account at our website</li>
            <li>Review your service agreement</li>
            <li>Contact us if you have any questions</li>
        </ol>
        
        <p>Our support team is available to assist you:</p>
        <ul>
            <li>Phone: {{from.phoneNumber}}</li>
            <li>Email: {{from.email}}</li>
            <li>Hours: Monday-Friday, 9 AM - 5 PM</li>
        </ul>
        
        <div class="signature">
            <p>Best regards,</p>
            <p class="signature-name">Customer Success Team</p>
            <p class="signature-title">{{from.companyName}}</p>
        </div>
        ';
        
        return $builder->buildTemplate($content, 'letter');
    }
    
    private function getContractTemplate(PhysicalMailTemplateBuilder $builder): string
    {
        $content = '
        <h1>SERVICE AGREEMENT</h1>
        
        <div class="mb-2">
            <p><strong>Contract Number:</strong> {{contract_number}}</p>
            <p><strong>Effective Date:</strong> {{start_date}}</p>
            <p><strong>Expiration Date:</strong> {{end_date}}</p>
        </div>
        
        <h2>Parties</h2>
        <p>This Service Agreement is entered into between:</p>
        <ul>
            <li><strong>Service Provider:</strong> {{from.companyName}}</li>
            <li><strong>Client:</strong> {{to.companyName}}</li>
        </ul>
        
        <h2>Terms and Conditions</h2>
        {{terms}}
        
        <h2>Signatures</h2>
        <div style="margin-top: 50px;">
            <table style="border: none;">
                <tr>
                    <td style="border: none; width: 45%;">
                        <p>_______________________________</p>
                        <p><strong>Service Provider</strong></p>
                        <p>{{from.firstName}} {{from.lastName}}</p>
                        <p>{{from.jobTitle}}</p>
                        <p>Date: _______________</p>
                    </td>
                    <td style="border: none; width: 10%;"></td>
                    <td style="border: none; width: 45%;">
                        <p>_______________________________</p>
                        <p><strong>Client</strong></p>
                        <p>{{to.firstName}} {{to.lastName}}</p>
                        <p>{{to.jobTitle}}</p>
                        <p>Date: _______________</p>
                    </td>
                </tr>
            </table>
        </div>
        ';
        
        return $builder->buildTemplate($content, 'letter');
    }
}