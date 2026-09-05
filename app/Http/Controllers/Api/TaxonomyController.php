<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Sector;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function units(Request $request): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id ?? Company::first()?->id;

        $units = Unit::where('company_id', $tenantId)
            ->where('active', true)
            ->with(['sectors' => function ($q) {
                $q->where('active', true)->select('id', 'unit_id', 'name');
            }])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($units, 200);
    }
}
