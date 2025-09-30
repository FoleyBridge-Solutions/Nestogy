<?php

namespace App\Domains\Core\Services;

class ShortcutService
{
    /**
     * Master shortcut registry - single source of truth
     */
    protected static $shortcuts = [
        // System shortcuts
        'system' => [
            [
                'id' => 'command_palette',
                'keys' => ['Ctrl', '/'],
                'command' => 'open_command_palette',
                'description' => 'Open command palette',
                'category' => 'system',
                'priority' => 1,
                'icon' => '🔍',
            ],
            [
                'id' => 'help',
                'keys' => ['Ctrl', 'Shift', 'H'],
                'command' => 'help',
                'description' => 'Show keyboard shortcuts help',
                'category' => 'system',
                'priority' => 2,
                'icon' => '❓',
            ],
            [
                'id' => 'toggle_sidebar',
                'keys' => ['Ctrl', 'B'],
                'command' => 'toggle_sidebar',
                'description' => 'Toggle sidebar',
                'category' => 'system',
                'priority' => 3,
                'icon' => '📋',
            ],
            [
                'id' => 'toggle_dark_mode',
                'keys' => ['Ctrl', 'Shift', 'D'],
                'command' => 'toggle_dark_mode',
                'description' => 'Toggle dark mode',
                'category' => 'system',
                'priority' => 4,
                'icon' => '🌙',
            ],
        ],

        // Dashboard shortcuts
        'dashboard' => [
            [
                'id' => 'dashboard',
                'keys' => ['Ctrl', '0'],
                'command' => 'show dashboard',
                'description' => 'Go to dashboard',
                'category' => 'navigation',
                'priority' => 1,
                'icon' => '🏠',
            ],
            [
                'id' => 'urgent_items',
                'keys' => ['Ctrl', '1'],
                'command' => 'show urgent',
                'description' => 'Show urgent items',
                'category' => 'dashboard',
                'priority' => 2,
                'icon' => '🔥',
            ],
            [
                'id' => 'today_tasks',
                'keys' => ['Ctrl', '2'],
                'command' => 'show today',
                'description' => "Show today's tasks",
                'category' => 'dashboard',
                'priority' => 3,
                'icon' => '📅',
            ],
            [
                'id' => 'scheduled_items',
                'keys' => ['Ctrl', '3'],
                'command' => 'show scheduled',
                'description' => 'Show scheduled items',
                'category' => 'dashboard',
                'priority' => 4,
                'icon' => '📋',
            ],
            [
                'id' => 'all_tickets',
                'keys' => ['Ctrl', '4'],
                'command' => 'show tickets',
                'description' => 'Show all tickets',
                'category' => 'dashboard',
                'priority' => 5,
                'icon' => '🎫',
            ],
        ],

        // Creation shortcuts
        'creation' => [
            [
                'id' => 'create_ticket',
                'keys' => ['Ctrl', 'Shift', 'T'],
                'command' => 'create ticket',
                'description' => 'Create new ticket',
                'category' => 'creation',
                'priority' => 1,
                'icon' => '🎫',
            ],
            [
                'id' => 'create_client',
                'keys' => ['Ctrl', 'Shift', 'C'],
                'command' => 'create client',
                'description' => 'Create new client',
                'category' => 'creation',
                'priority' => 2,
                'icon' => '👥',
            ],
            [
                'id' => 'create_quote',
                'keys' => ['Ctrl', 'Shift', 'Q'],
                'command' => 'create quote',
                'description' => 'Create new quote',
                'category' => 'creation',
                'priority' => 3,
                'icon' => '📝',
            ],
            [
                'id' => 'create_invoice',
                'keys' => ['Ctrl', 'Shift', 'I'],
                'command' => 'create invoice',
                'description' => 'Create new invoice',
                'category' => 'creation',
                'priority' => 4,
                'icon' => '💰',
            ],
            [
                'id' => 'create_project',
                'keys' => ['Ctrl', 'Shift', 'P'],
                'command' => 'create project',
                'description' => 'Create new project',
                'category' => 'creation',
                'priority' => 5,
                'icon' => '📊',
            ],
        ],

        // Navigation shortcuts
        'navigation' => [
            [
                'id' => 'goto_clients',
                'keys' => ['Ctrl', 'Alt', 'C'],
                'command' => 'go to clients',
                'description' => 'Navigate to clients',
                'category' => 'navigation',
                'priority' => 1,
                'icon' => '👥',
            ],
            [
                'id' => 'goto_tickets',
                'keys' => ['Ctrl', 'Alt', 'T'],
                'command' => 'go to tickets',
                'description' => 'Navigate to tickets',
                'category' => 'navigation',
                'priority' => 2,
                'icon' => '🎫',
            ],
            [
                'id' => 'goto_assets',
                'keys' => ['Ctrl', 'Alt', 'A'],
                'command' => 'go to assets',
                'description' => 'Navigate to assets',
                'category' => 'navigation',
                'priority' => 3,
                'icon' => '🖥️',
            ],
            [
                'id' => 'goto_billing',
                'keys' => ['Ctrl', 'Alt', 'B'],
                'command' => 'go to billing',
                'description' => 'Navigate to billing',
                'category' => 'navigation',
                'priority' => 4,
                'icon' => '💰',
            ],
        ],
    ];

