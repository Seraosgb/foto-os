<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\DTOs\StoreReportDTO;
use App\Services\ReportService;
use App\Models\Report;
use App\Models\Company;
use App\Models\ReportStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportService $service): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id;

        if (empty($tenantId)) {
            $tenantId = Company::first()?->id;
        }

        $dto = new StoreReportDTO(
            osNumber: $request->validated('os_number'),
            unit: $request->validated('unit'),
            sectors: $request->validated('sectors'),
            history: $request->validated('history'),
            technicians: $request->validated('technicians')
        );

        $report = $service->createProgressiveReport($dto, (string) $tenantId);

        return response()->json([
            'message' => 'Relatório salvo com sucesso!',
            'data' => [
                'id' => $report->id,
                'os_number' => $report->os_number,
                'status' => $report->status?->slug ?? 'rascunho'
            ]
        ], 200);
    }

    public function searchByOs(Request $request): JsonResponse
    {
        $osNumber = trim((string) $request->query('os_number'));

        if (empty($osNumber)) {
            return response()->json(['found' => false], 200);
        }

        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id ?? Company::first()?->id;

        $report = Report::withoutGlobalScopes()
            ->where('company_id', $tenantId)
            ->where('os_number', $osNumber)
            ->with(['unit', 'sectors', 'status', 'photos'])
            ->first();

        if (!$report) {
            return response()->json(['found' => false], 200);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'id' => $report->id,
                'os_number' => $report->os_number,
                'unit' => $report->unit?->name ?? '',
                'sectors' => $report->sectors->pluck('name')->toArray(),
                'technicians' => $report->technicians ?? '',
                'history' => $report->history ?? '',
                'status_slug' => $report->status?->slug ?? 'rascunho',
                'status_name' => $report->status?->name ?? 'Rascunho',
                'photos' => $report->photos->map(fn($p) => [
                    'id' => $p->id,
                    'url' => asset('storage/' . $p->processed_path),
                    'observation' => $p->observation ?? ''
                ])
            ]
        ], 200);
    }

    public function reopen(Report $report): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id ?? Company::first()?->id;

        if ($report->company_id !== $tenantId) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $inProgressStatus = ReportStatus::where('company_id', $tenantId)
            ->where('slug', 'em-execucao')
            ->first() ?? ReportStatus::where('company_id', $tenantId)->where('slug', 'rascunho')->first();

        $report->update([
            'status_id' => $inProgressStatus?->id,
            'finalized_at' => null,
        ]);

        return response()->json([
            'message' => 'Ordem de serviço reaberta com sucesso!',
            'status_slug' => $inProgressStatus?->slug ?? 'em-execucao'
        ], 200);
    }
}
