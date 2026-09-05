<?php

namespace App\Services;

use App\DTOs\StoreReportDTO;
use App\Models\Report;
use App\Models\Unit;
use App\Models\Sector;
use App\Models\Company; // <--- Importe o Model Company
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function createProgressiveReport(StoreReportDTO $dto, ?string $tenantId = null): Report
    {
        // 🛡️ Fallback de segurança: Se o tenantId vier vazio, pega a primeira empresa do banco (evita erro 1452)
        if (empty($tenantId)) {
            $defaultCompany = Company::first();
            $tenantId = $defaultCompany ? $defaultCompany->id : null;
        }

        if (!$tenantId) {
            throw new \Exception('Nenhuma empresa/tenant encontrada para associar o registro.');
        }

        return DB::transaction(function () use ($dto, $tenantId) {

            // 1. Resolve a Unidade (Se não for UUID, cria na hora)
            $unitId = $dto->unit;
            if (!Str::isUuid($unitId)) {
                $unit = Unit::firstOrCreate(
                    ['company_id' => $tenantId, 'normalized_name' => Str::slug($dto->unit)],
                    ['name' => $dto->unit, 'active' => true]
                );
                $unitId = $unit->id;
            }

            // 2. Resolve os Setores (Cria na hora se for texto livre)
            $sectorIds = [];
            foreach ($dto->sectors as $sectorItem) {
                if (!Str::isUuid($sectorItem)) {
                    $sector = Sector::firstOrCreate(
                        ['company_id' => $tenantId, 'unit_id' => $unitId, 'normalized_name' => Str::slug($sectorItem)],
                        ['name' => $sectorItem, 'active' => true]
                    );
                    $sectorIds[] = $sector->id;
                } else {
                    $sectorIds[] = $sectorItem;
                }
            }

            // 3. Busca o Status Dinâmico Inicial (Rascunho)
            $status = DB::table('report_statuses')
                        ->where('company_id', $tenantId)
                        ->where('slug', 'rascunho')
                        ->first();

            // 4. Cria o Relatório
            $report = Report::create([
                'company_id' => $tenantId,
                'os_number' => $dto->osNumber,
                'unit_id' => $unitId,
                'status_id' => $status ? $status->id : null,
                'history' => $dto->history,
                'technicians' => $dto->technicians,
                'server_created_at' => now(),
            ]);

            // 5. Anexa os setores
            $report->sectors()->sync($sectorIds);

            return $report;
        });
    }
}
