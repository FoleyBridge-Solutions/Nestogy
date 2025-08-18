@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="w-full px-4 px-4 py-4">
    <div class="flex flex-wrap -mx-4 justify-center">
        <div class="md:w-2/3 px-4 col-lg-6">
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm">
                <div class="p-6 text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x text-warning"></i>
                    </div>
                    
                    <h1 class="h3 mb-3">Access Denied</h1>
                    <p class="text-gray-600 mb-4">
                        Sorry, you don't have permission to access this resource.
                    </p>
                    
                    <div class="flex gap-2 justify-center">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Go Back
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection