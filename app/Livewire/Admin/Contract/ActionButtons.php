<?php

namespace App\Livewire\Admin\Contract;

use App\Models\ContractActionButton;
use Livewire\Component;

class ActionButtons extends Component
{
    public $actionButtons;

    public $enableSorting = false;

    public $showModal = false;

    public $editingButton = null;

    protected $listeners = ['refreshButtons' => '$refresh'];

    public function mount()
    {
        $this->loadButtons();
    }

    public function loadButtons()
    {
        $this->actionButtons = ContractActionButton::orderBy('sort_order')->get();
    }

    public function createDefaultButtons()
    {
        // Create default action buttons
        $defaults = [
            ['label' => 'Approve', 'icon' => 'check-circle', 'action_type' => 'approve', 'slug' => 'approve'],
            ['label' => 'Reject', 'icon' => 'x-circle', 'action_type' => 'reject', 'slug' => 'reject'],
            ['label' => 'Send for Review', 'icon' => 'paper-airplane', 'action_type' => 'review', 'slug' => 'send-review'],
            ['label' => 'Download PDF', 'icon' => 'arrow-down-tray', 'action_type' => 'download', 'slug' => 'download-pdf'],
            ['label' => 'Email', 'icon' => 'envelope', 'action_type' => 'email', 'slug' => 'email'],
        ];

        foreach ($defaults as $index => $button) {
            ContractActionButton::create([
                'label' => $button['label'],
                'icon' => $button['icon'],
                'action_type' => $button['action_type'],
                'slug' => $button['slug'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        $this->loadButtons();
        session()->flash('success', 'Default action buttons created successfully.');
    }

    public function editButton($buttonId)
    {
        $this->editingButton = ContractActionButton::find($buttonId);
        $this->showModal = true;
    }

    public function previewButton($buttonId)
    {
        // Preview logic
        $button = ContractActionButton::find($buttonId);
        $this->dispatch('show-preview', button: $button);
    }

    public function deleteButton($buttonId)
    {
        $button = ContractActionButton::find($buttonId);
        if ($button) {
            $button->delete();
            $this->loadButtons();
            session()->flash('success', 'Action button deleted successfully.');
        }
    }

    public function updateSortOrder($items)
    {
        foreach ($items as $index => $item) {
            ContractActionButton::where('id', $item['value'])->update(['sort_order' => $index]);
        }
        $this->loadButtons();
    }

    public function render()
    {
        return view('livewire.admin.contract.action-buttons');
    }
}
