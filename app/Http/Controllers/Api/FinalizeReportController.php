<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class FinalizeReportController extends Controller
{
    public function __invoke(Report $report, ReportPdfService $pdfService): JsonResponse
    {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id;

        // 🛡️ Fallback para quando o usuário não estiver logado
        if (empty($tenantId)) {
            $tenantId = $report->company_id ?? Company::first()?->id;
        }

        // 1. Blindagem Multi-Tenant
        if ($report->company_id !== $tenantId) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        // 2. Busca dinâmica do status 'finalizado'
        $finalizedStatus = DB::table('report_statuses')
            ->where('company_id', $tenantId)
            ->where('slug', 'finalizado')
            ->first();

        if (!$finalizedStatus) {
            return response()->json(['error' => 'Status finalizado não configurado para esta empresa.'], 500);
        }

        // 3. Trava de segurança: já finalizado?
        if ($report->status_id === $finalizedStatus->id) {
            return response()->json(['error' => 'Este relatório já foi finalizado.'], 422);
        }

        // 4. Trava de segurança: consulta direta das fotos ignorando scopes restritivos
        $photosCount = $report->photos()->withoutGlobalScopes()->count();

        if ($photosCount === 0) {
            return response()->json(['error' => 'O relatório precisa de pelo menos uma foto para ser finalizado.'], 422);
        }

        // 5. Atualiza o relatório
        $report->update([
            'status_id' => $finalizedStatus->id,
            'finalized_at' => now(),
        ]);

        // 6. Gera o PDF
        $pdfPath = $pdfService->generate($report);

        return response()->json([
            'message' => 'Relatório finalizado e documento gerado com sucesso!',
            'data' => [
                'id' => $report->id,
                'os_number' => $report->os_number,
                'pdf_url' => asset('storage/' . $pdfPath),
            ]
        ], 200);
    }
}
