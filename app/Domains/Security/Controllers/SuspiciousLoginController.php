<?php

namespace App\Domains\Security\Controllers;

use App\Domains\Security\Models\SuspiciousLoginAttempt;
use App\Domains\Security\Services\SuspiciousLoginService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SuspiciousLoginController extends Controller
{
    protected SuspiciousLoginService $suspiciousLoginService;

    public function __construct(SuspiciousLoginService $suspiciousLoginService)
    {
        $this->suspiciousLoginService = $suspiciousLoginService;
    }

    public function approve(Request $request, string $token)
    {
        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)
            ->pending()
            ->first();

        if (! $attempt) {
            return view('security.suspicious-login.invalid-token', [
                'title' => 'Invalid or Expired Verification',
                'message' => 'This verification link has expired or is invalid.',
            ]);
        }

        if ($request->isMethod('POST')) {
            $trustLocation = $request->boolean('trust_location');

            if ($trustLocation) {
                $attempt->trusted_location_requested = true;
                $attempt->save();
            }

            $success = $this->suspiciousLoginService->approveLoginAttempt($token, $request);

            if ($success) {
                Session::put('suspicious_login_approved', $attempt->verification_token);

                return view('security.suspicious-login.approved', [
                    'title' => 'Login Approved',
                    'message' => 'Your login has been approved successfully.',
                    'attempt' => $attempt,
                    'trustLocation' => $trustLocation,
                ]);
            } else {
                return view('security.suspicious-login.error', [
                    'title' => 'Approval Failed',
                    'message' => 'Unable to approve the login attempt. Please try again.',
                ]);
            }
        }

        return view('security.suspicious-login.approve', [
            'attempt' => $attempt,
            'title' => 'Approve Login Attempt',
        ]);
    }

    public function deny(Request $request, string $token)
    {
        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)
            ->pending()
            ->first();

        if (! $attempt) {
            return view('security.suspicious-login.invalid-token', [
                'title' => 'Invalid or Expired Verification',
                'message' => 'This verification link has expired or is invalid.',
            ]);
        }

        if ($request->isMethod('POST')) {
            $success = $this->suspiciousLoginService->denyLoginAttempt($token, $request);

            if ($success) {
                return view('security.suspicious-login.denied', [
                    'title' => 'Login Denied',
                    'message' => 'The suspicious login attempt has been blocked.',
                    'attempt' => $attempt,
                    'securityRecommendations' => $this->getSecurityRecommendations(),
                ]);
            } else {
                return view('security.suspicious-login.error', [
                    'title' => 'Denial Failed',
                    'message' => 'Unable to deny the login attempt. Please try again.',
                ]);
            }
        }

        return view('security.suspicious-login.deny', [
            'attempt' => $attempt,
            'title' => 'Deny Login Attempt',
        ]);
    }

    public function status(Request $request, string $token)
    {
        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)->first();

        if (! $attempt) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        return response()->json([
            'status' => $attempt->status,
            'expired' => $attempt->isExpired(),
            'approved_at' => $attempt->approved_at,
            'denied_at' => $attempt->denied_at,
            'expires_at' => $attempt->expires_at,
        ]);
    }

    public function checkApproval(Request $request)
    {
        $token = $request->input('token');

        if (! $token) {
            return response()->json(['approved' => false], 400);
        }

        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)->first();

        if (! $attempt) {
            return response()->json(['approved' => false, 'error' => 'Invalid token'], 404);
        }

        $approved = $attempt->isApproved();
        $response = ['approved' => $approved];

        if ($approved) {
            $response['redirect_url'] = route('dashboard');

            if ($attempt->user) {
                Auth::login($attempt->user, true);
                $response['authenticated'] = true;
            }
        } elseif ($attempt->isExpired()) {
            $response['expired'] = true;
        } elseif ($attempt->isDenied()) {
            $response['denied'] = true;
        }

        return response()->json($response);
    }

    protected function getSecurityRecommendations(): array
    {
        return [
            'Change your password immediately',
            'Enable two-factor authentication',
            'Review recent account activity',
            'Check for any unauthorized access',
            'Use a password manager for strong, unique passwords',
            'Keep your devices and browsers updated',
            'Be cautious when using public Wi-Fi networks',
        ];
    }
}
