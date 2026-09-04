<?php

namespace App\Services;

use App\DTOs\AddressDTO;
use App\Services\Contracts\GeocodingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapGeocodingService implements GeocodingServiceInterface
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org/reverse';

    public function getAddressFromCoordinates(float $lat, float $lng): ?AddressDTO
    {
        try {
            $response = Http::withHeaders([
                // OBRIGATÓRIO: O OpenStreetMap bloqueia requisições sem um User-Agent válido
                'User-Agent' => 'FotoOS-App/1.0 (contato@suaempresa.com.br)'
            ])->timeout(10)->get(self::BASE_URL, [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'jsonv2',
                'addressdetails' => 1,
            ]);

            if ($response->failed()) {
                Log::warning("Falha na geocodificação OSM: HTTP {$response->status()}");
                return null;
            }

            $data = $response->json();

            if (empty($data) || isset($data['error'])) {
                return null;
            }

            $address = $data['address'] ?? [];

            // Captura os dados com fallback para garantir robustez
            $street = $address['road'] ?? $address['pedestrian'] ?? null;
            $neighborhood = $address['suburb'] ?? $address['neighbourhood'] ?? null;
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
            $state = $address['state'] ?? null;
            $formatted = $data['display_name'] ?? null;

            return new AddressDTO(
                street: $street,
                neighborhood: $neighborhood,
                city: $city,
                state: $state,
                formattedAddress: $formatted
            );

        } catch (\Exception $e) {
            Log::error("Erro interno no GeocodingService: " . $e->getMessage());
            return null;
        }
    }
}
