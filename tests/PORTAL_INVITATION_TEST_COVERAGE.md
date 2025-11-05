# Portal Invitation Test Coverage

## Overview
This document describes the comprehensive test coverage created for the Portal Invitation system to prevent issues with the invitation token storage and validation.

## Problem Identified
The `invitation_token` field is in the `$guarded` array in the `Contact` model (line 142), which prevents it from being mass-assigned via `update()` or `create()`. However, the `PortalInvitationService` was attempting to set this field using mass assignment, resulting in the token being stored as `NULL` in the database. This caused all portal invitation links to show as "expired" even though they were just sent.

## Test Files Created

### Unit Tests: `tests/Unit/Services/PortalInvitationServiceTest.php`
Comprehensive service-level tests covering:

#### Critical Bug Detection Tests
- ✅ `test_invitation_token_is_stored_despite_being_guarded()` - **Key test that would have caught the bug**
- ✅ `test_send_invitation_creates_token_that_can_be_validated()` - Ensures end-to-end token creation and validation works

#### Token Generation & Validation
- ✅ `test_validate_token_finds_contact_with_valid_token()` - Verifies token validation works correctly
- ✅ `test_validate_token_returns_null_for_expired_invitation()` - Tests expiration logic
- ✅ `test_validate_token_returns_null_for_invalid_token()` - Tests security (wrong tokens rejected)

#### Invitation Lifecycle
- ✅ `test_invitation_status_is_set_to_sent()` - Status field updated correctly
- ✅ `test_invitation_expires_at_is_set_correctly()` - 72-hour expiration set properly
- ✅ `test_has_portal_access_is_enabled_when_invitation_sent()` - Portal access enabled
- ✅ `test_invitation_sent_by_is_recorded()` - Audit trail maintained
- ✅ `test_invitation_sent_at_is_recorded()` - Timestamp recorded

#### Invitation Acceptance
- ✅ `test_accept_invitation_sets_password()` - Password hash created correctly
- ✅ `test_accept_invitation_marks_invitation_as_accepted()` - Status updated
- ✅ `test_accept_invitation_clears_invitation_token()` - Security: token cleared after use
- ✅ `test_accept_invitation_sets_email_verified_at()` - Email verification handled
- ✅ `test_accept_invitation_fails_with_expired_token()` - Security: expired tokens rejected
- ✅ `test_accept_invitation_validates_password_requirements()` - Password strength enforced

#### Resend & Revoke Operations
- ✅ `test_resend_invitation_generates_new_token()` - New token generated on resend
- ✅ `test_revoke_invitation_clears_token()` - Token cleared on revoke
- ✅ `test_revoke_invitation_sets_status_to_revoked()` - Status updated
- ✅ `test_revoke_invitation_disables_portal_access()` - Access revoked

#### Validation & Edge Cases
- ✅ `test_send_invitation_fails_without_email()` - Email required
- ✅ `test_send_invitation_fails_for_inactive_client()` - Client must be active
- ✅ `test_update_expired_invitations_changes_status()` - Batch expiration handling

#### Contact Model Methods
- ✅ `test_contact_has_valid_invitation_method_works_correctly()` - Helper method accuracy
- ✅ `test_contact_is_invitation_expired_method_works_correctly()` - Expiration detection

**Total Unit Tests: 25**

### Feature Tests: `tests/Feature/Portal/PortalInvitationControllerTest.php`
End-to-end HTTP request tests covering:

#### Invitation Display
- ✅ `test_show_invitation_page_with_valid_token()` - Valid invitations display correctly
- ✅ `test_show_invitation_page_with_expired_token()` - Expired invitations show expired page
- ✅ `test_show_invitation_page_with_invalid_token()` - Invalid tokens handled gracefully

#### Invitation Acceptance Flow
- ✅ `test_accept_invitation_with_valid_password()` - Complete acceptance workflow
- ✅ `test_accept_invitation_logs_in_user_after_acceptance()` - Auto-login after acceptance
- ✅ `test_invitation_acceptance_creates_activity_log()` - Audit trail created

#### Password Validation
- ✅ `test_accept_invitation_fails_with_weak_password()` - Weak passwords rejected
- ✅ `test_accept_invitation_fails_with_mismatched_passwords()` - Password confirmation required
- ✅ `test_accept_invitation_requires_uppercase_letter()` - Uppercase requirement enforced
- ✅ `test_accept_invitation_requires_lowercase_letter()` - Lowercase requirement enforced
- ✅ `test_accept_invitation_requires_number()` - Number requirement enforced
- ✅ `test_accept_invitation_requires_minimum_length()` - Length requirement enforced

