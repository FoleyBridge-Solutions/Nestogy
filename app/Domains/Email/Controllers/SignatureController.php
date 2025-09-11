<?php

namespace App\Domains\Email\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Email\Models\EmailSignature;
use App\Domains\Email\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SignatureController extends Controller
{
    public function index()
    {
        $signatures = EmailSignature::forUser(Auth::id())
            ->with('emailAccount')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('email.signatures.index', compact('signatures'));
    }

    public function create()
    {
        $accounts = EmailAccount::forUser(Auth::id())->active()->get();
        
        return view('email.signatures.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content_html' => 'nullable|string',
            'content_text' => 'nullable|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
            'is_default' => 'boolean',
            'auto_append_replies' => 'boolean',
            'auto_append_forwards' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validate account ownership if specified
        if ($request->email_account_id) {
            $account = EmailAccount::forUser(Auth::id())->find($request->email_account_id);
            if (!$account) {
                return back()->withErrors(['email_account_id' => 'Invalid email account'])->withInput();
            }
        }

        EmailSignature::create([
            'user_id' => Auth::id(),
            'email_account_id' => $request->email_account_id,
            'name' => $request->name,
            'content_html' => $request->content_html,
            'content_text' => $request->content_text,
            'is_default' => $request->boolean('is_default'),
            'auto_append_replies' => $request->boolean('auto_append_replies', true),
            'auto_append_forwards' => $request->boolean('auto_append_forwards', true),
        ]);

        return redirect()
            ->route('email.signatures.index')
            ->with('success', 'Email signature created successfully.');
    }

    public function show(EmailSignature $signature)
    {
        $this->authorize('view', $signature);

        return view('email.signatures.show', compact('signature'));
    }

    public function edit(EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        $accounts = EmailAccount::forUser(Auth::id())->active()->get();
        
        return view('email.signatures.edit', compact('signature', 'accounts'));
    }

    public function update(Request $request, EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content_html' => 'nullable|string',
            'content_text' => 'nullable|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
            'is_default' => 'boolean',
            'auto_append_replies' => 'boolean',
            'auto_append_forwards' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validate account ownership if specified
        if ($request->email_account_id) {
            $account = EmailAccount::forUser(Auth::id())->find($request->email_account_id);
            if (!$account) {
                return back()->withErrors(['email_account_id' => 'Invalid email account'])->withInput();
            }
        }

        $signature->update([
            'name' => $request->name,
            'content_html' => $request->content_html,
            'content_text' => $request->content_text,
            'email_account_id' => $request->email_account_id,
            'is_default' => $request->boolean('is_default'),
            'auto_append_replies' => $request->boolean('auto_append_replies', true),
            'auto_append_forwards' => $request->boolean('auto_append_forwards', true),
        ]);

        return redirect()
            ->route('email.signatures.show', $signature)
            ->with('success', 'Email signature updated successfully.');
    }

    public function destroy(EmailSignature $signature)
    {
        $this->authorize('delete', $signature);

        $signatureName = $signature->name;
        $signature->delete();

        return redirect()
            ->route('email.signatures.index')
            ->with('success', "Email signature '{$signatureName}' deleted successfully.");
    }

    public function setDefault(EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        // Remove default flag from other signatures for this user/account combination
        EmailSignature::forUser(Auth::id())
            ->where('email_account_id', $signature->email_account_id)
            ->where('id', '!=', $signature->id)
            ->update(['is_default' => false]);

        // Set this signature as default
        $signature->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => "'{$signature->name}' set as default signature.",
        ]);
    }
}