<?php

namespace Tests;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

trait TestHelpers
{
    /**
     * Create a test company with users and data.
     */
    protected function createTestCompanyWithData(): array
    {
        $company = Company::factory()->create([
            'name' => 'Test Company Inc.',
            'email' => 'test@company.com',
        ]);

        // Create users with different roles
        $admin = User::factory()->create(['company_id' => $company->id]);
        UserSetting::create([
            'user_id' => $admin->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        $tech = User::factory()->create(['company_id' => $company->id]);
        UserSetting::create([
            'user_id' => $tech->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        $accountant = User::factory()->create(['company_id' => $company->id]);
        UserSetting::create([
            'user_id' => $accountant->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        // Create clients
        $clients = Client::factory()->count(5)->create([
            'company_id' => $company->id,
        ]);

        // Create tickets
        $tickets = Ticket::factory()->count(10)->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
            'user_id' => collect([$admin, $tech, $accountant])->random()->id,
        ]);

        // Create invoices
        $invoices = Invoice::factory()->count(8)->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
        ]);

        return [
            'company' => $company,
            'users' => [
                'admin' => $admin,
                'tech' => $tech,
                'accountant' => $accountant,
            ],
            'clients' => $clients,
            'tickets' => $tickets,
            'invoices' => $invoices,
        ];
    }

    /**
     * Create a user with specific permissions.
     */
    protected function createUserWithPermissions(Company $company, array $permissions, int $role = null): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        
        UserSetting::create([
            'user_id' => $user->id,
            'role' => $role ?? UserSetting::ROLE_ACCOUNTANT,
        ]);

        foreach ($permissions as $permission) {
            $user->allow($permission);
        }

        return $user;
    }

    /**
     * Create multiple companies for multi-tenant testing.
     */
    protected function createMultipleCompanies(int $count = 3): Collection
    {
        return Company::factory()->count($count)->create();
    }

    /**
     * Create test data across multiple companies to test isolation.
     */
    protected function createCrossTenantTestData(): array
    {
        $companies = $this->createMultipleCompanies(2);
        
        $company1Data = $this->createTestCompanyWithData();
        $company1Data['company'] = $companies[0];
        
        $company2Data = $this->createTestCompanyWithData();
        $company2Data['company'] = $companies[1];

        // Update company IDs for the data
        foreach (['clients', 'tickets', 'invoices'] as $dataType) {
            $company1Data[$dataType]->each(function ($item) use ($companies) {
                $item->update(['company_id' => $companies[0]->id]);
            });
            
            $company2Data[$dataType]->each(function ($item) use ($companies) {
                $item->update(['company_id' => $companies[1]->id]);
            });
        }

        foreach (['admin', 'tech', 'accountant'] as $userType) {
            $company1Data['users'][$userType]->update(['company_id' => $companies[0]->id]);
            $company2Data['users'][$userType]->update(['company_id' => $companies[1]->id]);
        }

        return [
            'company1' => $company1Data,
            'company2' => $company2Data,
        ];
    }

    /**
     * Assert that all items in a collection belong to the specified company.
     */
    protected function assertAllBelongToCompany(Collection $items, Company $company): void
    {
        $items->each(function ($item) use ($company) {
            $this->assertEquals(
                $company->id,
                $item->company_id,
                "Item {$item->id} does not belong to company {$company->id}"
            );
        });
    }

    /**
     * Assert that no items in a collection belong to the specified company.
     */
    protected function assertNoneBelongToCompany(Collection $items, Company $company): void
    {
        $items->each(function ($item) use ($company) {
            $this->assertNotEquals(
                $company->id,
                $item->company_id,
                "Item {$item->id} should not belong to company {$company->id}"
            );
        });
    }

    /**
     * Create a test invoice with items.
     */
    protected function createInvoiceWithItems(Client $client, int $itemCount = 3): Invoice
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
        ]);

        // Create invoice items (assuming InvoiceItem model exists)
        for ($i = 0; $i < $itemCount; $i++) {
            $invoice->items()->create([
                'description' => "Test Item " . ($i + 1),
                'quantity' => rand(1, 10),
                'rate' => rand(10, 100),
                'amount' => rand(100, 1000),
            ]);
        }

        return $invoice->fresh();
    }

    /**
     * Create a test ticket with replies.
     */
    protected function createTicketWithReplies(Client $client, User $user, int $replyCount = 2): Ticket
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        // Create ticket replies (assuming TicketReply model exists)
        for ($i = 0; $i < $replyCount; $i++) {
            $ticket->replies()->create([
                'user_id' => $user->id,
                'message' => "Test reply " . ($i + 1),
                'created_at' => now()->addMinutes($i * 10),
            ]);
        }

        return $ticket->fresh();
    }

    /**
     * Create a user and immediately log them in.
     */
    protected function createAndLoginUser(Company $company = null, int $role = null): User
    {
        $company = $company ?? $this->testCompany;
        $user = User::factory()->create(['company_id' => $company->id]);
        
        UserSetting::create([
            'user_id' => $user->id,
            'role' => $role ?? UserSetting::ROLE_ACCOUNTANT,
        ]);

        $this->actingAs($user);
        
        return $user;
    }

    /**
     * Assert that a response has specific validation errors.
     */
    protected function assertHasValidationErrors($response, array $fields): void
    {
        $response->assertStatus(422);
        
        foreach ($fields as $field) {
            $response->assertJsonValidationErrors($field);
        }
    }

    /**
     * Assert that a response does not have validation errors for specific fields.
     */
    protected function assertDoesNotHaveValidationErrors($response, array $fields): void
    {
        foreach ($fields as $field) {
            $response->assertJsonMissingValidationErrors($field);
        }
    }

    /**
     * Create test data for financial analytics.
     */
    protected function createFinancialTestData(Company $company): array
    {
        $clients = Client::factory()->count(3)->create(['company_id' => $company->id]);
        
        // Create invoices with various statuses
        $paidInvoices = Invoice::factory()->count(5)->paid()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
        ]);
        
        $overdueInvoices = Invoice::factory()->count(3)->overdue()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
        ]);
        
        $draftInvoices = Invoice::factory()->count(2)->draft()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
        ]);

        return [
            'clients' => $clients,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'draft_invoices' => $draftInvoices,
        ];
    }

    /**
     * Create test data for ticket analytics.
     */
    protected function createTicketTestData(Company $company): array
    {
        $clients = Client::factory()->count(2)->create(['company_id' => $company->id]);
        $users = User::factory()->count(2)->create(['company_id' => $company->id]);
        
        $openTickets = Ticket::factory()->count(4)->open()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
            'user_id' => $users->random()->id,
        ]);
        
        $closedTickets = Ticket::factory()->count(6)->closed()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
            'user_id' => $users->random()->id,
        ]);
        
        $overdueTickets = Ticket::factory()->count(2)->overdue()->create([
            'company_id' => $company->id,
            'client_id' => $clients->random()->id,
            'user_id' => $users->random()->id,
        ]);

        return [
            'clients' => $clients,
            'users' => $users,
            'open_tickets' => $openTickets,
            'closed_tickets' => $closedTickets,
            'overdue_tickets' => $overdueTickets,
        ];
    }

    /**
     * Mock external API responses.
     */
    protected function mockExternalApi(string $service, array $responses): void
    {
        // This can be extended based on specific external services used
        // For now, it's a placeholder for common mocking patterns
    }

    /**
     * Clear all caches for testing.
     */
    protected function clearAllCaches(): void
    {
        $this->artisan('cache:clear');
        $this->artisan('config:clear');
        $this->artisan('route:clear');
        $this->artisan('view:clear');
    }

    /**
     * Generate test file for upload testing.
     */
    protected function generateTestFile(string $extension = 'txt', int $sizeKb = 1): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create(
            "test.{$extension}",
            $sizeKb
        );
    }

    /**
     * Generate test image file.
     */
    protected function generateTestImage(int $width = 100, int $height = 100): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->image('test.jpg', $width, $height);
    }
}