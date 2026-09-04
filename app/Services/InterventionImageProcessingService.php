<?php

namespace App\Services;

use App\Services\Contracts\ImageProcessingServiceInterface;
use Illuminate\Http\UploadedFile;
use App\DTOs\ProcessedPhotoDTO;
use App\DTOs\AddressDTO;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

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

        // 2. Instancia o manager com a classe real do driver exigida na nova arquitetura
        $manager = new ImageManager(GdDriver::class);

        // 3. Lê o arquivo temporário (Fallback Dinâmico de Métodos)
        if (method_exists($manager, 'read')) {
            $image = $manager->read($file->getPathname());
        } elseif (method_exists($manager, 'make')) {
            $image = $manager->make($file->getPathname());
        } else {
            $image = $manager->load($file->getPathname());
        }

        // 4. Redimensiona proporcionalmente para no máximo 1280px
        if (method_exists($image, 'scaleDown')) {
            $image->scaleDown(1280);
        } else {
            $image->resize(1280, null, function ($constraint) {
                $constraint->aspectRatio();
                if (method_exists($constraint, 'upsize')) {
                    $constraint->upsize();
                }
            });
        }

        // 5. Monta as linhas de evidência da Marca D'água
        $addressText = $address ? $address->formattedAddress : 'Endereço indisponível ou offline';
        $lines = [
            "OS: {$osNumber} | Data: {$timestamp}",
            "GPS: {$lat}, {$lng}",
            "Local: {$addressText}"
        ];

        // 6. Escreve as linhas na imagem
        $y = 30;
        foreach ($lines as $line) {
            $image->text($line, 30, $y, function ($font) {
                if (method_exists($font, 'file')) {
                    $font->file(5);
                }
                if (method_exists($font, 'color')) {
                    $font->color('#FFCC00');
                }
            });
            $y += 30;
        }

        // 7. Salva a imagem processada no disco
        $processedFullPath = storage_path("app/public/{$processedDir}/{$filename}");

        if (!file_exists(storage_path("app/public/{$processedDir}"))) {
            mkdir(storage_path("app/public/{$processedDir}"), 0755, true);
        }

        // 8. Fallback de salvamento com compressão
        if (method_exists($image, 'toJpeg')) {
            $encoded = $image->toJpeg(80);
            if (method_exists($encoded, 'save')) {
                $encoded->save($processedFullPath);
            } else {
                file_put_contents($processedFullPath, (string) $encoded);
            }
        } else {
            $image->save($processedFullPath, 80);
        }

        $processedPath = "{$processedDir}/{$filename}";

        return new ProcessedPhotoDTO($originalPath, $processedPath);
    }
}
