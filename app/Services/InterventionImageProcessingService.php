<?php

namespace App\Services;

use App\Services\Contracts\ImageProcessingServiceInterface;
use Illuminate\Http\UploadedFile;
use App\DTOs\ProcessedPhotoDTO;
use App\DTOs\AddressDTO;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class InterventionImageProcessingService implements ImageProcessingServiceInterface
{
    public function processAndWatermark(
        UploadedFile $file,
        string $osNumber,
        string $timestamp,
        float $lat,
        float $lng,
        ?AddressDTO $address
    ): ProcessedPhotoDTO {
        $uuid = Str::uuid()->toString();
        $filename = "{$uuid}.jpg";

        $originalDir = 'photos/originals';
        $processedDir = 'photos/processed';

        // 1. Salva a imagem original no disco
        $originalPath = $file->storeAs($originalDir, $filename, 'public');

        // 2. Inicia o manipulador de imagem (Sintaxe Exclusiva V2)
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($file->getRealPath());

        // 3. Redimensiona proporcionalmente para no máximo 1280px (Sintaxe V2)
        $image->resize(1280, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize(); // Impede que imagens menores sejam esticadas
        });

        // 4. Monta as linhas de evidência da Marca D'água
        $addressText = $address ? $address->formattedAddress : 'Endereço indisponível ou offline';
        $lines = [
            "OS: {$osNumber} | Data: {$timestamp}",
            "GPS: {$lat}, {$lng}",
            "Local: {$addressText}"
        ];

        // 5. Escreve as linhas na imagem (Margem de 30px)
        $y = 30;
        foreach ($lines as $line) {
            $image->text($line, 30, $y, function ($font) {
                $font->file(5); // Fonte embutida no GD
                $font->color('#FFCC00');
            });
            $y += 30;
        }

        // 6. Salva a imagem processada no disco
        $processedFullPath = storage_path("app/public/{$processedDir}/{$filename}");

        // Garante que o diretório processado exista
        if (!file_exists(storage_path("app/public/{$processedDir}"))) {
            mkdir(storage_path("app/public/{$processedDir}"), 0755, true);
        }

        // Qualidade 80 (Sintaxe V2)
        $image->save($processedFullPath, 80);
        $processedPath = "{$processedDir}/{$filename}";

        return new ProcessedPhotoDTO($originalPath, $processedPath);
    }
}
