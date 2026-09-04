<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\DTOs\StoreReportDTO;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use App\Models\Company;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportService $service): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id;

        // 🛡️ Fallback para quando o usuário não estiver logado
        if (empty($tenantId)) {
            $tenantId = Company::first()?->id;
        }

        if ($report->company_id !== $tenantId) {
            return response()->json(['error' => 'Acesso negado a este relatório.'], 403);
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
            'message' => 'Relatório iniciado com sucesso!',
            'data' => [
                'id' => $report->id, // O abençoado UUID que usaremos no upload de fotos!
                'os_number' => $report->os_number,
                'status' => 'rascunho'
            ]
        ], 201);
    }
}
