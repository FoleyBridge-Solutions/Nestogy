<?php

namespace Tests\Unit\Auth;

use App\Models\Client;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    /** @test */
    public function admin_users_can_access_admin_gates()
    {
        $this->actAsAdmin();

        $this->assertTrue(Gate::allows('admin'));
        $this->assertTrue(Gate::allows('any-admin'));
        $this->assertTrue(Gate::allows('is-admin'));
        $this->assertFalse(Gate::allows('is-super-admin'));
    }

    /** @test */
    public function super_admin_users_can_access_all_admin_gates()
    {
        $this->actAsSuperAdmin();

        $this->assertTrue(Gate::allows('admin'));
        $this->assertTrue(Gate::allows('super-admin'));
        $this->assertTrue(Gate::allows('any-admin'));
        $this->assertTrue(Gate::allows('is-super-admin'));
        $this->assertFalse(Gate::allows('is-admin')); // They are super admin, not regular admin
    }

    /** @test */
    public function regular_users_cannot_access_admin_gates()
    {
        $this->actAsUser();

        $this->assertFalse(Gate::allows('admin'));
        $this->assertFalse(Gate::allows('super-admin'));
        $this->assertFalse(Gate::allows('any-admin'));
        $this->assertFalse(Gate::allows('is-admin'));
        $this->assertFalse(Gate::allows('is-super-admin'));
    }

    /** @test */
    public function role_hierarchy_is_enforced_correctly()
    {
        // Test different role levels
        $techUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $techUser->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        $accountantUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $accountantUser->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        // Tech user should have tech permissions
        $this->actingAs($techUser);
        $this->assertTrue(Gate::allows('tech'));
        $this->assertTrue(Gate::allows('manage-clients'));
        $this->assertTrue(Gate::allows('manage-assets'));

        // Accountant user should have accountant + tech permissions
        $this->actingAs($accountantUser);
        $this->assertTrue(Gate::allows('accountant'));
        $this->assertTrue(Gate::allows('tech'));
        $this->assertTrue(Gate::allows('manage-tickets'));
    }

    /** @test */
    public function user_management_permissions_work_correctly()
    {
        // Admin should be able to manage users
        $this->actAsAdmin();
        $this->assertTrue(Gate::allows('manage-users'));
        $this->assertTrue(Gate::allows('create-users'));
        $this->assertTrue(Gate::allows('edit-users'));
        $this->assertTrue(Gate::allows('delete-users'));

        // Regular user should not
        $this->actAsUser();
        $this->assertFalse(Gate::allows('manage-users'));
        $this->assertFalse(Gate::allows('create-users'));
        $this->assertFalse(Gate::allows('edit-users'));
        $this->assertFalse(Gate::allows('delete-users'));
    }

    /** @test */
    public function client_management_permissions_work_correctly()
    {
        // Create tech level user
        $techUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $techUser->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        // Tech user should be able to manage clients
        $this->actingAs($techUser);
        $this->assertTrue(Gate::allows('manage-clients'));
        $this->assertTrue(Gate::allows('create-clients'));
        $this->assertTrue(Gate::allows('edit-clients'));
        $this->assertFalse(Gate::allows('delete-clients')); // Only super admin can delete

        // Super admin can delete clients
        $this->actAsSuperAdmin();
        $this->assertTrue(Gate::allows('delete-clients'));

        // Regular user cannot manage clients
        $this->actAsUser();
        $this->assertFalse(Gate::allows('manage-clients'));
    }

    /** @test */
    public function financial_permissions_are_enforced()
    {
        // Set up user with financial dashboard access
        $this->testUser->userSetting->update([
            'financial_dashboard' => true
        ]);

        $this->actAsUser();
        $this->assertTrue(Gate::allows('manage-finances'));
        $this->assertTrue(Gate::allows('view-financial-reports'));
        $this->assertTrue(Gate::allows('manage-invoices'));

        // User without financial dashboard should not have access
        $this->testUser->userSetting->update([
            'financial_dashboard' => false
        ]);

        $this->assertFalse(Gate::allows('manage-finances'));
        $this->assertFalse(Gate::allows('view-financial-reports'));
        $this->assertFalse(Gate::allows('manage-invoices'));
    }

    /** @test */
    public function technical_permissions_are_enforced()
    {
        // Set up user with technical dashboard access
        $this->testUser->userSetting->update([
            'technical_dashboard' => true
        ]);

        $this->actAsUser();
        $this->assertTrue(Gate::allows('manage-technical'));
        $this->assertTrue(Gate::allows('view-technical-reports'));

        // User without technical dashboard should not have access
        $this->testUser->userSetting->update([
            'technical_dashboard' => false
        ]);

        $this->assertFalse(Gate::allows('manage-technical'));
        $this->assertFalse(Gate::allows('view-technical-reports'));
    }

    /** @test */
    public function cross_tenant_permissions_work_correctly()
    {
        // Regular admin should not have cross-tenant access
        $this->actAsAdmin();
        $this->assertFalse(Gate::allows('access-cross-tenant'));
        $this->assertFalse(Gate::allows('manage-subscriptions'));
        $this->assertFalse(Gate::allows('impersonate-tenant'));

        // Super admin should have cross-tenant access
        $this->actAsSuperAdmin();
        $this->assertTrue(Gate::allows('access-cross-tenant'));
        $this->assertTrue(Gate::allows('manage-subscriptions'));
        $this->assertTrue(Gate::allows('impersonate-tenant'));
    }

    /** @test */
    public function quote_management_permissions_work_correctly()
    {
        // Give user quote management permission
        $this->testUser->allow('financial.quotes.manage');
        
        $this->actAsUser();
        $this->assertTrue(Gate::allows('manage-quotes'));
        $this->assertTrue(Gate::allows('create-quotes'));
        $this->assertTrue(Gate::allows('send-quotes'));

        // Test quote approval permissions
        $this->testUser->allow('financial.quotes.approve');
        $this->testUser->assign('manager');
        
        $this->assertTrue(Gate::allows('approve-quotes'));
        $this->assertTrue(Gate::allows('approve-quotes-manager'));
    }

    /** @test */
    public function export_permissions_work_correctly()
    {
        // Give user export permissions
        $this->testUser->allow('clients.export');
        $this->testUser->allow('financial.export');
        
        $this->actAsUser();
        $this->assertTrue(Gate::allows('export-any-data'));
        $this->assertTrue(Gate::allows('export-client-data'));
        $this->assertTrue(Gate::allows('export-financial-data'));
        $this->assertTrue(Gate::allows('export-sensitive-data'));
    }

    /** @test */
    public function same_company_gate_works_correctly()
    {
        $this->actAsUser();
        
        // Create client in same company
        $sameCompanyClient = Client::create([
            'name' => 'Same Company Client',
            'email' => 'same@test.com',
            'company_id' => $this->testCompany->id,
        ]);

        // Create client in different company
        $differentCompanyClient = Client::create([
            'name' => 'Different Company Client',
            'email' => 'different@test.com',
            'company_id' => $this->createSecondaryCompany()->id,
        ]);

        $this->assertTrue(Gate::allows('same-company', $sameCompanyClient));
        $this->assertFalse(Gate::allows('same-company', $differentCompanyClient));
    }

    /** @test */
    public function security_gates_work_correctly()
    {
        // Give user security permissions
        $this->testUser->allow('system.logs.view');
        $this->testUser->allow('system.permissions.manage');
        
        $this->actAsUser();
        $this->assertTrue(Gate::allows('view-audit-logs'));

        // Admin users should have access to sensitive data
        $this->actAsAdmin();
        $this->assertTrue(Gate::allows('access-sensitive-data'));

        // Super admin should be able to impersonate users
        $this->actAsSuperAdmin();
        $this->assertTrue(Gate::allows('impersonate-users'));
        $this->assertTrue(Gate::allows('bypass-restrictions'));
    }

    /** @test */
    public function permission_based_gates_work_with_dynamic_permissions()
    {
        $this->actAsUser();
        
        // Test dynamic permission checking
        $this->testUser->allow('custom.permission');
        $this->assertTrue(Gate::allows('has-permission', 'custom.permission'));
        $this->assertFalse(Gate::allows('has-permission', 'nonexistent.permission'));

        // Test multiple permissions
        $this->testUser->allow('permission.one');
        $this->testUser->allow('permission.two');
        
        $this->assertTrue(Gate::allows('has-any-permission', ['permission.one', 'permission.three']));
        $this->assertFalse(Gate::allows('has-all-permissions', ['permission.one', 'permission.three']));
        $this->assertTrue(Gate::allows('has-all-permissions', ['permission.one', 'permission.two']));
    }

    /** @test */
    public function domain_access_gates_work_correctly()
    {
        $this->actAsUser();
        
        // Mock domain access check
        $this->testUser->allow('clients.view');
        $this->testUser->allow('clients.manage');
        
        $this->assertTrue(Gate::allows('access-domain', 'clients'));
        $this->assertTrue(Gate::allows('perform-action', 'clients', 'manage'));
        $this->assertFalse(Gate::allows('perform-action', 'restricted', 'access'));
    }

    /** @test */
    public function approval_workflow_gates_work_correctly()
    {
        $this->actAsUser();
        
        // Test expense approval
        $this->testUser->allow('financial.expenses.approve');
        $this->assertTrue(Gate::allows('approve-expenses'));

        // Test payment approval
        $this->testUser->allow('financial.payments.manage');
        $this->assertTrue(Gate::allows('approve-payments'));

        // Test budget approval
        $this->actAsAdmin();
        $this->assertTrue(Gate::allows('approve-budgets'));
    }

    /** @test */
    public function product_management_gates_work_correctly()
    {
        // Create accountant level user
        $accountantUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $accountantUser->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        $this->actingAs($accountantUser);
        $this->assertTrue(Gate::allows('access', 'products'));
        $this->assertTrue(Gate::allows('manage-products'));
        $this->assertTrue(Gate::allows('manage-product-inventory'));

        // Tech user should be able to create/update products
        $techUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $techUser->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        $this->actingAs($techUser);
        $this->assertTrue(Gate::allows('create', 'products'));
        $this->assertTrue(Gate::allows('update', 'products'));
        $this->assertTrue(Gate::allows('delete', 'products'));
        $this->assertTrue(Gate::allows('manage-product-pricing'));
        $this->assertTrue(Gate::allows('manage-bundles'));
        $this->assertTrue(Gate::allows('manage-pricing-rules'));
    }
}