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

        // 1. Salva a imagem original
        $originalPath = $file->storeAs($originalDir, $filename, 'public');

        // 2. Lê a imagem (Motor GD Nativo)
        $imageContent = file_get_contents($file->getPathname());
        $image = @imagecreatefromstring($imageContent);

        if (!$image) {
            throw new \Exception('Falha ao processar a imagem com o motor GD nativo do PHP.');
        }

        // 3. Redimensiona para no máximo 1280px (mantendo proporção)
        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = $width;
        $newHeight = $height;

        if ($width > 1280) {
            $newWidth = 1280;
            $newHeight = (int) floor($height * (1280 / $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // 4. Formata a Data e o Endereço igual à foto de referência
        \Carbon\Carbon::setLocale('pt_BR');
        $dataStr = \Carbon\Carbon::now()->translatedFormat('j \d\e M. \d\e Y H:i:s');

        $lines = [];
        $lines[] = $dataStr;

        if ($address && $address->street) {
            // Separa os blocos de endereço linha por linha
            $lines[] = $address->street;
            if ($address->neighborhood) $lines[] = $address->neighborhood;
            if ($address->city) $lines[] = $address->city;
            if ($address->state) $lines[] = $address->state;
        } else {
            $lines[] = "Lat: {$lat}, Lng: {$lng}";
        }

        // 5. Configurações de Fonte e Cores (Branco com Sombra)
        $white = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 70); // Sombra preta semi-transparente

        $font = 5; // Fonte nativa maior do GD
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $padding = 20;
        $lineSpacing = 6;

        $totalHeight = count($lines) * ($fontHeight + $lineSpacing);
        $startY = $newHeight - $padding - $totalHeight;

        // 6. Escreve as linhas alinhadas à direita (Canto inferior direito)
        foreach ($lines as $index => $line) {
            $text = mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8');
            $textWidth = strlen($text) * $fontWidth;

            // X dinâmico para alinhar à direita
            $x = $newWidth - $padding - $textWidth;
            $y = $startY + ($index * ($fontHeight + $lineSpacing));

            // Sombra
            imagestring($image, $font, $x + 2, $y + 2, $text, $shadow);
            // Texto em si
            imagestring($image, $font, $x, $y, $text, $white);
        }

        // 7. Salva
        $processedFullPath = storage_path("app/public/{$processedDir}/{$filename}");
        if (!file_exists(storage_path("app/public/{$processedDir}"))) {
            mkdir(storage_path("app/public/{$processedDir}"), 0755, true);
        }

        imagejpeg($image, $processedFullPath, 85);
        imagedestroy($image);

        $processedPath = "{$processedDir}/{$filename}";
        return new ProcessedPhotoDTO($originalPath, $processedPath);
    }
}
