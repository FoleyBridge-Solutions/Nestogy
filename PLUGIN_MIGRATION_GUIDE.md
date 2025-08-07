# Nestogy Plugin Migration Guide

This guide explains how to replace legacy third-party plugins in the Nestogy system with modern Laravel/Composer packages.

## Summary of Replacements

| Legacy Plugin | Replacement | Status |
|--------------|-------------|---------|
| PHPMailer | Laravel Mail (symfony/mailer) | ✅ Implemented |
| php-imap | webklex/laravel-imap | ✅ Implemented |
| pdfmake | barryvdh/laravel-dompdf + spatie/laravel-pdf | ✅ Implemented |
| Dropzone | Laravel File Uploads + spatie/laravel-medialibrary | ✅ Implemented |
| jQuery | Vanilla JS / Alpine.js | 📋 Planned |
| Bootstrap | Keep Bootstrap 5 via npm | 📋 Planned |
| Select2 | Tom Select | 📋 Planned |
| Moment.js | date-fns | 📋 Planned |
| Chart.js | Chart.js via npm | 📋 Planned |
| FullCalendar | FullCalendar via npm | 📋 Planned |
| SweetAlert2 | SweetAlert2 via npm or Alpine.js | 📋 Planned |
| Toastr | Alpine.js notifications | 📋 Planned |

## 1. Email System Migration (PHPMailer → Laravel Mail)

### Old Implementation (PHPMailer)
```php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.example.com';
$mail->SMTPAuth = true;
$mail->Username = 'user@example.com';
$mail->Password = 'secret';
$mail->setFrom('from@example.com', 'Mailer');
$mail->addAddress('joe@example.net', 'Joe User');
$mail->Subject = 'Here is the subject';
$mail->Body = 'This is the HTML message body';
$mail->send();
```

### New Implementation (Laravel Mail)
```php
use App\Services\EmailService;

// Inject the service
public function __construct(private EmailService $emailService) {}

// Send email
$this->emailService->send(
    to: 'joe@example.net',
    subject: 'Here is the subject',
    body: 'This is the HTML message body',
    attachments: ['/path/to/file.pdf']
);

// Or use Laravel's Mail facade directly
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketCreated;

Mail::to('joe@example.net')->send(new TicketCreated($ticket));
```

### Configuration
Update your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nestogy.com
MAIL_FROM_NAME="Nestogy ERP"
```

## 2. IMAP Integration (php-imap → webklex/laravel-imap)

### Old Implementation
```php
use PhpImap\Mailbox;

$mailbox = new Mailbox(
    '{imap.gmail.com:993/imap/ssl}INBOX',
    'username@gmail.com',
    'password'
);
$mailsIds = $mailbox->searchMailbox('UNSEEN');
```

### New Implementation
```php
use App\Services\ImapService;

// Inject the service
public function __construct(private ImapService $imapService) {}

// Connect and fetch emails
$this->imapService->connect();
$unreadMessages = $this->imapService->getUnreadMessages();
$this->imapService->disconnect();
```

### Configuration
Update your `.env` file:
```env
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
IMAP_USERNAME=username@gmail.com
IMAP_PASSWORD=password
IMAP_DEFAULT_ACCOUNT=default
IMAP_PROTOCOL=imap
```

## 3. PDF Generation (pdfmake → Laravel PDF)

### Old Implementation (Client-side with pdfmake)
```javascript
var docDefinition = {
    content: [
        'First paragraph',
        'Another paragraph',
        {
            table: {
                body: [
                    ['Column 1', 'Column 2'],
                    ['Value 1', 'Value 2']
                ]
            }
        }
    ]
};
pdfMake.createPdf(docDefinition).download('invoice.pdf');
```

### New Implementation (Server-side with Laravel)
```php
use App\Services\PdfService;

// Inject the service
public function __construct(private PdfService $pdfService) {}

// Generate and download PDF
return $this->pdfService->download(
    view: 'pdf.invoice',
    data: ['invoice' => $invoice],
    filename: 'invoice-2024-001.pdf'
);

// Or save to storage
$path = $this->pdfService->generateAndSave(
    view: 'pdf.report',
    data: ['report' => $reportData],
    filename: 'monthly-report.pdf'
);
```

### Blade Template Example
Create `resources/views/pdf/invoice.blade.php`:
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->number }}</title>
    <style>
        /* PDF-specific styles */
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice #{{ $invoice->number }}</h1>
        <p>Date: {{ $invoice->date->format('F d, Y') }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>${{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>${{ number_format($invoice->total, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
```

## 4. File Uploads (Dropzone → Laravel File Handling)

### Old Implementation (Dropzone.js)
```javascript
Dropzone.options.myDropzone = {
    url: "/upload",
    maxFilesize: 2,
    acceptedFiles: ".jpeg,.jpg,.png,.gif",
    success: function(file, response) {
        console.log("File uploaded:", response);
    }
};
```

### New Implementation

#### Backend (Laravel)
```php
use App\Services\FileUploadService;

public function upload(Request $request, FileUploadService $fileUploadService)
{
    $request->validate([
        'file' => 'required|file|max:10240' // 10MB max
    ]);

    $result = $fileUploadService->upload(
        file: $request->file('file'),
        collection: 'tickets'
    );

    return response()->json($result);
}
```

#### Frontend (Alpine.js + Fetch API)
```html
<div x-data="fileUpload()">
    <input type="file" @change="uploadFile($event)" accept=".jpg,.jpeg,.png,.pdf">
    <div x-show="uploading">Uploading...</div>
    <div x-show="error" x-text="error" class="text-danger"></div>
</div>

<script>
function fileUpload() {
    return {
        uploading: false,
        error: null,
        
        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.uploading = true;
            this.error = null;
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch('/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    console.log('File uploaded:', result.file);
                } else {
                    this.error = result.error;
                }
            } catch (error) {
                this.error = 'Upload failed';
            } finally {
                this.uploading = false;
            }
        }
    };
}
</script>
```

## 5. Frontend Library Migrations

### jQuery → Vanilla JavaScript / Alpine.js
```javascript
// Old (jQuery)
$('#element').hide();
$('.class').addClass('active');
$('#form').on('submit', function(e) {
    e.preventDefault();
    // handle form
});

// New (Vanilla JS)
document.getElementById('element').style.display = 'none';
document.querySelectorAll('.class').forEach(el => el.classList.add('active'));
document.getElementById('form').addEventListener('submit', (e) => {
    e.preventDefault();
    // handle form
});

// New (Alpine.js)
<div x-data="{ show: true }">
    <div x-show="show">Content</div>
    <button @click="show = !show">Toggle</button>
</div>
```

### Select2 → Tom Select
```javascript
// Old (Select2)
$('#select').select2({
    placeholder: 'Select an option',
    ajax: {
        url: '/api/options',
        dataType: 'json'
    }
});

