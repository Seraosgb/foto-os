<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\GeocodingServiceInterface;
use App\Services\OpenStreetMapGeocodingService;
use App\Services\Contracts\ImageProcessingServiceInterface;
use App\Services\InterventionImageProcessingService;

class AppServiceProvider extends ServiceProvider
{
    // Dentro do método register():
public function register(): void
{
    $this->app->bind(GeocodingServiceInterface::class, OpenStreetMapGeocodingService::class);
    $this->app->bind(ImageProcessingServiceInterface::class, InterventionImageProcessingService::class);
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
