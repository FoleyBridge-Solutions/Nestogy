<?php

namespace App\Domains\Core\Controllers;

use App\Domains\Core\Services\ShortcutService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortcutController extends Controller
{
    /**
     * Get active shortcuts for current user/context
     */
    public function getActiveShortcuts(Request $request): JsonResponse
    {
        $context = [
            'client_id' => session('selected_client_id'),
            'domain' => $request->get('domain'),
            'workflow' => session('workflow_context'),
            'user_id' => auth()->id(),
        ];

        $shortcuts = ShortcutService::getShortcutsForJs($context);

        return response()->json([
            'shortcuts' => $shortcuts,
            'context' => $context,
        ]);
    }

    /**
     * Execute shortcut command
     */
    public function executeShortcutCommand(Request $request): JsonResponse
    {
        $request->validate([
            'command' => 'required|string|max:100',
        ]);

        $context = [
            'client_id' => session('selected_client_id'),
            'domain' => $request->get('domain'),
            'workflow' => session('workflow_context'),
            'user_id' => auth()->id(),
        ];

        // Handle system commands directly
        $systemCommands = [
            'toggle_sidebar' => ['action' => 'toggle_sidebar'],
            'toggle_dark_mode' => ['action' => 'toggle_dark_mode'],
            'open_command_palette' => ['action' => 'open_command_palette'],
        ];

        if (isset($systemCommands[$request->command])) {
            return response()->json($systemCommands[$request->command]);
        }

        // For other commands, delegate to NavigationController
        $navigationController = new NavigationController;

        // Create a new request for the navigation controller
        $commandRequest = new Request(['command' => $request->command]);
        $commandRequest->merge($request->all());

        return $navigationController->executeCommand($commandRequest);
    }

    /**
     * Get help information for shortcuts
     */
    public function getShortcutHelp(Request $request): JsonResponse
    {
        $context = [
            'client_id' => session('selected_client_id'),
            'domain' => $request->get('domain'),
            'workflow' => session('workflow_context'),
            'user_id' => auth()->id(),
        ];

        $helpMessage = ShortcutService::getHelpMessage($context);
        $shortcuts = ShortcutService::getActiveShortcuts($context);

        return response()->json([
            'action' => 'help',
            'message' => $helpMessage,
            'shortcuts' => $shortcuts,
        ]);
    }
}
