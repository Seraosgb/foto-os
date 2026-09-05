<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - FotoOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 antialiased min-h-screen">

    <nav class="bg-gray-900 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div>
            <h1 class="font-bold text-lg">FotoOS — Gestão Operacional</h1>
            <p class="text-xs text-gray-400">{{ $company->name ?? 'Manserv Facilities' }}</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="/" target="_blank" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">Abrir PWA de Campo &rarr;</a>
            <form action="/logout" method="POST">
                @csrf
                <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg font-bold transition">
                    Sair
                </button>
            </form>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-6 space-y-6">

        @if(session('success'))
            <div class="p-3.5 bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs rounded-xl font-bold">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 bg-red-100 border border-red-300 text-red-800 text-xs rounded-xl font-bold">
                ✕ {{ session('error') }}
            </div>
        @endif

        <!-- Bloco 1: Identidade da Empresa e Ações Globais -->
        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                <h2 class="font-bold text-sm text-gray-900">Dados Cadastrais da Empresa</h2>

                <!-- Botão com Rota Direta -->
                <form method="POST" action="/painel/retencao/executar" onsubmit="return confirm('Deseja iniciar a checagem e expurgo de arquivos agora?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Executar Retenção/Expurgo
                    </button>
                </form>
            </div>

            <form action="/painel/empresa" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Razão Social / Nome de Exibição *</label>
                    <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 outline-none focus:bg-white focus:ring-2 focus:ring-blue-600 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Logomarca (Substitui o texto no PDF)</label>
                    <input type="file" name="logo" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                </div>
                <div class="flex items-center gap-4">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" class="h-10 border border-gray-300 p-1 rounded bg-white object-contain">
                    @endif
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow transition">
                        Atualizar Empresa
                    </button>
                </div>
            </form>
        </section>

        <!-- Bloco 2: Histórico de Relatórios -->
        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 space-y-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                <h2 class="font-bold text-sm text-gray-900">Relatórios Emitidos e Rascunhos</h2>
                <form method="GET" action="/painel" class="flex gap-2">
                    <input type="text" name="os_number" value="{{ request('os_number') }}" placeholder="Buscar por número da OS..." class="px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-xs outline-none focus:bg-white focus:ring-2 focus:ring-blue-600 transition">
                    <button type="submit" class="px-3 py-1.5 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-bold transition">Filtrar</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600">
                            <th class="p-3">OS</th>
                            <th class="p-3">Unidade</th>
                            <th class="p-3">Status / Retenção</th>
                            <th class="p-3">Data Servidor</th>
                            <th class="p-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reports as $r)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3 font-mono font-bold text-gray-900">{{ $r->os_number }}</td>
                                <td class="p-3">{{ $r->unit->name ?? 'N/A' }}</td>
                                <td class="p-3">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="px-2 py-0.5 rounded-full font-semibold {{ $r->status?->slug === 'finalizado' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $r->status->name ?? 'Rascunho' }}
                                        </span>

                                        @if($r->is_archived)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                Mídia Arquivada (+1 Ano)
                                            </span>
                                        @elseif($r->is_draft_expiring_soon)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 animate-pulse">
                                                    Expira em {{ max(0, $r->days_until_draft_purge) }}d
                                                </span>
                                                <form method="POST" action="{{ route('admin.retention.postpone', $r) }}">
                                                    @csrf
                                                    <button type="submit" title="Adiar expurgo por mais 30 dias" class="text-[10px] text-blue-600 hover:text-blue-800 underline font-semibold">
                                                        Adiar
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3 text-gray-500">{{ $r->server_created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-3 text-right">
                                    @if($r->is_archived)
                                        <span class="text-gray-400 font-medium italic text-[11px]">PDF Indisponível</span>
                                    @else
                                        <a href="/painel/relatorios/{{ $r->id }}/pdf" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold">
                                            Baixar PDF
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-400">Nenhum relatório encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $reports->links() }}
            </div>
        </section>

        <!-- Bloco 3: Gestão de Unidades e Setores -->
        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="font-bold text-sm text-gray-900 mb-3">Taxonomias Registradas Progressivamente</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($units as $u)
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl flex justify-between items-center">
                        <div>
                            <span class="font-bold text-xs text-gray-800">{{ $u->name }}</span>
                            <span class="block text-[10px] text-gray-400">{{ $u->sectors_count }} setor(es) vinculados</span>
                        </div>
                        <form action="/painel/unidades/{{ $u->id }}/toggle" method="POST">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold px-2 py-1 rounded transition {{ $u->active ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-700' }}">
                                {{ $u->active ? 'Ativa' : 'Inativa' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

    </main>
</body>
</html>
