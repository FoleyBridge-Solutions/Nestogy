<?php

namespace App\Domains\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShortcutsController extends Controller
{
    public function active()
    {
        $shortcuts = [
            [
                'key' => 'ctrl+s',
                'action' => 'save',
                'description' => 'Save quote'
            ],
            [
                'key' => 'ctrl+n',
                'action' => 'new',
                'description' => 'New quote'
            ],
            [
                'key' => '/',
                'action' => 'search',
                'description' => 'Focus search'
            ],
            [
                'key' => 'ctrl+d',
                'action' => 'duplicate',
                'description' => 'Duplicate quote'
            ],
            [
                'key' => 'escape',
                'action' => 'cancel',
                'description' => 'Cancel/Close'
            ]
        ];

        return response()->json([
            'shortcuts' => $shortcuts,
            'enabled' => true
        ]);
    }
}