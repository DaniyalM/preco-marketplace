<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Render the vendor dashboard page. Data is loaded by the frontend via GET /api/vendor/dashboard.
     */
    public function index(Request $request)
    {
        return Inertia::render('Vendor/Dashboard');
    }
}