    /**
     * Get all shortcuts for the current user/context
     */
    public static function getActiveShortcuts(array $context = []): array
    {
        $user = auth()->user();
        $shortcuts = [];

        // Always include system shortcuts
        $shortcuts = array_merge($shortcuts, static::$shortcuts['system']);
        $shortcuts = array_merge($shortcuts, static::$shortcuts['dashboard']);
        $shortcuts = array_merge($shortcuts, static::$shortcuts['creation']);
        $shortcuts = array_merge($shortcuts, static::$shortcuts['navigation']);

        // Filter by permissions if needed
        $shortcuts = static::filterByPermissions($shortcuts, $user);

        // Sort by category and priority
        usort($shortcuts, function ($a, $b) {
            if ($a['category'] === $b['category']) {
                return $a['priority'] <=> $b['priority'];
            }

            return $a['category'] <=> $b['category'];
        });

        return $shortcuts;
    }

    /**
     * Get shortcuts formatted for JavaScript consumption
     */
    public static function getShortcutsForJs(array $context = []): array
    {
        $shortcuts = static::getActiveShortcuts($context);
        $jsShortcuts = [];

        foreach ($shortcuts as $shortcut) {
            $jsShortcuts[] = [
                'id' => $shortcut['id'],
                'keys' => $shortcut['keys'],
                'command' => $shortcut['command'],
                'description' => $shortcut['description'],
                'category' => $shortcut['category'],
                'keyString' => implode('+', $shortcut['keys']),
            ];
        }

        return $jsShortcuts;
    }

    /**
     * Get shortcuts formatted for command palette display
     */
    public static function getShortcutsForPalette(array $context = []): array
    {
        $shortcuts = static::getActiveShortcuts($context);
        $paletteShortcuts = [];

        foreach ($shortcuts as $shortcut) {
            // Skip system shortcuts that don't appear in palette
            if (in_array($shortcut['id'], ['command_palette', 'toggle_sidebar', 'toggle_dark_mode'])) {
                continue;
            }

            $paletteShortcuts[] = [
                'command' => $shortcut['command'],
                'icon' => $shortcut['icon'] ?? static::getIconForCategory($shortcut['category']),
                'description' => $shortcut['description'],
                'type' => 'shortcut',
                'shortcut' => implode('+', $shortcut['keys']),
                'category' => $shortcut['category'],
            ];
        }

        return $paletteShortcuts;
    }

    /**
     * Get help message with all shortcuts organized by category
     */
    public static function getHelpMessage(array $context = []): string
    {
        $shortcuts = static::getActiveShortcuts($context);
        $categorized = [];

        // Group by category
        foreach ($shortcuts as $shortcut) {
            $categorized[$shortcut['category']][] = $shortcut;
        }

        $helpText = "🚀 KEYBOARD SHORTCUTS\n\n";

        $categoryLabels = [
            'system' => '⚡ System',
            'dashboard' => '📊 Dashboard',
            'creation' => '➕ Create Items',
            'navigation' => '🧭 Navigation',
        ];

        foreach ($categoryLabels as $category => $label) {
            if (! isset($categorized[$category])) {
                continue;
            }

            $helpText .= "{$label}:\n";
            foreach ($categorized[$category] as $shortcut) {
                $keyString = implode('+', $shortcut['keys']);
                $helpText .= "• {$keyString} - {$shortcut['description']}\n";
            }
            $helpText .= "\n";
        }

        $helpText .= "💬 Voice Commands:\n";
        $helpText .= "• 'create [item]' - Create new items\n";
        $helpText .= "• 'go to [place]' - Navigate anywhere\n";
        $helpText .= "• 'show [items]' - View lists\n";
        $helpText .= "• 'find [query]' - Search anything\n\n";
        $helpText .= '⌨️ Tip: Use shortcuts for faster navigation and actions';

        return $helpText;
    }

    /**
     * Find shortcut by command
     */
    public static function findShortcutByCommand(string $command): ?array
    {
        $shortcuts = static::getActiveShortcuts();

        foreach ($shortcuts as $shortcut) {
            if ($shortcut['command'] === $command) {
                return $shortcut;
            }
        }

        return null;
    }

    /**
     * Filter shortcuts by user permissions
     */
    protected static function filterByPermissions(array $shortcuts, $user): array
    {
        // For now, return all shortcuts
        // Future: implement permission checking based on user roles
        return $shortcuts;
    }

    /**
     * Get icon for category
     */
    protected static function getIconForCategory(string $category): string
    {
        $icons = [
            'system' => '⚙️',
            'dashboard' => '📊',
            'creation' => '➕',
            'navigation' => '🧭',
        ];

        return $icons[$category] ?? '🔸';
    }

    /**
     * Validate shortcut key combination
     */
    public static function isValidShortcut(array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }

        $modifiers = ['Ctrl', 'Alt', 'Shift', 'Meta'];
        $hasModifier = false;

        foreach ($keys as $key) {
            if (in_array($key, $modifiers)) {
                $hasModifier = true;
                break;
            }
        }

        return $hasModifier; // Require at least one modifier
    }

    /**
     * Add custom shortcut (for future extensibility)
     */
    public static function addShortcut(array $shortcut): bool
    {
        if (! static::isValidShortcut($shortcut['keys'])) {
            return false;
        }

        $category = $shortcut['category'] ?? 'custom';

        if (! isset(static::$shortcuts[$category])) {
            static::$shortcuts[$category] = [];
        }

        static::$shortcuts[$category][] = $shortcut;

        return true;
    }
}
