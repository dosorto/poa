<?php

namespace App\Http\Controllers;

use App\Models\Inventario\InventarioEntrada;
use Barryvdh\DomPDF\Facade\Pdf;

class InventarioEntradaActaController extends Controller
{
    public function show(InventarioEntrada $entrada)
    {
        $pdf = Pdf::loadView('pdf.inventario-acta-recepcion', $this->datosActa($entrada));
        $pdf->setPaper('letter', 'portrait');

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Acta-Recepcion-' . $entrada->numero_entrada . '.pdf"');
    }

    public function download(InventarioEntrada $entrada)
    {
        $pdf = Pdf::loadView('pdf.inventario-acta-recepcion', $this->datosActa($entrada));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('Acta-Recepcion-' . $entrada->numero_entrada . '.pdf');
    }

    private function datosActa(InventarioEntrada $entrada): array
    {
        $entrada->load([
            'bodega',
            'detalles.producto.unidadMedida',
            'requisicion.departamento.unidadEjecutora.administrador.empleado',
            'usuario.empleado.unidadEjecutora.administrador.empleado',
        ]);

        return [
            'entrada' => $entrada,
            'detalles' => $entrada->detalles,
            'total' => $entrada->detalles->sum(fn ($detalle) => (float) ($detalle->total ?? 0)),
            'marcaAgua' => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('Logo/LucemAspico-watermark-visible.png'))),
        ];
    }
}
