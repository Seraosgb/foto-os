<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#111827">
    <title>FotoOS - Relatórios em Campo</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="manifest" href="/manifest.json">

    <style>
        [x-cloak] { display: none !important; }
        .btn-camera {
            border: 2px dashed #059669;
            background-color: #ecfdf5;
            color: #065f46;
        }
        .btn-camera:hover {
            background-color: #d1fae5;
        }
        .btn-finalize-active {
            background-color: #111827 !important;
            color: #ffffff !important;
            cursor: pointer;
        }
        .btn-finalize-disabled {
            background-color: #e5e7eb !important;
            color: #9ca3af !important;
            border: 1px solid #d1d5db !important;
            cursor: not-allowed;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 antialiased min-h-screen">

    <div x-data="reportFlow" class="max-w-lg mx-auto min-h-screen bg-white shadow-xl flex flex-col justify-between">

        <!-- Topo / Barra de Status -->
        <header class="bg-gray-800 text-white px-5 py-3.5 flex items-center justify-between sticky top-0 z-50 border-b border-gray-700" style="background-color: #111827;">
            <div>
                <h1 class="text-base font-bold tracking-wide text-white">FotoOS</h1>
                <p class="text-[11px] text-gray-400">Manserv Facilities</p>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="!isOnline" x-cloak class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white">
                    Offline
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm"
                      :class="step === 3 ? 'bg-emerald-600' : 'bg-blue-600'"
                      x-text="step === 1 ? 'Etapa 1 de 2' : (step === 2 ? 'Etapa 2 de 2' : 'Concluído')">
                    Etapa 1 de 2
                </span>
            </div>
        </header>

        <!-- Barra de Progresso de Sincronização em Segundo Plano -->
        <div x-show="isSyncing" x-cloak class="bg-blue-600 text-white p-3 text-xs shadow-md transition">
            <div class="flex justify-between items-center mb-1 font-bold">
                <span x-text="syncStatusText">Sincronizando dados pendentes...</span>
                <span x-text="syncProgress + '%'">0%</span>
            </div>
            <div class="w-full bg-blue-900 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-400 h-2 transition-all duration-300" :style="`width: ${syncProgress}%`"></div>
            </div>
        </div>

        <!-- Alerta de Modo Offline Ativo -->
        <div x-show="!isOnline" x-cloak class="bg-amber-500 text-white text-xs px-4 py-1.5 font-bold text-center flex items-center justify-center gap-1.5 shadow-sm">
            <span>⚠️</span> Modo Offline Ativo. As OSs e fotos serão salvas localmente no aparelho.
        </div>

        <!-- Mensagem de Sucesso / Rascunho Encontrado -->
        <div x-show="successMessage" x-cloak class="mx-4 mt-4 p-3.5 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs rounded-lg flex items-center gap-2 shadow-sm font-medium">
            <span class="text-base">✓</span>
            <span x-text="successMessage" class="flex-1"></span>
        </div>

        <!-- Notificações de Erro / Bloqueio -->
        <div x-show="errorMessage" x-cloak class="mx-4 mt-4 p-3.5 bg-red-100 border border-red-400 text-red-700 text-sm rounded-lg flex items-start gap-2 shadow-sm">
            <span class="font-bold text-red-600">!</span>
            <div class="flex-1 text-xs leading-relaxed">
                <span x-text="errorMessage"></span>
                <div x-show="isFinalized" class="mt-2">
                    <button type="button" @click="reopenReport" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-bold transition shadow-sm">
                        Reabrir Ordem de Serviço
                    </button>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <main class="p-5 flex-1">

            <!-- ETAPA 1: Dados da OS -->
            <section x-show="step === 1" x-cloak class="space-y-4">
                <div class="border-b border-gray-200 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-900">Identificação do Serviço</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Digite o número da OS para buscar ou iniciar novo chamado.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Número da OS *</label>
                    <input
                        type="text"
                        x-model="osNumber"
                        @input="onOsInput"
                        placeholder="Ex: 3456789 ou OS-2026-01"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <!-- CAMPO UNIDADE COM AUTOCOMPLETE INTELIGENTE -->
                <div class="relative" @click.outside="showUnitDropdown = false">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Unidade *
                    </label>
                    <input
                        type="text"
                        x-model="unit"
                        @focus="showUnitDropdown = true; filterUnits()"
                        @input="showUnitDropdown = true"
                        placeholder="Digite para buscar ou cadastrar..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none transition"
                        :disabled="isFinalized"
                    >

                    <!-- Dropdown Flutuante de Unidades -->
                    <div
                        x-show="showUnitDropdown && unitSuggestions.length > 0"
                        x-cloak
                        class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="item in unitSuggestions" :key="item.id">
                            <button
                                type="button"
                                @click="selectUnit(item)"
                                class="w-full text-left px-3.5 py-2 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium transition flex items-center justify-between">
                                <span x-text="item.name"></span>
                                <span class="text-[10px] text-gray-400">Existente</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- CAMPO SETORES COM TAGS E AUTOCOMPLETE HIERÁRQUICO -->
                <div class="relative" @click.outside="showSectorDropdown = false">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Setores Vinculados *
                    </label>

                    <!-- Lista de Setores Selecionados (Tags) -->
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="sectors.length > 0">
                        <template x-for="(s, index) in sectors" :key="index">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold rounded-md">
                                <span x-text="s"></span>
                                <button type="button" @click="removeSector(index)" class="text-blue-500 hover:text-blue-800 font-bold">&times;</button>
                            </span>
                        </template>
                    </div>

                    <div class="flex gap-2">
                        <input
                            type="text"
                            x-model="sectorInput"
                            @focus="showSectorDropdown = true; filterSectors()"
                            @input="showSectorDropdown = true"
                            @keydown.enter.prevent="if(sectorInput.trim()) { addSector(sectorInput); sectorInput = ''; showSectorDropdown = false; }"
                            placeholder="Digite o setor e selecione ou aperte Enter..."
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none transition"
                            :disabled="isFinalized"
                        >
                        <button
                            type="button"
                            @click="if(sectorInput.trim()) { addSector(sectorInput); sectorInput = ''; showSectorDropdown = false; }"
                            class="px-4 py-2.5 bg-gray-800 hover:bg-black text-white rounded-lg text-xs font-bold transition">
                            Adicionar
                        </button>
                    </div>

                    <!-- Dropdown Flutuante de Setores -->
                    <div
                        x-show="showSectorDropdown && sectorSuggestions.length > 0"
                        x-cloak
                        class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="item in sectorSuggestions" :key="item.id">
                            <button
                                type="button"
                                @click="addSector(item.name); sectorInput = ''; showSectorDropdown = false;"
                                class="w-full text-left px-3.5 py-2 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium transition flex items-center justify-between">
                                <span x-text="item.name"></span>
                                <span class="text-[10px] text-gray-400">Setor da Unidade</span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Técnicos Envolvidos</label>
                    <input type="text" x-model="technicians" placeholder="Ex: Bruno Soares, Jonas" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Descrição / Histórico</label>
                    <textarea x-model="history" rows="3" placeholder="Resumo das atividades desenvolvidas..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none"></textarea>
                </div>

                <div class="pt-3">
                    <button
                        type="button"
                        @click="startReport"
                        :disabled="loading || isFinalized"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold text-sm shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <span x-show="!loading">Avançar para Fotos &rarr;</span>
                        <span x-show="loading" class="animate-pulse">Gravando Dados...</span>
                    </button>
                </div>
            </section>

            <!-- ETAPA 2: Registro Fotográfico -->
            <section x-show="step === 2" x-cloak class="space-y-4">
                <div class="border-b border-gray-200 pb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Evidências Fotográficas</h2>
                        <p class="text-xs text-slate-500">Capture as fotos e adicione as observações técnicas.</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-gray-200 text-gray-800 border border-gray-300 px-2.5 py-1 rounded" x-text="'OS: ' + osNumber"></span>
                </div>

                <input type="file" x-ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" class="hidden">

                <button type="button" @click="triggerCamera" :disabled="loading" class="w-full py-5 rounded-xl font-bold flex flex-col items-center justify-center gap-1.5 transition disabled:opacity-50 btn-camera shadow-sm">
                    <span class="text-2xl">📸</span>
                    <span x-show="!loading" class="text-sm font-bold">Tirar Foto com Câmera</span>
                    <span x-show="loading" class="text-xs animate-pulse">Comprimindo e Gravando...</span>
                </button>

                <div class="space-y-4 mt-4">
                    <template x-for="(photo, index) in photos" :key="photo.id">
                        <div class="p-3.5 bg-white border border-gray-300 rounded-xl shadow-sm flex flex-col gap-3">
                            <div class="relative w-full rounded-lg overflow-hidden border border-gray-200" style="min-height: 240px; height: 260px; background-color: #111827;">
                                <img :src="photo.url" class="w-full h-full object-cover block" alt="Evidência Fotográfica">

                                <div class="absolute top-2 right-2 flex gap-1 rounded-md p-1 z-20" style="background-color: rgba(0, 0, 0, 0.75); border: 1px solid rgba(255, 255, 255, 0.2);">
                                    <button
                                        type="button"
                                        @click="movePhoto(index, -1)"
                                        :disabled="index === 0"
                                        class="w-8 h-8 flex items-center justify-center text-sm font-bold text-white rounded hover:bg-white/20 disabled:opacity-20 transition"
                                        title="Mover para cima">
                                        ▲
                                    </button>
                                    <button
                                        type="button"
                                        @click="movePhoto(index, 1)"
                                        :disabled="index === photos.length - 1"
                                        class="w-8 h-8 flex items-center justify-center text-sm font-bold text-white rounded hover:bg-white/20 disabled:opacity-20 transition"
                                        title="Mover para baixo">
                                        ▼
                                    </button>
                                </div>

                                <div class="absolute bottom-2 left-2 text-white text-xs font-mono font-semibold px-2.5 py-1 rounded z-20" style="background-color: rgba(0, 0, 0, 0.75); border: 1px solid rgba(255, 255, 255, 0.2);">
                                    Foto #<span x-text="index + 1"></span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Observação da Foto #<span x-text="index + 1"></span>:
                                </label>
                                <input
                                    type="text"
                                    x-model="photo.observation"
                                    @change="updatePhotoObservation(photo.id, photo.observation)"
                                    placeholder="Ex: Disjuntor identificado / Quadro energizado"
                                    class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none">
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="photos.length === 0" x-cloak class="text-center py-8 text-gray-400 text-xs">
                    Nenhuma foto capturada nesta OS.
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-2">
                    <button
                        type="button"
                        @click="finalize"
                        :disabled="loading || photos.length === 0"
                        class="w-full py-3.5 px-4 rounded-lg font-bold text-sm shadow-md transition flex items-center justify-center gap-2"
                        :class="photos.length > 0 && !loading ? 'btn-finalize-active' : 'btn-finalize-disabled'">
                        <span x-show="!loading">Finalizar Relatório e Gerar PDF</span>
                        <span x-show="loading" x-cloak class="animate-pulse flex items-center gap-1.5 text-white">
                            <span>⏳</span> Compilando Documento...
                        </span>
                    </button>

                    <button type="button" @click="step = 1" class="w-full py-2 text-xs font-medium text-gray-500 hover:text-gray-900 text-center block transition">
                        &larr; Voltar para os dados da OS
                    </button>
                </div>
            </section>

            <!-- ETAPA 3: Conclusão -->
            <section x-show="step === 3" x-cloak class="space-y-6 text-center py-4">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-bold border border-emerald-300 shadow-sm">
                    ✓
                </div>

                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-gray-900">Relatório Concluído!</h2>
                    <p class="text-xs text-gray-500">
                        A Ordem de Serviço <strong class="text-gray-800" x-text="osNumber"></strong> foi finalizada e o PDF gerado.
                    </p>
                </div>

                <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-2.5">
                    <button
                        type="button"
                        @click="shareWhatsapp"
                        class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm shadow transition flex items-center justify-center gap-2">
                        <span>💬</span> Compartilhar no WhatsApp
                    </button>

                    <button
                        type="button"
                        @click="shareReport"
                        class="w-full py-2.5 px-4 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg font-semibold text-xs transition flex items-center justify-center gap-2">
                        <span>📲</span> Outras Formas de Envio
                    </button>

                    <a
                        :href="pdfUrl"
                        target="_blank"
                        class="w-full py-2.5 px-4 bg-gray-800 hover:bg-black text-white rounded-lg font-semibold text-xs transition block text-center shadow-sm">
                        Visualizar PDF no Navegador
                    </a>
                </div>

                <div class="pt-2 border-t border-gray-200">
                    <button
                        type="button"
                        @click="resetFlow"
                        class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                        <span>＋</span> Iniciar Novo Relatório
                    </button>
                    <p class="text-[11px] text-gray-400 mt-2">Os campos serão limpos para uma nova ordem de serviço.</p>
                </div>
            </section>

        </main>
    </div>

    <!-- Script do vite carrega o Alpine e o Axios globais primeiro -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Por último, carrega o seu arquivo de fluxo PWA -->
    <script src="/js/report-flow.js?v={{ time() }}"></script>

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
