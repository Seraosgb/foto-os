<?php

namespace App\Services;

use App\Services\Contracts\ImageProcessingServiceInterface;
use Illuminate\Http\UploadedFile;
use App\DTOs\ProcessedPhotoDTO;
use App\DTOs\AddressDTO;
use Illuminate\Support\Str;

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

        // 2. Lê a imagem usando a biblioteca GD nativa do PHP (Bypass completo no Intervention)
        $imageContent = file_get_contents($file->getPathname());
        $image = @imagecreatefromstring($imageContent);

        if (!$image) {
            throw new \Exception('Falha ao processar a imagem com o motor GD nativo do PHP.');
        }

        // 3. Redimensiona proporcionalmente para no máximo 1280px
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > 1280) {
            $newWidth = 1280;
            $newHeight = (int) floor($height * (1280 / $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // 4. Monta as linhas de evidência da Marca D'água
        $addressText = $address ? $address->formattedAddress : 'Endereço indisponível ou offline';
        $lines = [
            "OS: {$osNumber} | Data: {$timestamp}",
            "GPS: {$lat}, {$lng}",
            "Local: {$addressText}"
        ];

        // 5. Escreve as linhas na imagem
        $color = imagecolorallocate($image, 255, 204, 0); // Amarelo #FFCC00
        $y = 30;

        foreach ($lines as $line) {
            // Converte o charset para a fonte nativa do GD não quebrar a acentuação do endereço
            $text = mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8');
            imagestring($image, 5, 30, $y, $text, $color);
            $y += 25; // Espaçamento entre as linhas
        }

        // 6. Prepara o diretório de destino
        $processedFullPath = storage_path("app/public/{$processedDir}/{$filename}");

        if (!file_exists(storage_path("app/public/{$processedDir}"))) {
            mkdir(storage_path("app/public/{$processedDir}"), 0755, true);
        }

        // 7. Salva como JPEG com 80% de qualidade e limpa a memória
        imagejpeg($image, $processedFullPath, 80);
        imagedestroy($image);

        $processedPath = "{$processedDir}/{$filename}";

        return new ProcessedPhotoDTO($originalPath, $processedPath);
    }
}
