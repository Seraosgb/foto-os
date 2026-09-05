<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Report;
use App\Models\Unit;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = auth()->user()?->company_id ?? Company::first()?->id;

        $reports = Report::withoutGlobalScopes()
            ->where('company_id', $tenantId)
            ->with(['unit', 'status'])
            ->latest('server_created_at')
            ->paginate(15);

        $units = Unit::where('company_id', $tenantId)->withCount('sectors')->get();

        return view('admin.dashboard', compact('reports', 'units'));
    }
}
