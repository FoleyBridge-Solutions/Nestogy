<?php

namespace App\Domains\Product\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('company_id', auth()->user()->company_id)
            ->select('id', 'name')
            ->get();

        return response()->json($categories);
    }
}