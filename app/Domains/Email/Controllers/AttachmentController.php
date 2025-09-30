<?php

namespace App\Domains\Email\Controllers;

use App\Domains\Email\Models\EmailAttachment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(EmailAttachment $attachment)
    {
        $this->authorize('view', $attachment->emailMessage);

        if (! Storage::disk($attachment->storage_disk)->exists($attachment->storage_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk($attachment->storage_disk)->download(
            $attachment->storage_path,
            $attachment->filename,
            [
                'Content-Type' => $attachment->content_type,
                'Content-Disposition' => 'attachment; filename="'.$attachment->filename.'"',
            ]
        );
    }

    public function preview(EmailAttachment $attachment)
    {
        $this->authorize('view', $attachment->emailMessage);

        if (! Storage::disk($attachment->storage_disk)->exists($attachment->storage_path)) {
            abort(404, 'File not found');
        }

        // Only allow preview for certain file types
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'text/plain', 'text/html',
        ];

        if (! in_array($attachment->content_type, $allowedTypes)) {
            return $this->download($attachment);
        }

        return new StreamedResponse(function () use ($attachment) {
            $stream = Storage::disk($attachment->storage_disk)->readStream($attachment->storage_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $attachment->content_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->filename.'"',
            'Content-Length' => $attachment->size_bytes,
        ]);
    }

    public function thumbnail(EmailAttachment $attachment)
    {
        $this->authorize('view', $attachment->emailMessage);

        if (! $attachment->thumbnail_path || ! Storage::disk($attachment->storage_disk)->exists($attachment->thumbnail_path)) {
            // Return a default thumbnail or the original file for small images
            if ($attachment->isImageFile() && $attachment->size_bytes < 1024 * 1024) { // Less than 1MB
                return $this->preview($attachment);
            }

            abort(404, 'Thumbnail not found');
        }

        return new StreamedResponse(function () use ($attachment) {
            $stream = Storage::disk($attachment->storage_disk)->readStream($attachment->thumbnail_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline',
        ]);
    }
}
