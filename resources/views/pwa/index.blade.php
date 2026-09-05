<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f172a">
    <title>FotoOS - Relatórios em Campo</title>

    <link rel="manifest" href="/manifest.json">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script src="/js/report-flow.js?v={{ time() }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <div x-data="reportFlow" class="max-w-lg mx-auto min-h-screen bg-white shadow-xl flex flex-col justify-between">

        <!-- Topo / Barra de Status -->
        <header class="bg-slate-900 text-white px-5 py-3.5 flex items-center justify-between sticky top-0 z-50 border-b border-slate-800">
            <div>
                <h1 class="text-base font-bold tracking-wide">FotoOS</h1>
                <p class="text-[11px] text-slate-400">Manserv Facilities</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-200 border border-slate-700">
                    <span x-show="step === 1" x-cloak>Etapa 1 de 2</span>
                    <span x-show="step === 2" x-cloak>Etapa 2 de 2</span>
                </span>
            </div>
        </header>

        <!-- Mensagens de Notificação / Erro -->
        <div x-show="errorMessage" x-cloak class="mx-4 mt-4 p-3.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-start gap-2 shadow-sm">
            <span class="font-bold text-red-600">!</span>
            <span x-text="errorMessage" class="flex-1 text-xs leading-relaxed"></span>
        </div>

        <!-- Área de Conteúdo -->
        <main class="p-5 flex-1">

            <!-- ETAPA 1: Dados da OS -->
            <section x-show="step === 1" x-cloak class="space-y-4">
                <div class="border-b border-slate-200 pb-3 mb-4">
                    <h2 class="text-base font-bold text-slate-900">Identificação do Serviço</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Preencha as informações operacionais antes de anexar as fotos.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Número da OS *</label>
                    <input type="text" x-model="osNumber" placeholder="Ex: 3456789 ou OS-2026-01" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Unidade *</label>
                    <input type="text" x-model="unit" placeholder="Ex: Belford Roxo ou Polo Petroquímico" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Setor(es) *</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="sectorInput" @keydown.enter.prevent="addSector" placeholder="Ex: Sala de Máquinas" class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none">
                        <button type="button" @click="addSector" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">Adicionar</button>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mt-2.5">
                        <template x-for="(sec, idx) in sectors" :key="idx">
                            <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-medium px-3 py-1 rounded-full border border-blue-200">
                                <span x-text="sec"></span>
                                <button type="button" @click="removeSector(idx)" class="text-blue-500 hover:text-red-600 font-bold text-sm leading-none">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Técnicos Envolvidos</label>
                    <input type="text" x-model="technicians" placeholder="Ex: Bruno Soares, Jonas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Descrição / Histórico</label>
                    <textarea x-model="history" rows="3" placeholder="Resumo das intervenções realizadas no chamado..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none"></textarea>
                </div>

                <div class="pt-3">
                    <button type="button" @click="startReport" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold text-sm shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <span x-show="!loading">Avançar para Fotos &rarr;</span>
                        <span x-show="loading" class="animate-pulse">Criando Relatório...</span>
                    </button>
                </div>
            </section>

            <!-- ETAPA 2: Registro Fotográfico e Finalização -->
            <section x-show="step === 2" x-cloak class="space-y-4">
                <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Evidências Fotográficas</h2>
                        <p class="text-xs text-slate-500">Capture as fotos e descreva as intervenções.</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1 rounded" x-text="'OS: ' + osNumber"></span>
                </div>

                <!-- Input Invisível -->
                <input type="file" x-ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" class="hidden">

                <!-- Botão Disparador de Câmera -->
                <button type="button" @click="triggerCamera" :disabled="loading" class="w-full py-5 border-2 border-dashed border-emerald-500 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl font-bold flex flex-col items-center justify-center gap-1.5 transition disabled:opacity-50">
                    <span class="text-2xl">📸</span>
                    <span x-show="!loading" class="text-sm">Tirar Foto com Câmera</span>
                    <span x-show="loading" class="text-xs text-emerald-700 animate-pulse">Comprimindo e Carimbando...</span>
                </button>

                <!-- Grid / Lista de Fotos -->
                <div class="space-y-3.5 mt-4">
                    <template x-for="(photo, index) in photos" :key="photo.id">
                        <div class="p-3 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col gap-2.5">

                            <!-- Imagem com Ações de Posição -->
                            <div class="relative overflow-hidden rounded-lg bg-slate-950 aspect-[4/3]">
                                <img :src="photo.url" class="w-full h-full object-cover block">

                                <div class="absolute top-2 right-2 flex gap-1.5 bg-slate-900/80 rounded-md p-1 backdrop-blur-sm border border-slate-700">
                                    <button
                                        type="button"
                                        @click="movePhoto(index, -1)"
                                        :disabled="index === 0"
                                        class="w-7 h-7 flex items-center justify-center text-xs font-bold text-white disabled:opacity-20 hover:bg-white/20 rounded transition"
                                        title="Mover para cima">
                                        &uarr;
                                    </button>
                                    <button
                                        type="button"
                                        @click="movePhoto(index, 1)"
                                        :disabled="index === photos.length - 1"
                                        class="w-7 h-7 flex items-center justify-center text-xs font-bold text-white disabled:opacity-20 hover:bg-white/20 rounded transition"
                                        title="Mover para baixo">
                                        &darr;
                                    </button>
                                </div>

                                <div class="absolute bottom-2 left-2 bg-slate-900/80 text-white text-[10px] font-mono px-2 py-0.5 rounded border border-slate-700">
                                    Evidência #<span x-text="index + 1"></span>
                                </div>
                            </div>

                            <!-- Observação Individual -->
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 mb-1">
                                    Observação da Foto #<span x-text="index + 1"></span>:
                                </label>
                                <input
                                    type="text"
                                    x-model="photo.observation"
                                    @change="updatePhotoObservation(photo.id, photo.observation)"
                                    placeholder="Ex: Disjuntor identificado / Quadro energizado"
                                    class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition outline-none">
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="photos.length === 0" x-cloak class="text-center py-8 text-slate-400 text-xs">
                    Nenhuma foto capturada nesta OS.
                </div>

                <!-- Rodapé de Ações -->
                <div class="pt-4 border-t border-slate-200 space-y-2">
                    <button type="button" @click="finalize" :disabled="loading || photos.length === 0" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3.5 rounded-lg font-bold text-sm shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <span x-show="!loading">Finalizar Relatório e Gerar PDF</span>
                        <span x-show="loading" class="animate-pulse">Compilando Documento...</span>
                    </button>

                    <button type="button" @click="step = 1" class="w-full py-2 text-xs font-medium text-slate-500 hover:text-slate-800 text-center block transition">
                        &larr; Voltar para os dados da OS
                    </button>
                </div>
            </section>

        </main>
    </div>

    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW ativo:', reg.scope))
                    .catch(err => console.error('Erro SW:', err));
            });
        }
    </script>
</body>
</html>
