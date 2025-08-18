<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    /** @test */
    public function it_requires_name_field()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_requires_valid_email_field()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Test missing email
        $validator = Validator::make(['name' => 'Test'], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());

        // Test invalid email format
        $validator = Validator::make([
            'name' => 'Test',
            'email' => 'invalid-email'
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());

        // Test valid email
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        $this->actAsAdmin();

        // Create existing user
        User::factory()->create([
            'company_id' => $this->testCompany->id,
            'email' => 'existing@example.com'
        ]);

        $request = new StoreUserRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function it_requires_strong_password()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Test weak password
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // Test password without uppercase
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'lowercase123!',
            'password_confirmation' => 'lowercase123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // Test password without numbers
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'NoNumbers!',
            'password_confirmation' => 'NoNumbers!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // Test strong password
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_requires_password_confirmation()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_role_field()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Test missing role
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role', $validator->errors()->toArray());

        // Test invalid role
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => 99, // Invalid role
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role', $validator->errors()->toArray());

        // Test valid roles
        $validRoles = [
            UserSetting::ROLE_ACCOUNTANT,
            UserSetting::ROLE_TECH,
            UserSetting::ROLE_ADMIN,
            UserSetting::ROLE_SUPER_ADMIN,
        ];

        foreach ($validRoles as $role) {
            $validator = Validator::make([
                'name' => 'Test User',
                'email' => 'test' . $role . '@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'role' => $role,
            ], $rules);

            $this->assertFalse($validator->fails(), "Role {$role} should be valid");
        }
    }

    /** @test */
    public function it_allows_optional_phone_field()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Test without phone
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => UserSetting::ROLE_TECH,
        ], $rules);

        $this->assertFalse($validator->fails());

        // Test with valid phone
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => UserSetting::ROLE_TECH,
            'phone' => '123-456-7890',
        ], $rules);

        $this->assertFalse($validator->fails());

        // Test with too long phone
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => UserSetting::ROLE_TECH,
            'phone' => str_repeat('1', 25), // Too long
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_status_as_boolean()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Test with valid boolean values
        foreach ([true, false, 1, 0, '1', '0'] as $status) {
            $validator = Validator::make([
                'name' => 'Test User',
                'email' => 'test' . $status . '@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'role' => UserSetting::ROLE_TECH,
                'status' => $status,
            ], $rules);

            $this->assertFalse($validator->fails(), "Status {$status} should be valid");
        }

        // Test with invalid value
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => UserSetting::ROLE_TECH,
            'status' => 'invalid',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    /** @test */
    public function it_requires_company_id_for_super_admin_users()
    {
        $this->actAsSuperAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Super admin should have company_id in rules
        $this->assertArrayHasKey('company_id', $rules);
        $this->assertContains('required', $rules['company_id']);
        $this->assertContains('exists:companies,id', $rules['company_id']);
    }

    /** @test */
    public function it_does_not_require_company_id_for_regular_admin_users()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $rules = $request->rules();

        // Regular admin should not have company_id in rules
        $this->assertArrayNotHasKey('company_id', $rules);
    }

    /** @test */
    public function it_automatically_sets_company_id_for_non_super_admin()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        
        // Mock the request data
        $request->merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Call prepareForValidation
        $request->prepareForValidation();

        $this->assertEquals($this->testCompany->id, $request->get('company_id'));
    }

    /** @test */
    public function it_sets_default_status_when_not_provided()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        
        // Mock the request data without status
        $request->merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Call prepareForValidation
        $request->prepareForValidation();

        $this->assertEquals(1, $request->get('status'));
    }

    /** @test */
    public function it_sets_default_send_welcome_email_when_not_provided()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        
        // Mock the request data without send_welcome_email
        $request->merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Call prepareForValidation
        $request->prepareForValidation();

        $this->assertFalse($request->get('send_welcome_email'));
    }

    /** @test */
    public function it_provides_custom_error_messages()
    {
        $this->actAsAdmin();

        $request = new StoreUserRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'name.required',
            'email.required',
            'email.unique',
            'password.required',
            'password.confirmed',
            'role.required',
            'role.in',
            'company_id.required',
            'company_id.exists',
        ];

        foreach ($expectedMessages as $messageKey) {
            $this->assertArrayHasKey($messageKey, $messages);
            $this->assertNotEmpty($messages[$messageKey]);
        }
    }

    /** @test */
    public function it_provides_custom_attribute_names()
    {
        $request = new StoreUserRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name',
            'email',
            'phone',
            'role',
            'company_id',
            'send_welcome_email',
        ];

        foreach ($expectedAttributes as $attribute) {
            $this->assertArrayHasKey($attribute, $attributes);
            $this->assertNotEmpty($attributes[$attribute]);
        }
    }

    /** @test */
    public function it_authorizes_request_correctly()
    {
        $request = new StoreUserRequest();

        // Should always return true as authorization is handled in controller
        $this->assertTrue($request->authorize());
    }
}