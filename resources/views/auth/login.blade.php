<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Operativo - Estaciones de Servicio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
        
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-sky-400">Acceso Técnico / Operador</h1>
            <p class="text-xs text-slate-400 mt-1">Ingresa tus credenciales para administrar los tanques</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-950/80 border border-red-800 text-red-300 text-xs rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-sky-500">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-sky-500">
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-800 text-sky-500 focus:ring-0">
                    <span>Recordar sesión</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-4 rounded-xl text-sm transition-all shadow-lg shadow-sky-900/40">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-6 text-center border-t border-slate-800 pt-4">
            <a href="{{ route('publico') }}" class="text-xs text-slate-500 hover:text-sky-400">
                ← Volver a la Consulta Pública
            </a>
        </div>
    </div>
</body>
</html>