<?php

namespace App\Services\Contracts;

use App\DTOs\AddressDTO;

interface GeocodingServiceInterface
{
    /**
     * Converte Latitude e Longitude em um Endereço Físico.
     */
    public function getAddressFromCoordinates(float $lat, float $lng): ?AddressDTO;
}
