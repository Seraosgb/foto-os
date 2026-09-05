<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Administrativo - FotoOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm bg-white p-6 rounded-2xl shadow-xl border border-gray-200">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-900">Painel de Gestão</h1>
            <p class="text-xs text-gray-500 mt-1">Autenticação restrita aos administradores</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/login" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Senha</label>
                <input type="password" name="password" required class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white font-bold text-sm rounded-lg shadow-md transition">
                Entrar no Sistema
            </button>
        </form>
    </div>
</body>
</html>
