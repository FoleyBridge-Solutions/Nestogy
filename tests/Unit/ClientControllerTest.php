<?php

namespace Tests\Unit;

use App\Domains\Client\Controllers\ClientController;
use App\Domains\Client\Services\ClientMetricsService;
use App\Domains\Client\Services\ClientService;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ClientController $controller;

    protected ClientService $clientService;

    protected ClientMetricsService $metricsService;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->clientService = $this->app->make(ClientService::class);
        $this->metricsService = $this->app->make(ClientMetricsService::class);
        $this->controller = new ClientController($this->clientService, $this->metricsService);
    }

    public function test_controller_initializes_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('modelClass');
        $property->setAccessible(true);

        $this->controller->initializeController();

        $this->assertEquals(Client::class, $property->getValue($this->controller));
    }

    public function test_get_filters_returns_expected_filters(): void
    {
        $request = Request::create('/clients', 'GET', [
            'search' => 'test',
            'type' => 'business',
            'status' => 'active',
            'extra' => 'ignored',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getFilters');
        $method->setAccessible(true);

        $filters = $method->invoke($this->controller, $request);

        $this->assertArrayHasKey('search', $filters);
        $this->assertArrayHasKey('type', $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertArrayNotHasKey('extra', $filters);
        $this->assertEquals('test', $filters['search']);
    }

    public function test_apply_custom_filters_excludes_leads(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);
        Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => true,
        ]);

        $query = Client::query();
        $request = Request::create('/clients', 'GET');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('applyCustomFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $query, $request);

        $this->assertEquals(1, $result->count());
    }

    public function test_format_phone_number_formats_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->controller, '1234567890');
        $this->assertEquals('(123) 456-7890', $formatted);

        $empty = $method->invoke($this->controller, null);
        $this->assertEquals('', $empty);

        $unformatted = $method->invoke($this->controller, '123');
        $this->assertEquals('123', $unformatted);
    }

    public function test_is_valid_internal_url_validates_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->controller, '/clients/123'));
        $this->assertTrue($method->invoke($this->controller, '/dashboard'));
        $this->assertTrue($method->invoke($this->controller, '/tickets/456/edit'));

        $this->assertFalse($method->invoke($this->controller, 'https://external.com'));
        $this->assertFalse($method->invoke($this->controller, '//evil.com'));
        $this->assertFalse($method->invoke($this->controller, '/invalid/path'));
    }

    public function test_normalize_path_removes_directory_traversal(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        $normalized = $method->invoke($this->controller, '/clients/../tickets/123');
        $this->assertEquals('/tickets/123', $normalized);

        $normalized = $method->invoke($this->controller, '/./clients/./123');
        $this->assertEquals('/clients/123', $normalized);

        $normalized = $method->invoke($this->controller, '/clients//123///edit');
        $this->assertEquals('/clients/123/edit', $normalized);
    }

    public function test_build_safe_redirect_url_updates_client_id(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildSafeRedirectUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->controller, '/clients/123/edit', 456);
        $this->assertEquals('/clients/456/edit', $url);

        $url = $method->invoke($this->controller, '/clients/123?tab=overview', 789);
        $this->assertEquals('/clients/789?tab=overview', $url);
    }

    public function test_sanitize_query_string_removes_dangerous_params(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('sanitizeQueryString');
        $method->setAccessible(true);

        $sanitized = $method->invoke($this->controller, 'tab=overview&_token=abc&page=2');
        $this->assertStringNotContainsString('_token', $sanitized);
        $this->assertStringContainsString('tab=overview', $sanitized);
        $this->assertStringContainsString('page=2', $sanitized);

        $sanitized = $method->invoke($this->controller, 'return_to=/evil&redirect=/bad');
        $this->assertStringNotContainsString('return_to', $sanitized);
        $this->assertStringNotContainsString('redirect', $sanitized);
    }

    public function test_create_lead_column_mapping_maps_headers_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createLeadColumnMapping');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Email', 'Company Name', 'Phone'];
        $mapping = $method->invoke($this->controller, $headers);

        $this->assertArrayHasKey('first_name', $mapping);
        $this->assertArrayHasKey('last_name', $mapping);
        $this->assertArrayHasKey('email', $mapping);
        $this->assertArrayHasKey('company_name', $mapping);
        $this->assertArrayHasKey('phone', $mapping);

        $this->assertEquals(0, $mapping['first_name']);
        $this->assertEquals(1, $mapping['last_name']);
        $this->assertEquals(2, $mapping['email']);
    }

    public function test_create_lead_column_mapping_handles_variations(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createLeadColumnMapping');
        $method->setAccessible(true);

        $headers = ['firstname', 'surname', 'e-mail', 'organization'];
        $mapping = $method->invoke($this->controller, $headers);

        $this->assertArrayHasKey('first_name', $mapping);
        $this->assertArrayHasKey('last_name', $mapping);
        $this->assertArrayHasKey('email', $mapping);
        $this->assertArrayHasKey('company_name', $mapping);
    }

    public function test_map_csv_row_to_lead_data_builds_name_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Middle', 'Last', 'Email'];
        $row = ['John', 'Q', 'Doe', 'john@example.com'];
        $columnMap = [
            'first_name' => 0,
            'middle_name' => 1,
            'last_name' => 2,
            'email' => 3,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('John Q Doe', $data['name']);
        $this->assertEquals('john@example.com', $data['email']);
    }

    public function test_map_csv_row_to_lead_data_validates_email(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Email'];
        $row = ['John', 'Doe', 'invalid-email'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'email' => 2,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid email format');

        $method->invoke($this->controller, $row, $headers, $columnMap);
    }

    public function test_map_csv_row_to_lead_data_requires_name(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['Email'];
        $row = ['john@example.com'];
        $columnMap = ['email' => 0];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Name is required');

        $method->invoke($this->controller, $row, $headers, $columnMap);
    }

    public function test_map_csv_row_to_lead_data_builds_address(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Address Line 1', 'Address Line 2', 'City', 'State', 'ZIP'];
        $row = ['John', 'Doe', '123 Main St', 'Apt 4', 'New York', 'NY', '10001'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'address_line_1' => 2,
            'address_line_2' => 3,
            'city' => 4,
            'state' => 5,
            'postal_code' => 6,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('123 Main St, Apt 4', $data['address']);
        $this->assertEquals('New York', $data['city']);
        $this->assertEquals('NY', $data['state']);
        $this->assertEquals('10001', $data['postal_code']);
    }

    public function test_prepare_store_data_sets_selected_client(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('prepareStoreData');
        $method->setAccessible(true);

        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $data = ['client_id' => $client->id, 'name' => 'Test'];
        $result = $method->invoke($this->controller, $data);

        $this->assertEquals($client->id, session('selected_client_id'));
    }

    public function test_controller_has_correct_eager_load_relations(): void
    {
        $this->controller->initializeController();

        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('eagerLoadRelations');
        $property->setAccessible(true);

        $relations = $property->getValue($this->controller);

        $this->assertContains('primaryContact', $relations);
        $this->assertContains('primaryLocation', $relations);
    }

    public function test_controller_has_correct_resource_name(): void
    {
        $this->controller->initializeController();

        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('resourceName');
        $property->setAccessible(true);

        $this->assertEquals('clients', $property->getValue($this->controller));
    }

    public function test_controller_has_correct_view_prefix(): void
    {
        $this->controller->initializeController();

        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('viewPrefix');
        $property->setAccessible(true);

        $this->assertEquals('clients', $property->getValue($this->controller));
    }
}
