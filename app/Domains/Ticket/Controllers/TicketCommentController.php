<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Requests\AddCommentRequest;
use App\Domains\Ticket\Services\CommentService;
use App\Http\Controllers\Controller;

class TicketCommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    public function store(AddCommentRequest $request, Ticket $ticket)
    {
        try {
            $options = [];
            if ($request->filled('time_minutes')) {
                $options['time_minutes'] = $request->time_minutes;
                $options['billable'] = $request->boolean('billable', true);
            }

            $comment = $this->commentService->addComment(
                $ticket,
                $request->content,
                $request->visibility,
                $request->user(),
                TicketComment::SOURCE_MANUAL,
                $options
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Comment added successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add comment: '.$e->getMessage());
        }
    }

    public function addReply(AddCommentRequest $request, Ticket $ticket)
    {
        return $this->store($request, $ticket);
    }
}
