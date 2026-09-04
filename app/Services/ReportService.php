<?php

namespace App\Services;

use App\DTOs\StoreReportDTO;
use App\Models\Report;
use App\Models\Unit;
use App\Models\Sector;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function createProgressiveReport(StoreReportDTO $dto, string $tenantId): Report
    {
        return DB::transaction(function () use ($dto, $tenantId) {

            // 1. Resolve a Unidade (Se não for UUID, cria na hora)
            $unitId = $dto->unit;
            if (!Str::isUuid($unitId)) {
                $unit = Unit::firstOrCreate(
                    ['company_id' => $tenantId, 'normalized_name' => Str::slug($unitId)],
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
            // Assumindo que você tem uma tabela report_statuses populada
            $status = DB::table('report_statuses')
                        ->where('company_id', $tenantId)
                        ->where('slug', 'rascunho')
                        ->first();

            // 4. Cria o Relatório
            $report = Report::create([
                'company_id' => $tenantId,
                'os_number' => $dto->osNumber,
                'unit_id' => $unitId,
                'status_id' => $status ? $status->id : null, // Idealmente o status sempre existirá
                'history' => $dto->history,
                'technicians' => $dto->technicians,
                'server_created_at' => now(),
            ]);

            // 5. Anexa os setores (Tabela pivô)
            // Lembre-se de ter a relação belongsToMany 'sectors' no model Report
            $report->sectors()->sync($sectorIds);

            return $report;
        });
    }
}
