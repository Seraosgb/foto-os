<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão - FotoOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-900">Painel de Relatórios</h1>
        <div class="text-sm text-gray-500">
            Tenant Ativo: <span class="font-semibold text-gray-700">{{ auth()->user()->company->name ?? 'Matriz' }}</span>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-6 space-y-6">
        <!-- Resumo de Taxonomias Dinâmicas -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-2">Unidades Ativas</h2>
                <div class="text-2xl font-bold text-blue-600">{{ $units->count() }}</div>
                <p class="text-xs text-gray-400 mt-1">Alimentadas progressivamente via campo</p>
            </div>
            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-2">Total de Setores Mapeados</h2>
                <div class="text-2xl font-bold text-emerald-600">{{ $units->sum('sectors_count') }}</div>
                <p class="text-xs text-gray-400 mt-1">Distribuídos entre as unidades</p>
            </div>
        </section>

        <!-- Tabela de Relatórios Emitidos -->
        <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="font-semibold text-gray-900">Histórico de OS Finalizadas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3">OS</th>
                            <th class="px-4 py-3">Unidade</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Data Servidor</th>
                            <th class="px-4 py-3 text-right">Documento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reports as $rep)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium">{{ $rep->os_number }}</td>
                                <td class="px-4 py-3">{{ $rep->unit->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ $rep->status->name ?? 'Concluído' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $rep->server_created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ asset('storage/reports/' . $rep->id . '.pdf') }}" target="_blank" class="text-blue-600 hover:underline">
                                        Abrir PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">Nenhum relatório finalizado registrado até o momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200">
                {{ $reports->links() }}
            </div>
        </section>
    </main>
</body>
</html>
