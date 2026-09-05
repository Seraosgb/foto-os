<?php

namespace App\Services;

use App\DTOs\StoreReportDTO;
use App\Models\Report;
use App\Models\Unit;
use App\Models\Sector;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function createProgressiveReport(StoreReportDTO $dto, ?string $tenantId = null): Report
    {
        if (empty($tenantId)) {
            $defaultCompany = Company::first();
            $tenantId = $defaultCompany ? $defaultCompany->id : null;
        }

        if (!$tenantId) {
            throw new \Exception('Nenhuma empresa/tenant encontrada para associar o registro.');
        }

        return DB::transaction(function () use ($dto, $tenantId) {
            // 1. Resolve a Unidade
            $unitId = $dto->unit;
            if (!Str::isUuid($unitId)) {
                $unit = Unit::firstOrCreate(
                    ['company_id' => $tenantId, 'normalized_name' => Str::slug($dto->unit)],
                    ['name' => $dto->unit, 'active' => true]
                );
                $unitId = $unit->id;
            }

            // 2. Resolve os Setores
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

            // 3. Status Rascunho / Em Execução
            $status = DB::table('report_statuses')
                ->where('company_id', $tenantId)
                ->where('slug', 'rascunho')
                ->first();

            // 4. Cria ou Atualiza (Idempotência por OS)
            $report = Report::withoutGlobalScopes()
                ->where('company_id', $tenantId)
                ->where('os_number', $dto->osNumber)
                ->first();

            if ($report) {
                $report->update([
                    'unit_id' => $unitId,
                    'history' => $dto->history,
                    'technicians' => $dto->technicians,
                ]);
            } else {
                $report = Report::create([
                    'company_id' => $tenantId,
                    'os_number' => $dto->osNumber,
                    'unit_id' => $unitId,
                    'status_id' => $status ? $status->id : null,
                    'history' => $dto->history,
                    'technicians' => $dto->technicians,
                    'server_created_at' => now(),
                ]);
            }

            // 5. Atualiza setores pivô
            $report->sectors()->sync($sectorIds);

            return $report;
        });
    }
}
