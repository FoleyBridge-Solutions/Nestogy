<?php

namespace App\Livewire\PhysicalMail;

use App\Domains\PhysicalMail\Jobs\SendLetterJob;
use App\Domains\PhysicalMail\Models\PhysicalMailTemplate;
use App\Models\Client;
use App\Models\Invoice;
use App\Domains\PhysicalMail\Services\PhysicalMailTemplateBuilder;
use Livewire\Component;

class SendMailModal extends Component
{
    public bool $show = false;
    public ?Invoice $invoice = null;
    public ?Client $client = null;
    public string $documentType = '';
    
    public string $recipientName = '';
    public string $recipientTitle = '';
    public string $recipientCompany = '';
    public string $recipientAddressLine1 = '';
    public string $recipientAddressLine2 = '';
    public string $recipientCity = '';
    public string $recipientState = '';
    public string $recipientPostalCode = '';
    public string $recipientCountry = 'US';
    
    public ?int $templateId = null;
    public string $customContent = '';
    public bool $useTemplate = true;
    public bool $includeCoverLetter = true;
    public string $coverLetterMessage = '';
    
    public bool $expressDelivery = false;
    public bool $certifiedMail = false;
    public bool $returnReceipt = false;
    public bool $colorPrinting = false;
    public bool $doubleSided = false;
    
    protected $listeners = [
        'sendPhysicalMail' => 'openModal',
    ];
    
    protected $rules = [
        'recipientName' => 'required|string|max:255',
        'recipientCompany' => 'nullable|string|max:255',
        'recipientAddressLine1' => 'required|string|max:255',
        'recipientAddressLine2' => 'nullable|string|max:255',
        'recipientCity' => 'required|string|max:255',
        'recipientState' => 'required|string|size:2',
        'recipientPostalCode' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
        'templateId' => 'nullable|exists:physical_mail_templates,id',
        'customContent' => 'nullable|string',
        'coverLetterMessage' => 'nullable|string|max:2000',
    ];
    
    protected $messages = [
        'recipientPostalCode.regex' => 'Please enter a valid ZIP code (12345 or 12345-6789)',
        'recipientState.size' => 'Please enter a 2-letter state code',
    ];
    
    public function mount()
    {
        if ($this->client) {
            $this->prefillFromClient();
        }
        
        if ($this->invoice) {
            $this->documentType = 'invoice';
            $this->setDefaultCoverLetter();
        }
    }
    
    public function openModal($params = [])
    {
        if (isset($params['invoice_id'])) {
            $this->invoice = Invoice::find($params['invoice_id']);
            $this->client = $this->invoice->client;
            $this->documentType = 'invoice';
            $this->mount();
        } elseif (isset($params['client_id'])) {
            $this->client = Client::find($params['client_id']);
            $this->mount();
        }
        
        $this->show = true;
    }
    
    public function closeModal()
    {
        $this->show = false;
        $this->reset();
    }
    
    protected function prefillFromClient()
    {
        if (!$this->client) return;
        
        $this->recipientName = $this->client->contact_name ?: $this->client->name;
        $this->recipientCompany = $this->client->name;
        $this->recipientAddressLine1 = $this->client->address_line_1 ?? '';
        $this->recipientAddressLine2 = $this->client->address_line_2 ?? '';
        $this->recipientCity = $this->client->city ?? '';
        $this->recipientState = $this->client->state ?? '';
        $this->recipientPostalCode = $this->client->zip ?? '';
    }
    
    protected function setDefaultCoverLetter()
    {
        if (!$this->invoice) return;
        
        $this->coverLetterMessage = "Please find enclosed Invoice #{$this->invoice->invoice_number} " .
            "for \${$this->invoice->total}. Payment is due by {$this->invoice->due_date->format('F j, Y')}. " .
            "Thank you for your business!";
    }
    
    public function updatedUseTemplate()
    {
        if ($this->useTemplate && !$this->templateId) {
            $defaultTemplate = PhysicalMailTemplate::where('name', 'Invoice')
                ->orWhere('name', 'Business Letter')
                ->first();
            
            if ($defaultTemplate) {
                $this->templateId = $defaultTemplate->id;
            }
        }
    }
    
    public function sendMail()
    {
        $this->validate();
        
        try {
            $letterData = $this->prepareLetter();
            
            SendLetterJob::dispatch($letterData);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Physical mail has been queued for sending!',
            ]);
            
