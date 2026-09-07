@php
    $estado = $detallePdf['estado'] ?? '';
    $estadoColor = match ($estado) {
        'Presentado' => 'bg-zinc-800 text-white',
        'Recibido' => 'bg-cyan-700 text-white',
        'En Proceso de Compra' => 'bg-blue-700 text-white',
        'Aprobado' => 'bg-green-700 text-white',
        'Rechazado' => 'bg-red-700 text-white',
        'Finalizado' => 'bg-emerald-700 text-white',
        default => 'bg-zinc-700 text-white',
    };
@endphp

<div class="bg-white text-zinc-950 dark:bg-zinc-950 dark:text-zinc-100 rounded-lg border border-zinc-300 dark:border-zinc-700 p-5 shadow-sm dark:shadow-none sm:p-7">
    <div class="relative text-center border-b border-zinc-300 dark:border-zinc-700 pb-5">
        <div class="absolute left-0 top-0 rounded-md bg-white px-2 py-1">
            <img src="{{ asset('Logo/logounah.png') }}" alt="UNAH" class="h-16 w-auto">
        </div>
        <h1 class="text-base font-bold leading-snug">UNIVERSIDAD NACIONAL AUTONOMA DE HONDURAS</h1>
        <p class="text-xs mt-1">CENTRO UNIVERSITARIO REGIONAL DEL LITORAL PACIFICO</p>
        <p class="text-xs">DEPARTAMENTO DE ADMINISTRACION</p>
        <p class="text-sm font-bold mt-4">REQUISICION MATERIALES</p>
        <span class="absolute right-0 top-0 inline-flex px-3 py-1.5 rounded-lg text-xs font-semibold {{ $estadoColor }}">
            {{ $estado ?: 'Presentado' }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-4 mt-5">
        <div class="text-xs leading-relaxed text-zinc-700 dark:text-zinc-300">
            <p class="font-semibold text-zinc-900 dark:text-zinc-100">Instrucciones:</p>
            <p>
                Favor ingresar los datos para la solicitud, indicar con claridad y detalle la descripcion de los equipos o insumos requeridos
                (Cantidad, medidas, color, material, especificaciones tecnicas, etc). Hacer referencia al numero de actividad en el POA.
            </p>
        </div>
    </div>

    <div class="overflow-x-auto mt-5">
        <table class="w-full border-collapse text-xs">
            <tbody>
                <tr>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left w-1/4 dark:border-zinc-600 dark:bg-zinc-800">DEPARTAMENTO SOLICITANTE:</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 w-1/3">{{ $detallePdf['departamento'] ?? '-' }}</td>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left dark:border-zinc-600 dark:bg-zinc-800">REQUISICION No.</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $detallePdf['correlativo'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left dark:border-zinc-600 dark:bg-zinc-800">JEFE DEPARTAMENTO:</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $detallePdf['jefe_departamento'] ?? '-' }}</td>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left dark:border-zinc-600 dark:bg-zinc-800">FECHA DE SOLICITUD:</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-0 py-0">
                        <div class="grid grid-cols-3 text-center">
                            <span class="border-r border-zinc-400 dark:border-zinc-600 py-2">{{ $detallePdf['fecha_presentado_dia'] ?? '-' }}</span>
                            <span class="border-r border-zinc-400 dark:border-zinc-600 py-2">{{ $detallePdf['fecha_presentado_mes'] ?? '-' }}</span>
                            <span class="py-2">{{ $detallePdf['fecha_presentado_anio'] ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left dark:border-zinc-600 dark:bg-zinc-800">PROPOSITO / ACTIVIDAD:</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $detallePdf['proposito'] ?? '-' }}</td>
                    <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 text-left dark:border-zinc-600 dark:bg-zinc-800">FECHA REQUERIDO:</th>
                    <td class="border border-zinc-400 dark:border-zinc-600 px-0 py-0">
                        <div class="grid grid-cols-3 text-center">
                            <span class="border-r border-zinc-400 dark:border-zinc-600 py-2">{{ $detallePdf['fecha_requerido_dia'] ?? '-' }}</span>
                            <span class="border-r border-zinc-400 dark:border-zinc-600 py-2">{{ $detallePdf['fecha_requerido_mes'] ?? '-' }}</span>
                            <span class="py-2">{{ $detallePdf['fecha_requerido_anio'] ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="overflow-x-auto mt-5">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr class="bg-[#B8CCE4] dark:bg-zinc-800">
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">No.</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">CANTIDAD</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-left">DESCRIPCION</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">UNIDAD MEDIDA</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">PRECIO UNIDAD</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">TOTAL</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">REF. POA</th>
                    <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">REF. ACTA ENTREGA</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($detallePdf['recursos'] ?? []) as $index => $recurso)
                    <tr>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-center">{{ $recurso['cantidad'] }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $recurso['recurso'] }} {{ $recurso['detalle_tecnico'] }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-center">{{ $recurso['unidad'] ?? '-' }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-right">L {{ number_format($recurso['precio_unitario'], 2) }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 text-right">L {{ number_format($recurso['total'], 2) }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $recurso['ref_poa'] ?? '' }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">{{ $recurso['ref_acta'] ?? '' }}</td>
                    </tr>
                @endforeach

                @for ($i = count($detallePdf['recursos'] ?? []); $i < 5; $i++)
                    <tr>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3 text-center">{{ $i + 1 }}</td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                        <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td>
                    </tr>
                @endfor

                <tr>
                    <td colspan="5" class="border border-zinc-400 dark:border-zinc-600 px-2 py-2"></td>
                    <td class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 font-semibold dark:border-zinc-600 dark:bg-zinc-800">COSTO TOTAL:</td>
                    <td colspan="2" class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 font-semibold dark:border-zinc-600 dark:bg-zinc-800">L {{ number_format($detallePdf['monto_total'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-5 text-xs font-bold">FAVOR ADJUNTAR COTIZACIONES</div>
    <table class="w-full border-collapse text-xs mt-2">
        <thead>
            <tr>
                <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 dark:border-zinc-600 dark:bg-zinc-800">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3 min-h-10 align-top">{{ $detallePdf['observaciones'] ?? '' }}</td>
            </tr>
            <tr><td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td></tr>
            <tr><td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td></tr>
        </tbody>
    </table>

    <table class="w-full border-collapse text-xs mt-5">
        <thead>
            <tr class="bg-[#B8CCE4] dark:bg-zinc-800">
                <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 w-[32%]">FIRMA Y SELLO DEL SOLICITANTE</th>
                <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2 w-[23%]">RECIBIDO POR</th>
                <th class="border border-zinc-400 dark:border-zinc-600 px-2 py-2">Vo. Bo. (PLANIFICACION)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-zinc-400 dark:border-zinc-600 h-20"></td>
                <td class="border border-zinc-400 dark:border-zinc-600 p-0 align-top">
                    <div class="grid grid-cols-[70px_1fr]">
                        <span class="bg-[#B8CCE4] border-r border-b border-zinc-400 px-2 py-1 font-semibold dark:border-zinc-600 dark:bg-zinc-800">NOMBRE</span>
                        <span class="border-b border-zinc-400 dark:border-zinc-600 px-2 py-1">{{ $detallePdf['recibido_nombre'] ?? '' }}</span>
                        <span class="bg-[#B8CCE4] border-r border-b border-zinc-400 px-2 py-1 font-semibold dark:border-zinc-600 dark:bg-zinc-800">FECHA</span>
                        <span class="border-b border-zinc-400 dark:border-zinc-600 px-2 py-1">{{ $detallePdf['recibido_fecha'] ?? '' }}</span>
                        <span class="bg-[#B8CCE4] border-r border-zinc-400 px-2 py-1 font-semibold dark:border-zinc-600 dark:bg-zinc-800">HORA</span>
                        <span class="px-2 py-1">{{ $detallePdf['recibido_hora'] ?? '' }}</span>
                    </div>
                </td>
                <td class="border border-zinc-400 dark:border-zinc-600 h-20"></td>
            </tr>
        </tbody>
    </table>

    <table class="w-full border-collapse text-xs mt-5">
        <thead>
            <tr>
                <th class="border border-zinc-400 bg-[#B8CCE4] px-2 py-2 dark:border-zinc-600 dark:bg-zinc-800">PARA USO DE LA ADMINISTRACION</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td></tr>
            <tr><td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td></tr>
            <tr><td class="border border-zinc-400 dark:border-zinc-600 px-2 py-3"></td></tr>
        </tbody>
    </table>
</div>
