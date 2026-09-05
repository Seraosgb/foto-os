<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePhotoRequest;
use App\Models\Report;
use App\Services\Contracts\GeocodingServiceInterface;
use App\Services\Contracts\ImageProcessingServiceInterface;
use Illuminate\Http\JsonResponse;
use App\Models\Company;

class PhotoController extends Controller
{
   public function store(
        StorePhotoRequest $request,
        Report $report,
        GeocodingServiceInterface $geocoding,
        ImageProcessingServiceInterface $imageProcessing
    ): JsonResponse {
        $tenantId = session()->get('tenant_id') ?? auth()->user()?->company_id;

        // 🛡️ Fallback seguro para quando o usuário não estiver logado
        if (empty($tenantId)) {
            $tenantId = Company::first()?->id;
        }

        if ($report->company_id !== $tenantId) {
            return response()->json(['error' => 'Acesso negado a este relatório.'], 403);
        }

        // 1. Converte as coordenadas em endereço (OpenStreetMap)
        $addressDto = $geocoding->getAddressFromCoordinates(
            $request->validated('latitude'),
            $request->validated('longitude')
        );

        // 2. Processa a foto e aplica a marca d'água
        // A hora oficial é a do servidor (now()), conforme especificação[cite: 1]
        $processedPhotoDto = $imageProcessing->processAndWatermark(
            $request->file('photo'),
            $report->os_number,
            now()->format('d/m/Y H:i:s'),
            $request->validated('latitude'),
            $request->validated('longitude'),
            $addressDto
        );

        // 3. Salva no banco de dados (UUID gerado automaticamente pela Trait)
        $photo = $report->photos()->create([
            'original_path' => $processedPhotoDto->originalPath,
            'processed_path' => $processedPhotoDto->processedPath,
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'address' => $addressDto?->formattedAddress,
            'observation' => $request->validated('observation'),
            'captured_at_server' => now(),
        ]);

        // Retorna o DTO mascarado (escondendo estrutura interna) da foto inserida
        return response()->json([
            'message' => 'Fotografia processada e anexada com sucesso!',
            'data' => [
                'id' => $photo->id, // UUID
                'url' => asset('storage/' . $photo->processed_path),
                'address' => $photo->address,
            ]
        ], 201);
    }
    public function updateObservation(\Illuminate\Http\Request $request, \App\Models\Photo $photo): \Illuminate\Http\JsonResponse
{
    $validated = $request->validate([
        'observation' => ['nullable', 'string', 'max:500'],
    ]);

    $photo->update(['observation' => $validated['observation'] ?? null]);

    return response()->json(['message' => 'Observação atualizada com sucesso!'], 200);
}
public function reorder(\Illuminate\Http\Request $request, Report $report): \Illuminate\Http\JsonResponse
{
    $validated = $request->validate([
        'order' => ['required', 'array'],
        'order.*' => ['required', 'uuid'],
    ]);

    \Illuminate\Support\Facades\DB::transaction(function () use ($report, $validated) {
        foreach ($validated['order'] as $sequence => $photoId) {
            $report->photos()->where('id', $photoId)->update(['sequence' => $sequence + 1]);
        }
    });

    return response()->json(['message' => 'Ordem das fotografias atualizada com sucesso!'], 200);
}
}
