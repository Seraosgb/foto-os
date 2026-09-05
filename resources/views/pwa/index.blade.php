<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3a8a">
    <title>FotoOS - Relatórios em Campo</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">

    <script src="/js/report-flow.js?v={{ time() }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 antialiased min-h-screen pb-12">

    <div x-data="reportFlow" class="max-w-md mx-auto min-h-screen bg-white shadow-md flex flex-col justify-between">

        <!-- Cabeçalho -->
        <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-50">
            <div>
                <h1 class="text-base font-bold tracking-wide">FotoOS</h1>
                <p class="text-xs text-slate-400">Manserv Facilities</p>
            </div>
            <div class="text-xs font-mono bg-slate-800 px-2 py-1 rounded border border-slate-700">
                <span x-show="step === 1">Passo 1/2</span>
                <span x-show="step === 2">Passo 2/2</span>
            </div>
        </header>

        <!-- Mensagens de Alerta / Erro -->
        <div x-show="errorMessage" x-cloak class="p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm m-4 rounded">
            <span x-text="errorMessage"></span>
        </div>

        <!-- Conteúdo Principal -->
        <main class="p-4 flex-1">

            <!-- ETAPA 1: Identificação da OS e Local -->
            <section x-show="step === 1" x-cloak class="space-y-4">
                <div class="border-b pb-2 mb-3">
                    <h2 class="text-lg font-bold text-gray-900">1. Dados da Atividade</h2>
                    <p class="text-xs text-gray-500">Informe os parâmetros operacionais da ordem de serviço.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Número da OS *</label>
                    <input type="text" x-model="osNumber" placeholder="Ex: 3456789 ou OS-2026-01" class="w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Unidade Operacional *</label>
                    <input type="text" x-model="unit" placeholder="Ex: Belford Roxo ou Matriz" class="w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Setor(es) *</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" x-model="sectorInput" @keydown.enter.prevent="addSector" placeholder="Ex: Sala de Máquinas / Subestação" class="flex-1 px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        <button type="button" @click="addSector" class="bg-slate-800 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700">Adicionar</button>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <template x-for="(sec, idx) in sectors" :key="idx">
                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-800 text-xs px-2.5 py-1 rounded-full border border-blue-200">
                                <span x-text="sec"></span>
                                <button type="button" @click="removeSector(idx)" class="text-blue-600 hover:text-red-600 font-bold ml-1">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Técnicos Envolvidos</label>
                    <input type="text" x-model="technicians" placeholder="Ex: Bruno Soares, Jonas" class="w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Histórico / Descrição do Serviço</label>
                    <textarea x-model="history" rows="3" placeholder="Descreva brevemente o serviço executado..." class="w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                </div>

                <div class="pt-4">
                    <button type="button" @click="startReport" :disabled="loading" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold shadow hover:bg-blue-700 transition disabled:opacity-50 flex justify-center items-center gap-2">
                        <span x-show="!loading">Avançar para Fotografias &rarr;</span>
                        <span x-show="loading">Iniciando Relatório...</span>
                    </button>
                </div>
            </section>

            <!-- ETAPA 2: Captura, Ordenação e Evidências -->
            <section x-show="step === 2" x-cloak class="space-y-4">
                <div class="border-b pb-2 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">2. Fotografias</h2>
                        <p class="text-xs text-gray-500">Capture as evidências técnicas do serviço.</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-gray-200 px-2 py-1 rounded text-gray-700" x-text="'OS: ' + osNumber"></span>
                </div>

                <!-- Input Invisível de Câmera -->
                <input type="file" x-ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" class="hidden">

                <!-- Botão de Disparo -->
                <button type="button" @click="triggerCamera" :disabled="loading" class="w-full py-4 border-2 border-dashed border-emerald-600 bg-emerald-50 text-emerald-800 rounded-xl font-bold flex flex-col items-center justify-center gap-1 hover:bg-emerald-100 transition disabled:opacity-50">
                    <span class="text-xl">📷</span>
                    <span x-show="!loading">Tirar Foto (Câmera)</span>
                    <span x-show="loading" class="text-xs text-emerald-700 animate-pulse">Comprimindo e Carimbando Imagem...</span>
                </button>

                <!-- Listagem das Fotos -->
                <div class="space-y-4 mt-4">
                    <template x-for="(photo, index) in photos" :key="photo.id">
                        <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col gap-2.5">

                            <!-- Preview com Botões de Reordenação -->
                            <div class="relative overflow-hidden rounded-md border border-gray-200 bg-gray-900">
                                <img :src="photo.url" class="w-full h-52 object-cover block">

                                <div class="absolute top-2 right-2 flex gap-1 bg-black/60 rounded p-1 backdrop-blur-sm">
                                    <button
                                        type="button"
                                        @click="movePhoto(index, -1)"
                                        :disabled="index === 0"
                                        class="px-2 py-1 text-xs font-bold text-white disabled:opacity-30 hover:bg-white/20 rounded"
                                        title="Mover para cima">
                                        &uarr;
                                    </button>
                                    <button
                                        type="button"
                                        @click="movePhoto(index, 1)"
                                        :disabled="index === photos.length - 1"
                                        class="px-2 py-1 text-xs font-bold text-white disabled:opacity-30 hover:bg-white/20 rounded"
                                        title="Mover para baixo">
                                        &darr;
                                    </button>
                                </div>

                                <div class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded font-mono">
                                    Foto #<span x-text="index + 1"></span>
                                </div>
                            </div>

                            <!-- Observação Individual com Autosave -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Observação da Evidência #<span x-text="index + 1"></span>:
                                </label>
                                <input
                                    type="text"
                                    x-model="photo.observation"
                                    @change="updatePhotoObservation(photo.id, photo.observation)"
                                    placeholder="Ex: Disjuntor geral identificado / Quadro limpo"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="photos.length === 0" class="text-center py-6 text-gray-400 text-sm">
                    Nenhuma foto anexada ainda.
                </div>

                <!-- Finalização -->
                <div class="pt-4 border-t space-y-2">
                    <button type="button" @click="finalize" :disabled="loading || photos.length === 0" class="w-full bg-slate-900 text-white py-3 rounded-lg font-bold shadow hover:bg-slate-800 transition disabled:opacity-50 flex justify-center items-center gap-2">
                        <span x-show="!loading">Finalizar Relatório e Gerar PDF</span>
                        <span x-show="loading">Processando e Compilando Documento...</span>
                    </button>
                    <button type="button" @click="step = 1" class="w-full py-2 text-xs text-gray-500 hover:text-gray-800 text-center block">
                        &larr; Voltar para dados da OS
                    </button>
                </div>
            </section>

        </main>
    </div>

    <!-- Registro do Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registrado com sucesso:', reg.scope))
                    .catch(err => console.error('Falha ao registrar SW:', err));
            });
        }
    </script>
</body>
</html>
