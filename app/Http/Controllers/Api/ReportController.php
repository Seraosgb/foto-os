<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\DTOs\StoreReportDTO;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportService $service): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id;

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
