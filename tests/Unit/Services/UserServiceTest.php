<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserSetting;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    /** @test */
    public function it_can_create_a_user_with_valid_data()
    {
        $this->actAsAdmin();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'phone' => '123-456-7890',
            'status' => true,
        ];

        $user = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals($this->testCompany->id, $user->company_id);
        $this->assertTrue(Hash::check('SecurePassword123!', $user->password));
        
        // Check user settings were created
        $this->assertInstanceOf(UserSetting::class, $user->userSetting);
        $this->assertEquals(UserSetting::ROLE_TECH, $user->userSetting->role);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_user()
    {
        $this->actAsAdmin();

        $this->expectException(ValidationException::class);

        $this->userService->create([
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_validates_email_uniqueness_within_company()
    {
        $this->actAsAdmin();

        // Create first user
        $this->userService->create([
            'name' => 'First User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
        ]);

        // Try to create second user with same email in same company
        $this->expectException(ValidationException::class);

        $this->userService->create([
            'name' => 'Second User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
        ]);
    }

    /** @test */
    public function it_allows_same_email_in_different_companies()
    {
        $secondaryCompany = $this->createSecondaryCompany();
        $secondaryUser = $this->createSecondaryCompanyUser($secondaryCompany);

        // Create user in first company
        $this->actAsAdmin();
        $user1 = $this->userService->create([
            'name' => 'User One',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
        ]);

        // Create user in second company with same email
        $this->actingAs($secondaryUser);
        $user2 = $this->userService->create([
            'name' => 'User Two',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'company_id' => $secondaryCompany->id,
        ]);

        $this->assertNotEquals($user1->company_id, $user2->company_id);
        $this->assertEquals('test@example.com', $user1->email);
        $this->assertEquals('test@example.com', $user2->email);
    }

    /** @test */
    public function it_can_update_user_with_valid_data()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => UserSetting::ROLE_TECH,
            'phone' => '987-654-3210',
            'status' => false,
        ];

        $updatedUser = $this->userService->update($user, $updateData);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertEquals('987-654-3210', $updatedUser->phone);
        $this->assertEquals(UserSetting::ROLE_TECH, $updatedUser->userSetting->role);
        $this->assertFalse($updatedUser->status);
    }

    /** @test */
    public function it_can_update_user_password()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        $updateData = [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ];

        $updatedUser = $this->userService->update($user, $updateData);

        $this->assertTrue(Hash::check('NewSecurePassword123!', $updatedUser->password));
    }

    /** @test */
    public function it_does_not_update_password_if_not_provided()
    {
        $this->actAsAdmin();

        $originalPassword = 'OriginalPassword123!';
        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'password' => Hash::make($originalPassword),
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $updatedUser = $this->userService->update($user, $updateData);

        $this->assertTrue(Hash::check($originalPassword, $updatedUser->password));
    }

    /** @test */
    public function it_can_soft_delete_a_user()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        $this->userService->delete($user);

        $this->assertSoftDeleted($user);
        $this->assertNotNull($user->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_user()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        $user->delete();
        $this->assertSoftDeleted($user);

        $this->userService->restore($user);

        $this->assertNull($user->fresh()->deleted_at);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_can_assign_roles_to_user()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        $this->userService->assignRole($user, 'admin');

        $this->assertTrue($user->isA('admin'));
    }

    /** @test */
    public function it_can_grant_permissions_to_user()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        $permissions = ['clients.view', 'clients.manage', 'assets.view'];

        $this->userService->grantPermissions($user, $permissions);

        foreach ($permissions as $permission) {
            $this->assertTrue($user->can($permission));
        }
    }

    /** @test */
    public function it_can_revoke_permissions_from_user()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
        ]);

        // Grant permissions first
        $permissions = ['clients.view', 'clients.manage', 'assets.view'];
        $user->allow($permissions);

        // Revoke some permissions
        $revokePermissions = ['clients.manage', 'assets.view'];
        $this->userService->revokePermissions($user, $revokePermissions);

        $this->assertTrue($user->can('clients.view'));
        $this->assertFalse($user->can('clients.manage'));
        $this->assertFalse($user->can('assets.view'));
    }

    /** @test */
    public function it_can_change_user_status()
    {
        $this->actAsAdmin();

        $user = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'status' => true,
        ]);

        // Deactivate user
        $this->userService->changeStatus($user, false);
        $this->assertFalse($user->fresh()->status);

        // Reactivate user
        $this->userService->changeStatus($user, true);
        $this->assertTrue($user->fresh()->status);
    }

    /** @test */
    public function it_can_get_users_by_company()
    {
        $secondaryCompany = $this->createSecondaryCompany();
        $this->actAsAdmin();

        // Create users for primary company
        User::factory()->count(3)->create([
            'company_id' => $this->testCompany->id,
        ]);

        // Create users for secondary company
        User::factory()->count(2)->create([
            'company_id' => $secondaryCompany->id,
        ]);

        $primaryCompanyUsers = $this->userService->getUsersByCompany($this->testCompany->id);
        $secondaryCompanyUsers = $this->userService->getUsersByCompany($secondaryCompany->id);

        // +3 from factory, +3 from setUp (testUser, adminUser, superAdminUser)
        $this->assertCount(6, $primaryCompanyUsers);
        $this->assertCount(2, $secondaryCompanyUsers);

        foreach ($primaryCompanyUsers as $user) {
            $this->assertEquals($this->testCompany->id, $user->company_id);
        }

        foreach ($secondaryCompanyUsers as $user) {
            $this->assertEquals($secondaryCompany->id, $user->company_id);
        }
    }

    /** @test */
    public function it_can_get_users_by_role()
    {
        $this->actAsAdmin();

        // Create users with different roles
        $techUsers = User::factory()->count(2)->create([
            'company_id' => $this->testCompany->id,
        ]);

        foreach ($techUsers as $user) {
            UserSetting::create([
                'user_id' => $user->id,
                'role' => UserSetting::ROLE_TECH,
            ]);
        }

        $adminUsers = User::factory()->count(1)->create([
            'company_id' => $this->testCompany->id,
        ]);

        foreach ($adminUsers as $user) {
            UserSetting::create([
                'user_id' => $user->id,
                'role' => UserSetting::ROLE_ADMIN,
            ]);
        }

        $techRoleUsers = $this->userService->getUsersByRole(UserSetting::ROLE_TECH);
        $adminRoleUsers = $this->userService->getUsersByRole(UserSetting::ROLE_ADMIN);

        $this->assertCount(2, $techRoleUsers);
        $this->assertCount(2, $adminRoleUsers); // +1 from setUp adminUser

        foreach ($techRoleUsers as $user) {
            $this->assertEquals(UserSetting::ROLE_TECH, $user->userSetting->role);
        }

        foreach ($adminRoleUsers as $user) {
            $this->assertEquals(UserSetting::ROLE_ADMIN, $user->userSetting->role);
        }
    }

    /** @test */
    public function it_can_search_users_by_name_or_email()
    {
        $this->actAsAdmin();

        User::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        User::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Bob Johnson',
            'email' => 'bob@company.com',
        ]);

        // Search by name
        $johnUsers = $this->userService->searchUsers('John');
        $this->assertCount(2, $johnUsers); // John Doe and Bob Johnson

        // Search by email domain
        $exampleUsers = $this->userService->searchUsers('example.com');
        $this->assertCount(2, $exampleUsers); // john@example.com and jane@example.com

        // Search by partial name
        $jUsers = $this->userService->searchUsers('J');
        $this->assertCount(2, $jUsers); // John Doe and Jane Smith
    }

    /** @test */
    public function it_sends_welcome_email_when_requested()
    {
        Mail::fake();
        $this->actAsAdmin();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'send_welcome_email' => true,
        ];

        $user = $this->userService->create($userData);

        Mail::assertQueued(\App\Mail\WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function it_does_not_send_welcome_email_when_not_requested()
    {
        Mail::fake();
        $this->actAsAdmin();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'send_welcome_email' => false,
        ];

        $user = $this->userService->create($userData);

        Mail::assertNotQueued(\App\Mail\WelcomeEmail::class);
    }

    /** @test */
    public function it_enforces_company_boundaries_for_non_super_admin()
    {
        $secondaryCompany = $this->createSecondaryCompany();
        $this->actAsAdmin(); // Regular admin, not super admin

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        // Should not be able to create user for different company
        $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'company_id' => $secondaryCompany->id,
        ]);
    }

    /** @test */
    public function it_allows_super_admin_to_create_users_for_any_company()
    {
        $secondaryCompany = $this->createSecondaryCompany();
        $this->actAsSuperAdmin();

        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
            'company_id' => $secondaryCompany->id,
        ]);

        $this->assertEquals($secondaryCompany->id, $user->company_id);
    }
}