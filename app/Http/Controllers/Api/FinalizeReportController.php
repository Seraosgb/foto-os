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
            $tenantId = Company::first()?->id;
        }

        // 1. Blindagem Multi-Tenant
        if ($report->company_id !== $tenantId) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        // 2. Busca o ID do status 'Finalizado' no banco de forma dinâmica
        $finalizedStatus = DB::table('report_statuses')
            ->where('company_id', $tenantId)
            ->where('slug', 'finalizado')
            ->first();

        // 3. Trava de segurança: já está finalizado?
        if ($report->status_id === $finalizedStatus->id) {
            return response()->json(['error' => 'Este relatório já foi finalizado.'], 422);
        }

        // 4. Trava de segurança: tem foto?
        if ($report->photos()->count() === 0) {
            return response()->json(['error' => 'O relatório precisa de pelo menos uma foto para ser finalizado.'], 422);
        }

        // 5. Atualiza o banco de dados
        $report->update([
            'status_id' => $finalizedStatus->id,
            'finalized_at' => now(),
        ]);

        // 6. Chama o motor para gerar o PDF físico no servidor
        $pdfPath = $pdfService->generate($report);

        return response()->json([
            'message' => 'Relatório finalizado e documento gerado com sucesso!',
            'data' => [
                'id' => $report->id,
                'os_number' => $report->os_number,
                'pdf_url' => asset('storage/' . $pdfPath), // Link direto para o arquivo final
            ]
        ], 200);
    }
}
