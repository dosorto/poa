<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actividad {{ $accion }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Actividad {{ $accion }}</h1>

    <p>Hola {{ $destinatario->name ?? 'usuario' }},</p>

    <p>La actividad fue {{ $accion }} correctamente.</p>

    @if($detalle)
        <p><strong>Actualizacion realizada:</strong> {{ $detalle }}</p>
    @endif

    <p>
        <strong>Actividad:</strong> {{ $actividad->nombre ?? '-' }}<br>
        <strong>Correlativo:</strong> {{ $actividad->correlativo_formateado ?? '-' }}<br>
        <strong>Estado:</strong> {{ $actividad->estado ?? '-' }}<br>
        <strong>POA:</strong> {{ $actividad->poa->anio ?? '-' }}<br>
        <strong>Departamento:</strong> {{ $actividad->departamento->name ?? '-' }}<br>
        <strong>Unidad ejecutora:</strong> {{ $actividad->unidadEjecutora->name ?? '-' }}<br>
        <strong>Tipo:</strong> {{ $actividad->tipo->tipo ?? '-' }}<br>
        <strong>Categoria:</strong> {{ $actividad->categoria->categoria ?? '-' }}<br>
        <strong>Resultado:</strong> {{ $actividad->resultado->nombre ?? '-' }}
    </p>

    <p>
        <strong>Descripcion:</strong><br>
        {{ $actividad->descripcion ?? '-' }}
    </p>

    <h2 style="font-size: 16px; margin-top: 18px;">Indicadores y planificaciones</h2>
    @forelse($actividad->indicadores as $indicador)
        <p style="margin-bottom: 8px;">
            <strong>{{ $indicador->nombre }}</strong><br>
            Meta: {{ $indicador->cantidadPlanificada ?? '-' }}<br>
            Planificado:
            @if($indicador->planificacions->isEmpty())
                -
            @else
                {{ $indicador->planificacions->sum('cantidad') }}
            @endif
        </p>

        @if($indicador->planificacions->isNotEmpty())
            <ul>
                @foreach($indicador->planificacions as $planificacion)
                    <li>
                        Trimestre {{ $planificacion->mes->trimestre->trimestre ?? '-' }}:
                        {{ $planificacion->cantidad ?? '-' }}
                        ({{ $planificacion->fechaInicio ? \Carbon\Carbon::parse($planificacion->fechaInicio)->format('d/m/Y') : '-' }}
                        - {{ $planificacion->fechaFin ? \Carbon\Carbon::parse($planificacion->fechaFin)->format('d/m/Y') : '-' }})
                    </li>
                @endforeach
            </ul>
        @endif
    @empty
        <p>No hay indicadores registrados.</p>
    @endforelse

    <h2 style="font-size: 16px; margin-top: 18px;">Tareas</h2>
    @forelse($actividad->tareas as $tarea)
        <p style="margin-bottom: 8px;">
            <strong>{{ $tarea->nombre }}</strong><br>
            Estado: {{ $tarea->estado ?? '-' }}<br>
            Presupuesto: L {{ number_format($tarea->presupuestos->sum('total'), 2) }}
        </p>
    @empty
        <p>No hay tareas registradas.</p>
    @endforelse
</body>
</html>
