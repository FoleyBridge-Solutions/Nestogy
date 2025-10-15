<?php

namespace App\Domains\Ticket\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketQueryService
{
    public function applyBasicFilters(Builder $query, Request $request): Builder
    {
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = $request->get('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyDateFilters(Builder $query, Request $request): Builder
    {
        if ($createdFrom = $request->get('created_from')) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }

        if ($createdTo = $request->get('created_to')) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        if ($scheduledFrom = $request->get('scheduled_from')) {
            $query->whereDate('scheduled_at', '>=', $scheduledFrom);
        }

        if ($scheduledTo = $request->get('scheduled_to')) {
            $query->whereDate('scheduled_at', '<=', $scheduledTo);
        }

        return $query;
    }

    public function applyAdvancedFilters(Builder $query, Request $request): Builder
    {
        if ($hasAttachments = $request->get('has_attachments')) {
            $query->has('attachments');
        }

        if ($hasTimeEntries = $request->get('has_time_entries')) {
            $query->has('timeEntries');
        }

        if ($hasComments = $request->get('has_comments')) {
            $query->has('comments');
        }

        if ($tags = $request->get('tags')) {
            $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
            $query->where(function ($q) use ($tagsArray) {
                foreach ($tagsArray as $tag) {
                    $q->orWhereJsonContains('tags', trim($tag));
                }
            });
        }

        return $query;
    }

    public function applySentimentFilters(Builder $query, Request $request): Builder
    {
        if ($sentiment = $request->get('sentiment')) {
            $query->where('sentiment', $sentiment);
        }

        if ($request->boolean('negative_sentiment_only')) {
            $query->where('sentiment', 'negative');
        }

        return $query;
    }

    public function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSortFields = [
            'created_at', 'updated_at', 'number', 'priority',
            'status', 'subject', 'scheduled_at', 'resolved_at',
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function getFilterOptions(int $companyId): array
    {
        return [
            'statuses' => ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'],
            'priorities' => ['Low', 'Medium', 'High', 'Critical'],
            'clients' => Client::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'assignees' => User::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
