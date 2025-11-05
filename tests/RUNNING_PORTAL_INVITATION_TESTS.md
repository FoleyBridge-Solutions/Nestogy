# Quick Reference: Running Portal Invitation Tests

## Run All Tests
```bash
# Run all portal invitation tests
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php tests/Feature/Portal/PortalInvitationControllerTest.php

# Run only unit tests
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php

# Run only feature tests
php artisan test tests/Feature/Portal/PortalInvitationControllerTest.php
```

## Run Specific Critical Tests
```bash
# The test that catches the mass assignment bug
php artisan test --filter=test_invitation_token_is_stored_despite_being_guarded

# End-to-end token validation test
php artisan test --filter=test_send_invitation_creates_token_that_can_be_validated

# Token validation logic
php artisan test --filter=test_validate_token_finds_contact_with_valid_token
```

## Run by Category

### Security Tests
```bash
php artisan test --filter="expired_token|invalid_token|password_requirements"
```

### Token Management Tests
```bash
php artisan test --filter="token"
```

### Acceptance Flow Tests
```bash
php artisan test --filter="accept_invitation"
```

### Validation Tests
```bash
php artisan test --filter="validate|password|requires"
```

## Expected Results (With Bug)
When the bug is present, you should see failures like:
```
FAIL  Tests\Unit\Services\PortalInvitationServiceTest
⨯ invitation token is stored despite being guarded
Failed asserting that null is not null.
```

## Expected Results (Bug Fixed)
All tests should pass:
```
PASS  Tests\Unit\Services\PortalInvitationServiceTest
✓ send invitation creates token that can be validated
✓ invitation token is stored despite being guarded
✓ validate token finds contact with valid token
... (42 total tests passing)
```

## Quick Test During Development
```bash
# Run tests with stop on first failure
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php --stop-on-failure

# Run with detailed output
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php -v
```

## Coverage Analysis
```bash
# Generate coverage report (requires Xdebug)
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php --coverage

# Minimum coverage threshold check
php artisan test tests/Unit/Services/PortalInvitationServiceTest.php --coverage-text --min=80
```

## Continuous Integration
These tests should be run:
- ✅ On every commit
- ✅ Before merging PRs
- ✅ In CI/CD pipeline
- ✅ Before deployment

## Test Statistics
- **Total Tests**: 42
- **Unit Tests**: 25
- **Feature Tests**: 17
- **Critical Bug Detection Tests**: 2
- **Security Tests**: 8
- **Validation Tests**: 7
