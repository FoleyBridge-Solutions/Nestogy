<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ContactPortalAccessController extends Controller
{
    private const CONTACT_NOT_FOUND_MESSAGE = 'Contact not found for this client';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
        $this->middleware('permission:clients.contacts.manage');
    }

    public function updatePortalAccess(Request $request, Client $client, Contact $contact)
    {
        try {
            if ($contact->client_id !== $client->id) {
                return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
            }

            if (! auth()->user()->hasPermission('clients.contacts.manage')) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            \Log::info('Portal access update request', [
                'contact_id' => $contact->id,
                'client_id' => $client->id,
                'request_data' => $request->all(),
            ]);

            $validator = Validator::make($request->all(), [
                'has_portal_access' => 'boolean',
                'auth_method' => 'nullable|in:none,password,pin',
                'password' => 'nullable|string|min:8',
                'pin' => 'nullable|string|min:4|max:10|regex:/^[0-9]+$/',
                'must_change_password' => 'boolean',
                'email_verified' => 'boolean',
            ]);

            if ($validator->fails()) {
                \Log::error('Portal access validation failed', ['errors' => $validator->errors()]);

                return response()->json(['errors' => $validator->errors()], 422);
            }

            $contact->has_portal_access = $request->boolean('has_portal_access');

            if ($contact->has_portal_access) {
                $contact->auth_method = $request->input('auth_method', 'none');

                if ($request->filled('password')) {
                    $contact->password_hash = Hash::make($request->input('password'));
                    $contact->password_changed_at = now();
                }

                if ($request->filled('pin') && $contact->auth_method === 'pin') {
                    $contact->pin = Hash::make($request->input('pin'));
                }

                $contact->must_change_password = $request->boolean('must_change_password');
                $contact->email_verified_at = $request->boolean('email_verified') ? now() : null;
            } else {
                $contact->auth_method = 'none';
                $contact->password_hash = null;
                $contact->pin = null;
                $contact->must_change_password = false;
            }

            $contact->save();

            \Log::info('Portal access updated successfully', ['contact_id' => $contact->id]);

            return response()->json([
                'message' => 'Portal access updated successfully',
                'data' => [
                    'has_portal_access' => $contact->has_portal_access,
                    'auth_method' => $contact->auth_method,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating portal access', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error: '.$e->getMessage()], 500);
        }
    }

    public function updateSecurity(Request $request, Client $client, Contact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $validator = Validator::make($request->all(), [
            'session_timeout_minutes' => 'nullable|integer|min:5|max:480',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contact->session_timeout_minutes = $request->input('session_timeout_minutes', 30);

        $allowedIps = array_filter($request->input('allowed_ips', []));
        $contact->allowed_ip_addresses = $allowedIps;

        $contact->save();

        return response()->json(['message' => 'Security settings updated successfully']);
    }

    public function updatePermissions(Request $request, Client $client, Contact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $validator = Validator::make($request->all(), [
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permissions = array_filter($request->input('permissions', []));
        $contact->portal_permissions = $permissions;

        $contact->save();

        return response()->json(['message' => 'Permissions updated successfully']);
    }

    public function lockAccount(Client $client, Contact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->locked_until = now()->addMinutes(30);
        $contact->save();

        return response()->json(['message' => 'Account locked successfully']);
    }

    public function unlockAccount(Client $client, Contact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->locked_until = null;
        $contact->save();

        return response()->json(['message' => 'Account unlocked successfully']);
    }

    public function resetFailedAttempts(Client $client, Contact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->failed_login_count = 0;
        $contact->save();

        return response()->json(['message' => 'Failed login attempts reset successfully']);
    }
}
