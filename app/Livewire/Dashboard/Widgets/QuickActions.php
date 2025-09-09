<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class QuickActions extends Component
{
    public string $view = 'executive';
    public array $actions = [];
    
    public function mount(string $view = 'executive')
    {
        $this->view = $view;
        $this->loadActions();
    }
    
    public function loadActions()
    {
        $user = Auth::user();
        
        $actionSets = [
            'executive' => [
                [
                    'title' => 'Create Invoice',
                    'description' => 'Generate a new invoice for a client',
                    'icon' => 'document-plus',
                    'color' => 'blue',
                    'route' => 'invoices.create',
                    'permission' => 'create-invoices',
                ],
                [
                    'title' => 'New Client',
                    'description' => 'Add a new client to the system',
                    'icon' => 'user-plus',
                    'color' => 'green',
                    'route' => 'clients.create',
                    'permission' => 'create-clients',
                ],
                [
                    'title' => 'Export Report',
                    'description' => 'Generate business reports',
                    'icon' => 'arrow-down-tray',
                    'color' => 'purple',
                    'action' => 'exportReports',
                    'permission' => 'view-reports',
                ],
                [
                    'title' => 'Team Overview',
                    'description' => 'View team performance metrics',
                    'icon' => 'user-group',
                    'color' => 'orange',
                    'route' => 'team.index',
                    'permission' => 'view-team',
                ],
            ],
            'operations' => [
                [
                    'title' => 'Create Ticket',
                    'description' => 'Open a new support ticket',
                    'icon' => 'plus-circle',
                    'color' => 'blue',
                    'route' => 'tickets.create',
                    'permission' => 'create-tickets',
                ],
                [
                    'title' => 'Asset Check',
                    'description' => 'Monitor client assets',
                    'icon' => 'server',
                    'color' => 'green',
                    'route' => 'assets.index',
                    'permission' => 'view-assets',
                ],
                [
                    'title' => 'Remote Access',
                    'description' => 'Connect to client systems',
                    'icon' => 'globe-alt',
                    'color' => 'red',
                    'action' => 'remoteAccess',
                    'permission' => 'remote-access',
                ],
                [
                    'title' => 'Knowledge Base',
                    'description' => 'Search solutions',
                    'icon' => 'academic-cap',
                    'color' => 'purple',
                    'route' => 'knowledge.index',
                    'permission' => 'view-knowledge',
                ],
            ],
            'financial' => [
                [
                    'title' => 'Create Invoice',
                    'description' => 'Generate a new invoice',
                    'icon' => 'document-text',
                    'color' => 'blue',
                    'route' => 'invoices.create',
                    'permission' => 'create-invoices',
                ],
                [
                    'title' => 'Record Payment',
                    'description' => 'Log a payment received',
                    'icon' => 'currency-dollar',
                    'color' => 'green',
                    'route' => 'payments.create',
                    'permission' => 'create-payments',
                ],
                [
                    'title' => 'Collections',
                    'description' => 'Manage overdue accounts',
                    'icon' => 'exclamation-triangle',
                    'color' => 'orange',
                    'route' => 'collections.index',
                    'permission' => 'view-collections',
                ],
                [
                    'title' => 'Financial Reports',
                    'description' => 'Generate financial reports',
                    'icon' => 'chart-pie',
                    'color' => 'purple',
                    'route' => 'reports.financial',
                    'permission' => 'view-financial-reports',
                ],
            ],
            'support' => [
                [
                    'title' => 'My Tickets',
                    'description' => 'View assigned tickets',
                    'icon' => 'ticket',
                    'color' => 'blue',
                    'route' => 'tickets.my',
                    'permission' => 'view-own-tickets',
                ],
                [
                    'title' => 'Time Entry',
                    'description' => 'Log time worked',
                    'icon' => 'clock',
                    'color' => 'green',
                    'route' => 'time.create',
                    'permission' => 'create-time-entries',
                ],
                [
                    'title' => 'Client Portal',
                    'description' => 'Access client portal',
                    'icon' => 'building-office',
                    'color' => 'purple',
                    'action' => 'clientPortal',
                    'permission' => 'access-client-portal',
                ],
                [
                    'title' => 'Documentation',
                    'description' => 'Browse documentation',
                    'icon' => 'book-open',
                    'color' => 'orange',
                    'route' => 'documentation.index',
                    'permission' => 'view-documentation',
                ],
            ],
        ];
        
        $actions = $actionSets[$this->view] ?? $actionSets['executive'];
        
        // Filter actions based on permissions and route availability
        $this->actions = collect($actions)->filter(function ($action) use ($user) {
            // Check permissions
            if (isset($action['permission']) && !$user->can($action['permission'])) {
                return false;
            }
            
            // Check if route exists
            if (isset($action['route'])) {
                try {
                    route($action['route']);
                } catch (\Exception $e) {
                    // Route doesn't exist, skip this action
                    return false;
                }
            }
            
            return true;
        })->values()->toArray();
    }
    
    public function executeAction($actionKey)
    {
        $action = collect($this->actions)->firstWhere('action', $actionKey);
        
        if (!$action) {
            return;
        }
        
        switch ($actionKey) {
            case 'exportReports':
                $this->dispatch('export-reports');
                break;
                
            case 'remoteAccess':
                $this->dispatch('open-remote-access');
                break;
                
            case 'clientPortal':
                $this->dispatch('open-client-portal');
                break;
                
            default:
                $this->dispatch('quick-action-executed', ['action' => $actionKey]);
                break;
        }
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.quick-actions');
    }
}