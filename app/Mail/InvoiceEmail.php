<?php

namespace App\Mail;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $recipientEmail;

    public ?string $recipientName;

    public string $emailSubject;

    public ?string $customMessage;

    public bool $attachPdf;

    public string $viewUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        array $options = []
    ) {
        $this->recipientEmail = $options['to'] ?? $invoice->client->email;
        $this->recipientName = $options['recipient_name'] ?? $invoice->client->name;
        $this->emailSubject = $options['subject'] ?? 'Invoice #'.($invoice->invoice_number ?? $invoice->number);
        $this->customMessage = $options['message'] ?? null;
        $this->attachPdf = $options['attach_pdf'] ?? true;
        $this->viewUrl = $options['view_url'] ?? route('financial.invoices.show', $invoice);

        // Set queue for email processing
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipientEmail,
            subject: $this->emailSubject,
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoices.send',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'customMessage' => $this->customMessage,
                'viewUrl' => $this->viewUrl,
                'totalAmount' => number_format($this->invoice->amount, 2),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->attachPdf) {
            try {
                $pdfService = app(PdfServiceInterface::class);

                // Load invoice relationships
                $this->invoice->load(['client', 'items', 'payments']);

                // Generate PDF content
                $pdfContent = $pdfService->generateInvoice(['invoice' => $this->invoice]);

                // Generate filename
                $filename = $pdfService->generateFilename('invoice', $this->invoice->invoice_number ?? $this->invoice->number);

                // Save to temporary storage
                $tempPath = 'temp/'.$filename;
                Storage::disk('local')->put($tempPath, $pdfContent);

                // Add attachment
                $attachments[] = Attachment::fromPath(Storage::disk('local')->path($tempPath))
                    ->as($filename)
                    ->withMime('application/pdf');

                // Schedule cleanup of temporary file
                $this->afterSending(function () use ($tempPath) {
                    if (Storage::disk('local')->exists($tempPath)) {
                        Storage::disk('local')->delete($tempPath);
                    }
                });

            } catch (\Exception $e) {
                Log::error('Failed to generate PDF attachment for invoice email', [
                    'invoice_id' => $this->invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice email job failed', [
            'invoice_id' => $this->invoice->id,
            'recipient' => $this->recipientEmail,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * The job completed successfully.
     */
    public function afterSending(): void
    {
        Log::info('Invoice email sent successfully via queue', [
            'invoice_id' => $this->invoice->id,
            'recipient' => $this->recipientEmail,
            'subject' => $this->emailSubject,
            'pdf_attached' => $this->attachPdf,
        ]);
    }
}
