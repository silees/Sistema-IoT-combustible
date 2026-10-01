<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control Técnico - Estación de Servicio</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-950 text-slate-100 min-h-screen p-6">

    <div class="max-w-6xl mx-auto">

        <!-- ENCABEZADO Y CONTROL DE SESIÓN -->
        <header
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-4 border-b border-slate-800 gap-4">
            <div>
                <a href="{{ route('publico') }}" class="text-xs text-sky-400 hover:underline mb-1 inline-block">← Volver
                    al Portal Público</a>
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
                    <button type="submit"
                        class="text-xs bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-800 px-3 py-2 rounded-lg font-semibold transition-all">
                        Cerrar Sesión 🚪
                    </button>
                </form>
            </div>
        </header>

        <!-- NAVEGACIÓN ENTRE ESTACIONES -->
        <div class="flex gap-2 mb-6 text-xs font-semibold">
            <span class="text-slate-400 self-center mr-2">Cambiar Estación:</span>
            <a href="/admin/estacion/1"
                class="px-3 py-1.5 rounded-lg border {{ $estacionId == 1 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Central
                (#1)</a>
            <a href="/admin/estacion/2"
                class="px-3 py-1.5 rounded-lg border {{ $estacionId == 2 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Sur
                (#2)</a>
            <a href="/admin/estacion/3"
                class="px-3 py-1.5 rounded-lg border {{ $estacionId == 3 ? 'bg-sky-600 border-sky-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white' }}">Norte
                (#3)</a>
        </div>

        <!-- FILA DE 4 TARJETAS MÉTRICAS DE MONITOREO -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

            <!-- Tarjeta 1: Capacidad -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Capacidad Disponible</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span id="porcentaje" class="text-3xl font-extrabold text-white">0%</span>
                    <span id="badge-estado"
                        class="px-2.5 py-1 text-xs rounded-full bg-slate-800 text-slate-300">--</span>
                </div>
                <div class="w-full bg-slate-800 h-3 rounded-full mt-4 overflow-hidden">
                    <div id="barra-progreso" class="bg-sky-500 h-full transition-all duration-500" style="width: 0%">
                    </div>
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
                    <code id="token-api"
                        class="text-xs font-mono bg-slate-950 text-sky-400 px-2 py-1 rounded border border-slate-800 block truncate">--</code>
                    <p class="text-xs text-slate-500 mt-2">Clave de autenticación hardware</p>
                </div>
            </div>

        </div>

        <!-- SECCIÓN INFERIOR: GRÁFICA A ANCHO COMPLETO -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Histórico de Nivel de Combustible</h2>
                    <p class="text-xs text-slate-400">Evolución porcentual de las últimas lecturas recibidas desde el
                        sensor</p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-950 text-emerald-400 border border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    En Vivo
                </span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="graficoCombustible"></canvas>
            </div>
        </div>
        <!-- 3. REGISTRO DE AUDITORÍA Y LOGS DE INTENTOS NO AUTORIZADOS -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-white">Auditoría de Seguridad y Accesos</h2>
                        <span
                            class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded bg-indigo-950 text-indigo-400 border border-indigo-800">
                            Security Shield Active
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Detección de accesos no autorizados, fallos de API Key y eventos
                        del sistema</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-mono text-slate-400">Filtro: <strong class="text-slate-200">Todos los
                            eventos</strong></span>
                </div>
            </div>

            <!-- Tabla de Logs de Seguridad -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead
                        class="bg-slate-950/80 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-bold">Fecha / Hora</th>
                            <th class="py-3 px-4 font-bold">IP Solicitante</th>
                            <th class="py-3 px-4 font-bold">Evento / Detalle de Seguridad</th>
                            <th class="py-3 px-4 font-bold text-center">Código HTTP</th>
                            <th class="py-3 px-4 font-bold text-right">Nivel Riesgo</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-audit-body" class="divide-y divide-slate-800/60 font-mono text-xs">
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 font-sans">
                                Cargando registros de auditoría...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- 4. CONTROL MANUAL DE UMBRALES DE ALERTA -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>⚙️</span> Configuración de Umbrales de Alerta
                    </h2>
                    <p class="text-xs text-slate-400">Ajuste los límites operacionales para disparar eventos visuales y
                        alarmas del tanque</p>
                </div>
                <div id="status-umbral-msg" class="hidden text-xs font-semibold px-3 py-1 rounded-lg"></div>
            </div>

            <form id="form-umbrales" onsubmit="guardarUmbrales(event)" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Umbral Nivel Crítico (Muy Bajo) -->
                <div class="bg-slate-950/70 border border-red-900/40 rounded-xl p-4">
                    <label class="block text-xs font-bold text-red-400 uppercase tracking-wider mb-1">
                        Alarma Crítica / Vacío (&le; %)
                    </label>
                    <p class="text-[11px] text-slate-500 mb-3">Dispara estado Crítico y alerta prioritaria.</p>
                    <div class="flex items-center gap-2">
                        <input type="number" id="input-umbral-critico" min="1" max="50" step="0.1" value="10.0"
                            class="w-full bg-slate-900 border border-slate-700 text-white font-mono font-bold text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-red-500">
                        <span class="text-slate-400 font-bold text-sm">%</span>
                    </div>
                </div>

                <!-- Umbral Nivel Bajo (Advertencia) -->
                <div class="bg-slate-950/70 border border-amber-900/40 rounded-xl p-4">
                    <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider mb-1">
                        Advertencia Nivel Bajo (&le; %)
                    </label>
                    <p class="text-[11px] text-slate-500 mb-3">Cambia indicador a estado Bajo / Reabastecer.</p>
                    <div class="flex items-center gap-2">
                        <input type="number" id="input-umbral-bajo" min="5" max="80" step="0.1" value="20.0"
                            class="w-full bg-slate-900 border border-slate-700 text-white font-mono font-bold text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-amber-500">
                        <span class="text-slate-400 font-bold text-sm">%</span>
                    </div>
                </div>

                <!-- Umbral Nivel Alto / Desborde -->
                <div class="bg-slate-950/70 border border-sky-900/40 rounded-xl p-4">
                    <label class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-1">
                        Alerta Alto / Llenado (&ge; %)
                    </label>
                    <p class="text-[11px] text-slate-500 mb-3">Aviso de tanque óptimo o riesgo de rebalse.</p>
                    <div class="flex items-center gap-2">
                        <input type="number" id="input-umbral-alto" min="50" max="100" step="0.1" value="90.0"
                            class="w-full bg-slate-900 border border-slate-700 text-white font-mono font-bold text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-sky-500">
                        <span class="text-slate-400 font-bold text-sm">%</span>
                    </div>
                </div>

                <!-- Botón Guardar -->
                <div class="md:col-span-3 flex justify-end gap-3 pt-2">
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                        <span>💾</span> Guardar Parámetros de Operación
                    </button>
                </div>

            </form>
        </div>
        <!-- Módulo de Exportación y Reportes -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8 shadow-xl">
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>📊</span> Módulo de Exportación y Reportes
                    </h2>
                    <p class="text-xs text-slate-400">Descargue reportes o exporte la información histórica de la
                        estación.</p>
                </div>
                <div id="status-export-msg" class="hidden text-xs px-3 py-1.5 rounded-lg border font-mono"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Exportar CSV -->
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700/50 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-sky-400 mb-1 flex items-center gap-2">
                            <span>📄</span> Exportar Histórico en CSV / Excel
                        </h3>
                        <p class="text-xs text-slate-400 mb-4">
                            Genera una hoja de cálculo con la totalidad de registros de lecturas de telemetría e IP
                            origen.
                        </p>
                    </div>
                    <button type="button" onclick="descargarCSV()"
                        class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl transition-all shadow-lg shadow-sky-600/20 flex items-center justify-center gap-2">
                        <span>📥</span> Descargar Data CSV
                    </button>
                </div>

                <!-- Generar PDF / Imprimir -->
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700/50 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-emerald-400 mb-1 flex items-center gap-2">
                            <span>🖨️</span> Reporte Resumen Consolidado
                        </h3>
                        <p class="text-xs text-slate-400 mb-4">
                            Visualiza e imprime un informe de estadísticas, promedios e incidencias operativas.
                        </p>
                    </div>
                    <button type="button" onclick="generarReporteImprimible()"
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition-all shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2">
                        <span>📑</span> Generar Reporte PDF
                    </button>
                </div>
            </div>
        </div>
        <!-- PANEL DE TELEMETRÍA E INFRAESTRUCTURA DE RED -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-lg font-bold text-white mb-1">Estado del Nodo de Red (ESP32)</h2>
            <p class="text-xs text-slate-400 mb-6">Diagnóstico del enlace de comunicación y sockets de telemetría</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- Estado de Enlace -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 flex items-center gap-3">
                    <div id="indicador-red-ping" class="w-3 h-3 rounded-full bg-slate-600"></div>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Estado
                            Enlace</span>
                        <span id="red-estado" class="text-sm font-extrabold text-slate-300">COMPROBANDO...</span>
                    </div>
                </div>

                <!-- IP de Origen -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-sky-400 text-lg">🌐</span>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">IP Nodo
                            ESP32</span>
                        <span id="red-ip" class="text-sm font-mono text-sky-400 font-bold">0.0.0.0</span>
                    </div>
                </div>

                <!-- Último Heartbeat -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-emerald-400 text-lg">⏱️</span>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Último
                            Heartbeat</span>
                        <span id="red-heartbeat" class="text-sm font-semibold text-slate-200">--</span>
                    </div>
                </div>

                <!-- Protocolo / Versión -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-purple-400 text-lg">⚙️</span>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Protocolo /
                            FW</span>
                        <span id="red-protocolo" class="text-sm font-mono text-purple-300 font-semibold">HTTP/1.1</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        const estacionId = {{ $estacionId }};
        let chartInstancia = null;

        // Función auxiliar para asignar texto a elementos sin causar excepciones de tipo null
        function setTexto(id, valor) {
            const el = document.getElementById(id);
            if (el) el.textContent = valor;
        }

        // 1. Inicializar la gráfica Chart.js
        function inicializarGrafico() {
            const canvas = document.getElementById('graficoCombustible');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            chartInstancia = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Nivel (%)',
                        data: [],
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#38bdf8',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            grid: { color: 'rgba(51, 65, 85, 0.4)' },
                            ticks: { color: '#94a3b8', callback: value => `${value}%` }
                        },
                        x: {
                            grid: { color: 'rgba(51, 65, 85, 0.4)' },
                            ticks: { color: '#94a3b8' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // 2. Consulta de datos por API con asignación segura
        async function cargarDatosEstacion() {
            try {
                const response = await fetch(`/api/estacion/${estacionId}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const estacion = await response.json();

                // Asignaciones seguras usando setTexto
                setTexto('nombre-estacion', estacion.nombre);
                setTexto('ubicacion-estacion', estacion.ubicacion);
                setTexto('capacidad-max', Number(estacion.capacidad_maxima_litros).toLocaleString());
                setTexto('token-api', estacion.token_api);

                const ultima = estacion.ultima_lectura;

                if (ultima) {
                    const porcentaje = parseFloat(ultima.porcentaje).toFixed(1);
                    setTexto('porcentaje', `${porcentaje}%`);
                    setTexto('litros', `${Number(ultima.litros).toLocaleString()} L`);
                    setTexto('distancia', `${ultima.distancia_cm} cm`);

                    const barra = document.getElementById('barra-progreso');
                    if (barra) barra.style.width = `${porcentaje}%`;

                    const badge = document.getElementById('badge-estado');
                    if (badge) {
                        badge.textContent = ultima.estado;
                        if (ultima.estado === 'Normal' || ultima.estado === 'Óptimo') {
                            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800';
                            if (barra) barra.className = 'bg-emerald-500 h-full transition-all duration-500';
                        } else if (ultima.estado === 'Bajo') {
                            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-amber-950 text-amber-400 border border-amber-800';
                            if (barra) barra.className = 'bg-amber-500 h-full transition-all duration-500';
                        } else {
                            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-full bg-red-950 text-red-400 border border-red-800';
                            if (barra) barra.className = 'bg-red-500 h-full transition-all duration-500';
                        }
                    }
                } else {
                    setTexto('porcentaje', 'Sin datos');
                    setTexto('litros', '0 L');
                    setTexto('distancia', '--');
                }

                // Actualizar puntos de la gráfica
                if (estacion.historial && chartInstancia) {
                    const etiquetas = estacion.historial.map(item => {
                        const fecha = new Date(item.created_at);
                        return fecha.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    });
                    const valores = estacion.historial.map(item => parseFloat(item.porcentaje));

                    chartInstancia.data.labels = etiquetas;
                    chartInstancia.data.datasets[0].data = valores;
                    chartInstancia.update();
                }

                // Mapeo de Telemetría de Red
                if (estacion.red) {
                    setTexto('red-ip', estacion.red.ip_origen);
                    setTexto('red-heartbeat', estacion.red.ultimo_heartbeat);
                    setTexto('red-protocolo', `${estacion.red.protocolo} | ${estacion.red.firmware_ver}`);

                    const badgeRed = document.getElementById('red-estado');
                    const pingRed = document.getElementById('indicador-red-ping');

                    if (badgeRed && pingRed) {
                        if (estacion.red.estado_nodo === 'ONLINE') {
                            badgeRed.textContent = 'ONLINE (OPERATIVO)';
                            badgeRed.className = 'text-sm font-extrabold text-emerald-400';
                            pingRed.className = 'w-3 h-3 rounded-full bg-emerald-500 animate-pulse';
                        } else {
                            badgeRed.textContent = 'OFFLINE (SIN RESPUESTA)';
                            badgeRed.className = 'text-sm font-extrabold text-red-400';
                            pingRed.className = 'w-3 h-3 rounded-full bg-red-500';
                        }
                    }
                }

                // Mapear Umbrales de Alerta
                if (estacion.umbrales) {
                    const inputCritico = document.getElementById('input-umbral-critico');
                    const inputBajo = document.getElementById('input-umbral-bajo');
                    const inputAlto = document.getElementById('input-umbral-alto');

                    if (inputCritico && document.activeElement !== inputCritico) {
                        inputCritico.value = estacion.umbrales.umbral_critico;
                    }
                    if (inputBajo && document.activeElement !== inputBajo) {
                        inputBajo.value = estacion.umbrales.umbral_bajo;
                    }
                    if (inputAlto && document.activeElement !== inputAlto) {
                        inputAlto.value = estacion.umbrales.umbral_alto;
                    }
                }

                // Renderizar Tabla de Logs de Auditoría y Seguridad
                const tbodyAudit = document.getElementById('tabla-audit-body');

                if (tbodyAudit && estacion.logs_seguridad) {
                    if (estacion.logs_seguridad.length === 0) {
                        tbodyAudit.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500 font-sans">
                            Sin registros de auditoría recientes.
                        </td>
                    </tr>`;
                    } else {
                        tbodyAudit.innerHTML = estacion.logs_seguridad.map(log => {
                            let badgeClass = 'bg-emerald-950 text-emerald-400 border-emerald-800';
                            let statusClass = 'text-emerald-400';

                            if (log.nivel === 'WARN') {
                                badgeClass = 'bg-amber-950 text-amber-400 border-amber-800';
                                statusClass = 'text-amber-400';
                            } else if (log.nivel === 'DANGER') {
                                badgeClass = 'bg-red-950 text-red-400 border-red-800';
                                statusClass = 'text-red-400';
                            }

                            return `
                    <tr class="hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-4 font-sans text-slate-400">${log.fecha}</td>
                        <td class="py-3 px-4 font-bold text-sky-400">${log.ip}</td>
                        <td class="py-3 px-4 font-sans text-slate-200">${log.evento}</td>
                        <td class="py-3 px-4 text-center font-bold ${statusClass}">${log.estado_http}</td>
                        <td class="py-3 px-4 text-right">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold border uppercase ${badgeClass}">
                                ${log.nivel}
                            </span>
                        </td>
                    </tr>`;
                        }).join('');
                    }
                }

            } catch (error) {
                console.error('Error al obtener datos de la estación:', error);
            }
        }

        // 3. Función global de guardado de umbrales
        async function guardarUmbrales(event) {
            if (event) event.preventDefault();

            const inputCritico = document.getElementById('input-umbral-critico');
            const inputBajo = document.getElementById('input-umbral-bajo');
            const inputAlto = document.getElementById('input-umbral-alto');

            if (!inputCritico || !inputBajo || !inputAlto) return;

            const critico = parseFloat(inputCritico.value);
            const bajo = parseFloat(inputBajo.value);
            const alto = parseFloat(inputAlto.value);

            // Validación de consistencia
            if (critico >= bajo) {
                mostrarMensajeUmbral('El umbral crítico debe ser menor que el umbral bajo.', 'error');
                return;
            }
            if (bajo >= alto) {
                mostrarMensajeUmbral('El umbral bajo debe ser menor que el umbral alto.', 'error');
                return;
            }

            try {
                const response = await fetch(`/api/estacion/${estacionId}/umbrales`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        umbral_critico: critico,
                        umbral_bajo: bajo,
                        umbral_alto: alto
                    })
                });

                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status}`);
                }

                const res = await response.json();
                mostrarMensajeUmbral(res.mensaje || 'Parámetros guardados y actualizados correctamente en el sistema.', 'exito');

                cargarDatosEstacion();

            } catch (error) {
                console.error('Error al guardar los umbrales:', error);
                mostrarMensajeUmbral('Ocurrió un error al intentar guardar en la base de datos.', 'error');
            }
        }

        function mostrarMensajeUmbral(mensaje, tipo) {
            const msgBox = document.getElementById('status-umbral-msg');
            if (!msgBox) return;

            msgBox.textContent = mensaje;
            msgBox.classList.remove('hidden', 'bg-red-950', 'text-red-400', 'border-red-800', 'bg-emerald-950', 'text-emerald-400', 'border-emerald-800');

            if (tipo === 'error') {
                msgBox.classList.add('bg-red-950', 'text-red-400', 'border', 'border-red-800');
            } else {
                msgBox.classList.add('bg-emerald-950', 'text-emerald-400', 'border', 'border-emerald-800');
            }

            setTimeout(() => {
                msgBox.classList.add('hidden');
            }, 4000);
        }

        // 4. Funciones del Módulo de Exportación y Reportes
        function descargarCSV() {
            mostrarMensajeExportacion('Generando archivo CSV...', 'info');
            window.location.href = `/api/estacion/${estacionId}/exportar/csv`;

            setTimeout(() => {
                mostrarMensajeExportacion('Descarga iniciada con éxito.', 'exito');
            }, 2000);
        }

        async function generarReporteImprimible() {
            try {
                mostrarMensajeExportacion('Compilando datos del reporte...', 'info');

                const response = await fetch(`/api/estacion/${estacionId}/reporte/resumen`);
                if (!response.ok) throw new Error('Error al obtener el reporte');

                const { data } = await response.json();

                const ventana = window.open('', '_blank', 'width=800,height=900');
                ventana.document.write(`
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Reporte Técnico - ${data.estacion}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 30px; color: #1e293b; }
                        .header { text-align: center; border-bottom: 2px solid #0284c7; padding-bottom: 10px; margin-bottom: 20px; }
                        .header h1 { margin: 0; color: #0284c7; font-size: 20px; }
                        .header p { margin: 5px 0 0; font-size: 12px; color: #64748b; }
                        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
                        .card { border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; background: #f8fafc; }
                        .card span { font-size: 11px; color: #64748b; display: block; }
                        .card strong { font-size: 16px; color: #0f172a; }
                        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                        th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; text-align: left; }
                        th { background-color: #f1f5f9; }
                        .footer { margin-top: 30px; font-size: 10px; text-align: center; color: #94a3b8; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>SISTEMA DE TELEMETRÍA Y CONTROL DE COMBUSTIBLE</h1>
                        <p>REPORTE DE ESTADO Y RESUMEN OPERATIVO DE ESTACIÓN</p>
                    </div>

                    <div class="grid">
                        <div class="card"><span>ESTACIÓN:</span><strong>${data.estacion}</strong></div>
                        <div class="card"><span>UBICACIÓN:</span><strong>${data.ubicacion}</strong></div>
                        <div class="card"><span>CAPACIDAD MÁXIMA:</span><strong>${Number(data.capacidad_maxima).toLocaleString()} L</strong></div>
                        <div class="card"><span>FECHA EMISIÓN:</span><strong>${data.fecha_reporte}</strong></div>
                    </div>

                    <h3>Resumen Estadístico</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Métrica</th>
                                <th>Valor Registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Total de Lecturas Almacenadas</td><td><strong>${data.total_lecturas}</strong></td></tr>
                            <tr><td>Promedio de Nivel Operativo</td><td><strong>${data.promedio_porcentaje}%</strong></td></tr>
                            <tr><td>Volumen Máximo Alcanzado</td><td><strong>${Number(data.max_litros).toLocaleString()} L</strong></td></tr>
                            <tr><td>Volumen Mínimo Alcanzado</td><td><strong>${Number(data.min_litros).toLocaleString()} L</strong></td></tr>
                            <tr><td>Alertas de Nivel Crítico</td><td><strong style="color:red;">${data.conteo_criticos}</strong></td></tr>
                            <tr><td>Alertas de Nivel Bajo</td><td><strong style="color:orange;">${data.conteo_bajos}</strong></td></tr>
                            <tr><td>Registros en Estado Normal</td><td><strong style="color:green;">${data.conteo_normales}</strong></td></tr>
                        </tbody>
                    </table>

                    <div class="footer">
                        Documento generado automáticamente por la plataforma de telemetría IoT.
                    </div>

                    <script>
                        window.onload = function() {
                            window.print();
                        };
                    <\/script>
                </body>
                </html>
            `);

                ventana.document.close();
                mostrarMensajeExportacion('Reporte generado correctamente.', 'exito');

            } catch (error) {
                console.error('Error al generar reporte:', error);
                mostrarMensajeExportacion('No se pudo generar el reporte consolidado.', 'error');
            }
        }

        function mostrarMensajeExportacion(mensaje, tipo) {
            const msgBox = document.getElementById('status-export-msg');
            if (!msgBox) return;

            msgBox.textContent = mensaje;
            msgBox.classList.remove('hidden', 'bg-red-950', 'text-red-400', 'border-red-800', 'bg-emerald-950', 'text-emerald-400', 'border-emerald-800', 'bg-sky-950', 'text-sky-400', 'border-sky-800');

            if (tipo === 'error') {
                msgBox.classList.add('bg-red-950', 'text-red-400', 'border', 'border-red-800');
            } else if (tipo === 'exito') {
                msgBox.classList.add('bg-emerald-950', 'text-emerald-400', 'border', 'border-emerald-800');
            } else {
                msgBox.classList.add('bg-sky-950', 'text-sky-400', 'border', 'border-sky-800');
            }

            setTimeout(() => {
                msgBox.classList.add('hidden');
            }, 4000);
        }

        // 5. Exposición al ámbito global `window`
        window.guardarUmbrales = guardarUmbrales;
        window.descargarCSV = descargarCSV;
        window.generarReporteImprimible = generarReporteImprimible;
        window.mostrarMensajeExportacion = mostrarMensajeExportacion;

        // 6. Ejecución al cargar el DOM
        document.addEventListener('DOMContentLoaded', () => {
            inicializarGrafico();
            cargarDatosEstacion();
            setInterval(cargarDatosEstacion, 3000);
        });
    </script>
</body>

</html>