<?php

namespace App\DTOs;

readonly class ProcessedPhotoDTO
{
    public function __construct(
        public string $originalPath,
        public string $processedPath
    ) {}

    public function toArray(): array
    {
        return [
            'original_path' => $this->originalPath,
            'processed_path' => $this->processedPath,
        ];
    }
}
