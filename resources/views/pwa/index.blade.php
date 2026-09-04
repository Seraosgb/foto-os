@extends('layouts.pwa')

@section('title', 'Registro em Campo')
@section('header_title', 'Nova OS')

@section('content')
<div x-data="reportFlow" class="space-y-6">

    <!-- Alerta de Erro -->
    <template x-if="errorMessage">
        <div class="p-3 bg-red-100 border border-red-400 text-red-700 text-sm rounded-lg" x-text="errorMessage"></div>
    </template>

    <!-- Indicador de Carregamento -->
    <div x-show="loading" class="text-center py-4 text-blue-600 font-semibold animate-pulse">
        Processando informações...
    </div>

    <!-- ETAPA 1: Dados Cadastrais -->
    <div x-show="step === 1" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-bold text-gray-800">1. Dados Operacionais</h2>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Número da OS *</label>
            <input type="text" x-model="osNumber" placeholder="Ex: MAN-4587" class="w-full px-3 py-2 border rounded-lg uppercase">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Unidade *</label>
            <input type="text" x-model="unit" placeholder="Ex: Unidade Centro" class="w-full px-3 py-2 border rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Setores *</label>
            <div class="flex gap-2">
                <input type="text" x-model="sectorInput" @keydown.enter.prevent="addSector" placeholder="Nome do setor" class="w-full px-3 py-2 border rounded-lg">
                <button type="button" @click="addSector" class="bg-gray-800 text-white px-4 py-2 rounded-lg">+</button>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                <template x-for="(s, index) in sectors" :key="index">
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center gap-1">
                        <span x-text="s"></span>
                        <button type="button" @click="removeSector(index)" class="text-red-500 font-bold">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Técnicos Envolvidos</label>
            <input type="text" x-model="technicians" placeholder="Ex: Bruno e Carlos" class="w-full px-3 py-2 border rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Histórico do Serviço</label>
            <textarea x-model="history" rows="3" placeholder="Descrição do atendimento..." class="w-full px-3 py-2 border rounded-lg"></textarea>
        </div>

        <button type="button" @click="startReport" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg">
            Avançar para Fotos
        </button>
    </div>

    <!-- ETAPA 2: Captura de Evidências Fotográficas -->
    <div x-show="step === 2" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">2. Fotografias</h2>
            <span class="text-xs bg-gray-200 px-2 py-1 rounded font-mono" x-text="'OS: ' + osNumber"></span>
        </div>

        <!-- Input Oculto de Câmera -->
        <input type="file" x-ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" class="hidden">

        <button type="button" @click="triggerCamera" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-lg flex items-center justify-center gap-2">
            <span>📷 Tirar Foto (Câmera)</span>
        </button>

        <!-- Galeria de Fotos Registradas -->
        <div class="grid grid-cols-2 gap-3 mt-4">
            <template x-for="(photo, index) in photos" :key="photo.id">
                <div class="border rounded-lg p-2 bg-gray-50 text-xs">
                    <img :src="photo.url" class="w-full h-24 object-cover rounded mb-1">
                    <p class="truncate text-gray-600 font-semibold" x-text="photo.address"></p>
                </div>
            </template>
        </div>

        <div class="pt-4 border-t flex gap-2">
            <button type="button" @click="step = 1" class="w-1/3 bg-gray-200 text-gray-700 font-semibold py-3 rounded-lg">
                Voltar
            </button>
            <button type="button" @click="finalize" :disabled="photos.length === 0" class="w-2/3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white font-bold py-3 rounded-lg">
                Finalizar e Gerar PDF
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/report-flow.js') }}"></script>
@endpush
