@props(['widget'])

<div class="card stats-card h-100">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="stats-icon">
                <i class="{{ $widget['icon'] ?? 'fas fa-chart-line' }} fa-2x {{ $widget['icon_color'] ?? 'text-primary' }}"></i>
            </div>
            <div class="ms-3 flex-grow-1">
                <div class="stats-value">
                    {{ $widget['value'] ?? '0' }}
                </div>
                <div class="stats-label text-muted">
                    {{ $widget['label'] ?? $widget['title'] }}
                </div>
            </div>
        </div>
        
        @if(isset($widget['change']) && $widget['change'] !== null)
            <div class="stats-change mt-2">
                <span class="badge bg-{{ $widget['change'] > 0 ? 'success' : ($widget['change'] < 0 ? 'danger' : 'secondary') }}">
                    @if($widget['change'] > 0)
                        <i class="fas fa-arrow-up"></i>
                    @elseif($widget['change'] < 0)
                        <i class="fas fa-arrow-down"></i>
                    @else
                        <i class="fas fa-minus"></i>
                    @endif
                    {{ abs($widget['change']) }}{{ $widget['change_suffix'] ?? '%' }}
                </span>
                <small class="text-muted ms-1">{{ $widget['change_period'] ?? 'vs last month' }}</small>
            </div>
        @endif

        @if(isset($widget['progress']) && $widget['progress'] !== null)
            <div class="mt-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">{{ $widget['progress_label'] ?? 'Progress' }}</small>
                    <small class="text-muted">{{ $widget['progress'] }}%</small>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-{{ $widget['progress_color'] ?? 'primary' }}" 
                         style="width: {{ $widget['progress'] }}%"></div>
                </div>
            </div>
        @endif

        @if(isset($widget['subtitle']) && $widget['subtitle'])
            <div class="stats-subtitle mt-2">
                <small class="text-muted">{{ $widget['subtitle'] }}</small>
            </div>
        @endif
    </div>
    
    @if(isset($widget['actions']) && !empty($widget['actions']))
        <div class="card-footer bg-transparent border-top-0 pt-0">
            <div class="d-flex gap-1">
                @foreach($widget['actions'] as $action)
                    <a href="{{ $action['url'] ?? '#' }}" 
                       class="btn btn-sm btn-outline-{{ $action['color'] ?? 'primary' }} flex-grow-1">
                        @if($action['icon'] ?? false)
                            <i class="{{ $action['icon'] }} me-1"></i>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
.stats-card {
    border-left: 4px solid {{ $widget['accent_color'] ?? 'var(--bs-primary)' }};
    transition: all 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stats-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: {{ $widget['icon_bg'] ?? 'rgba(var(--bs-primary-rgb), 0.1)' }};
    border-radius: 12px;
}

.stats-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
    color: {{ $widget['value_color'] ?? 'var(--bs-dark)' }};
}

.stats-label {
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stats-change .badge {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .stats-value {
        font-size: 1.5rem;
    }
    
    .stats-icon {
        width: 50px;
        height: 50px;
    }
    
    .stats-icon i {
        font-size: 1.5rem !important;
    }
}
</style>