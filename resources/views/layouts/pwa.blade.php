<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <title>@yield('title', 'FotoOS - Relatórios')</title>

    <!-- Link para o Manifest do PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Carrega o Tailwind e Alpine via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 antialiased font-sans">

    <!-- Barra Superior -->
    <header class="bg-blue-600 text-white shadow-md p-4 sticky top-0 z-50 flex justify-between items-center">
        <h1 class="text-xl font-bold truncate">@yield('header_title', 'Aliados da Manutenção')</h1>
        <!-- Espaço para um botão de sincronização no futuro -->
        <div id="sync-status"></div>
    </header>

    <!-- Conteúdo Dinâmico da Página -->
    <main class="p-4 pb-24 max-w-lg mx-auto">
        @yield('content')
    </main>

    <!-- Scripts de Suporte PWA (Service Worker) entrarão aqui depois -->
    @stack('scripts')
    <script src="{{ asset('js/offline-store.js') }}"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('SW registrado:', reg.scope))
                    .catch((err) => console.error('Erro no SW:', err));
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