            $this->closeModal();
            
            if ($this->invoice) {
                $this->dispatch('mailSent', ['invoice_id' => $this->invoice->id]);
            }
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to send mail: ' . $e->getMessage(),
            ]);
        }
    }
    
    protected function prepareLetter()
    {
        $content = $this->generateContent();
        
        $letterData = [
            'to' => [
                'name' => $this->recipientName,
                'title' => $this->recipientTitle,
                'company' => $this->recipientCompany,
                'address_line1' => $this->recipientAddressLine1,
                'address_line2' => $this->recipientAddressLine2,
                'address_city' => $this->recipientCity,
                'address_state' => $this->recipientState,
                'address_postal_code' => $this->recipientPostalCode,
                'address_country' => $this->recipientCountry,
            ],
            'from' => [
                'name' => config('app.name'),
                'company' => config('nestogy.company_name'),
                'address_line1' => config('nestogy.company_address_line1'),
                'address_city' => config('nestogy.company_city'),
                'address_state' => config('nestogy.company_state'),
                'address_postal_code' => config('nestogy.company_postal_code'),
                'address_country' => 'US',
            ],
            'html' => $content,
            'color' => $this->colorPrinting,
            'double_sided' => $this->doubleSided,
            'extra_service' => $this->getExtraService(),
            'mail_type' => $this->expressDelivery ? 'usps_first_class' : 'usps_standard',
            'metadata' => [
                'client_id' => $this->client?->id,
                'invoice_id' => $this->invoice?->id,
                'document_type' => $this->documentType,
                'sent_by' => auth()->id(),
                'sent_at' => now()->toIso8601String(),
            ],
        ];
        
        if ($this->returnReceipt) {
            $letterData['return_envelope'] = true;
        }
        
        return $letterData;
    }
    
    protected function generateContent()
    {
        $builder = new PhysicalMailTemplateBuilder();
        
        if ($this->invoice) {
            return $this->generateInvoiceContent($builder);
        }
        
        if ($this->useTemplate && $this->templateId) {
            $template = PhysicalMailTemplate::find($this->templateId);
            if ($template) {
                return $this->processTemplate($template->content);
            }
        }
        
        if ($this->customContent) {
            return $builder->build($this->customContent, 'letter');
        }
        
        return $builder->build('No content provided', 'letter');
    }
    
    protected function generateInvoiceContent($builder)
    {
        $content = '';
        
        if ($this->includeCoverLetter && $this->coverLetterMessage) {
            $content .= $builder->build($this->coverLetterMessage, 'letter');
            $content .= '<div style="page-break-after: always;"></div>';
        }
        
        $invoiceHtml = $this->invoice->renderForPhysicalMail();
        $content .= $builder->build($invoiceHtml, 'invoice');
        
        return $content;
    }
    
    protected function processTemplate($templateContent)
    {
        $replacements = [
            '{{client_name}}' => $this->client?->name ?? '',
            '{{contact_name}}' => $this->recipientName,
            '{{company_name}}' => config('nestogy.company_name'),
            '{{date}}' => now()->format('F j, Y'),
            '{{invoice_number}}' => $this->invoice?->invoice_number ?? '',
            '{{invoice_amount}}' => $this->invoice ? '$' . number_format($this->invoice->total, 2) : '',
            '{{due_date}}' => $this->invoice?->due_date->format('F j, Y') ?? '',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $templateContent);
    }
    
    protected function getExtraService()
    {
        if ($this->certifiedMail && $this->returnReceipt) {
            return 'certified_return_receipt';
        }
        
        if ($this->certifiedMail) {
            return 'certified';
        }
        
        if ($this->returnReceipt) {
            return 'certified_return_receipt';
        }
        
        return null;
    }
    
    public function calculateEstimatedCost()
    {
        $cost = 1.95; // Base cost
        
        if ($this->expressDelivery) $cost += 1.50;
        if ($this->certifiedMail) $cost += 4.95;
        if ($this->returnReceipt) $cost += 2.95;
        if ($this->colorPrinting) $cost += 0.50;
        
        return $cost;
    }
    
    public function render()
    {
        $templates = PhysicalMailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('livewire.physical-mail.send-mail-modal', compact('templates'));
    }
}