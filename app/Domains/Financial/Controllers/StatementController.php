<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    public function index(Request $request)
    {
        return view('financial.statements.index');
    }
}
