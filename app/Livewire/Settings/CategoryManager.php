<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use Livewire\Component;

class CategoryManager extends Component
{
    public $typeFilter = 'all';
    public $search = '';
    public $showModal = false;
    public $editing = null;
    public $form = [
        'name' => '',
        'type' => '',
        'parent_id' => null,
        'description' => '',
        'color' => '',
        'icon' => '',
        'is_active' => true,
        'metadata' => [],
    ];

    protected $queryString = ['typeFilter', 'search'];

    public function mount()
    {
        // Initialize form with default values
        $this->resetForm();
    }

    public function render()
    {
        $query = Category::where('company_id', auth()->user()->company_id)
            ->with('parent');

        // Apply type filter
        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        // Apply search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        $categories = $query->ordered()->get();
        $types = Category::TYPE_LABELS;
        $parentOptions = Category::where('company_id', auth()->user()->company_id)
            ->when($this->editing, fn($q) => $q->where('id', '!=', $this->editing))
            ->active()
            ->ordered()
            ->get();

        return view('livewire.settings.category-manager', [
            'categories' => $categories,
            'types' => $types,
            'parentOptions' => $parentOptions,
        ])->layout('components.layouts.app', [
            'sidebarContext' => 'settings'
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->editing = null;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $category = Category::where('company_id', auth()->user()->company_id)->findOrFail($id);

        $this->editing = $category->id;
        $this->form = [
            'name' => $category->name,
            'type' => $category->type,
            'parent_id' => $category->parent_id,
            'description' => $category->description ?? '',
            'color' => $category->color ?? '',
            'icon' => $category->icon ?? '',
            'is_active' => $category->is_active,
            'metadata' => $category->metadata ?? [],
        ];

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.type' => 'required|in:' . implode(',', array_keys(Category::TYPE_LABELS)),
            'form.parent_id' => 'nullable|exists:categories,id',
            'form.description' => 'nullable|string',
            'form.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'form.icon' => 'nullable|string|max:100',
            'form.is_active' => 'boolean',
        ]);

        $data = $this->form;
        $data['company_id'] = auth()->user()->company_id;

        if ($this->editing) {
            $category = Category::where('company_id', auth()->user()->company_id)
                ->findOrFail($this->editing);
            $category->update($data);
            $message = 'Category updated successfully.';
        } else {
            Category::create($data);
            $message = 'Category created successfully.';
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $message);
    }

    public function delete($id)
    {
        $category = Category::where('company_id', auth()->user()->company_id)->findOrFail($id);

        try {
            $category->delete();
            session()->flash('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        $category = Category::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'type' => '',
            'parent_id' => null,
            'description' => '',
            'color' => '',
            'icon' => '',
            'is_active' => true,
            'metadata' => [],
        ];
        $this->editing = null;
    }
}
