@extends('layouts.app')

@section('title', 'Mail Queue')

@section('content')
<flux:container>
    <div class="mb-6">
        <flux:heading size="xl">Mail Queue</flux:heading>
        <flux:text class="mt-2">Monitor and manage email delivery queue</flux:text>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <flux:card>
            <flux:heading size="sm" class="text-zinc-500">Total Emails</flux:heading>
            <flux:heading size="2xl" class="mt-2">{{ number_format($stats['total']) }}</flux:heading>
        </flux:card>

        <flux:card>
            <flux:heading size="sm" class="text-zinc-500">Sent</flux:heading>
            <flux:heading size="2xl" class="mt-2 text-green-600">{{ number_format($stats['by_status']['sent'] ?? 0) }}</flux:heading>
        </flux:card>

        <flux:card>
            <flux:heading size="sm" class="text-zinc-500">Failed</flux:heading>
            <flux:heading size="2xl" class="mt-2 text-red-600">{{ number_format($stats['by_status']['failed'] ?? 0) }}</flux:heading>
        </flux:card>

        <flux:card>
            <flux:heading size="sm" class="text-zinc-500">Success Rate</flux:heading>
            <flux:heading size="2xl" class="mt-2">{{ $stats['success_rate'] }}%</flux:heading>
        </flux:card>
    </div>

    <!-- Recent Failures -->
    @if(count($stats['recent_failures']) > 0)
        <flux:card class="mb-8">
            <flux:heading size="lg" class="mb-4">Recent Failures</flux:heading>
            <div class="space-y-2">
                @foreach($stats['recent_failures'] as $failure)
                    <div class="flex items-start justify-between p-3 bg-red-50 dark:bg-red-900/10 rounded-lg">
                        <div class="flex-1">
                            <flux:text class="font-medium">{{ $failure->to_email }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">{{ $failure->subject }}</flux:text>
                            <flux:text size="xs" class="text-red-600 mt-1">{{ $failure->failure_reason }}</flux:text>
                        </div>
                        <flux:text size="xs" class="text-zinc-400">{{ $failure->failed_at?->diffForHumans() }}</flux:text>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    <!-- Emails Table -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Email Queue</flux:heading>
        
        @if($emails->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">To</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($emails as $email)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $email->to_email }}</td>
                                <td class="px-4 py-3 text-sm">{{ Str::limit($email->subject, 50) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($email->status === 'sent')
                                        <flux:badge color="green">Sent</flux:badge>
                                    @elseif($email->status === 'failed')
                                        <flux:badge color="red">Failed</flux:badge>
                                    @elseif($email->status === 'pending')
                                        <flux:badge color="yellow">Pending</flux:badge>
                                    @else
                                        <flux:badge color="zinc">{{ ucfirst($email->status) }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ ucfirst($email->priority) }}</td>
                                <td class="px-4 py-3 text-sm">{{ $email->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('mail-queue.show', $email) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $emails->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon name="envelope" class="w-12 h-12 text-zinc-400 mx-auto mb-4" />
                <flux:heading size="md">No emails in queue</flux:heading>
                <flux:text class="mt-2">Emails will appear here when they are sent through the system.</flux:text>
            </div>
        @endif
    </flux:card>
</flux:container>
@endsection
