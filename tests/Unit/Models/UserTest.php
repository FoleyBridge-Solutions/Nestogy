<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_company()
    {
        $user = User::factory()->create([
            'company_id' => $this->testCompany->id
        ]);

        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertEquals($this->testCompany->id, $user->company->id);
    }

    /** @test */
    public function it_has_user_settings()
    {
        $user = User::factory()->create([
            'company_id' => $this->testCompany->id
        ]);

        $userSetting = UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
            'timezone' => 'America/New_York',
        ]);

        $this->assertInstanceOf(UserSetting::class, $user->userSetting);
        $this->assertEquals($userSetting->id, $user->userSetting->id);
    }

    /** @test */
    public function it_can_check_if_user_is_admin()
    {
        // Create admin user
        $adminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $adminUser->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        // Create regular user
        $regularUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $regularUser->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    /** @test */
    public function it_can_check_if_user_is_super_admin()
    {
        // Create super admin user
        $superAdminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $superAdminUser->id,
            'role' => UserSetting::ROLE_SUPER_ADMIN,
        ]);

        // Create admin user
        $adminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $adminUser->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        $this->assertTrue($superAdminUser->isSuperAdmin());
        $this->assertFalse($adminUser->isSuperAdmin());
    }

    /** @test */
    public function it_can_check_if_user_is_any_admin()
    {
        // Create super admin user
        $superAdminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $superAdminUser->id,
            'role' => UserSetting::ROLE_SUPER_ADMIN,
        ]);

        // Create admin user
        $adminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $adminUser->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        // Create regular user
        $regularUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $regularUser->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        $this->assertTrue($superAdminUser->isAnyAdmin());
        $this->assertTrue($adminUser->isAnyAdmin());
        $this->assertFalse($regularUser->isAnyAdmin());
    }

    /** @test */
    public function it_can_get_user_role()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        $this->assertEquals(UserSetting::ROLE_TECH, $user->getRole());
    }

    /** @test */
    public function it_returns_default_role_when_no_user_settings()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);

        // Should return a default role when no user settings exist
        $this->assertEquals(UserSetting::ROLE_ACCOUNTANT, $user->getRole());
    }

    /** @test */
    public function it_can_get_role_level()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        $this->assertEquals(UserSetting::ROLE_ADMIN, $user->getRoleLevel());
    }

    /** @test */
    public function it_can_check_cross_tenant_access()
    {
        // Regular admin should not have cross-tenant access
        $adminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $adminUser->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        // Super admin should have cross-tenant access
        $superAdminUser = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $superAdminUser->id,
            'role' => UserSetting::ROLE_SUPER_ADMIN,
        ]);

        $this->assertFalse($adminUser->canAccessCrossTenant());
        $this->assertTrue($superAdminUser->canAccessCrossTenant());
    }

    /** @test */
    public function it_can_check_domain_access()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        // Give user specific domain permissions
        $user->allow('clients.view');
        $user->allow('assets.manage');

        $this->assertTrue($user->canAccessDomain('clients'));
        $this->assertTrue($user->canAccessDomain('assets'));
        $this->assertFalse($user->canAccessDomain('restricted'));
    }

    /** @test */
    public function it_can_check_specific_actions()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_TECH,
        ]);

        // Give user specific action permissions
        $user->allow('clients.manage');
        $user->allow('assets.view');

        $this->assertTrue($user->canPerformAction('clients', 'manage'));
        $this->assertTrue($user->canPerformAction('assets', 'view'));
        $this->assertFalse($user->canPerformAction('assets', 'delete'));
    }

    /** @test */
    public function it_can_check_permission_existence()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        
        // Give user some permissions
        $user->allow('clients.view');
        $user->allow('assets.manage');

        $this->assertTrue($user->hasPermission('clients.view'));
        $this->assertTrue($user->hasPermission('assets.manage'));
        $this->assertFalse($user->hasPermission('restricted.access'));
    }

    /** @test */
    public function it_can_check_any_permission()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        
        $user->allow('clients.view');
        $user->allow('assets.manage');

        $permissions = ['clients.view', 'nonexistent.permission'];
        $this->assertTrue($user->hasAnyPermission($permissions));

        $permissions = ['nonexistent.one', 'nonexistent.two'];
        $this->assertFalse($user->hasAnyPermission($permissions));
    }

    /** @test */
    public function it_can_check_all_permissions()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        
        $user->allow('clients.view');
        $user->allow('assets.manage');

        $permissions = ['clients.view', 'assets.manage'];
        $this->assertTrue($user->hasAllPermissions($permissions));

        $permissions = ['clients.view', 'nonexistent.permission'];
        $this->assertFalse($user->hasAllPermissions($permissions));
    }

    /** @test */
    public function it_encrypts_password_when_set()
    {
        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'password' => 'plain-password'
        ]);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    /** @test */
    public function it_has_required_fillable_attributes()
    {
        $fillable = (new User())->getFillable();

        $expectedFillable = [
            'company_id',
            'name',
            'email',
            'password',
            'phone',
            'status',
            'avatar',
            'token',
            'specific_encryption_ciphertext',
            'php_session',
            'extension_key',
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable, "Attribute {$attribute} should be fillable");
        }
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);

        $hidden = (new User())->getHidden();

        $expectedHidden = [
            'password',
            'remember_token',
            'specific_encryption_ciphertext',
            'php_session',
            'extension_key',
        ];

        foreach ($expectedHidden as $attribute) {
            $this->assertContains($attribute, $hidden, "Attribute {$attribute} should be hidden");
        }

        // Test that hidden attributes are not in array output
        $userArray = $user->toArray();
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'email_verified_at' => now(),
            'status' => 1
        ]);

        $casts = (new User())->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('status', $casts);

        // Test actual casting
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
        $this->assertIsBool($user->status);
    }

    /** @test */
    public function it_validates_email_uniqueness_per_company()
    {
        $company1 = $this->testCompany;
        $company2 = $this->createSecondaryCompany();

        // Create user in company 1
        $user1 = User::factory()->create([
            'company_id' => $company1->id,
            'email' => 'test@example.com'
        ]);

        // Should be able to create user with same email in different company
        $user2 = User::factory()->create([
            'company_id' => $company2->id,
            'email' => 'test@example.com'
        ]);

        $this->assertEquals('test@example.com', $user1->email);
        $this->assertEquals('test@example.com', $user2->email);
        $this->assertNotEquals($user1->company_id, $user2->company_id);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);
        $userId = $user->id;

        $user->delete();

        // Should be soft deleted, not actually removed
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertNotNull($user->fresh()->deleted_at);

        // Should not appear in normal queries
        $this->assertNull(User::find($userId));

        // Should appear in withTrashed queries
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    /** @test */
    public function it_maintains_bouncer_relationships()
    {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);

        // Test that bouncer traits are available
        $this->assertTrue(method_exists($user, 'assign'));
        $this->assertTrue(method_exists($user, 'allow'));
        $this->assertTrue(method_exists($user, 'disallow'));
        $this->assertTrue(method_exists($user, 'can'));

        // Test role assignment
        $user->assign('admin');
        $this->assertTrue($user->isA('admin'));

        // Test permission assignment
        $user->allow('test.permission');
        $this->assertTrue($user->can('test.permission'));
    }
}