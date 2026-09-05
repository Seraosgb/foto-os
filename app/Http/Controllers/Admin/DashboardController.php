<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Report;
use App\Models\Unit;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\RetentionService;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id ?? Company::first()?->id;
        $company = Company::find($tenantId) ?? Company::first();

        $query = Report::withoutGlobalScopes()
            ->where('company_id', $tenantId)
            ->with(['unit', 'status', 'sectors'])
            ->latest('server_created_at');

        if ($request->filled('os_number')) {
            $query->where('os_number', 'like', '%' . trim($request->os_number) . '%');
        }

        $reports = $query->paginate(15)->withQueryString();
        $units = Unit::where('company_id', $tenantId)->withCount('sectors')->get();

        return view('admin.dashboard', compact('reports', 'units', 'company'));
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id ?? Company::first()?->id;
        $company = Company::findOrFail($tenantId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
        ]);

        $data = ['name' => $validated['name']];

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        $company->update($data);

        return back()->with('success', 'Configurações da empresa atualizadas com sucesso!');
    }

    public function toggleUnit(Unit $unit): RedirectResponse
    {
        $unit->update(['active' => !$unit->active]);
        return back()->with('success', 'Status da unidade alterado com sucesso!');
    }
    public function runRetentionPurge(RetentionService $retentionService): RedirectResponse
{
    $result = $retentionService->purge();

    $msg = "Expurgo executado: {$result['purged_drafts']} rascunhos limpos, {$result['purged_original_photos']} fotos originais removidas, {$result['archived_reports']} relatórios antigos arquivados.";

    return back()->with('success', $msg);
}

public function postponePurge(Report $report, RetentionService $retentionService): RedirectResponse
{
    $retentionService->postponeDraft($report, 30);

    return back()->with('success', "Expurgo da OS {$report->os_number} adiado com sucesso em +30 dias!");
}
}
