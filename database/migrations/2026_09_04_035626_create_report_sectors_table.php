<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function run(): void
    {
        Schema::create('report_sectors', function (Blueprint $table) {
            $table->id();
            // Relacionamento com reports (UUID)
            $table->uuid('report_id');
            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');

            // Relacionamento com sectors (UUID)
            $table->uuid('sector_id');
            $table->foreign('sector_id')->references('id')->on('sectors')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sectors');
    }
};
