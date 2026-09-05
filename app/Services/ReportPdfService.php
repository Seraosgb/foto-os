<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportPdfService
{
    public function generate(Report $report): string
    {
        // 1. Garante o carregamento dos relacionamentos blindados
        $report->load(['photos' => function ($query) {
            $query->orderBy('sequence', 'asc');
        }, 'unit', 'sectors']);

        // 2. Resolve a logomarca da empresa vinculada
        $company = $report->company;
        $logoBase64 = null;

        if ($company && $company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            $logoData = Storage::disk('public')->get($company->logo_path);
            $mime = Storage::disk('public')->mimeType($company->logo_path);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoData);
        }

        // 3. Renderiza o HTML estático via DomPDF
        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'company' => $company,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait')
          ->setWarnings(false);

        // 4. Salva no disco local com UUID para evitar adivinhação
        $fileName = 'reports/' . Str::uuid() . '.pdf';

        if (!Storage::disk('public')->exists('reports')) {
            Storage::disk('public')->makeDirectory('reports');
        }

        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
