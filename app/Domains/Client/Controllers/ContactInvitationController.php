<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Client\Services\PortalInvitationService;
use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactInvitationController extends Controller
{
    private const CONTACT_NOT_FOUND_MESSAGE = 'Contact not found for this client';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
        $this->middleware('permission:clients.contacts.manage');
    }

    public function sendInvitation(Request $request, Contact $contact)
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->sendInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully',
            'expires_at' => $result['data']['expires_at'],
        ]);
    }

    public function resendInvitation(Request $request, Contact $contact)
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->resendInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully',
            'expires_at' => $result['data']['expires_at'],
        ]);
    }

    public function revokeInvitation(Request $request, Contact $contact)
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->revokeInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation revoked successfully',
        ]);
    }
}
