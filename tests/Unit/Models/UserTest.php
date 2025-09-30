<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_with_factory(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertEquals($company->id, $user->company->id);
    }

    public function test_user_has_user_settings_relationship(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $settings = UserSetting::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => UserSetting::ROLE_ACCOUNTANT,
        ]);

        $this->assertInstanceOf(UserSetting::class, $user->userSetting);
        $this->assertEquals($settings->id, $user->userSetting->id);
    }

    public function test_user_role_constants_exist(): void
    {
        $this->assertEquals(1, UserSetting::ROLE_ACCOUNTANT);
        $this->assertEquals(2, UserSetting::ROLE_TECH);
        $this->assertEquals(3, UserSetting::ROLE_ADMIN);
        $this->assertEquals(4, UserSetting::ROLE_SUPER_ADMIN);
    }

    public function test_user_password_is_hashed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(strlen($user->password) > 20);
    }

    public function test_user_has_fillable_attributes(): void
    {
        $fillable = (new User)->getFillable();

        $expectedFillable = ['name', 'email', 'password', 'company_id', 'status'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_user_hides_sensitive_attributes(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    public function test_user_casts_attributes_correctly(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    public function test_user_can_be_soft_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $userId = $user->id;
        $user->delete();

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
            'archived_at' => null,
        ]);
    }

    public function test_user_has_unique_email(): void
    {
        $company = Company::factory()->create();

        $user1 = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'unique@example.com',
        ]);

        $this->assertEquals('unique@example.com', $user1->email);
        $this->assertDatabaseHas('users', [
            'email' => 'unique@example.com',
            'company_id' => $company->id,
        ]);
    }

    public function test_user_has_company_id_attribute(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertNotNull($user->company_id);
        $this->assertEquals($company->id, $user->company_id);
    }

    public function test_unverified_factory_state(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->unverified()->create(['company_id' => $company->id]);

        $this->assertNull($user->email_verified_at);
    }
}