<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Company;

/**
 * CompanyController
 * 
 * Handles company selection for multi-tenant functionality
 */
class CompanyController extends Controller
{
    /**
     * Show company selection page
     */
    public function select()
    {
        $user = Auth::user();
        
        // Get companies accessible to this user
        // Since users belong to a company, we'll get all companies for selection
        // You may want to restrict this based on your business logic
        $companies = Company::all();
        
        // Alternative: If users should only see their assigned company:
        // $companies = collect([$user->company])->filter();
        
        // If user only has access to one company, auto-select it
        if ($companies->count() === 1) {
            $company = $companies->first();
            Session::put('company_id', $company->id);
            
            return redirect()->route('dashboard')
                ->with('success', 'Company selected: ' . $company->name);
        }
        
        return view('company.select', compact('companies'));
    }
    
    /**
     * Set the selected company in session
     */
    public function setCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);
        
        $user = Auth::user();
        $companyId = $request->input('company_id');
        
        // Verify company exists
        $company = Company::find($companyId);
        
        if (!$company) {
            return back()->with('error', 'The selected company does not exist.');
        }
        
        // Additional access control can be added here based on your business logic
        
        // Set company in session
        Session::put('company_id', $company->id);
        
        \Log::info('CompanyController: User ' . $user->id . ' selected company: ' . $company->id);
        
        return redirect()->route('dashboard')
            ->with('success', 'Company selected: ' . $company->name);
    }
    
    /**
     * Switch to a different company
     */
    public function switch(Request $request)
    {
        Session::forget('company_id');
        return redirect()->route('company.select');
    }
}