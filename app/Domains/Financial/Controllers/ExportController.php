<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function index()
    {
        // TODO: Implement export options page
        return view('financial.export.index', [
            'exportTypes' => [
                'invoices' => 'Invoices',
                'quotes' => 'Quotes',
                'payments' => 'Payments',
                'expenses' => 'Expenses',
                'reports' => 'Financial Reports'
            ]
        ]);
    }

    public function exportInvoices(Request $request)
    {
        // TODO: Implement invoice export logic
        return response()->download('invoices.csv');
    }

    public function exportQuotes(Request $request)
    {
        // TODO: Implement quote export logic
        return response()->download('quotes.csv');
    }

    public function exportPayments(Request $request)
    {
        // TODO: Implement payment export logic
        return response()->download('payments.csv');
    }

    public function exportExpenses(Request $request)
    {
        // TODO: Implement expense export logic
        return response()->download('expenses.csv');
    }

    public function exportReports(Request $request)
    {
        // TODO: Implement financial reports export logic
        return response()->download('reports.csv');
    }
}