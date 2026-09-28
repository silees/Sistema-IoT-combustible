<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad de Combustible - Consulta Pública</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-8">
    <div class="max-w-5xl mx-auto">
        <header class="mb-8 text-center md:text-left border-b border-slate-800 pb-4">
            <h1 class="text-3xl font-extrabold text-sky-400">Estado de Estaciones de Servicio</h1>
            <p class="text-slate-400 text-sm mt-1">Consulta en tiempo real la disponibilidad de combustible para conductores.</p>
        </header>

        <!-- Grilla de Estaciones -->
        <div id="estaciones-container" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="text-slate-500">Cargando estaciones de servicio...</div>
        </div>
    </div>

    <script>
        async function cargarEstaciones() {
            try {
                const response = await fetch('/api/estaciones/publicas');
                const estaciones = await response.json();

                const container = document.getElementById('estaciones-container');
                container.innerHTML = '';

                estaciones.forEach(estacion => {
                    const lectura = estacion.ultima_lectura;
                    const porcentaje = lectura ? lectura.porcentaje : 0;
                    const estado = lectura ? lectura.estado : 'Sin Datos';

                    let badgeColor = "bg-gray-800 text-gray-400 border-gray-700";
                    let estadoTexto = "Desconocido";
                    let icono = "❓";

                    if (estado === 'Normal') {
                        badgeColor = "bg-emerald-950 text-emerald-300 border-emerald-800";
                        estadoTexto = "Disponible";
                        icono = "🟢";
                    } else if (estado === 'Bajo') {
                        badgeColor = "bg-amber-950 text-amber-300 border-amber-800";
                        estadoTexto = "Nivel Bajo / Fila Alta";
                        icono = "🟡";
                    } else if (estado === 'Crítico') {
                        badgeColor = "bg-red-950 text-red-300 border-red-800";
                        estadoTexto = "Agotado / Sin Stock";
                        icono = "🔴";
                    }

                    const cardHtml = `
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-lg hover:border-slate-700 transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-xl font-bold text-white">${estacion.nombre}</h2>
                                    <p class="text-xs text-slate-400 mt-1">📍 ${estacion.ubicacion}</p>
                                </div>
                                <span class="px-3 py-1 text-xs font-bold rounded-full border ${badgeColor} flex items-center gap-1">
                                    ${icono} ${estadoTexto}
                                </span>
                            </div>

                            <div class="mt-6">
                                <div class="flex justify-between text-xs text-slate-400 mb-1 font-semibold">
                                    <span>Capacidad Tanque</span>
                                    <span>${porcentaje}% Disponible</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-3 overflow-hidden border border-slate-800">
                                    <div class="h-full ${porcentaje <= 15 ? 'bg-red-500' : (porcentaje <= 35 ? 'bg-amber-500' : 'bg-blue-500')} transition-all duration-500" style="width: ${porcentaje}%"></div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-800 flex justify-between items-center text-xs text-slate-500">
                                <span>Actualizado: ${lectura ? new Date(lectura.created_at).toLocaleTimeString() : 'N/A'}</span>
                                <a href="/admin/estacion/${estacion.id}" class="text-sky-400 hover:underline font-semibold">Acceso Técnico →</a>
                            </div>
                        </div>
                    `;
                    container.innerHTML += cardHtml;
                });

            } catch (error) {
                console.error("Error al consultar estaciones públicas:", error);
            }
        }

        setInterval(cargarEstaciones, 3000);
        cargarEstaciones();
    </script>
</body>
</html>