<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    // Empresas (Tenants)
    Schema::create('companies', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('logo_path')->nullable();
        $table->json('settings')->nullable();
        $table->timestamps();
    });

    // Unidades (Lista Suspensa Dinâmica)
    Schema::create('units', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->string('name');
        $table->string('normalized_name');
        $table->boolean('active')->default(true);
        $table->timestamps();
    });

    // Setores (Lista Suspensa Dinâmica)
    Schema::create('sectors', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
        $table->string('name');
        $table->string('normalized_name');
        $table->boolean('active')->default(true);
        $table->timestamps();
    });

    // Status da OS (Lista Suspensa Dinâmica - Zero Hardcode)
    Schema::create('report_statuses', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->string('name'); // Ex: Rascunho, Em Execução, Finalizado
        $table->string('slug');
        $table->timestamps();
    });

    // Relatórios de OS
    Schema::create('reports', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->string('os_number');
        $table->foreignUuid('unit_id')->constrained('units');
        $table->foreignUuid('status_id')->constrained('report_statuses');
        $table->text('history')->nullable();
        $table->text('technicians')->nullable();
        $table->timestamp('server_created_at')->useCurrent();
        $table->timestamp('finalized_at')->nullable();
        $table->timestamps();
    });

    // Fotos do Relatório
    Schema::create('photos', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
        $table->integer('sequence')->default(1);
        $table->string('original_path');
        $table->string('processed_path');
        $table->text('observation')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('address')->nullable();
        $table->timestamp('captured_at_server')->useCurrent();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foto_os_core_tables');
    }
};
