<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ReportStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria a Empresa Matriz (Gerando UUID manualmente para usar nas filhas)
        $companyId = (string) Str::uuid();

        Company::create([
            'id' => $companyId,
            'name' => 'Manserv Facilities',
            'logo_path' => null,
        ]);

        // 2. Injeta os Status Dinâmicos da OS para essa empresa
        $statuses = [
            ['name' => 'Rascunho', 'slug' => 'rascunho'],
            ['name' => 'Em Execução', 'slug' => 'em-execucao'],
            ['name' => 'Finalizado', 'slug' => 'finalizado'],
        ];

        foreach ($statuses as $status) {
            ReportStatus::create([
                'company_id' => $companyId,
                'name' => $status['name'],
                'slug' => $status['slug'],
            ]);
        }
    }
}
