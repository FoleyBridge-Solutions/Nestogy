<?php

namespace Tests\Unit\Controllers;

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

    public function test_controller_has_correct_service_class(): void
    {
        $this->controller->initializeController();

        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('serviceClass');
        $property->setAccessible(true);

        $this->assertEquals(ClientService::class, $property->getValue($this->controller));
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
        $this->assertEquals('business', $filters['type']);
        $this->assertEquals('active', $filters['status']);
    }

    public function test_get_filters_returns_empty_array_when_no_filters(): void
    {
        $request = Request::create('/clients', 'GET', []);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getFilters');
        $method->setAccessible(true);

        $filters = $method->invoke($this->controller, $request);

        $this->assertIsArray($filters);
        $this->assertEmpty($filters['search'] ?? null);
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

    public function test_apply_custom_filters_returns_query_builder(): void
    {
        $query = Client::query();
        $request = Request::create('/clients', 'GET');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('applyCustomFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $query, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_format_phone_number_formats_ten_digits_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->controller, '1234567890');
        $this->assertEquals('(123) 456-7890', $formatted);
    }

    public function test_format_phone_number_returns_empty_for_null(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $empty = $method->invoke($this->controller, null);
        $this->assertEquals('', $empty);
    }

    public function test_format_phone_number_returns_unformatted_for_non_ten_digits(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $unformatted = $method->invoke($this->controller, '123');
        $this->assertEquals('123', $unformatted);
    }

    public function test_format_phone_number_strips_non_numeric_characters(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->controller, '(123) 456-7890');
        $this->assertEquals('(123) 456-7890', $formatted);
    }

    public function test_is_valid_internal_url_accepts_client_routes(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->controller, '/clients/123'));
        $this->assertTrue($method->invoke($this->controller, '/clients/123/edit'));
        $this->assertTrue($method->invoke($this->controller, '/clients'));
    }

    public function test_is_valid_internal_url_accepts_whitelisted_routes(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->controller, '/dashboard'));
        $this->assertTrue($method->invoke($this->controller, '/tickets/456'));
        $this->assertTrue($method->invoke($this->controller, '/invoices/789/edit'));
        $this->assertTrue($method->invoke($this->controller, '/assets/123'));
        $this->assertTrue($method->invoke($this->controller, '/contracts/456/view'));
        $this->assertTrue($method->invoke($this->controller, '/reports/monthly'));
        $this->assertTrue($method->invoke($this->controller, '/settings/profile'));
    }

    public function test_is_valid_internal_url_rejects_external_urls(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller, 'https://external.com'));
        $this->assertFalse($method->invoke($this->controller, 'http://evil.com'));
        $this->assertFalse($method->invoke($this->controller, 'ftp://server.com'));
    }

    public function test_is_valid_internal_url_rejects_protocol_relative_urls(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller, '//evil.com'));
        $this->assertFalse($method->invoke($this->controller, '//evil.com/path'));
    }

    public function test_is_valid_internal_url_rejects_non_whitelisted_paths(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller, '/invalid/path'));
        $this->assertFalse($method->invoke($this->controller, '/admin/users'));
        $this->assertFalse($method->invoke($this->controller, '/api/tokens'));
    }

    public function test_is_valid_internal_url_rejects_malformed_urls(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidInternalUrl');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller, ''));
        $this->assertFalse($method->invoke($this->controller, 'not-a-url'));
    }

    public function test_normalize_path_removes_empty_segments(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        $normalized = $method->invoke($this->controller, '/clients//123///edit');
        $this->assertEquals('/clients/123/edit', $normalized);
    }

    public function test_normalize_path_removes_current_directory_references(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        $normalized = $method->invoke($this->controller, '/./clients/./123');
        $this->assertEquals('/clients/123', $normalized);
    }

    public function test_normalize_path_handles_parent_directory_traversal(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        $normalized = $method->invoke($this->controller, '/clients/../tickets/123');
        $this->assertEquals('/tickets/123', $normalized);
    }

    public function test_normalize_path_prevents_escaping_root(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);

        $normalized = $method->invoke($this->controller, '/../../../etc/passwd');
        $this->assertEquals('/etc/passwd', $normalized);
    }

    public function test_build_safe_redirect_url_updates_client_id(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildSafeRedirectUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->controller, '/clients/123/edit', 456);
        $this->assertEquals('/clients/456/edit', $url);
    }

    public function test_build_safe_redirect_url_preserves_query_string(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildSafeRedirectUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->controller, '/clients/123?tab=overview', 789);
        $this->assertEquals('/clients/789?tab=overview', $url);
    }

    public function test_build_safe_redirect_url_handles_non_client_routes(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildSafeRedirectUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->controller, '/dashboard', 123);
        $this->assertEquals('/dashboard', $url);
    }

    public function test_build_safe_redirect_url_sanitizes_query_params(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildSafeRedirectUrl');
        $method->setAccessible(true);

        $url = $method->invoke($this->controller, '/clients/123?_token=abc&tab=overview', 456);
        $this->assertStringNotContainsString('_token', $url);
        $this->assertStringContainsString('tab=overview', $url);
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
    }

    public function test_sanitize_query_string_removes_return_to_param(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('sanitizeQueryString');
        $method->setAccessible(true);

        $sanitized = $method->invoke($this->controller, 'return_to=/evil&tab=overview');
        $this->assertStringNotContainsString('return_to', $sanitized);
        $this->assertStringContainsString('tab=overview', $sanitized);
    }

    public function test_sanitize_query_string_removes_redirect_param(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('sanitizeQueryString');
        $method->setAccessible(true);

        $sanitized = $method->invoke($this->controller, 'redirect=/bad&valid=param');
        $this->assertStringNotContainsString('redirect', $sanitized);
        $this->assertStringContainsString('valid=param', $sanitized);
    }

    public function test_sanitize_query_string_only_allows_alphanumeric_keys(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('sanitizeQueryString');
        $method->setAccessible(true);

        $sanitized = $method->invoke($this->controller, 'valid_key=value&invalid<key>=bad');
        $this->assertStringContainsString('valid_key', $sanitized);
        $this->assertStringNotContainsString('invalid<key>', $sanitized);
    }

    public function test_create_lead_column_mapping_maps_standard_headers(): void
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
    }

    public function test_create_lead_column_mapping_correct_indices(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createLeadColumnMapping');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Email'];
        $mapping = $method->invoke($this->controller, $headers);

        $this->assertEquals(0, $mapping['first_name']);
        $this->assertEquals(1, $mapping['last_name']);
        $this->assertEquals(2, $mapping['email']);
    }

    public function test_create_lead_column_mapping_handles_name_variations(): void
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

    public function test_create_lead_column_mapping_handles_address_fields(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createLeadColumnMapping');
        $method->setAccessible(true);

        $headers = ['Address Line 1', 'Address Line 2', 'City', 'State', 'ZIP'];
        $mapping = $method->invoke($this->controller, $headers);

        $this->assertArrayHasKey('address_line_1', $mapping);
        $this->assertArrayHasKey('address_line_2', $mapping);
        $this->assertArrayHasKey('city', $mapping);
        $this->assertArrayHasKey('state', $mapping);
        $this->assertArrayHasKey('postal_code', $mapping);
    }

    public function test_create_lead_column_mapping_is_case_insensitive(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createLeadColumnMapping');
        $method->setAccessible(true);

        $headers = ['FIRST', 'LAST', 'EMAIL'];
        $mapping = $method->invoke($this->controller, $headers);

        $this->assertArrayHasKey('first_name', $mapping);
        $this->assertArrayHasKey('last_name', $mapping);
        $this->assertArrayHasKey('email', $mapping);
    }

    public function test_map_csv_row_to_lead_data_builds_full_name(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Middle', 'Last'];
        $row = ['John', 'Q', 'Doe'];
        $columnMap = [
            'first_name' => 0,
            'middle_name' => 1,
            'last_name' => 2,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('John Q Doe', $data['name']);
    }

    public function test_map_csv_row_to_lead_data_handles_missing_middle_name(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last'];
        $row = ['John', 'Doe'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('John Doe', $data['name']);
    }

    public function test_map_csv_row_to_lead_data_validates_email(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Email'];
        $row = ['John', 'Doe', 'john@example.com'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'email' => 2,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('john@example.com', $data['email']);
    }

    public function test_map_csv_row_to_lead_data_throws_on_invalid_email(): void
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

    public function test_map_csv_row_to_lead_data_builds_address_from_parts(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Address Line 1', 'Address Line 2'];
        $row = ['John', 'Doe', '123 Main St', 'Apt 4'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'address_line_1' => 2,
            'address_line_2' => 3,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('123 Main St, Apt 4', $data['address']);
    }

    public function test_map_csv_row_to_lead_data_includes_city_state_zip(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'City', 'State', 'ZIP'];
        $row = ['John', 'Doe', 'New York', 'NY', '10001'];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'city' => 2,
            'state' => 3,
            'postal_code' => 4,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('New York', $data['city']);
        $this->assertEquals('NY', $data['state']);
        $this->assertEquals('10001', $data['postal_code']);
    }

    public function test_map_csv_row_to_lead_data_trims_whitespace(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapCsvRowToLeadData');
        $method->setAccessible(true);

        $headers = ['First', 'Last', 'Email'];
        $row = [' John ', ' Doe ', ' john@example.com '];
        $columnMap = [
            'first_name' => 0,
            'last_name' => 1,
            'email' => 2,
        ];

        $data = $method->invoke($this->controller, $row, $headers, $columnMap);

        $this->assertEquals('John Doe', $data['name']);
        $this->assertEquals('john@example.com', $data['email']);
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

    public function test_prepare_store_data_does_not_set_session_when_no_client_id(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('prepareStoreData');
        $method->setAccessible(true);

        session(['selected_client_id' => null]);

        $data = ['name' => 'Test'];
        $method->invoke($this->controller, $data);

        $this->assertNull(session('selected_client_id'));
    }
}
