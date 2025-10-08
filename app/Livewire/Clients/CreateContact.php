<?php

namespace App\Livewire\Clients;

use App\Domains\Core\Services\NavigationService;
use App\Models\Contact;
use Livewire\Component;

class CreateContact extends Component
{
    private const MAX_HANDLE_LENGTH = 100;

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
                'timezone' => ['nullable', 'string', 'max:' . self::MAX_HANDLE_LENGTH],
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
                'twitter_handle' => ['nullable', 'string', 'max:' . self::MAX_HANDLE_LENGTH],
                'facebook_url' => ['nullable', 'url', 'max:255'],
                'instagram_handle' => ['nullable', 'string', 'max:' . self::MAX_HANDLE_LENGTH],
                'company_blog' => ['nullable', 'url', 'max:255'],
            ];
        }

        return $rules;
    }

    public function save()
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            session()->flash('error', 'No client selected. Please select a client first.');

            return redirect()->route('clients.contacts.index');
        }

        // Validate all tabs at once for final save
        $this->activeTab = 'all';
        $this->validate();

        $contactData = [
            'client_id' => $client->id,
            'company_id' => auth()->user()->company_id,
            // Basic information
            'name' => $this->name,
            'title' => $this->title,
            'email' => $this->email,
            'phone' => $this->phone,
            'extension' => $this->extension,
            'mobile' => $this->mobile,
            'department' => $this->department,
            'role' => $this->role,
            'notes' => $this->notes,
            // Contact type
            'primary' => $this->primary,
            'billing' => $this->billing,
            'technical' => $this->technical,
            'important' => $this->important,
            // Communication preferences
            'preferred_contact_method' => $this->preferred_contact_method,
            'best_time_to_contact' => $this->best_time_to_contact,
            'timezone' => $this->timezone ?: null,
            'language' => $this->language,
            'do_not_disturb' => $this->do_not_disturb,
            'marketing_opt_in' => $this->marketing_opt_in,
            // Professional details
            'linkedin_url' => $this->linkedin_url ?: null,
            'assistant_name' => $this->assistant_name ?: null,
            'assistant_email' => $this->assistant_email ?: null,
            'assistant_phone' => $this->assistant_phone ?: null,
            'reports_to_id' => $this->reports_to_id,
            'work_schedule' => $this->work_schedule ?: null,
            'professional_bio' => $this->professional_bio ?: null,
            // Location & Availability
            'office_location_id' => $this->office_location_id,
            'is_emergency_contact' => $this->is_emergency_contact,
            'is_after_hours_contact' => $this->is_after_hours_contact,
            'out_of_office_start' => $this->out_of_office_start ?: null,
            'out_of_office_end' => $this->out_of_office_end ?: null,
            // Social & Web presence
            'website' => $this->website ?: null,
            'twitter_handle' => $this->twitter_handle ?: null,
            'facebook_url' => $this->facebook_url ?: null,
            'instagram_handle' => $this->instagram_handle ?: null,
            'company_blog' => $this->company_blog ?: null,
            // Portal access
            'has_portal_access' => $this->has_portal_access,
            'auth_method' => $this->has_portal_access ? $this->auth_method : null,
        ];

        // Handle password if portal access is enabled
        if ($this->has_portal_access) {
            if ($this->portal_access_method === 'manual_password' && $this->password) {
                $contactData['password_hash'] = bcrypt($this->password);
                $contactData['password_changed_at'] = now();
            } elseif ($this->portal_access_method === 'send_invitation') {
                $this->send_invitation = true;
            }
        }

        $contact = Contact::create($contactData);

        // Send invitation if requested
        if ($this->send_invitation && $this->has_portal_access) {
            $invitationService = app(\App\Domains\Client\Services\PortalInvitationService::class);
            $result = $invitationService->sendInvitation($contact, auth()->user());

            if (! $result['success']) {
                session()->flash('warning', 'Contact created but invitation failed: '.$result['message']);
            } else {
                session()->flash('success', 'Contact created and invitation sent successfully.');
            }
        }

        // If this is set as primary, unset other primary contacts for this client
        if ($contact->primary) {
            Contact::where('client_id', $client->id)
                ->where('id', '!=', $contact->id)
                ->update(['primary' => false]);
        }

        // Only show the default success message if we haven't already shown a more specific one
        if (! session()->has('success') && ! session()->has('warning')) {
            session()->flash('success', 'Contact created successfully.');
        }

        return redirect()->route('clients.contacts.index');
    }

    public function render()
    {
        $client = app(NavigationService::class)->getSelectedClient();

        // Get data for dropdowns
        $contacts = $client ? Contact::where('client_id', $client->id)->where('id', '!=', 0)->get(['id', 'name', 'title']) : collect();
        $locations = $client ? $client->locations()->get(['id', 'name', 'address']) : collect();

        return view('livewire.clients.create-contact', [
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
