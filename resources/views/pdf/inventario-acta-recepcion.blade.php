<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Recepcion</title>
    <style>
        @page { margin: 1.6cm 1.5cm; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; }
        .header { text-align: center; margin-bottom: 18px; position: relative; }
        .logo { position: absolute; left: 0; top: 0; width: 72px; }
        .title { font-size: 12pt; font-weight: bold; margin: 0; }
        .subtitle { font-size: 11pt; font-weight: bold; margin: 4px 0; }
        .meta { margin: 18px 0; line-height: 1.65; }
        .label { font-weight: bold; }
        .intro { margin: 16px 0 12px; line-height: 1.5; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { text-align: center; font-weight: bold; background: #f0f0f0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .total-label { font-weight: bold; text-align: center; }
        .signature { margin-top: 70px; text-align: center; }
        .signature-line { border-top: 1px solid #111; display: inline-block; min-width: 260px; padding-top: 5px; }
        .small { font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <img class="logo" src="{{ public_path('Logo/logounah.png') }}" alt="UNAH">
        <p class="title">Universidad Nacional Autonoma de Honduras</p>
        <p class="subtitle">Acta de recepcion No. {{ $entrada->numero_entrada }}</p>
    </div>

    <div class="meta">
        <div><span class="label">Fecha:</span> {{ optional($entrada->fecha_entrada)->format('d/m/Y') }}</div>
        <div><span class="label">Nombre de la unidad:</span> {{ $entrada->requisicion?->departamento?->name ?? $entrada->bodega?->nombre ?? 'Bodega' }}</div>
        <div><span class="label">Proveedor:</span> {{ $entrada->proveedor ?? '-' }}</div>
        <div><span class="label">Factura:</span> {{ $entrada->numero_factura ?? '-' }}</div>
        @if ($entrada->orden_compra_referencia)
            <div><span class="label">Orden de compra:</span> {{ $entrada->orden_compra_referencia }}</div>
        @endif
    </div>

    <p class="intro">
        Por medio de la presente se hace constar que esta unidad recibio a conformidad por parte del proveedor lo siguiente:
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">No.</th>
                <th>Descripcion</th>
                <th style="width: 15%;">Unidad de medida</th>
                <th style="width: 10%;">Cant.</th>
                <th style="width: 16%;">Precio unitario</th>
                <th style="width: 16%;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $detalle)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        {{ $detalle->producto?->nombre ?? 'Producto no disponible' }}
                        @if ($detalle->codigo_lote)
                            <br><span class="small">Lote: {{ $detalle->codigo_lote }}</span>
                        @endif
                    </td>
                    <td class="center">{{ $detalle->producto?->unidadMedida?->nombre ?? '-' }}</td>
                    <td class="center">{{ number_format((float) $detalle->cantidad, 2) }}</td>
                    <td class="right">L. {{ number_format((float) ($detalle->costo_unitario ?? 0), 2) }}</td>
                    <td class="right">L. {{ number_format((float) ($detalle->total ?? 0), 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="total-label">TOTAL</td>
                <td class="right"><strong>L. {{ number_format($total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if ($entrada->observacion)
        <p class="intro"><span class="label">Observacion:</span> {{ $entrada->observacion }}</p>
    @endif

    <div class="signature">
        <span class="signature-line">
            {{ $entrada->usuario?->empleado?->nombre ?? $entrada->usuario?->name ?? 'Responsable' }}<br>
            Administrador
        </span>
    </div>
</body>
</html>
