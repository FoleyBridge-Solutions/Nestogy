<?php

namespace App\Livewire\Clients;

use App\Models\Contact;
use Livewire\Component;

class EditContact extends Component
{
    public Contact $contact;

    // Tab management
    public string $activeTab = 'essential';

    // Basic Information
    public string $name = '';

    public string $title = '';

    public string $email = '';

    public string $phone = '';

    public string $extension = '';

    public string $mobile = '';

    public string $department = '';

    public string $role = '';

    // Contact Type
    public bool $primary = false;

    public bool $billing = false;

    public bool $technical = false;

    public bool $important = false;

    // Additional Information
    public string $notes = '';

    // Portal Access
    public bool $has_portal_access = false;

    public string $auth_method = 'password';

    public string $portal_access_method = 'manual_password';

    public bool $send_invitation = false;

    public string $password = '';

    public string $password_confirmation = '';

    // Portal Permissions
    public array $portal_permissions = [];

    // Communication preferences
    public string $preferred_contact_method = 'email';

    public string $best_time_to_contact = 'anytime';

    public string $timezone = '';

    public string $language = 'en';

    public bool $do_not_disturb = false;

    public bool $marketing_opt_in = false;

    // Professional details
    public string $linkedin_url = '';

    public string $assistant_name = '';

    public string $assistant_email = '';

    public string $assistant_phone = '';

    public ?int $reports_to_id = null;

    public string $work_schedule = '';

    public string $professional_bio = '';

    // Location & Availability
    public ?int $office_location_id = null;

    public bool $is_emergency_contact = false;

    public bool $is_after_hours_contact = false;

    public ?string $out_of_office_start = null;

    public ?string $out_of_office_end = null;

    // Social & Web presence
    public string $website = '';

    public string $twitter_handle = '';

    public string $facebook_url = '';

    public string $instagram_handle = '';

