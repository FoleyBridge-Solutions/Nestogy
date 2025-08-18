<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Silber\Bouncer\BouncerFacade as Bouncer;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, TestHelpers;

    protected Company $testCompany;
    protected User $testUser;
    protected User $adminUser;
    protected User $superAdminUser;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing bouncer cache
        Bouncer::refresh();
    }

    /**
     * Create test tenants and users for multi-tenant testing.
     */
    protected function createTestTenants(): void
    {
        // Create test company
        $this->testCompany = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        // Create regular test user
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'email' => 'user@test.com',
        ]);

        // Create user settings for the test user
        UserSetting::create([
            'user_id' => $this->testUser->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'email' => 'admin@test.com',
        ]);

        UserSetting::create([
            'user_id' => $this->adminUser->id,
            'role' => UserSetting::ROLE_ADMIN,
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ]);

        // Create super admin user
        $this->superAdminUser = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'email' => 'superadmin@test.com',
        ]);

        UserSetting::create([
            'user_id' => $this->superAdminUser->id,
            'role' => UserSetting::ROLE_SUPER_ADMIN,
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ]);

        // Assign roles using Bouncer
        $this->testUser->assign('user');
        $this->adminUser->assign('admin');
        $this->superAdminUser->assign('super-admin');
    }

    /**
     * Act as the test user.
     */
    protected function actAsUser(): self
    {
        return $this->actingAs($this->testUser);
    }

    /**
     * Act as the admin user.
     */
    protected function actAsAdmin(): self
    {
        return $this->actingAs($this->adminUser);
    }

    /**
     * Act as the super admin user.
     */
    protected function actAsSuperAdmin(): self
    {
        return $this->actingAs($this->superAdminUser);
    }

    /**
     * Create a separate company for cross-tenant testing.
     */
    protected function createSecondaryCompany(): Company
    {
        return Company::factory()->create([
            'name' => 'Secondary Test Company',
            'email' => 'secondary@company.com',
        ]);
    }

    /**
     * Create a user for the secondary company.
     */
    protected function createSecondaryCompanyUser(Company $company): User
    {
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'user@secondary.com',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ]);

        $user->assign('user');

        return $user;
    }

    /**
     * Assert that a model belongs to the test company.
     */
    protected function assertBelongsToTestCompany($model): void
    {
        $this->assertEquals($this->testCompany->id, $model->company_id);
    }

    /**
     * Assert that a model does not belong to the test company.
     */
    protected function assertDoesNotBelongToTestCompany($model): void
    {
        $this->assertNotEquals($this->testCompany->id, $model->company_id);
    }

    /**
     * Assert that the response contains validation errors for specific fields.
     */
    protected function assertValidationErrors(array $fields, $response = null): void
    {
        $response = $response ?: $this->response;
        
        $response->assertStatus(422);
        
        foreach ($fields as $field) {
            $response->assertJsonValidationErrors($field);
        }
    }

    /**
     * Assert that a user has specific permissions.
     */
    protected function assertUserHasPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertTrue(
                $user->can($permission),
                "User does not have permission: {$permission}"
            );
        }
    }

    /**
     * Assert that a user does not have specific permissions.
     */
    protected function assertUserDoesNotHavePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertFalse(
                $user->can($permission),
                "User should not have permission: {$permission}"
            );
        }
    }

    /**
     * Create test data that belongs to the test company.
     */
    protected function createTestData(string $modelClass, array $attributes = []): object
    {
        $factory = $modelClass::factory();
        
        // Add company_id if the model supports it
        if (method_exists($modelClass, 'getTable')) {
            $model = new $modelClass;
            if ($model->isFillable('company_id')) {
                $attributes['company_id'] = $this->testCompany->id;
            }
        }

        return $factory->create($attributes);
    }

    /**
     * Get the test company ID for use in tests.
     */
    protected function getTestCompanyId(): int
    {
        return $this->testCompany->id;
    }

    /**
     * Clear all caches between tests.
     */
    protected function tearDown(): void
    {
        Bouncer::refresh();
        parent::tearDown();
    }
}
