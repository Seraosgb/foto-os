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
        $realPath = $file->getPathname();

        // 2. Lê a imagem (Motor GD Nativo)
        $imageContent = file_get_contents($realPath);
        $image = @imagecreatefromstring($imageContent);

        if (!$image) {
            throw new \Exception('Falha ao processar a imagem com o motor GD nativo do PHP.');
        }

        // 3. CORREÇÃO DE EXIF (Celular deitado/em pé)
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($realPath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $image = imagerotate($image, 180, 0); break;
                    case 6: $image = imagerotate($image, -90, 0); break;
                    case 8: $image = imagerotate($image, 90, 0); break;
                }
            }
        }

        // 4. Redimensiona mantendo a proporção (baseado na MAIOR aresta)
        $width = imagesx($image);
        $height = imagesy($image);
        $maxDimension = 1280;

        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int) floor($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) floor($width * ($maxDimension / $height));
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // 5. PEGA AS DIMENSÕES FINAIS EXATAS APÓS GINÁSTICA DE ROTAÇÃO E REDIMENSIONAMENTO
        $finalWidth = imagesx($image);
        $finalHeight = imagesy($image);

        // 6. Formata a Data e o Endereço
        \Carbon\Carbon::setLocale('pt_BR');
        $dataStr = \Carbon\Carbon::now()->translatedFormat('j \d\e M. \d\e Y H:i:s');

        $lines = [];
        $lines[] = $dataStr;

        if ($address && $address->street) {
            $lines[] = $address->street;
            if ($address->neighborhood) $lines[] = $address->neighborhood;
            if ($address->city) $lines[] = $address->city;
            if ($address->state) $lines[] = $address->state;
        } else {
            $lines[] = "Lat: {$lat}, Lng: {$lng}";
        }

        // 7. Configurações de Fonte e Cores
        $white = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 70);

        $font = 5;
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $padding = 20;
        $lineSpacing = 6;

        $totalHeight = count($lines) * ($fontHeight + $lineSpacing);

        // Posição Y inicial garantida
        $startY = $finalHeight - $padding - $totalHeight;

        // 8. Escreve as linhas alinhadas à direita
        foreach ($lines as $index => $line) {
            $text = mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8');
            $textWidth = strlen($text) * $fontWidth;

            // Posição X garantida encostada na direita, não importa se é retrato ou paisagem
            $x = $finalWidth - $padding - $textWidth;
            $y = $startY + ($index * ($fontHeight + $lineSpacing));

            imagestring($image, $font, $x + 2, $y + 2, $text, $shadow);
            imagestring($image, $font, $x, $y, $text, $white);
        }

        // 9. Salva
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
