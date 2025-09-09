<?php

namespace App\Livewire\Admin\Contract;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ContractClause;

class ClausesLibrary extends Component
{
    use WithPagination;
    
    public $search = '';
    public $categoryFilter = '';
    public $typeFilter = '';
    public $reviewFilter = '';
    public $requiredOnly = false;
    public $showModal = false;
    public $editingClause = null;
    
    // Form fields
    public $title = '';
    public $content = '';
    public $category = '';
    public $type = 'standard';
    public $is_required = false;
    public $review_status = 'pending';
    public $tags = [];
    
    protected $rules = [
        'title' => 'required|min:3',
        'content' => 'required|min:10',
        'category' => 'required',
        'type' => 'required|in:standard,legal,compliance,custom',
    ];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'reviewFilter' => ['except' => ''],
    ];
    
    public function mount()
    {
        // Initialize
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function createMSPClauses()
    {
        $mspClauses = [
            ['title' => 'Service Level Agreement', 'category' => 'service', 'type' => 'standard'],
            ['title' => 'Response Time Requirements', 'category' => 'service', 'type' => 'standard'],
            ['title' => 'Uptime Guarantee', 'category' => 'service', 'type' => 'standard'],
            ['title' => 'Remote Monitoring Terms', 'category' => 'technical', 'type' => 'standard'],
            ['title' => 'Backup and Recovery', 'category' => 'technical', 'type' => 'standard'],
        ];
        
        foreach ($mspClauses as $clause) {
            ContractClause::create([
                'title' => $clause['title'],
                'content' => 'Standard ' . $clause['title'] . ' clause content.',
                'category' => $clause['category'],
                'type' => $clause['type'],
                'review_status' => 'approved',
                'is_required' => false,
            ]);
        }
        
        session()->flash('success', 'MSP clauses created successfully.');
    }
    
    public function createLegalClauses()
    {
        $legalClauses = [
            ['title' => 'Limitation of Liability', 'category' => 'legal', 'type' => 'legal'],
            ['title' => 'Indemnification', 'category' => 'legal', 'type' => 'legal'],
            ['title' => 'Confidentiality', 'category' => 'legal', 'type' => 'legal'],
            ['title' => 'Force Majeure', 'category' => 'legal', 'type' => 'legal'],
            ['title' => 'Governing Law', 'category' => 'legal', 'type' => 'legal'],
        ];
        
        foreach ($legalClauses as $clause) {
            ContractClause::create([
                'title' => $clause['title'],
                'content' => 'Standard ' . $clause['title'] . ' legal provision.',
                'category' => $clause['category'],
                'type' => $clause['type'],
                'review_status' => 'approved',
                'is_required' => true,
            ]);
        }
        
        session()->flash('success', 'Legal boilerplate clauses created successfully.');
    }
    
    public function createComplianceClauses()
    {
        $complianceClauses = [
            ['title' => 'GDPR Compliance', 'category' => 'compliance', 'type' => 'compliance'],
            ['title' => 'HIPAA Requirements', 'category' => 'compliance', 'type' => 'compliance'],
            ['title' => 'PCI DSS Standards', 'category' => 'compliance', 'type' => 'compliance'],
            ['title' => 'Data Protection', 'category' => 'compliance', 'type' => 'compliance'],
        ];
        
        foreach ($complianceClauses as $clause) {
            ContractClause::create([
                'title' => $clause['title'],
                'content' => 'Standard ' . $clause['title'] . ' compliance requirements.',
                'category' => $clause['category'],
                'type' => $clause['type'],
                'review_status' => 'approved',
                'is_required' => true,
            ]);
        }
        
        session()->flash('success', 'Compliance clauses created successfully.');
    }
    
    public function importStandardLibrary()
    {
        // Import a comprehensive standard library
        $this->createMSPClauses();
        $this->createLegalClauses();
        $this->createComplianceClauses();
        
        session()->flash('success', 'Standard library imported successfully.');
    }
    
    public function openModal($clauseId = null)
    {
        if ($clauseId) {
            $this->editingClause = ContractClause::find($clauseId);
            $this->title = $this->editingClause->title;
            $this->content = $this->editingClause->content;
            $this->category = $this->editingClause->category;
            $this->type = $this->editingClause->type;
            $this->is_required = $this->editingClause->is_required;
            $this->review_status = $this->editingClause->review_status;
            $this->tags = $this->editingClause->tags ?? [];
        } else {
            $this->resetForm();
        }
        
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function resetForm()
    {
        $this->editingClause = null;
        $this->title = '';
        $this->content = '';
        $this->category = '';
        $this->type = 'standard';
        $this->is_required = false;
        $this->review_status = 'pending';
        $this->tags = [];
    }
    
    public function save()
    {
        $this->validate();
        
        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'type' => $this->type,
            'is_required' => $this->is_required,
            'review_status' => $this->review_status,
            'tags' => $this->tags,
        ];
        
        if ($this->editingClause) {
            $this->editingClause->update($data);
            session()->flash('success', 'Clause updated successfully.');
        } else {
            ContractClause::create($data);
            session()->flash('success', 'Clause created successfully.');
        }
        
        $this->closeModal();
    }
    
    public function deleteClause($clauseId)
    {
        $clause = ContractClause::find($clauseId);
        if ($clause) {
            $clause->delete();
            session()->flash('success', 'Clause deleted successfully.');
        }
    }
    
    public function toggleApproval($clauseId)
    {
        $clause = ContractClause::find($clauseId);
        if ($clause) {
            $clause->review_status = $clause->review_status === 'approved' ? 'pending' : 'approved';
            $clause->save();
        }
    }
    
    public function getCategories()
    {
        return [
            'service' => ['name' => 'Service Terms', 'icon' => 'server'],
            'payment' => ['name' => 'Payment Terms', 'icon' => 'credit-card'],
            'legal' => ['name' => 'Legal Terms', 'icon' => 'scale'],
            'technical' => ['name' => 'Technical Requirements', 'icon' => 'cpu-chip'],
            'compliance' => ['name' => 'Compliance', 'icon' => 'shield-check'],
            'termination' => ['name' => 'Termination', 'icon' => 'x-circle'],
        ];
    }
    
    public function render()
    {
        $clauses = ContractClause::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('content', 'like', '%'.$this->search.'%'))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->when($this->reviewFilter, fn($q) => $q->where('review_status', $this->reviewFilter))
            ->when($this->requiredOnly, fn($q) => $q->where('is_required', true))
            ->orderBy('category')
            ->orderBy('title')
            ->paginate(10);
        
        $stats = [
            'total' => ContractClause::count(),
            'approved' => ContractClause::where('review_status', 'approved')->count(),
            'pending' => ContractClause::where('review_status', 'pending')->count(),
            'required' => ContractClause::where('is_required', true)->count(),
        ];
        
        return view('livewire.admin.contract.clauses-library', [
            'clauses' => $clauses,
            'categories' => $this->getCategories(),
            'stats' => $stats,
        ]);
    }
}