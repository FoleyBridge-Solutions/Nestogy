@extends('client-portal.layouts.app')

@section('title', 'Support Ticket #' . ($ticket->number ?? $ticket->id))

@section('content')
<div class="portal-container">
    <!-- Modern Hero Section -->
    <div class="ticket-hero">
        <div class="hero-background"></div>
        <div class="hero-content">
            <div class="hero-header">
                <nav class="hero-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('client.tickets') }}" class="breadcrumb-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>Support Tickets</span>
                    </a>
                </nav>
                
                <div class="ticket-header-main">
                    <div class="ticket-meta">
                        <div class="ticket-number">
                            <i class="fas fa-ticket-alt ticket-icon"></i>
                            <span class="number-text">Ticket #{{ $ticket->number ?? $ticket->id }}</span>
                        </div>
                        <div class="ticket-date">
                            Created {{ $ticket->created_at->format('M j, Y') }} at {{ $ticket->created_at->format('g:i A') }}
                        </div>
                    </div>
                    
                    <h1 class="ticket-title">{{ $ticket->subject }}</h1>
                </div>
            </div>

            <!-- Compact Info Row -->
            <div class="hero-info-row">
                <!-- Status & Priority Badges -->
                <div class="hero-badges">
                    <div class="status-badge status-{{ strtolower(str_replace(' ', '-', $ticket->status)) }}">
                        <i class="fas fa-circle status-dot"></i>
                        <span>{{ $ticket->status ?? 'Open' }}</span>
                    </div>
                    
                    <div class="priority-badge priority-{{ strtolower($ticket->priority) }}">
                        <i class="fas fa-flag"></i>
                        <span>{{ $ticket->priority ?? 'Medium' }}</span>
                    </div>

                    @if($ticket->category)
                        <div class="category-badge">
                            <i class="fas fa-tag"></i>
                            <span>{{ ucfirst($ticket->category) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Compact Progress Bar -->
                <div class="status-progress-compact">
                    @php
                        $statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
                        $currentIndex = array_search($ticket->status, $statuses);
                        $currentIndex = $currentIndex !== false ? $currentIndex : 0;
                    @endphp
                    
                    @foreach($statuses as $index => $status)
                        <div class="progress-step-compact {{ $index <= $currentIndex ? 'active' : '' }} {{ $index == $currentIndex ? 'current' : '' }}" title="{{ $status }}">
                            <div class="step-indicator-compact">
                                @if($index < $currentIndex)
                                    <i class="fas fa-check"></i>
                                @elseif($index == $currentIndex)
                                    <div class="step-dot-current"></div>
                                @else
                                    <div class="step-dot"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <div class="progress-labels">
                        <span class="progress-current">{{ $ticket->status }}</span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Response Time -->
            @php
                $responseTimeHours = [
                    'Critical' => 1,
                    'High' => 4, 
                    'Medium' => 24,
                    'Low' => 48
                ];
                $slaHours = $responseTimeHours[$ticket->priority] ?? 24;
                $slaDeadline = $ticket->created_at->addHours($slaHours);
                $now = now();
                
                if ($now > $slaDeadline) {
                    $overdueDuration = $now->diff($slaDeadline);
                    $overdueText = '';
                    if ($overdueDuration->days > 0) {
                        $overdueText = $overdueDuration->days . ' day' . ($overdueDuration->days > 1 ? 's' : '');
                    } elseif ($overdueDuration->h > 0) {
                        $overdueText = $overdueDuration->h . ' hour' . ($overdueDuration->h > 1 ? 's' : '');
                    } else {
                        $overdueText = $overdueDuration->i . ' minute' . ($overdueDuration->i > 1 ? 's' : '');
                    }
                } else {
                    $remainingTime = $now->diff($slaDeadline);
                    $remainingText = '';
                    if ($remainingTime->days > 0) {
                        $remainingText = $remainingTime->days . ' day' . ($remainingTime->days > 1 ? 's' : '');
                    } elseif ($remainingTime->h > 0) {
                        $remainingText = $remainingTime->h . ' hour' . ($remainingTime->h > 1 ? 's' : '');
                    } else {
                        $remainingText = $remainingTime->i . ' minute' . ($remainingTime->i > 1 ? 's' : '');
                    }
                }
            @endphp
            
            @if(in_array($ticket->status, ['Open', 'In Progress', 'Waiting']))
                <div class="response-estimate {{ $now > $slaDeadline ? 'overdue' : '' }}">
                    @if($now > $slaDeadline)
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><strong>Overdue</strong> by {{ $overdueText }}</span>
                    @else
                        <i class="fas fa-clock"></i>
                        <span>Response expected within <strong>{{ $remainingText }}</strong></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="ticket-content-grid">
        <!-- Primary Content Area -->
        <div class="content-primary">
            <!-- Ticket Details Card -->
            <div class="content-card details-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-info-circle"></i>
                        <span>Ticket Details</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="ticket-description">
                        <div class="description-content">
                            {!! nl2br(e($ticket->details)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="content-card timeline-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-history"></i>
                        <span>Activity Timeline</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="activity-timeline">
                        <!-- Initial Creation Entry -->
                        <div class="timeline-entry">
                            <div class="timeline-avatar">
                                <div class="avatar-circle client-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-author">{{ $contact->name ?? 'You' }}</div>
                                    <div class="timeline-action">created this ticket</div>
                                    <div class="timeline-timestamp">{{ $ticket->created_at->format('M j, Y \a\t g:i A') }}</div>
                                </div>
                                <div class="timeline-details">
                                    <div class="timeline-source">
                                        <i class="fas fa-desktop"></i>
                                        <span>via {{ $ticket->source ?? 'Portal' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Future: Additional timeline entries for comments/updates -->
                        {{-- 
                        @if($ticket->replies && count($ticket->replies) > 0)
                            @foreach($ticket->replies as $reply)
                            <div class="timeline-entry">
                                <!-- Reply timeline entry -->
                            </div>
                            @endforeach
                        @endif
                        --}}

                        <!-- Add Comment Section -->
                        @if(in_array($ticket->status, ['Open', 'In Progress', 'Waiting', 'On Hold']))
                        <div class="comment-composer">
                            <div class="composer-header">
                                <div class="composer-avatar">
                                    <div class="avatar-circle client-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div class="composer-title">Add a comment</div>
                            </div>
                            
                            <form action="{{ route('client.tickets.comment', $ticket) ?? '#' }}" method="POST" enctype="multipart/form-data" class="comment-form">
                                @csrf
                                <div class="form-group">
                                    <textarea 
                                        name="comment" 
                                        class="comment-textarea" 
                                        rows="4" 
                                        placeholder="Provide additional information, ask a question, or update us on your situation..."
                                        required></textarea>
                                </div>
                                
                                <div class="form-group file-upload-group">
                                    <div class="file-upload-area" onclick="document.getElementById('attachments').click()">
                                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                        <div class="upload-text">
                                            <strong>Drop files here or click to browse</strong>
                                            <small>Maximum 5 files, 10MB each. JPG, PNG, PDF, DOC, DOCX, TXT, ZIP</small>
                                        </div>
                                    </div>
                                    <input type="file" id="attachments" name="attachments[]" multiple 
                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip" style="display: none;">
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Send Comment</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="content-sidebar">
            <!-- Quick Actions -->
            @if(in_array($ticket->status, ['Open', 'In Progress', 'Waiting', 'On Hold']))
            <div class="sidebar-card actions-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-bolt"></i>
                        <span>Quick Actions</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="{{ route('client.tickets.comment', $ticket) ?? '#' }}" class="quick-action">
                            <i class="fas fa-comment"></i>
                            <span>Add Comment</span>
                        </a>
                        <a href="tel:5551234567" class="quick-action">
                            <i class="fas fa-phone"></i>
                            <span>Call Support</span>
                        </a>
                        <a href="mailto:support@company.com" class="quick-action">
                            <i class="fas fa-envelope"></i>
                            <span>Email Support</span>
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Ticket Information -->
            <div class="sidebar-card info-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-info-circle"></i>
                        <span>Ticket Information</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <div class="status-badge status-{{ strtolower(str_replace(' ', '-', $ticket->status)) }}">
                                    <i class="fas fa-circle status-dot"></i>
                                    <span>{{ $ticket->status ?? 'Open' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Priority</div>
                            <div class="info-value">
                                <div class="priority-badge priority-{{ strtolower($ticket->priority ?? 'medium') }}">
                                    <i class="fas fa-flag"></i>
                                    <span>{{ $ticket->priority ?? 'Medium' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Source</div>
                            <div class="info-value">
                                <i class="fas fa-desktop info-icon"></i>
                                <span>{{ $ticket->source ?? 'Portal' }}</span>
                            </div>
                        </div>

                        @if($ticket->assigned_to)
                        <div class="info-item">
                            <div class="info-label">Assigned To</div>
                            <div class="info-value">
                                <i class="fas fa-user-tie info-icon"></i>
                                <span>{{ $ticket->assignee->name ?? 'Support Team' }}</span>
                            </div>
                        </div>
                        @endif

                        <div class="info-item">
                            <div class="info-label">Created</div>
                            <div class="info-value">
                                <i class="fas fa-calendar info-icon"></i>
                                <span>{{ $ticket->created_at->format('M j, Y g:i A') }}</span>
                            </div>
                        </div>

                        @if($ticket->closed_at)
                        <div class="info-item">
                            <div class="info-label">Closed</div>
                            <div class="info-value">
                                <i class="fas fa-check-circle info-icon"></i>
                                <span>{{ $ticket->closed_at->format('M j, Y g:i A') }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Support Information -->
            <div class="sidebar-card support-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-life-ring"></i>
                        <span>Support Information</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="support-info">
                        <div class="support-section">
                            <h6 class="support-section-title">Response Times</h6>
                            <div class="response-times">
                                <div class="response-time-item">
                                    <span class="priority-dot priority-critical"></span>
                                    <span class="priority-name">Critical</span>
                                    <span class="response-time">1 hour</span>
                                </div>
                                <div class="response-time-item">
                                    <span class="priority-dot priority-high"></span>
                                    <span class="priority-name">High</span>
                                    <span class="response-time">4 hours</span>
                                </div>
                                <div class="response-time-item">
                                    <span class="priority-dot priority-medium"></span>
                                    <span class="priority-name">Medium</span>
                                    <span class="response-time">24 hours</span>
                                </div>
                                <div class="response-time-item">
                                    <span class="priority-dot priority-low"></span>
                                    <span class="priority-name">Low</span>
                                    <span class="response-time">48 hours</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="support-section">
                            <h6 class="support-section-title">Business Hours</h6>
                            <div class="business-hours">
                                <div class="hours-item">
                                    <i class="fas fa-clock"></i>
                                    <div class="hours-text">
                                        <div>Monday - Friday</div>
                                        <div class="hours-time">8:00 AM - 6:00 PM EST</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="support-section">
                            <h6 class="support-section-title">Emergency Contact</h6>
                            <div class="emergency-contact">
                                <a href="tel:5551234567" class="contact-link">
                                    <i class="fas fa-phone"></i>
                                    <span>(555) 123-4567</span>
                                </a>
                                <small class="contact-note">For critical issues outside business hours</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Modern Ticket View Styles */

/* Hero Section */
.ticket-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    margin-bottom: 2rem;
    overflow: hidden;
    color: white;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.6;
}

.hero-content {
    position: relative;
    z-index: 2;
    padding: 1.25rem 1.5rem;
}

.hero-breadcrumb {
    margin-bottom: 0.5rem;
}

.breadcrumb-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s;
}

.breadcrumb-link:hover {
    color: white;
}

.ticket-header-main {
    margin-bottom: 1rem;
}

.ticket-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.ticket-number {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 1rem;
}

.ticket-icon {
    font-size: 1.2rem;
    opacity: 0.9;
}

.ticket-date {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
}

.ticket-title {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.3;
    margin: 0;
}

/* Compact Hero Layout */
.hero-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

/* Compact Progress Bar */
.status-progress-compact {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.progress-step-compact {
    opacity: 0.4;
    transition: opacity 0.3s;
}

.progress-step-compact.active {
    opacity: 1;
}

.progress-step-compact.current {
    opacity: 1;
}

.step-indicator-compact {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s;
    font-size: 0.625rem;
}

.progress-step-compact.active .step-indicator-compact {
    background: rgba(255, 255, 255, 0.9);
    border-color: white;
    color: #667eea;
}

.step-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.step-dot-current {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse-dot 2s infinite;
}

.progress-labels {
    margin-left: 0.75rem;
}

.progress-current {
    font-size: 0.8125rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Hero Badges */
.hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.status-badge, .priority-badge, .category-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 16px;
    font-size: 0.8125rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.status-badge { background: rgba(255, 255, 255, 0.15); }
.priority-badge { background: rgba(255, 255, 255, 0.15); }
.category-badge { background: rgba(255, 255, 255, 0.1); }

.status-dot {
    font-size: 0.5rem;
}

.response-estimate {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.response-estimate.overdue {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    animation: pulse-overdue 2s infinite;
}

@keyframes pulse-overdue {
    0%, 100% { background: rgba(239, 68, 68, 0.15); }
    50% { background: rgba(239, 68, 68, 0.25); }
}

/* Content Grid */
.ticket-content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .ticket-content-grid {
        grid-template-columns: 1fr;
    }
}

/* Content Cards */
.content-card, .sidebar-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: box-shadow 0.2s;
}

.content-card:hover, .sidebar-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.card-content {
    padding: 1.5rem;
}

/* Ticket Details */
.ticket-description {
    line-height: 1.6;
    color: #374151;
    font-size: 0.95rem;
}

/* Activity Timeline */
.activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.timeline-entry {
    display: flex;
    gap: 1rem;
}

.timeline-avatar {
    flex-shrink: 0;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.client-avatar {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.timeline-content {
    flex: 1;
}

.timeline-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.timeline-author {
    font-weight: 600;
    color: #111827;
}

.timeline-action {
    color: #6b7280;
}

.timeline-timestamp {
    color: #9ca3af;
    font-size: 0.875rem;
    margin-left: auto;
}

.timeline-details {
    margin-top: 0.5rem;
}

.timeline-source {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

/* Comment Composer */
.comment-composer {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
}

.composer-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.composer-title {
    font-weight: 600;
    color: #374151;
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.comment-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    resize: vertical;
    font-family: inherit;
    font-size: 0.875rem;
    line-height: 1.5;
    transition: border-color 0.2s;
}

.comment-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.file-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.file-upload-area:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.02);
}

.upload-icon {
    font-size: 2rem;
    color: #9ca3af;
    margin-bottom: 0.5rem;
}

.upload-text strong {
    display: block;
    color: #374151;
    margin-bottom: 0.25rem;
}

.upload-text small {
    color: #6b7280;
    font-size: 0.75rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

/* Sidebar Styles */
.content-sidebar {
    position: sticky;
    top: 2rem;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    color: #374151;
    text-decoration: none;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.quick-action:hover {
    background: #f3f4f6;
}

/* Info List */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    font-weight: 500;
    color: #6b7280;
    font-size: 0.875rem;
}

.info-value {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.info-icon {
    color: #9ca3af;
    font-size: 0.75rem;
}

/* Status and Priority Badges (Compact) */
.status-badge {
    background: #f3f4f6;
    color: #374151;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.status-open { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.status-in-progress { background: #fffbeb; color: #d97706; border-color: #fed7aa; }
.status-waiting { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.status-on-hold { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
.status-resolved { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }
.status-closed { background: #f9fafb; color: #4b5563; border-color: #e5e7eb; }

.priority-badge {
    background: #f3f4f6;
    color: #374151;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.priority-critical { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.priority-high { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
.priority-medium { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.priority-low { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }

/* Support Info */
.support-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.support-section-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
}

.response-times {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.response-time-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.priority-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.priority-dot.priority-critical { background: #dc2626; }
.priority-dot.priority-high { background: #ea580c; }
.priority-dot.priority-medium { background: #2563eb; }
.priority-dot.priority-low { background: #059669; }

.priority-name {
    font-weight: 500;
    color: #374151;
    flex: 1;
}

.response-time {
    color: #6b7280;
    font-size: 0.8125rem;
}

.business-hours, .emergency-contact {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.hours-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.hours-text {
    font-size: 0.875rem;
    color: #374151;
}

.hours-time {
    color: #6b7280;
    font-size: 0.8125rem;
}

.contact-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.contact-note {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        padding: 1rem;
    }
    
    .ticket-title {
        font-size: 1.25rem;
    }
    
    .hero-info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .status-progress-compact {
        align-self: flex-start;
    }
    
    .ticket-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .timeline-timestamp {
        margin-left: 0;
    }
    
    .content-sidebar {
        position: static;
        order: -1;
    }
}

/* Accessibility */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus States */
.quick-action:focus,
.contact-link:focus,
.breadcrumb-link:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

.comment-textarea:focus,
.btn-primary:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}
</style>
@endpush