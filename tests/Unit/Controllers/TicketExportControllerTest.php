<?php

namespace Tests\Unit\Controllers;

use App\Domains\Ticket\Controllers\TicketExportController;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Tests\RefreshesDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TicketExportControllerTest extends TestCase
{
    use RefreshesDatabase;

    protected TicketExportController $controller;

    protected Company $company;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->controller = new TicketExportController;
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'status' => true,
        ]);
        
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_export_request_generates_csv_headers(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $request = Request::create('/tickets/export', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
