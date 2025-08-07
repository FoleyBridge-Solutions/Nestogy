<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\ImapService;
use App\Services\PdfService;
use App\Services\FileUploadService;

/**
 * Example controller demonstrating how to replace legacy plugins
 * with modern Laravel packages
 */
class ExamplePluginReplacementController extends Controller
{
    protected EmailService $emailService;
    protected ImapService $imapService;
    protected PdfService $pdfService;
    protected FileUploadService $fileUploadService;

    public function __construct(
        EmailService $emailService,
        ImapService $imapService,
        PdfService $pdfService,
        FileUploadService $fileUploadService
    ) {
        $this->emailService = $emailService;
        $this->imapService = $imapService;
        $this->pdfService = $pdfService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Example: Replace PHPMailer with Laravel Mail
     * 
     * Old way with PHPMailer:
     * $mail = new PHPMailer(true);
     * $mail->isSMTP();
     * $mail->Host = 'smtp.example.com';
     * $mail->SMTPAuth = true;
     * $mail->Username = 'user@example.com';
     * $mail->Password = 'secret';
     * $mail->setFrom('from@example.com', 'Mailer');
     * $mail->addAddress('joe@example.net', 'Joe User');
     * $mail->Subject = 'Here is the subject';
     * $mail->Body = 'This is the HTML message body';
     * $mail->send();
     */
    public function sendEmail(Request $request)
    {
        // New way with Laravel Mail Service
        $sent = $this->emailService->send(
            to: 'joe@example.net',
            subject: 'Here is the subject',
            body: 'This is the HTML message body',
            attachments: []
        );

        // Or send notification emails
        $this->emailService->sendNotification(
            to: 'user@example.com',
            type: 'ticket_created',
            data: [
                'ticket_id' => '12345',
                'client_name' => 'ACME Corp',
                'subject' => 'Server is down'
            ]
        );

        return response()->json(['sent' => $sent]);
    }

    /**
     * Example: Replace php-imap with Laravel IMAP
     * 
     * Old way:
     * $mailbox = new PhpImap\Mailbox(
     *     '{imap.gmail.com:993/imap/ssl}INBOX',
     *     'username@gmail.com',
     *     'password'
     * );
     * $mailsIds = $mailbox->searchMailbox('UNSEEN');
     */
    public function checkEmails()
    {
        // New way with Laravel IMAP Service
        $this->imapService->connect();
        
        // Get unread messages
        $unreadMessages = $this->imapService->getUnreadMessages();
        
        // Search messages
        $searchResults = $this->imapService->searchMessages([
            'from' => 'client@example.com',
            'since' => now()->subDays(7),
            'seen' => false
        ]);

        // Process incoming emails for ticket creation
        $processedEmails = $this->imapService->processIncomingEmails();
        
        $this->imapService->disconnect();

        return response()->json([
            'unread_count' => $unreadMessages->count(),
            'search_results' => $searchResults->count(),
            'processed' => count($processedEmails)
        ]);
    }

    /**
     * Example: Replace pdfmake with Laravel PDF
     * 
     * Old way with pdfmake (client-side):
     * var docDefinition = {
     *     content: ['First paragraph', 'Another paragraph']
     * };
     * pdfMake.createPdf(docDefinition).download();
     */
    public function generatePdf(Request $request)
    {
        // New way with Laravel PDF (server-side)
        $invoiceData = [
            'invoice_number' => 'INV-2024-001',
            'client' => [
                'name' => 'ACME Corp',
                'address' => '123 Main St'
            ],
            'items' => [
                ['description' => 'Web Development', 'amount' => 1500],
                ['description' => 'Hosting', 'amount' => 100]
            ],
            'total' => 1600
        ];

        // Generate and download invoice
        return $this->pdfService->download(
            view: 'pdf.invoice',
            data: $invoiceData,
            filename: $this->pdfService->generateFilename('invoice', 'INV-2024-001')
        );
    }

    /**
     * Example: Replace Dropzone with Laravel file uploads
     * 
     * Old way with Dropzone:
     * Dropzone.options.myDropzone = {
     *     url: "/upload",
     *     maxFilesize: 2,
     *     acceptedFiles: ".jpeg,.jpg,.png,.gif"
     * };
     */
    public function uploadFile(Request $request)
    {
        // New way with Laravel File Upload Service
        $request->validate([
            'file' => 'required|file'
        ]);

        $result = $this->fileUploadService->upload(
            file: $request->file('file'),
            collection: 'tickets',
            model: null // Or pass a model that uses HasMedia trait
        );

        if ($result['success']) {
            // Create thumbnail for images
            if (str_starts_with($result['file']['mime_type'], 'image/')) {
                $thumbnailPath = $this->fileUploadService->createThumbnail(
                    path: $result['file']['path'],
                    width: 150,
                    height: 150
                );
            }
        }

        return response()->json($result);
    }

    /**
     * Example: Frontend integration guide
     */
    public function frontendExamples()
    {
        return view('examples.plugin-replacements', [
            'examples' => [
                'jquery' => [
                    'old' => '$("#element").hide();',
                    'new' => 'document.getElementById("element").style.display = "none";'
                ],
                'select2' => [
                    'old' => '$("#select").select2();',
                    'new' => 'new TomSelect("#select");'
                ],
                'moment' => [
                    'old' => 'moment().format("YYYY-MM-DD");',
                    'new' => 'new Date().toISOString().split("T")[0];'
                ],
                'sweetalert' => [
                    'old' => 'swal("Good job!", "You clicked the button!", "success");',
                    'new' => 'Swal.fire("Good job!", "You clicked the button!", "success");'
                ]
            ]
        ]);
    }
}