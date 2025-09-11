@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<flux:container>
    <!-- Page Header -->
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">User Management</flux:heading>
                <flux:text>Manage user accounts, roles, and permissions for {{ Auth::user()->company->name }}</flux:text>
            </div>
            <div class="flex gap-3">
                <flux:button href="{{ route('users.export.csv') }}" variant="ghost" icon="arrow-down-tray">
                    Export CSV
                </flux:button>
                @can('create', App\Models\User::class)
                <flux:button href="{{ route('users.create') }}" variant="primary" icon="plus">
                    Add User
                </flux:button>
                @endcan
            </div>
        </div>
    </flux:card>

    <!-- Filters -->
    <flux:card>
        <flux:heading size="lg">Filters</flux:heading>
        <flux:separator class="my-4" />
        
        <form method="GET" action="{{ route('users.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <!-- Search -->
                <flux:input 
                    name="search" 
                    placeholder="Name or email..." 
                    value="{{ request('search') }}"
                    icon="magnifying-glass"
                />

                <!-- Role Filter -->
                <flux:select 
                    name="role" 
                    placeholder="All Roles"
                    value="{{ request('role') }}"
                >
                    <flux:select.option value="">All Roles</flux:select.option>
                    @foreach(\Silber\Bouncer\BouncerFacade::role()->get() as $role)
                        @if($role->name !== 'super-admin' || Auth::user()->isA('super-admin'))
                            <flux:select.option value="{{ $role->name }}">
                                {{ $role->title ?: ucwords(str_replace('-', ' ', $role->name)) }}
                            </flux:select.option>
                        @endif
                    @endforeach
                </flux:select>

                <!-- Status Filter -->
                <flux:select 
                    name="status" 
                    placeholder="All Status"
                    value="{{ request('status') }}"
                >
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="1">Active</flux:select.option>
                    <flux:select.option value="0">Inactive</flux:select.option>
                </flux:select>

                <!-- Filter Button -->
                <flux:button type="submit" variant="primary" icon="funnel">
                    Filter
                </flux:button>
            </div>
            
            @if(request()->hasAny(['search', 'role', 'status']))
                <flux:button href="{{ route('users.index') }}" variant="ghost" size="sm">
                    Clear Filters
                </flux:button>
            @endif
        </form>
    </flux:card>

    <!-- Users Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>User</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Last Login</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column class="w-1">Actions</flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($users as $user)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar 
                                src="{{ $user->getAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" 
                                size="xs"
                            />
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <flux:text class="text-sm">{{ $user->email }}</flux:text>
                            </div>
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @php
                            $userRoles = $user->getRoles();
                            $primaryRole = $userRoles->first();
                            
                            $roleVariants = [
                                'super-admin' => 'danger',
                                'admin' => 'warning',
                                'technician' => 'info',
                                'accountant' => 'info',
                                'sales-representative' => 'info',
                                'marketing-specialist' => 'info',
                                'user' => 'ghost',
                                'client-user' => 'ghost',
                            ];
                            
                            $variant = $roleVariants[$primaryRole] ?? 'ghost';
                            $displayName = ucwords(str_replace('-', ' ', $primaryRole));
                        @endphp
                        <flux:badge color="{{ $variant === 'danger' ? 'red' : ($variant === 'warning' ? 'yellow' : ($variant === 'info' ? 'blue' : 'zinc')) }}" size="sm" inset="top bottom">
                            {{ $displayName }}
                        </flux:badge>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if($user->status)
                            <flux:badge color="green" size="sm" inset="top bottom">Active</flux:badge>
                        @else
                            <flux:badge color="red" size="sm" inset="top bottom">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <flux:text class="text-sm">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </flux:text>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <flux:text class="text-sm">
                            {{ $user->created_at->format('M d, Y') }}
                        </flux:text>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button 
                                href="{{ route('users.show', $user) }}" 
                                variant="ghost" 
                                size="sm" 
                                icon="eye"
                                icon-variant="mini"
                            />
                            
                            @can('update', $user)
                            <flux:button 
                                href="{{ route('users.edit', $user) }}" 
                                variant="ghost" 
                                size="sm" 
                                icon="pencil"
                                icon-variant="mini"
                            />
                            @endcan
                            
                            @can('delete', $user)
                                @if($user->id !== Auth::id())
                                <flux:dropdown align="end">
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm" 
                                        icon="ellipsis-vertical"
                                        icon-variant="mini"
                                    />
                                    
                                    <flux:menu>
                                        <form action="{{ route('users.archive', $user) }}" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to archive this user?');">
                                            @csrf
                                            @method('POST')
                                            <flux:menu.item 
                                                type="submit"
                                                icon="archive-box"
                                                variant="danger"
                                            >
                                                Archive User
                                            </flux:menu.item>
                                        </form>
                                        
                                        @if($user->archived_at)
                                        <form action="{{ route('users.restore', $user) }}" method="POST">
                                            @csrf
                                            @method('POST')
                                            <flux:menu.item 
                                                type="submit"
                                                icon="arrow-path"
                                            >
                                                Restore User
                                            </flux:menu.item>
                                        </form>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                                @endif
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="text-center py-8">
                            <flux:icon name="users" class="mx-auto text-zinc-400 mb-4" size="lg" />
                            <flux:text>No users found</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <!-- Pagination -->
        @if($users->hasPages())
            <flux:separator class="my-4" />
            <div class="flex items-center justify-between">
                <flux:text class="text-sm">
                    Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                </flux:text>
                
                <div class="flex gap-2">
                    @if($users->onFirstPage())
                        <flux:button variant="ghost" size="sm" disabled icon="chevron-left">
                            Previous
                        </flux:button>
                    @else
                        <flux:button 
                            href="{{ $users->previousPageUrl() }}" 
                            variant="ghost" 
                            size="sm" 
                            icon="chevron-left"
                        >
                            Previous
                        </flux:button>
                    @endif
                    
                    @php
                        $currentPage = $users->currentPage();
                        $lastPage = $users->lastPage();
                        $start = max(1, $currentPage - 2);
                        $end = min($lastPage, $currentPage + 2);
                    @endphp
                    
                    @if($start > 1)
                        <flux:button href="{{ $users->url(1) }}" variant="ghost" size="sm">1</flux:button>
                        @if($start > 2)
                            <span class="px-2 text-zinc-400">...</span>
                        @endif
                    @endif
                    
                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $currentPage)
                            <flux:button variant="primary" size="sm" disabled>
                                {{ $page }}
                            </flux:button>
                        @else
                            <flux:button href="{{ $users->url($page) }}" variant="ghost" size="sm">
                                {{ $page }}
                            </flux:button>
                        @endif
                    @endfor
                    
                    @if($end < $lastPage)
                        @if($end < $lastPage - 1)
                            <span class="px-2 text-zinc-400">...</span>
                        @endif
                        <flux:button href="{{ $users->url($lastPage) }}" variant="ghost" size="sm">{{ $lastPage }}</flux:button>
                    @endif
                    
                    @if($users->hasMorePages())
                        <flux:button 
                            href="{{ $users->nextPageUrl() }}" 
                            variant="ghost" 
                            size="sm" 
                            icon-trailing="chevron-right"
                        >
                            Next
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" disabled icon-trailing="chevron-right">
                            Next
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</flux:container>
@endsection