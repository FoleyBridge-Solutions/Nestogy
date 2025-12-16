<!DOCTYPE html>
<html>
<head>
    <title>Badge Color Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="p-8">
    <h1 class="text-2xl mb-4">Direct Flux Badges (Should have colors)</h1>
    <div class="flex gap-2 mb-8">
        <flux:badge color="yellow">Yellow (Open)</flux:badge>
        <flux:badge color="blue">Blue (In Progress)</flux:badge>
        <flux:badge color="green">Green (Resolved)</flux:badge>
        <flux:badge color="zinc">Zinc (Closed)</flux:badge>
        <flux:badge color="red">Red (Critical)</flux:badge>
        <flux:badge color="orange">Orange (High)</flux:badge>
    </div>

    <h1 class="text-2xl mb-4">Component Badges (Testing our components)</h1>
    <div class="flex gap-2 mb-8">
        <x-status-badge status="open" type="ticket" />
        <x-status-badge status="in_progress" type="ticket" />
        <x-status-badge status="resolved" type="ticket" />
        <x-status-badge status="closed" type="ticket" />
    </div>

    <h1 class="text-2xl mb-4">Priority Badges</h1>
    <div class="flex gap-2">
        <x-priority-badge priority="critical" />
        <x-priority-badge priority="high" />
        <x-priority-badge priority="medium" />
        <x-priority-badge priority="low" />
    </div>
</body>
</html>
