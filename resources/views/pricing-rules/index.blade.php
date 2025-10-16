@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">Pricing Rules</x-slot>
        <x-slot name="description">Manage client-specific pricing rules and discounts</x-slot>
        <x-slot name="actions">
            @can('create', App\Domains\Product\Models\PricingRule::class)
                <a href="{{ route('pricing-rules.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus"></i> Create Pricing Rule
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <x-content-card>
        <!-- Search and Filters -->
        <div class="flex flex-wrap -mx-4 mb-4">
            <div class="md:w-2/3 px-4">
                <form method="GET" class="flex">
                    <input type="text" name="search" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm mr-2" 
                           placeholder="Search pricing rules..." 
                           value="{{ request('search') }}">
                    <select name="rule_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm mr-2" style="width: auto;">
                        <option value="">All Types</option>
                        <option value="discount" {{ request('rule_type') === 'discount' ? 'selected' : '' }}>Discount</option>
                        <option value="markup" {{ request('rule_type') === 'markup' ? 'selected' : '' }}>Markup</option>
                        <option value="fixed_price" {{ request('rule_type') === 'fixed_price' ? 'selected' : '' }}>Fixed Price</option>
                        <option value="tiered" {{ request('rule_type') === 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                    </select>
                    <select name="is_active" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm mr-2" style="width: auto;">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <flux:button variant="ghost" type="submit">
                        <i class="fas fa-search"></i>
                    </flux:button>
                    @if(request()->hasAny(['search', 'rule_type', 'is_active']))
                        <flux:button variant="ghost" color="zinc" href="{{ route('pricing-rules.index') }}" class="ml-2" >
                            <i class="fas fa-times"></i>
                        </flux:button>
                    @endif
                </form>
            </div>
        </div>

        @if($pricingRules->count() > 0)
            <div class="min-w-full divide-y divide-gray-200-responsive">
                <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100 dark:bg-gray-800">
                    <thead>
                        <tr>
                            <th scope="col-span-12">Name</th>
                            <th scope="col-span-12">Type</th>
                            <th scope="col-span-12">Target</th>
                            <th scope="col-span-12">Client</th>
                            <th scope="col-span-12">Value</th>
                            <th scope="col-span-12">Priority</th>
                            <th scope="col-span-12">Valid Period</th>
                            <th scope="col-span-12">Status</th>
                            <th scope="col-span-12">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pricingRules as $rule)
                            <tr>
                                <td>
                                    <strong>{{ $rule->name }}</strong>
                                    @if($rule->description)
                                        <br><small class="text-gray-600 dark:text-gray-400">{{ Str::limit($rule->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $rule->rule_type)) }}</span>
                                </td>
                                <td>
                                    @if($rule->target_type === 'product')
                                        <i class="fas fa-box text-blue-600"></i> {{ $rule->product->name ?? 'All Products' }}
                                    @elseif($rule->target_type === 'category')
                                        <i class="fas fa-tags text-green-600"></i> {{ $rule->category->name ?? 'All Categories' }}
                                    @else
                                        <i class="fas fa-globe text-gray-600 dark:text-gray-400"></i> All Products
                                    @endif
                                </td>
                                <td>
                                    @if($rule->client_id)
                                        <i class="fas fa-user text-cyan-600 dark:text-cyan-400"></i> {{ $rule->client->name }}
                                    @else
                                        <i class="fas fa-users text-gray-600 dark:text-gray-400"></i> All Clients
                                    @endif
                                </td>
                                <td>
                                    @if($rule->rule_type === 'discount')
                                        <span class="text-red-600">
                                            @if($rule->discount_type === 'percentage')
                                                -{{ $rule->discount_value }}%
                                            @else
                                                -${{ number_format($rule->discount_value, 2) }}
                                            @endif
                                        </span>
                                    @elseif($rule->rule_type === 'markup')
                                        <span class="text-yellow-600 dark:text-yellow-400">
                                            @if($rule->markup_type === 'percentage')
                                                +{{ $rule->markup_value }}%
                                            @else
                                                +${{ number_format($rule->markup_value, 2) }}
                                            @endif
                                        </span>
                                    @elseif($rule->rule_type === 'fixed_price')
                                        <span class="text-blue-600">${{ number_format($rule->fixed_price, 2) }}</span>
                                    @else
                                        <span class="text-cyan-600 dark:text-cyan-400">Tiered</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-gray-600">{{ $rule->priority }}</span>
                                </td>
                                <td>
                                    @if($rule->valid_from && $rule->valid_to)
                                        <small>
                                            {{ $rule->valid_from->format('M j') }} - {{ $rule->valid_to->format('M j, Y') }}
                                        </small>
                                    @elseif($rule->valid_from)
                                        <small>From {{ $rule->valid_from->format('M j, Y') }}</small>
                                    @elseif($rule->valid_to)
                                        <small>Until {{ $rule->valid_to->format('M j, Y') }}</small>
                                    @else
                                        <small class="text-gray-600 dark:text-gray-400">Always</small>
                                    @endif
                                </td>
                                <td>
                                    @if($rule->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class=" role="group">
                                        @can('view', $rule)
                                            <a href="{{ route('pricing-rules.show', $rule) }}" class="btn px-3 py-1 text-sm 
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('update', $rule)
                                            <a href="{{ route('pricing-rules.edit', $rule) }}" class="btn px-3 py-1 text-sm 
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $rule)
                                            <button type="button" class="btn px-3 py-1 text-sm  
                                                    onclick="confirmDelete('{{ $rule->name }}', '{{ route('pricing-rules.destroy', $rule) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $pricingRules->links() }}
        @else
            <div class="text-center py-5">
                <i class="fas fa-percentage fa-3x text-gray-600 dark:text-gray-400 mb-3"></i>
                <h5 class="text-gray-600 dark:text-gray-400">No pricing rules found</h5>
                <p class="text-gray-600 dark:text-gray-400">
                    @if(request()->hasAny(['search', 'rule_type', 'is_active']))
                        Try adjusting your search criteria or <a href="{{ route('pricing-rules.index') }}">view all pricing rules</a>.
                    @else
                        Create your first pricing rule to customize client pricing.
                    @endif
                </p>
                @can('create', App\Domains\Product\Models\PricingRule::class)
                    <a href="{{ route('pricing-rules.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus"></i> Create Pricing Rule
                    </a>
                @endcan
            </div>
        @endif
    </x-content-card>
</div>

<script>
async function confirmDelete(name, url) {
    const confirmed = await confirmAction(
        `Are you sure you want to delete the pricing rule "${name}"? This action cannot be undone.`,
        {
            title: 'Delete Pricing Rule',
            confirmText: 'Delete Rule',
            cancelText: 'Cancel',
            type: 'error'
        }
    );
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
