<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control Técnico - Estación de Servicio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">

    <div class="max-w-6xl mx-auto">
        
        <!-- ENCABEZADO Y CONTROL DE SESIÓN -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-4 border-b border-slate-800 gap-4">
            <div>
                <a href="{{ route('publico') }}" class="text-xs text-sky-400 hover:underline mb-1 inline-block">← Volver al Portal Público</a>
                <h1 class="text-3xl font-bold text-white flex items-center gap-2">
                    <span>⛽</span> <span id="nombre-estacion">Cargando estación...</span>
                </h1>
                <p id="ubicacion-estacion" class="text-xs text-slate-400 mt-1">Obteniendo ubicación...</p>
            </div>

            <div class="flex items-center gap-4 bg-slate-900 border border-slate-800 p-3 rounded-xl">
                <div class="text-right text-xs">
                    <span class="block text-slate-400">Operador Activo:</span>
                    <span class="font-bold text-emerald-400">👤 {{ Auth::user()->name }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-800 px-3 py-2 rounded-lg font-semibold transition-all">
                        Cerrar Sesión 🚪
                    </button>
                </form>
            </div>
        </header>

        <!-- NAVEGACIÓN ENTRE ESTACIONES -->
        <div class="flex gap-2 mb-6 text-xs font-semibold">
            <span class="text-slate-400 self-center mr-2">Cambiar Estación:</span>
            <a href="/admin/estacion/1" class="px-3 py-1.5 rounded-lg border {{ $estacionId == 1 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Central (#1)</a>
            <a href="/admin/estacion/2" class="px-3 py-1.5 rounded-lg border {{ $estacionId == 2 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Sur (#2)</a>
            <a href="/admin/estacion/3" class="px-3 py-1.5 rounded-lg border {{ $estacionId == 3 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Norte (#3)</a>
        </div>

        <!-- TARJETAS DE MONITOREO DEL TANQUE -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            
            <!-- Tarjeta 1: Porcentaje -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Capacidad Disponible</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span id="porcentaje" class="text-3xl font-extrabold text-white">0%</span>
                    <span id="badge-estado" class="px-2.5 py-1 text-xs rounded-full bg-slate-800 text-slate-300">--</span>
                </div>
                <!-- Barra de Progreso -->
                <div class="w-full bg-slate-800 h-3 rounded-full mt-4 overflow-hidden">
                    <div id="barra-progreso" class="bg-sky-500 h-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <!-- Tarjeta 2: Volumen en Litros -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Volumen Actual</span>
                <div class="mt-2">
                    <span id="litros" class="text-3xl font-extrabold text-white">0 L</span>
                    <p class="text-xs text-slate-500 mt-1">Capacidad Máx: <span id="capacidad-max">0</span> L</p>
                </div>
            </div>

            <!-- Tarjeta 3: Telemetría / Sensor -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sensor Ultrasónico</span>
                <div class="mt-2">
                    <span id="distancia" class="text-3xl font-extrabold text-white">0 cm</span>
                    <p class="text-xs text-slate-500 mt-1">Distancia al combustible</p>
                </div>
            </div>

            <!-- Tarjeta 4: Token de API Asignado -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Token del ESP32</span>
                <div class="mt-2">
                    <code id="token-api" class="text-xs font-mono bg-slate-950 text-sky-400 px-2 py-1 rounded border border-slate-800 block truncate">--</code>
                    <p class="text-xs text-slate-500 mt-2">Clave de autenticación hardware</p>
                </div>
            </div>

        </div>

    </div>

    <script>
        const estacionId = {{ $estacionId }};

        async function cargarDatosEstacion() {
            try {
                const response = await fetch(`/api/estaciones/publicas`);
                const estaciones = await response.json();
                
                // Encontrar la estación por ID
                const estacion = estaciones.find(e => e.id == estacionId);

                if (!estacion) {
                    document.getElementById('nombre-estacion').textContent = 'Estación no encontrada';
                    return;
                }

                document.getElementById('nombre-estacion').textContent = estacion.nombre;
                document.getElementById('ubicacion-estacion').textContent = estacion.ubicacion;
                document.getElementById('capacidad-max').textContent = Number(estacion.capacidad_maxima_litros).toLocaleString();
                document.getElementById('token-api').textContent = estacion.token_api;

                const ultima = estacion.ultima_lectura;

                if (ultima) {
                    const porcentaje = parseFloat(ultima.porcentaje).toFixed(1);
                    document.getElementById('porcentaje').textContent = `${porcentaje}%`;
                    document.getElementById('litros').textContent = `${Number(ultima.litros).toLocaleString()} L`;
                    document.getElementById('distancia').textContent = `${ultima.distancia_cm} cm`;

                    // Barra de progreso y colores
                    const barra = document.getElementById('barra-progreso');
                    barra.style.width = `${porcentaje}%`;

                    const badge = document.getElementById('badge-estado');
                    badge.textContent = ultima.estado;

                    if (ultima.estado === 'Óptimo') {
                        badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800';
                        barra.className = 'bg-emerald-500 h-full transition-all duration-500';
                    } else if (ultima.estado === 'Bajo') {
                        badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-amber-950 text-amber-400 border border-amber-800';
                        barra.className = 'bg-amber-500 h-full transition-all duration-500';
                    } else {
                        badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-red-950 text-red-400 border border-red-800';
                        barra.className = 'bg-red-500 h-full transition-all duration-500';
                    }
                } else {
                    document.getElementById('porcentaje').textContent = 'Sin datos';
                    document.getElementById('litros').textContent = '0 L';
                    document.getElementById('distancia').textContent = '--';
                }

            } catch (error) {
                console.error('Error al obtener datos de la estación:', error);
            }
        }

        // Carga inicial y actualización automática cada 3 segundos
        cargarDatosEstacion();
        setInterval(cargarDatosEstacion, 3000);
    </script>
</body>
</html>