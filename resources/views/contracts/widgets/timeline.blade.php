@php
    $timelineData = $widget['data'] ?? [];
    $showFilters = $widget['show_filters'] ?? false;
    $groupBy = $widget['group_by'] ?? 'date';
    $maxItems = $widget['max_items'] ?? 50;
    $showAvatars = $widget['show_avatars'] ?? true;
    $showIcons = $widget['show_icons'] ?? true;
@endphp

<div class="widget-timeline-container">
    @if($showFilters)
        <div class="timeline-filters mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" id="timeline-type-{{ $widget['id'] }}">
                        <option value="">All Types</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="status_change">Status Changes</option>
                        <option value="comment">Comments</option>
                        <option value="milestone">Milestones</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" id="timeline-user-{{ $widget['id'] }}">
                        <option value="">All Users</option>
                        @foreach($widget['users'] ?? [] as $user)
                            <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" id="timeline-period-{{ $widget['id'] }}">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                    </select>
                </div>
            </div>
        </div>
    @endif

    @if(empty($timelineData))
        <div class="text-center text-muted py-4">
            <i class="fas fa-clock fa-3x mb-3"></i>
            <p class="mb-0">No timeline events</p>
            <small>Recent contract activities will appear here</small>
        </div>
    @else
        <div class="timeline" id="timeline-{{ $widget['id'] }}">
            @php
                $groupedData = $groupBy === 'date' ? collect($timelineData)->groupBy(function($item) {
                    return \Carbon\Carbon::parse($item['created_at'])->format('Y-m-d');
                }) : collect($timelineData);
            @endphp

            @if($groupBy === 'date')
                @foreach($groupedData as $date => $events)
                    <div class="timeline-group">
                        <div class="timeline-date-header">
                            <h6 class="mb-0">{{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</h6>
                            <small class="text-muted">{{ count($events) }} events</small>
                        </div>
                        
                        @foreach($events->take($maxItems) as $event)
                            @include('contracts.widgets.partials.timeline-item', ['event' => $event])
                        @endforeach
                    </div>
                @endforeach
            @else
                @foreach($timelineData as $event)
                    @include('contracts.widgets.partials.timeline-item', ['event' => $event])
                @endforeach
            @endif
        </div>

        @if(count($timelineData) > $maxItems)
            <div class="text-center mt-3">
                <button class="btn btn-sm btn-outline-secondary" id="load-more-{{ $widget['id'] }}">
                    Load More Events
                </button>
            </div>
        @endif
    @endif
</div>

@if(!empty($timelineData))
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const widgetId = '{{ $widget['id'] }}';
        const timeline = document.getElementById('timeline-' + widgetId);
        const typeFilter = document.getElementById('timeline-type-' + widgetId);
        const userFilter = document.getElementById('timeline-user-' + widgetId);
        const periodFilter = document.getElementById('timeline-period-' + widgetId);
        const loadMoreBtn = document.getElementById('load-more-' + widgetId);

        let currentPage = 1;
        const originalEvents = @json($timelineData);

        // Filter functionality
        @if($showFilters)
        [typeFilter, userFilter, periodFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', applyFilters);
            }
        });

        function applyFilters() {
            const typeValue = typeFilter ? typeFilter.value : '';
            const userValue = userFilter ? userFilter.value : '';
            const periodValue = periodFilter ? periodFilter.value : 'all';

            let filteredEvents = originalEvents.filter(event => {
                // Type filter
                if (typeValue && event.type !== typeValue) return false;
                
                // User filter
                if (userValue && event.user_id != userValue) return false;
                
                // Period filter
                if (periodValue !== 'all') {
                    const eventDate = new Date(event.created_at);
                    const now = new Date();
                    
                    switch (periodValue) {
                        case 'today':
                            if (eventDate.toDateString() !== now.toDateString()) return false;
                            break;
                        case 'week':
                            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                            if (eventDate < weekAgo) return false;
                            break;
                        case 'month':
                            const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                            if (eventDate < monthAgo) return false;
                            break;
                        case 'quarter':
                            const quarterAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
                            if (eventDate < quarterAgo) return false;
                            break;
                    }
                }
                
                return true;
            });

            renderTimeline(filteredEvents);
        }

        function renderTimeline(events) {
            // This would re-render the timeline with filtered events
            // For now, just hide/show existing events
            const timelineItems = timeline.querySelectorAll('.timeline-item');
            
            timelineItems.forEach(item => {
                const eventId = item.dataset.eventId;
                const shouldShow = events.some(event => event.id == eventId);
                item.style.display = shouldShow ? 'block' : 'none';
            });
        }
        @endif

        // Load more functionality
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                currentPage++;
                
                fetch(`/api/contracts/dashboard/widgets/{{ $widget['id'] }}/timeline?page=${currentPage}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.events.length > 0) {
                        appendTimelineEvents(data.events);
                        
                        if (!data.has_more) {
                            loadMoreBtn.style.display = 'none';
                        }
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Failed to load more timeline events:', error);
                });
            });
        }

        function appendTimelineEvents(events) {
            events.forEach(event => {
                const eventElement = createTimelineEventElement(event);
                timeline.appendChild(eventElement);
            });
        }

        function createTimelineEventElement(event) {
            // This would create a new timeline item element
            const div = document.createElement('div');
            div.className = 'timeline-item fade-in';
            div.dataset.eventId = event.id;
            div.innerHTML = `
                <!-- Timeline item HTML would be generated here -->
            `;
            return div;
        }

        // Auto-refresh functionality
        @if($widget['auto_refresh'] ?? false)
        setInterval(() => {
            refreshTimeline();
        }, {{ ($widget['refresh_interval'] ?? 300) * 1000 }});

        function refreshTimeline() {
            fetch(`/api/contracts/dashboard/widgets/{{ $widget['id'] }}/refresh`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update timeline with new data
                    location.reload(); // Simple refresh for now
                }
            })
            .catch(error => {
                console.error('Failed to refresh timeline:', error);
            });
        }
        @endif
    });
    </script>
    @endpush
@endif

<style>
.widget-timeline-container {
    max-height: 400px;
    overflow-y: auto;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e9ecef 0%, #e9ecef 100%);
}

.timeline-group {
    margin-bottom: 2rem;
}

.timeline-date-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border-left: 4px solid #0d6efd;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.3s ease-out;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2.25rem;
    top: 0.5rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #fff;
    border: 3px solid #0d6efd;
    z-index: 1;
}

.timeline-content {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.timeline-content:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.timeline-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.timeline-user {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: #495057;
}

.timeline-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.timeline-time {
    font-size: 0.75rem;
    color: #6c757d;
}

.timeline-body {
    color: #495057;
    line-height: 1.5;
}

.timeline-meta {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
    font-size: 0.75rem;
    color: #6c757d;
}

.timeline-filters .form-select {
    font-size: 0.875rem;
}

/* Event type specific colors */
.timeline-item[data-event-type="created"]::before { border-color: #28a745; }
.timeline-item[data-event-type="updated"]::before { border-color: #17a2b8; }
.timeline-item[data-event-type="status_change"]::before { border-color: #ffc107; }
.timeline-item[data-event-type="comment"]::before { border-color: #6f42c1; }
.timeline-item[data-event-type="milestone"]::before { border-color: #fd7e14; }

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.3s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .timeline {
        padding-left: 1.5rem;
    }
    
    .timeline::before {
        left: 0.75rem;
    }
    
    .timeline-item::before {
        left: -1.75rem;
        width: 8px;
        height: 8px;
    }
    
    .timeline-content {
        padding: 0.75rem;
    }
    
    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .widget-timeline-container {
        max-height: 300px;
    }
}
</style>