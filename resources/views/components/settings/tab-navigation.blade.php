@props([
    'tabs' => [],
    'activeTab' => null
])

<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8 overflow-x-auto">
        @foreach($tabs as $key => $label)
            <button type="button" 
                    @click="activeTab = '{{ $key }}'"
                    :class="{
                        'border-blue-500 text-blue-600': activeTab === '{{ $key }}', 
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== '{{ $key }}'
                    }"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none">
                {{ $label }}
            </button>
        @endforeach
    </nav>
</div>