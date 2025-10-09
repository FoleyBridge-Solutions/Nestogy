<?php

namespace App\Domains\Email\Controllers;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailMessage;
use App\Domains\Email\Models\EmailSignature;
use App\Domains\Email\Services\EmailService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ComposeController extends Controller
{
    private const NULLABLE_STRING = 'nullable|string';

    public function __construct(
        private EmailService $emailService
    ) {}

    public function index(Request $request)
    {
        $accounts = EmailAccount::forUser(Auth::id())
            ->active()
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('email.accounts.create')
                ->with('info', 'Please add an email account to compose emails.');
        }

        $selectedAccountId = $request->get('account_id', $accounts->first()->id);
        $selectedAccount = $accounts->find($selectedAccountId) ?: $accounts->first();

        $signatures = EmailSignature::forUser(Auth::id())
            ->where(function ($query) use ($selectedAccount) {
                $query->whereNull('email_account_id')
                    ->orWhere('email_account_id', $selectedAccount->id);
            })
            ->get();

        // Pre-fill data from request (for reply/forward)
        $prefill = [
            'to' => $request->get('to', ''),
            'cc' => $request->get('cc', ''),
            'bcc' => $request->get('bcc', ''),
            'subject' => $request->get('subject', ''),
            'body' => $request->get('body', ''),
        ];

        return view('email.compose.index', compact(
            'accounts',
            'selectedAccount',
            'signatures',
            'prefill'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:email_accounts,id',
            'to' => 'required|string',
            'cc' => self::NULLABLE_STRING,
            'bcc' => self::NULLABLE_STRING,
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'signature_id' => 'nullable|exists:email_signatures,id',
            'save_as_draft' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600', // 25MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = EmailAccount::forUser(Auth::id())->findOrFail($request->account_id);

        // Parse email addresses
        $toAddresses = $this->parseEmailAddresses($request->to);
        $ccAddresses = $this->parseEmailAddresses($request->cc ?: '');
        $bccAddresses = $this->parseEmailAddresses($request->bcc ?: '');

        // Handle attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('email_temp', 'local');
                $attachments[] = [
                    'path' => storage_path('app/'.$path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $emailData = [
            'to' => $toAddresses,
            'cc' => $ccAddresses,
            'bcc' => $bccAddresses,
            'subject' => $request->subject,
            'body' => $request->body,
            'signature_id' => $request->signature_id,
            'attachments' => $attachments,
        ];

        // Save as draft or send
        if ($request->boolean('save_as_draft')) {
            try {
                $draft = $this->emailService->saveDraft($emailData, $account);

                return response()->json([
                    'success' => true,
                    'message' => 'Draft saved successfully',
                    'draft_id' => $draft->id,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save draft: '.$e->getMessage(),
                ], 500);
            }
        } else {
            $result = $this->emailService->sendEmail($emailData, $account);

            // Clean up temp files
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    unlink($attachment['path']);
                }
            }

            return response()->json($result, $result['success'] ? 200 : 500);
        }
    }

    public function reply(EmailMessage $message)
    {
        $this->authorize('view', $message);

        $accounts = EmailAccount::forUser(Auth::id())->active()->get();
        $selectedAccount = $message->emailAccount;

        $signatures = EmailSignature::forUser(Auth::id())
            ->where(function ($query) use ($selectedAccount) {
                $query->whereNull('email_account_id')
                    ->orWhere('email_account_id', $selectedAccount->id);
            })
            ->get();

        $prefill = [
            'to' => $message->from_address,
            'cc' => '',
            'bcc' => '',
            'subject' => 'Re: '.preg_replace('/^re:\s*/i', '', $message->subject),
            'body' => '',
            'original_message' => $message,
        ];

        return view('email.compose.reply', compact(
            'accounts',
            'selectedAccount',
            'signatures',
            'prefill',
            'message'
        ));
    }

    public function replyAll(EmailMessage $message)
    {
        $this->authorize('view', $message);

        $accounts = EmailAccount::forUser(Auth::id())->active()->get();
        $selectedAccount = $message->emailAccount;

        $signatures = EmailSignature::forUser(Auth::id())
            ->where(function ($query) use ($selectedAccount) {
                $query->whereNull('email_account_id')
                    ->orWhere('email_account_id', $selectedAccount->id);
            })
            ->get();

        // Build CC list (original TO + CC, excluding our account)
        $ccAddresses = array_merge(
            $message->to_addresses ?: [],
            $message->cc_addresses ?: []
        );
        $ccAddresses = array_filter($ccAddresses, function ($email) use ($selectedAccount) {
            return strtolower($email) !== strtolower($selectedAccount->email_address);
        });

        $prefill = [
            'to' => $message->from_address,
            'cc' => implode(', ', $ccAddresses),
            'bcc' => '',
            'subject' => 'Re: '.preg_replace('/^re:\s*/i', '', $message->subject),
            'body' => '',
            'original_message' => $message,
        ];

        return view('email.compose.reply', compact(
            'accounts',
            'selectedAccount',
            'signatures',
            'prefill',
            'message'
        ));
    }

    public function forward(EmailMessage $message)
    {
        $this->authorize('view', $message);

        $accounts = EmailAccount::forUser(Auth::id())->active()->get();
        $selectedAccount = $message->emailAccount;

        $signatures = EmailSignature::forUser(Auth::id())
            ->where(function ($query) use ($selectedAccount) {
                $query->whereNull('email_account_id')
                    ->orWhere('email_account_id', $selectedAccount->id);
            })
            ->get();

        $prefill = [
            'to' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Fwd: '.$message->subject,
            'body' => '',
            'original_message' => $message,
            'include_attachments' => true,
        ];

        return view('email.compose.forward', compact(
            'accounts',
            'selectedAccount',
            'signatures',
            'prefill',
            'message'
        ));
    }

    public function sendReply(Request $request, EmailMessage $message)
    {
        $this->authorize('view', $message);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'reply_all' => 'boolean',
            'signature_id' => 'nullable|exists:email_signatures,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = $message->emailAccount;

        // Handle attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('email_temp', 'local');
                $attachments[] = [
                    'path' => storage_path('app/'.$path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $replyData = [
            'body' => $request->body,
            'reply_all' => $request->boolean('reply_all'),
            'signature_id' => $request->signature_id,
            'attachments' => $attachments,
        ];

        $result = $this->emailService->replyToEmail($message, $replyData, $account);

        // Clean up temp files
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                unlink($attachment['path']);
            }
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendForward(Request $request, EmailMessage $message)
    {
        $this->authorize('view', $message);

        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'cc' => self::NULLABLE_STRING,
            'bcc' => self::NULLABLE_STRING,
            'body' => self::NULLABLE_STRING,
            'include_attachments' => 'boolean',
            'signature_id' => 'nullable|exists:email_signatures,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = $message->emailAccount;

        // Handle attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('email_temp', 'local');
                $attachments[] = [
                    'path' => storage_path('app/'.$path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Include original attachments if requested
        if ($request->boolean('include_attachments')) {
            foreach ($message->attachments as $attachment) {
                $attachments[] = [
                    'path' => storage_path('app/'.$attachment->storage_path),
                    'name' => $attachment->filename,
                    'mime' => $attachment->content_type,
                ];
            }
        }

        $forwardData = [
            'to' => $this->parseEmailAddresses($request->to),
            'cc' => $this->parseEmailAddresses($request->cc ?: ''),
            'bcc' => $this->parseEmailAddresses($request->bcc ?: ''),
            'body' => $request->body ?: '',
            'signature_id' => $request->signature_id,
            'attachments' => $attachments,
        ];

        $result = $this->emailService->forwardEmail($message, $forwardData, $account);

        // Clean up temp files (but not original attachments)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $index => $file) {
                $tempPath = $attachments[$index]['path'];
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function loadDraft(EmailMessage $draft)
    {
        $this->authorize('view', $draft);

        if (! $draft->is_draft) {
            return response()->json([
                'success' => false,
                'message' => 'This is not a draft message',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'draft' => [
                'id' => $draft->id,
                'to' => implode(', ', $draft->to_addresses ?: []),
                'cc' => implode(', ', $draft->cc_addresses ?: []),
                'bcc' => implode(', ', $draft->bcc_addresses ?: []),
                'subject' => $draft->subject,
                'body' => $draft->body_html,
            ],
        ]);
    }

    private function parseEmailAddresses(string $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        // Simple email parsing - could be enhanced with proper email parsing
        $emails = array_map('trim', explode(',', $addresses));

        return array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }
}
