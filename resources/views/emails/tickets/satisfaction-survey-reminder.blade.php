@component('mail::message')
# We'd love your feedback!

Hi {{ $ticket->contact->name ?? 'there' }},

Your ticket **#{{ $ticket->number }}** was recently resolved. We'd appreciate it if you could take a moment to rate your experience.

**Ticket Subject:** {{ $ticket->subject }}

@component('mail::button', ['url' => $surveyUrl])
Rate Your Experience
@endcomponent

Your feedback helps us improve our service and better serve you in the future.

Thanks,<br>
{{ config('app.name') }}

---

<small style="color: #6b7280;">
This is an automated message. If you've already submitted feedback, please disregard this reminder.
</small>
@endcomponent
