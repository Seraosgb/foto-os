<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;
use App\DTOs\ProcessedPhotoDTO;
use App\DTOs\AddressDTO;

interface ImageProcessingServiceInterface
{
    /**
     * Salva a imagem original, aplica a marca d'água com evidências e salva a processada.
     */
    public function processAndWatermark(
        UploadedFile $file,
        string $osNumber,
        string $timestamp,
        float $lat,
        float $lng,
        ?AddressDTO $address
    ): ProcessedPhotoDTO;
}
