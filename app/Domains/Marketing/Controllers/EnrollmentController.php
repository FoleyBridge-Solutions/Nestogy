<?php

namespace App\Domains\Marketing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        return view('marketing.enrollments.index-livewire');
    }
}
