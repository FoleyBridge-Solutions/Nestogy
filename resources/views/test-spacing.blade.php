<!DOCTYPE html>
<html>
<head>
    <title>Client Switcher Spacing Test</title>
    @vite(['resources/css/app.css'])
    @fluxStyles
</head>
<body class="bg-gray-100 p-8">
    <h1 class="text-2xl font-bold mb-4">Testing Client Item Spacing</h1>
    
    <div class="bg-white p-4 rounded-lg shadow-lg max-w-md">
        <h2 class="font-semibold mb-2">Test 1: With space-y-0</h2>
        <div class="space-y-0 border border-red-500">
            @for($i = 1; $i <= 3; $i++)
                <button class="w-full flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-zinc-50 text-left border border-blue-500">
                    <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-xs">C{{ $i }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">Client {{ $i }}</div>
                        <div class="text-xs text-gray-500">Company {{ $i }}</div>
                    </div>
                </button>
            @endfor
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-lg max-w-md mt-4">
        <h2 class="font-semibold mb-2">Test 2: With -space-y-1</h2>
        <div class="-space-y-1 border border-red-500">
            @for($i = 1; $i <= 3; $i++)
                <button class="w-full flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-zinc-50 text-left border border-blue-500">
                    <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-xs">C{{ $i }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">Client {{ $i }}</div>
                        <div class="text-xs text-gray-500">Company {{ $i }}</div>
                    </div>
                </button>
            @endfor
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-lg max-w-md mt-4">
        <h2 class="font-semibold mb-2">Test 3: No spacing class</h2>
        <div class="border border-red-500">
            @for($i = 1; $i <= 3; $i++)
                <button class="w-full flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-zinc-50 text-left border border-blue-500">
                    <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-xs">C{{ $i }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">Client {{ $i }}</div>
                        <div class="text-xs text-gray-500">Company {{ $i }}</div>
                    </div>
                </button>
            @endfor
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-lg max-w-md mt-4">
        <h2 class="font-semibold mb-2">Test 4: With flex flex-col</h2>
        <div class="flex flex-col border border-red-500">
            @for($i = 1; $i <= 3; $i++)
                <button class="w-full flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-zinc-50 text-left border border-blue-500">
                    <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-xs">C{{ $i }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">Client {{ $i }}</div>
                        <div class="text-xs text-gray-500">Company {{ $i }}</div>
                    </div>
                </button>
            @endfor
        </div>
    </div>

    <script>
        // Log computed styles for debugging
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('button');
            buttons.forEach((btn, idx) => {
                const styles = window.getComputedStyle(btn);
                console.log(`Button ${idx}:`, {
                    marginTop: styles.marginTop,
                    marginBottom: styles.marginBottom,
                    paddingTop: styles.paddingTop,
                    paddingBottom: styles.paddingBottom,
                    height: styles.height,
                    display: styles.display
                });
            });
        });
    </script>
</body>
</html>