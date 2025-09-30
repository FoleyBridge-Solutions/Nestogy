<?php

namespace App\Domains\Email\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MailQueue;
use Illuminate\Http\Request;

class EmailTrackingController extends Controller
{
    /**
     * Track email open
     */
    public function trackOpen(Request $request, string $token)
    {
        $mailQueue = MailQueue::where('tracking_token', $token)->first();

        if ($mailQueue) {
            $mailQueue->recordOpen([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Return 1x1 transparent pixel
        return response(
            base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
            200,
            ['Content-Type' => 'image/gif']
        );
    }

    /**
     * Track email click
     */
    public function trackClick(Request $request, string $token)
    {
        $url = base64_decode($request->get('url', ''));

        if (! $url) {
            return redirect('/');
        }

        $mailQueue = MailQueue::where('tracking_token', $token)->first();

        if ($mailQueue) {
            $mailQueue->recordClick($url, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return redirect($url);
    }

    /**
     * View email online
     */
    public function viewEmail(string $uuid)
    {
        $mailQueue = MailQueue::where('uuid', $uuid)->first();

        if (! $mailQueue) {
            abort(404, 'Email not found');
        }

        return response($mailQueue->html_body);
    }

    /**
     * Handle unsubscribe
     */
    public function unsubscribe(string $token)
    {
        $mailQueue = MailQueue::where('tracking_token', $token)->first();

        if (! $mailQueue) {
            return view('emails.unsubscribe-error');
        }

        // Here you would implement unsubscribe logic
        // For now, just show a confirmation page

        return view('emails.unsubscribe', [
            'email' => $mailQueue->to_email,
        ]);
    }
}
