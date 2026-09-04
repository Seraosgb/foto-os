<?php

namespace App\DTOs;

readonly class StoreReportDTO
{
    public function __construct(
        public string $osNumber,
        public string $unit, // Pode ser um UUID ou o nome de uma nova unidade
        public array $sectors, // Array de UUIDs ou nomes de novos setores
        public ?string $history,
        public ?string $technicians
    ) {}
}
