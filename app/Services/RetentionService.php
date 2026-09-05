<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetentionService
{
    public function purge(): array
    {
        $purgedDraftsCount = 0;
        $purgedOriginalPhotosCount = 0;
        $archivedReportsCount = 0;

        $disk = Storage::disk('public');

        // 1. EXPURGO DE FOTOS ORIGINAIS COM MAIS DE 60 DIAS (Mantém a processada)
        $oldPhotos = Photo::where('created_at', '<=', now()->subDays(60))
            ->whereNotNull('original_path')
            ->get();

        foreach ($oldPhotos as $photo) {
            if ($photo->original_path && $disk->exists($photo->original_path)) {
                $disk->delete($photo->original_path);
            }
            $photo->update(['original_path' => null]);
            $purgedOriginalPhotosCount++;
        }

        // 2. EXPURGO DE RASCUNHOS ABANDONADOS (> 30 dias sem adiamento)
        $draftsToPurge = Report::withoutGlobalScopes()
            ->whereHas('status', function ($q) {
                $q->whereIn('slug', ['rascunho', 'em-execucao']);
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('retained_until')
                      ->where('server_created_at', '<=', now()->subDays(30));
                })->orWhere(function ($q) {
                    $q->whereNotNull('retained_until')
                      ->where('retained_until', '<=', now());
                });
            })
            ->with(['photos'])
            ->get();

        foreach ($draftsToPurge as $draft) {
            DB::transaction(function () use ($draft, $disk, &$purgedDraftsCount) {
                foreach ($draft->photos as $photo) {
                    if ($photo->original_path && $disk->exists($photo->original_path)) {
                        $disk->delete($photo->original_path);
                    }
                    if ($photo->processed_path && $disk->exists($photo->processed_path)) {
                        $disk->delete($photo->processed_path);
                    }
                    $photo->delete();
                }
                $draft->sectors()->detach();
                $draft->delete();
                $purgedDraftsCount++;
            });
        }

        // 3. SOFT CLEAN DE RELATÓRIOS FINALIZADOS COM MAIS DE 365 DIAS
        $reportsToArchive = Report::withoutGlobalScopes()
            ->where('is_archived', false)
            ->whereHas('status', function ($q) {
                $q->where('slug', 'finalizado');
            })
            ->where('finalized_at', '<=', now()->subDays(365))
            ->with('photos')
            ->get();

        foreach ($reportsToArchive as $report) {
            DB::transaction(function () use ($report, $disk, &$archivedReportsCount) {
                foreach ($report->photos as $photo) {
                    if ($photo->original_path && $disk->exists($photo->original_path)) {
                        $disk->delete($photo->original_path);
                    }
                    if ($photo->processed_path && $disk->exists($photo->processed_path)) {
                        $disk->delete($photo->processed_path);
                    }
                }

                $report->update(['is_archived' => true]);
                $archivedReportsCount++;
            });
        }

        $summary = [
            'purged_drafts' => $purgedDraftsCount,
            'purged_original_photos' => $purgedOriginalPhotosCount,
            'archived_reports' => $archivedReportsCount,
            'executed_at' => now()->format('d/m/Y H:i:s'),
        ];

        Log::info('FotoOS: Rotina de retenção e expurgo finalizada com sucesso.', $summary);

        return $summary;
    }

    public function postponeDraft(Report $report, int $days = 30): bool
    {
        $currentDate = $report->draft_expiration_date;
        $newExpiration = $currentDate->isPast() ? now()->addDays($days) : $currentDate->addDays($days);

        return $report->update([
            'retained_until' => $newExpiration,
        ]);
    }
}