    public string $company_blog = '';

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->fillFromContact();
    }

    protected function fillFromContact()
    {
        // Basic Information
        $this->name = $this->contact->name ?? '';
        $this->title = $this->contact->title ?? '';
        $this->email = $this->contact->email ?? '';
        $this->phone = $this->contact->phone ?? '';
        $this->extension = $this->contact->extension ?? '';
        $this->mobile = $this->contact->mobile ?? '';
        $this->department = $this->contact->department ?? '';
        $this->role = $this->contact->role ?? '';
        $this->notes = $this->contact->notes ?? '';

        // Contact Type
        $this->primary = (bool) $this->contact->primary;
        $this->billing = (bool) $this->contact->billing;
        $this->technical = (bool) $this->contact->technical;
        $this->important = (bool) $this->contact->important;

        // Portal Access
        $this->has_portal_access = (bool) $this->contact->has_portal_access;
        $this->auth_method = $this->contact->auth_method ?? 'password';
        $this->portal_permissions = $this->contact->portal_permissions ?? [];

        // Communication preferences
        $this->preferred_contact_method = $this->contact->preferred_contact_method ?? 'email';
        $this->best_time_to_contact = $this->contact->best_time_to_contact ?? 'anytime';
        $this->timezone = $this->contact->timezone ?? '';
        $this->language = $this->contact->language ?? 'en';
        $this->do_not_disturb = (bool) $this->contact->do_not_disturb;
        $this->marketing_opt_in = (bool) $this->contact->marketing_opt_in;

        // Professional details
        $this->linkedin_url = $this->contact->linkedin_url ?? '';
        $this->assistant_name = $this->contact->assistant_name ?? '';
        $this->assistant_email = $this->contact->assistant_email ?? '';
        $this->assistant_phone = $this->contact->assistant_phone ?? '';
        $this->reports_to_id = $this->contact->reports_to_id;
        $this->work_schedule = $this->contact->work_schedule ?? '';
        $this->professional_bio = $this->contact->professional_bio ?? '';

        // Location & Availability
        $this->office_location_id = $this->contact->office_location_id;
        $this->is_emergency_contact = (bool) $this->contact->is_emergency_contact;
        $this->is_after_hours_contact = (bool) $this->contact->is_after_hours_contact;
        $this->out_of_office_start = $this->contact->out_of_office_start?->format('Y-m-d');
        $this->out_of_office_end = $this->contact->out_of_office_end?->format('Y-m-d');

        // Social & Web presence
        $this->website = $this->contact->website ?? '';
        $this->twitter_handle = $this->contact->twitter_handle ?? '';
        $this->facebook_url = $this->contact->facebook_url ?? '';
        $this->instagram_handle = $this->contact->instagram_handle ?? '';
        $this->company_blog = $this->contact->company_blog ?? '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function rules()
    {
        $rules = [
            // Essential tab - Basic Information
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'extension' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            // Contact Type
            'primary' => ['boolean'],
            'billing' => ['boolean'],
            'technical' => ['boolean'],
            'important' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];

        // Professional tab rules
        if ($this->activeTab === 'professional' || $this->activeTab === 'all') {
            $rules += [
                'preferred_contact_method' => ['nullable', 'string', 'in:email,phone,mobile,sms'],
                'best_time_to_contact' => ['nullable', 'string', 'in:morning,afternoon,evening,anytime'],
                'timezone' => ['nullable', 'string', 'max:100'],
                'language' => ['nullable', 'string', 'max:50'],
                'do_not_disturb' => ['boolean'],
                'marketing_opt_in' => ['boolean'],
                'linkedin_url' => ['nullable', 'url', 'max:255'],
                'assistant_name' => ['nullable', 'string', 'max:255'],
                'assistant_email' => ['nullable', 'email', 'max:255'],
                'assistant_phone' => ['nullable', 'string', 'max:50'],
                'reports_to_id' => ['nullable', 'integer', 'exists:contacts,id'],
                'work_schedule' => ['nullable', 'string'],
                'professional_bio' => ['nullable', 'string'],
            ];
        }

        // Portal tab rules
        if ($this->activeTab === 'portal' || $this->activeTab === 'all') {
            $rules += [
                'has_portal_access' => ['boolean'],
                'auth_method' => ['nullable', 'string', 'in:password,pin,none'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['nullable', 'string'],
            ];
        }

        // Extended tab rules
        if ($this->activeTab === 'extended' || $this->activeTab === 'all') {
            $rules += [
                'office_location_id' => ['nullable', 'integer', 'exists:locations,id'],
                'is_emergency_contact' => ['boolean'],
                'is_after_hours_contact' => ['boolean'],
                'out_of_office_start' => ['nullable', 'date'],
                'out_of_office_end' => ['nullable', 'date', 'after_or_equal:out_of_office_start'],
                'website' => ['nullable', 'url', 'max:255'],
                'twitter_handle' => ['nullable', 'string', 'max:100'],
                'facebook_url' => ['nullable', 'url', 'max:255'],
                'instagram_handle' => ['nullable', 'string', 'max:100'],
                'company_blog' => ['nullable', 'url', 'max:255'],
            ];
        }

        return $rules;
    }

    public function update()
    {
        $this->activeTab = 'all';
        $this->validate();

        $contactData = $this->prepareContactData();
        $this->contact->update($contactData);
        $this->ensureSinglePrimaryContact();

        session()->flash('success', 'Contact updated successfully.');

        return redirect()->route('clients.contacts.show', $this->contact);
    }

    protected function prepareContactData(): array
    {
        $contactData = [
            'name' => $this->name,
            'title' => $this->title,
            'email' => $this->email,
            'phone' => $this->phone,
            'extension' => $this->extension,
            'mobile' => $this->mobile,
            'department' => $this->department,
            'role' => $this->role,
            'notes' => $this->notes,
            'primary' => $this->primary,
            'billing' => $this->billing,
            'technical' => $this->technical,
            'important' => $this->important,
            'preferred_contact_method' => $this->preferred_contact_method,
            'best_time_to_contact' => $this->best_time_to_contact,
            'timezone' => $this->timezone ?: null,
            'language' => $this->language,
            'do_not_disturb' => $this->do_not_disturb,
            'marketing_opt_in' => $this->marketing_opt_in,
            'linkedin_url' => $this->linkedin_url ?: null,
            'assistant_name' => $this->assistant_name ?: null,
            'assistant_email' => $this->assistant_email ?: null,
            'assistant_phone' => $this->assistant_phone ?: null,
            'reports_to_id' => $this->reports_to_id,
            'work_schedule' => $this->work_schedule ?: null,
            'professional_bio' => $this->professional_bio ?: null,
            'office_location_id' => $this->office_location_id,
            'is_emergency_contact' => $this->is_emergency_contact,
            'is_after_hours_contact' => $this->is_after_hours_contact,
            'out_of_office_start' => $this->out_of_office_start ?: null,
            'out_of_office_end' => $this->out_of_office_end ?: null,
            'website' => $this->website ?: null,
            'twitter_handle' => $this->twitter_handle ?: null,
            'facebook_url' => $this->facebook_url ?: null,
            'instagram_handle' => $this->instagram_handle ?: null,
            'company_blog' => $this->company_blog ?: null,
            'has_portal_access' => $this->has_portal_access,
            'auth_method' => $this->has_portal_access ? $this->auth_method : null,
            'portal_permissions' => $this->has_portal_access ? $this->portal_permissions : [],
        ];

        if ($this->shouldUpdatePassword()) {
            $contactData['password_hash'] = bcrypt($this->password);
            $contactData['password_changed_at'] = now();
        }

        return $contactData;
    }

    protected function shouldUpdatePassword(): bool
    {
        return $this->has_portal_access && $this->password;
    }

    protected function ensureSinglePrimaryContact(): void
    {
        if ($this->contact->primary) {
            Contact::where('client_id', $this->contact->client_id)
                ->where('id', '!=', $this->contact->id)
                ->update(['primary' => false]);
        }
    }

        $this->contact->update($contactData);

        // If this is set as primary, unset other primary contacts for this client
        if ($this->contact->primary) {
            Contact::where('client_id', $this->contact->client_id)
                ->where('id', '!=', $this->contact->id)
                ->update(['primary' => false]);
        }

        session()->flash('success', 'Contact updated successfully.');

        return redirect()->route('clients.contacts.show', $this->contact);
    }

    /**
     * Send portal invitation to the contact
     */
    public function sendInvitation()
    {
        try {
            $invitationService = app(\App\Domains\Client\Services\PortalInvitationService::class);
            $result = $invitationService->sendInvitation($this->contact, auth()->user());

            if ($result['success']) {
                session()->flash('success', 'Portal invitation sent successfully!');
                // Refresh the contact model to get updated invitation status
                $this->contact->refresh();
                $this->mount($this->contact);
            } else {
                \Log::warning('Portal invitation failed', [
                    'contact_id' => $this->contact->id,
                    'result' => $result,
                ]);
                session()->flash('error', 'Failed to send invitation: '.$result['message']);
            }
        } catch (\Exception $e) {
            \Log::error('Exception sending portal invitation', [
                'contact_id' => $this->contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An error occurred while sending the invitation. Please try again.');
        }
    }

    /**
     * Resend portal invitation to the contact
     */
    public function resendInvitation()
    {
        try {
            $invitationService = app(\App\Domains\Client\Services\PortalInvitationService::class);
            $result = $invitationService->resendInvitation($this->contact, auth()->user());

            if ($result['success']) {
                session()->flash('success', 'Portal invitation resent successfully!');
                $this->contact->refresh();
                $this->mount($this->contact);
            } else {
                \Log::warning('Portal invitation resend failed', [
                    'contact_id' => $this->contact->id,
                    'result' => $result,
                ]);
                session()->flash('error', 'Failed to resend invitation: '.$result['message']);
            }
        } catch (\Exception $e) {
            \Log::error('Exception resending portal invitation', [
                'contact_id' => $this->contact->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'An error occurred while resending the invitation. Please try again.');
        }
    }

    /**
     * Revoke portal invitation
     */
    public function revokeInvitation()
    {
        $invitationService = app(\App\Domains\Client\Services\PortalInvitationService::class);
        $result = $invitationService->revokeInvitation($this->contact, auth()->user());

        if ($result['success']) {
            session()->flash('success', 'Portal invitation revoked successfully.');
            $this->contact->refresh();
            $this->mount($this->contact);
        } else {
            session()->flash('error', 'Failed to revoke invitation: '.$result['message']);
        }
    }

    public function render()
    {
        $client = $this->contact->client;

        // Get data for dropdowns
        $contacts = $client ? Contact::where('client_id', $client->id)->where('id', '!=', $this->contact->id)->get(['id', 'name', 'title']) : collect();
        $locations = $client ? $client->locations()->get(['id', 'name', 'address']) : collect();

        return view('livewire.clients.edit-contact', [
            'client' => $client,
            'contacts' => $contacts,
            'locations' => $locations,
            'contactMethods' => [
                'email' => 'Email',
                'phone' => 'Phone',
                'mobile' => 'Mobile',
                'sms' => 'SMS',
            ],
            'contactTimes' => [
                'anytime' => 'Anytime',
                'morning' => 'Morning (8AM - 12PM)',
                'afternoon' => 'Afternoon (12PM - 5PM)',
                'evening' => 'Evening (5PM - 9PM)',
            ],
            'timezones' => [
                'America/New_York' => 'Eastern Time (ET)',
                'America/Chicago' => 'Central Time (CT)',
                'America/Denver' => 'Mountain Time (MT)',
                'America/Los_Angeles' => 'Pacific Time (PT)',
                'America/Phoenix' => 'Arizona Time (MST)',
                'America/Anchorage' => 'Alaska Time (AKST)',
                'Pacific/Honolulu' => 'Hawaii Time (HST)',
            ],
            'languages' => [
                'en' => 'English',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'zh' => 'Chinese',
                'ja' => 'Japanese',
            ],
        ]);
    }
}
