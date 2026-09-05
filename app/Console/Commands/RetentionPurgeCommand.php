<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetentionService;

class RetentionPurgeCommand extends Command
{
    protected $signature = 'app:retention-purge';
    protected $description = 'Executa a política de retenção documental e expurgo de mídias do FotoOS';

    public function handle(RetentionService $service): int
    {
        $this->info('Iniciando checagem de retenção e expurgo...');

        $res = $service->purge();

        $this->table(
            ['Métrica Operacional', 'Quantidade Processada'],
            [
                ['Rascunhos Excluídos (>30 dias)', $res['purged_drafts']],
                ['Fotos Originais Descartadas (>60 dias)', $res['purged_original_photos']],
                ['Relatórios Arquivados (>365 dias)', $res['archived_reports']],
                ['Executado em', $res['executed_at']],
            ]
        );

        $this->info('Políticas de expurgo aplicadas com sucesso!');
        return self::SUCCESS;
    }
}
