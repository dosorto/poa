<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Entrega Intermedia</title>
    @php
        $isDarkPdf = ($pdfTheme ?? 'light') === 'dark';
        $pageBg = $isDarkPdf ? '#050505' : '#ffffff';
        $textColor = $isDarkPdf ? '#f5f5f5' : '#111111';
        $mutedColor = $isDarkPdf ? '#d4d4d8' : '#111111';
        $borderColor = $isDarkPdf ? '#525252' : '#000000';
        $headerBg = $isDarkPdf ? '#262626' : '#B8CCE4';
        $footerColor = $isDarkPdf ? '#a3a3a3' : '#666666';
    @endphp
    <style>
        @page {
            margin: 2cm 1.5cm;
            background-color: {{ $pageBg }};
        }
        html {
            background-color: {{ $pageBg }};
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: {{ $textColor }};
            background-color: {{ $pageBg }};
        }
        .page-background {
            position: fixed;
            top: -2cm;
            right: -1.5cm;
            bottom: -2cm;
            left: -1.5cm;
            background-color: {{ $pageBg }};
            z-index: -1;
        }
        .logo-row {
            width: 100%;
            margin-bottom: 0;
            padding-top: 0;
        }
        .logo-img {
            float: left;
            margin-left: 0;
            margin-top: 0;
            margin-bottom: 0;
            height: 70px;
            width: auto;
            /* Elimina cualquier espacio extra arriba */
            display: block;
        }
        .header-text {
            clear: both;
            text-align: center;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .header-text h1 {
            font-size: 13pt;
            margin: 0 0 2px 0;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-text h2 {
            font-size: 12pt;
            margin: 2px 0 6px 0;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-text h3 {
            font-size: 11pt;
            margin: 6px 0 0 0;
            font-weight: bold;
        }
        .header-divider {
            border-bottom: 2px solid #000;
            margin: 10px 0 20px 0;
        }
        .title {
            text-align: left;
            font-size: 10pt;
            font-weight: normal;
            margin: 20px 0 15px 0;
            text-decoration: none;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            margin: 8px 0;
            font-size: 10pt;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }
        .content-text {
            text-align: justify;
            margin: 20px 0 15px 0;
            line-height: 1.6;
        }
        .recipient-reference-table {
            width: 100%;
            margin: 0 0 14px 0;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .recipient-reference-table td {
            border: none;
            padding: 0;
            line-height: 1.15;
            color: {{ $textColor }};
            background-color: {{ $pageBg }};
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }
        table th, table td {
            border: 1px solid {{ $borderColor }};
            padding: 8px 6px;
            text-align: left;
        }
        table td {
            background-color: {{ $pageBg }};
        }
        table th {
            background-color: {{ $headerBg }};
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signatures {
            margin-top: 80px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: top;
        }
        .signature-line {
            border-top: 2px solid {{ $borderColor }};
            margin-top: 60px;
            padding-top: 8px;
            font-size: 9pt;
            line-height: 1.5;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: {{ $footerColor }};
        }
    </style>
</head>
<body>
    <div class="page-background"></div>
    @php
        $directorDecano = $requisicion->departamento?->unidadEjecutora?->directorDecano;
        $directorEmpleado = $directorDecano?->empleado;
        $nombreDirectorDecano = $directorEmpleado
            ? trim(($directorEmpleado->nombres ?? $directorEmpleado->nombre ?? '') . ' ' . ($directorEmpleado->apellidos ?? $directorEmpleado->apellido ?? ''))
            : ($directorDecano->name ?? 'CELEO EMILIO ARIAS');
    @endphp
    <div class="logo-row" style="margin-top:0; padding-top:0;">
        <img class="logo-img" src="{{ public_path('Logo/logounah.png') }}" alt="Logo UNAH">
    </div>
    <div class="header-text">
        <h1>UNIVERSIDAD NACIONAL AUTÓNOMA DE HONDURAS</h1>
        <h2>UNAH</h2>
        <h3>ACTA ENTREGA</h3>
    </div>

    <table class="recipient-reference-table">
        <tr>
            <td>El suscrito administrador del Centro Regional del Litoral Pacífico <strong>HACE CONSTAR</strong> que se entregó los suministros a: <strong>@if($requisicion->creador->empleado){{ trim(($requisicion->creador->empleado->nombres ?? $requisicion->creador->empleado->nombre) . ' ' . ($requisicion->creador->empleado->apellidos ?? $requisicion->creador->empleado->apellido)) }}@else{{ $requisicion->creador->name }}@endif</strong></td>
        </tr>
        <tr>
            <td>Según requisición #{{ $requisicion->correlativo }}. Que a continuación se detallan:</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 10%;">Cantidad</th>
                <th style="width: 8%;">UM</th>
                <th style="width: 38%;">Descripción</th>
                <th style="width: 16%;">Precio Unitario</th>
                <th style="width: 22%;">No. de factura</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total = 0;
                $contador = 1;
                $detallesAgrupados = $detalles->groupBy('idDetalleRequisicion');
            @endphp
            @foreach($detallesAgrupados as $idDetalle => $grupo)
                @php
                    $primerDetalle = $grupo->first();
                    $detalleReq = $primerDetalle->detalleRequisicion;
                    $presupuesto = $detalleReq->presupuesto;
                    $cantidadTotal = $grupo->sum('log_cant_ejecutada');
                    $montoUnitario = $grupo->last()->log_monto_unitario_ejecutado;
                    $subtotal = $cantidadTotal * $montoUnitario;
                    $total += $subtotal;
                    $ultimaEjecucion = $primerDetalle->detalleEjecucionPresupuestaria;
                    $factura = $ultimaEjecucion->referenciaActaEntrega ?? '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $contador++ }}</td>
                    <td class="text-center">{{ number_format($cantidadTotal, 2) }}</td>
                    <td class="text-center">
                        {{ $presupuesto->unidadMedida->nombre ?? $presupuesto->unidadMedida->unidad ?? 'UND' }}
                    </td>
                    <td>
                        <strong>{{ $presupuesto->recurso ?? '-' }}</strong>
                        @if($presupuesto->detalle_tecnico)
                            <br><small style="color: {{ $mutedColor }}; font-size: 8pt;">{{ $presupuesto->detalle_tecnico }}</small>
                        @endif
                    </td>
                    <td class="text-right">L {{ number_format($montoUnitario, 2) }}</td>
                    <td class="text-center">{{ $factura }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: {{ $headerBg }};">
                <td colspan="4" class="text-right" style="font-size: 10pt;">TOTAL</td>
                <td class="text-right" style="font-size: 10pt;">L {{ number_format($total, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <p class="content-text" style="margin-top: 25px;">
        Para los fines que se estime conveniente se extiende en la ciudad de Choluteca a los 
        <strong>{{ $acta->fecha_extendida->day }}</strong> días del mes de 
        <strong>{{ $acta->fecha_extendida->locale('es')->translatedFormat('F') }}</strong> del año 
        <strong>{{ $acta->fecha_extendida->year }}</strong>.
    </p>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">
                <strong>{{ strtoupper($nombreDirectorDecano) }}</strong><br>
                Director<br>
                UNAH - CURLP
            </div>
        </div>
        <div class="signature-block">
            <div class="signature-line">
                <strong>
                    @if($requisicion->creador->empleado)
                        {{ strtoupper(trim(
                            ($requisicion->creador->empleado->nombres ?? $requisicion->creador->empleado->nombre)
                            . ' ' .
                            ($requisicion->creador->empleado->apellidos ?? $requisicion->creador->empleado->apellido)
                        )) }}
                    @else
                        {{ strtoupper($requisicion->creador->name) }}
                    @endif
                </strong><br>
                {{ $requisicion->departamento->name ?? '-' }}<br>
                UNAH - CURLP
            </div>
        </div>
    </div>

</body>
</html>