#### Security & Data Integrity
- ✅ `test_accept_invitation_fails_with_expired_token()` - Expired tokens can't be accepted
- ✅ `test_accept_invitation_sets_email_verified_at()` - Email verification recorded
- ✅ `test_accept_invitation_sets_password_changed_at()` - Password change timestamp
- ✅ `test_accept_invitation_resets_failed_login_attempts()` - Login counter reset
- ✅ `test_accept_invitation_clears_must_change_password_flag()` - Password flags cleared

#### UI & Routes
- ✅ `test_expired_invitation_page_shows_correct_view()` - Expired page displays correctly

**Total Feature Tests: 17**

## Total Test Coverage
**42 tests** covering all aspects of the portal invitation system

## How These Tests Prevent the Bug

### The Critical Test
The most important test is `test_invitation_token_is_stored_despite_being_guarded()`:

```php
public function test_invitation_token_is_stored_despite_being_guarded()
{
    $result = $this->service->sendInvitation($this->contact, $this->user);
    
    $this->assertTrue($result['success']);
    
    // Refresh contact from database
    $this->contact->refresh();
    
    // The invitation_token field should NOT be null
    $this->assertNotNull(
        $this->contact->invitation_token,
        'invitation_token should be stored in database even though it is in the guarded array'
    );
}
```

This test specifically checks that:
1. The invitation is sent successfully
2. The contact is refreshed from the database (not using cached values)
3. The `invitation_token` field is **not null** in the database
4. Includes a clear error message explaining the expected behavior

### Test Execution Results (Before Fix)
When run against the current code with the bug:

```
FAIL  Tests\Unit\Services\PortalInvitationServiceTest
⨯ invitation token is stored despite being guarded

Failed asserting that null is not null.
invitation_token should be stored in database even though it is in the guarded array
```

This clearly identifies the issue.

## Running the Tests

### Run all portal invitation tests:
```bash
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php
php artisan test tests/Feature/Portal/PortalInvitationControllerTest.php
```

### Run only the critical bug detection test:
```bash
php artisan test --filter=test_invitation_token_is_stored_despite_being_guarded
```

### Run with coverage:
```bash
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php --coverage
```

## What Needs to Be Fixed

The `PortalInvitationService::sendInvitation()` method (line 78-86) needs to use direct property assignment instead of mass assignment for the `invitation_token` field:

**Current (Broken) Code:**
```php
$contact->update([
    'invitation_token' => Hash::make($token),
    'invitation_sent_at' => now(),
    'invitation_expires_at' => $expiresAt,
    'invitation_sent_by' => $sentBy->id,
    'invitation_status' => 'sent',
    'has_portal_access' => true,
]);
```

**Fixed Code Options:**

Option 1: Direct assignment for guarded field
```php
$contact->invitation_token = Hash::make($token);
$contact->update([
    'invitation_sent_at' => now(),
    'invitation_expires_at' => $expiresAt,
    'invitation_sent_by' => $sentBy->id,
    'invitation_status' => 'sent',
    'has_portal_access' => true,
]);
```

Option 2: Use forceFill for all fields
```php
$contact->forceFill([
    'invitation_token' => Hash::make($token),
    'invitation_sent_at' => now(),
    'invitation_expires_at' => $expiresAt,
    'invitation_sent_by' => $sentBy->id,
    'invitation_status' => 'sent',
    'has_portal_access' => true,
])->save();
```

Similar fixes needed in:
- `resendInvitation()` method (calls sendInvitation, so will be fixed automatically)
- `acceptInvitation()` method (line 191-199) - uses update() for password_hash (also guarded)
- `revokeInvitation()` method (line 243-247) - sets invitation_token to null

## Maintenance

### When to Update Tests
- When adding new invitation-related features
- When changing password requirements
- When modifying expiration logic
- When adding new statuses or states
- When changing security requirements

### Test Naming Convention
All tests follow the pattern: `test_<what>_<expected_behavior>()`
- Clear, descriptive names
- Easy to understand what's being tested
- Easy to identify what failed when a test breaks

## Additional Notes

### Why invitation_token is Guarded
The field is in `$guarded` to prevent accidental mass assignment in other parts of the application. This is a security best practice - sensitive authentication tokens should not be mass-assignable. However, the service needs explicit permission to set it, which is why direct assignment or `forceFill()` is required.

### Test Data Management
All tests use factories and clean up automatically via `RefreshesDatabase` trait. No manual cleanup required.

### Mock Usage
Email sending is mocked in unit tests to avoid actual SMTP calls and speed up test execution.
