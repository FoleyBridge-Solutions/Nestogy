<?php

namespace App\Domains\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Marketing\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('marketing.templates.index-livewire');
    }

    public function create(): View
    {
        return view('marketing.templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:marketing,transactional,follow_up,onboarding,notification',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create([
            ...$validated,
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('marketing.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(EmailTemplate $template): View
    {
        $this->authorize('update', $template);

        return view('marketing.templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:marketing,transactional,follow_up,onboarding,notification',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('marketing.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(EmailTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('marketing.templates.index')
            ->with('success', 'Template deleted successfully.');
    }
}
