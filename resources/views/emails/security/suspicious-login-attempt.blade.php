@component('mail::message')
# 🔒 Suspicious Login Attempt Detected

Hello {{ $user->name }},

We detected a login attempt to your Nestogy account from an unusual location or device. For your security, we've temporarily blocked this login and need your approval to proceed.

## Login Attempt Details

@component('mail::panel')
**Location:** {{ $location }}
**Device:** {{ $device }}
**IP Address:** {{ $attempt->ip_address }}
**Time:** {{ $attempt->created_at->format('F j, Y \a\t g:i A T') }}
**Risk Level:** <span style="color: {{ $riskColor }}; font-weight: bold;">{{ $riskLevel }}</span>
@endcomponent

## Why was this flagged?

{{ $reasons }}

## What should you do?

If this login attempt was made by you, click the "Approve Login" button below. If you don't recognize this activity, click "Deny & Report" to block this attempt and secure your account.

@component('mail::table')
| Action | Description |
|:-------|:------------|
| **If this was you** | Click "Approve Login" to complete your sign-in |
| **If this wasn't you** | Click "Deny & Report" to block this attempt and change your password |
@endcomponent

@component('mail::button', ['url' => $approvalUrl, 'color' => 'success'])
✅ Approve Login
@endcomponent

@component('mail::button', ['url' => $denialUrl, 'color' => 'error'])
🚫 Deny & Report
@endcomponent

## Additional Security Options

When you approve this login, you'll have the option to:
- Mark this location as trusted for future logins
- Require additional verification for unrecognized devices
- Set up two-factor authentication for enhanced security

@component('mail::panel')
**⏰ This verification will expire at {{ $expiresAt->format('F j, Y \a\t g:i A T') }}**

If you don't take action by then, the login attempt will be automatically blocked.
@endcomponent

## Need Help?

If you have any questions or concerns about this security alert, please contact our support team immediately.

@component('mail::subcopy')
This security notification was sent from {{ config('app.name') }}. The login attempt originated from IP address {{ $attempt->ip_address }}. If you did not attempt to log in, please secure your account immediately by changing your password and enabling two-factor authentication.
@endcomponent

Thanks,<br>
{{ config('app.name') }} Security Team
@endcomponent