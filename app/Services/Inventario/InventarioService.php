<?php

namespace App\Services\Inventario;

use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioEntradaDetalle;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioKardex;
use App\Models\Inventario\InventarioLote;
use App\Models\Inventario\InventarioProducto;
use App\Models\Inventario\InventarioSalida;
use App\Models\Inventario\InventarioSalidaDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    public function confirmarEntrada(InventarioEntrada $entrada): InventarioEntrada
    {
        return DB::transaction(function () use ($entrada) {
            $entrada = InventarioEntrada::with('detalles.producto')->lockForUpdate()->findOrFail($entrada->id);

            if ($entrada->estado !== 'borrador') {
                throw ValidationException::withMessages(['entrada' => 'Solo se pueden confirmar entradas en borrador.']);
            }

            if ($entrada->detalles->isEmpty()) {
                throw ValidationException::withMessages(['detalles' => 'La entrada debe tener al menos un producto.']);
            }

            foreach ($entrada->detalles as $detalle) {
                $lote = $this->resolverLoteEntrada($entrada, $detalle);
                $detalle->forceFill(['lote_id' => $lote->id])->save();
                $this->aumentarExistencia(
                    bodegaId: $entrada->bodega_id,
                    productoId: $detalle->producto_id,
                    loteId: $lote->id,
                    cantidad: (float) $detalle->cantidad,
                    usuarioId: $entrada->usuario_id,
                    documentoTipo: 'inventario_entrada',
                    documentoId: $entrada->id,
                    referencia: $entrada->numero_entrada,
                    fecha: $entrada->fecha_entrada,
                    observacion: $entrada->observacion,
                    tipoMovimiento: 'entrada',
                );
            }

            $entrada->forceFill(['estado' => 'confirmado'])->save();

            return $entrada->refresh();
        });
    }

    public function confirmarSalida(InventarioSalida $salida): InventarioSalida
    {
        return DB::transaction(function () use ($salida) {
            $salida = InventarioSalida::with('detalles.producto')->lockForUpdate()->findOrFail($salida->id);

            if ($salida->estado !== 'borrador') {
                throw ValidationException::withMessages(['salida' => 'Solo se pueden confirmar salidas en borrador.']);
            }

            if ($salida->detalles->isEmpty()) {
                throw ValidationException::withMessages(['detalles' => 'La salida debe tener al menos un producto.']);
            }

            if ($salida->acta_entrega_id === null && $salida->tipo_salida === 'manual') {
                $this->validarSalidaManual($salida);
            }

            $this->validarSalidaDesdeActa($salida);

            foreach ($salida->detalles as $detalle) {
                $this->descontarExistencia(
                    bodegaId: $salida->bodega_id,
                    productoId: $detalle->producto_id,
                    loteId: $detalle->lote_id,
                    cantidad: (float) $detalle->cantidad,
                    usuarioId: $salida->usuario_id,
                    documentoTipo: 'inventario_salida',
                    documentoId: $salida->id,
                    referencia: $salida->numero_salida,
                    fecha: $salida->fecha_salida,
                    observacion: $salida->observacion,
                    tipoMovimiento: 'salida',
                );
            }

            $salida->forceFill(['estado' => 'confirmado'])->save();

            return $salida->refresh();
        });
    }

    public function registrarSaldoInicial(array $data): InventarioExistencia
    {
        return DB::transaction(function () use ($data) {
            $producto = InventarioProducto::findOrFail($data['producto_id']);
            $lote = $this->resolverLote(
                $producto,
                $data['codigo_lote'] ?? null,
                $data['fecha_ingreso'] ?? now()->toDateString(),
                $data['fecha_vencimiento'] ?? null,
                $data['ubicacion'] ?? null,
                $data['usuario_id'],
            );

            return $this->aumentarExistencia(
                bodegaId: $data['bodega_id'],
                productoId: $producto->id,
                loteId: $lote->id,
                cantidad: (float) $data['cantidad'],
                usuarioId: $data['usuario_id'],
                documentoTipo: 'inventario_importacion',
                documentoId: $data['documento_id'] ?? null,
                referencia: $data['referencia'] ?? 'Carga inicial',
                fecha: $data['fecha_movimiento'] ?? now(),
                observacion: $data['observacion'] ?? null,
                tipoMovimiento: 'saldo_inicial',
            );
        });
    }

    public function sugerirLotes(int $bodegaId, int $productoId): array
    {
        $producto = InventarioProducto::findOrFail($productoId);

        $query = InventarioExistencia::with('lote')
            ->where('inventario_existencias.bodega_id', $bodegaId)
            ->where('inventario_existencias.producto_id', $productoId)
            ->where('inventario_existencias.cantidad_disponible', '>', 0);

        if ($producto->maneja_vencimiento) {
            $query->join('inventario_lotes', 'inventario_existencias.lote_id', '=', 'inventario_lotes.id')
                ->orderByRaw('inventario_lotes.fecha_vencimiento IS NULL')
                ->orderBy('inventario_lotes.fecha_vencimiento')
                ->select('inventario_existencias.*');
        } else {
            $query->join('inventario_lotes', 'inventario_existencias.lote_id', '=', 'inventario_lotes.id')
                ->orderBy('inventario_lotes.fecha_ingreso')
                ->select('inventario_existencias.*');
        }

        return $query->get()->map(fn (InventarioExistencia $existencia) => [
            'lote_id' => $existencia->lote_id,
            'codigo_lote' => $existencia->lote?->codigo_lote,
            'cantidad_disponible' => $existencia->cantidad_disponible,
            'fecha_vencimiento' => $existencia->lote?->fecha_vencimiento?->format('Y-m-d'),
        ])->toArray();
    }

    public function anularEntrada(InventarioEntrada $entrada): InventarioEntrada
    {
        return DB::transaction(function () use ($entrada) {
            $entrada = InventarioEntrada::with('detalles')->lockForUpdate()->findOrFail($entrada->id);

            if ($entrada->estado !== 'confirmado') {
                throw ValidationException::withMessages(['entrada' => 'Solo se pueden anular entradas confirmadas.']);
            }

            foreach ($entrada->detalles as $detalle) {
                $this->descontarExistencia(
                    $entrada->bodega_id,
                    $detalle->producto_id,
                    $detalle->lote_id,
                    (float) $detalle->cantidad,
                    $entrada->usuario_id,
                    'inventario_entrada_anulacion',
                    $entrada->id,
                    $entrada->numero_entrada,
                    now(),
                    'Anulacion de entrada confirmada.',
                    'ajuste_negativo',
                );
            }

            $entrada->forceFill(['estado' => 'anulado'])->save();

            return $entrada->refresh();
        });
    }

    public function anularSalida(InventarioSalida $salida): InventarioSalida
    {
        return DB::transaction(function () use ($salida) {
            $salida = InventarioSalida::with('detalles')->lockForUpdate()->findOrFail($salida->id);

            if ($salida->estado !== 'confirmado') {
                throw ValidationException::withMessages(['salida' => 'Solo se pueden anular salidas confirmadas.']);
            }

            foreach ($salida->detalles as $detalle) {
                $this->aumentarExistencia(
                    $salida->bodega_id,
                    $detalle->producto_id,
                    $detalle->lote_id,
                    (float) $detalle->cantidad,
                    $salida->usuario_id,
                    'inventario_salida_anulacion',
                    $salida->id,
                    $salida->numero_salida,
                    now(),
                    'Anulacion de salida confirmada.',
                    'devolucion',
                );
            }

            $salida->forceFill(['estado' => 'anulado'])->save();

            return $salida->refresh();
        });
    }

    private function resolverLoteEntrada(InventarioEntrada $entrada, InventarioEntradaDetalle $detalle): InventarioLote
    {
        if ($detalle->lote_id) {
            return InventarioLote::findOrFail($detalle->lote_id);
        }

        $codigoLote = $detalle->codigo_lote ?: null;
        $producto = $detalle->producto;

        return $this->resolverLote(
            producto: $producto,
            codigoLote: $codigoLote,
            fechaIngreso: $entrada->fecha_entrada,
            fechaVencimiento: $detalle->fecha_vencimiento,
            ubicacion: null,
            usuarioId: $entrada->usuario_id,
        );
    }

    private function resolverLote(InventarioProducto $producto, ?string $codigoLote, mixed $fechaIngreso, mixed $fechaVencimiento, ?string $ubicacion, int $usuarioId): InventarioLote
    {
        $codigoLote = trim((string) ($codigoLote ?: 'SIN-LOTE'));

        return InventarioLote::firstOrCreate(
            [
                'producto_id' => $producto->id,
                'codigo_lote' => $producto->maneja_lote ? $codigoLote : 'SIN-LOTE',
            ],
            [
                'fecha_ingreso' => $fechaIngreso,
                'fecha_vencimiento' => $producto->maneja_vencimiento ? $fechaVencimiento : null,
                'ubicacion' => $ubicacion,
                'estado' => 'disponible',
                'created_by' => $usuarioId,
            ],
        );
    }

    private function aumentarExistencia(int $bodegaId, int $productoId, ?int $loteId, float $cantidad, int $usuarioId, ?string $documentoTipo, ?int $documentoId, ?string $referencia, mixed $fecha, ?string $observacion, string $tipoMovimiento): InventarioExistencia
    {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor a cero.']);
        }

        $existencia = $this->obtenerExistenciaBloqueada($bodegaId, $productoId, $loteId);
        $saldoAnterior = (float) $existencia->cantidad_disponible;
        $saldoNuevo = $saldoAnterior + $cantidad;

        $existencia->forceFill([
            'cantidad_disponible' => $saldoNuevo,
            'updated_by' => $usuarioId,
        ])->save();

        $this->registrarKardex($bodegaId, $productoId, $loteId, $tipoMovimiento, $cantidad, 0, $saldoAnterior, $saldoNuevo, $documentoTipo, $documentoId, $referencia, $usuarioId, $fecha, $observacion);

        return $existencia;
    }

    private function descontarExistencia(int $bodegaId, int $productoId, ?int $loteId, float $cantidad, int $usuarioId, ?string $documentoTipo, ?int $documentoId, ?string $referencia, mixed $fecha, ?string $observacion, string $tipoMovimiento): InventarioExistencia
    {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor a cero.']);
        }

        $existencia = $this->obtenerExistenciaBloqueada($bodegaId, $productoId, $loteId);
        $saldoAnterior = (float) $existencia->cantidad_disponible;

        if ($saldoAnterior < $cantidad) {
            throw ValidationException::withMessages(['existencia' => 'No hay existencia suficiente para confirmar la salida.']);
        }

        $saldoNuevo = $saldoAnterior - $cantidad;

        $existencia->forceFill([
            'cantidad_disponible' => $saldoNuevo,
            'updated_by' => $usuarioId,
        ])->save();

        $this->registrarKardex($bodegaId, $productoId, $loteId, $tipoMovimiento, 0, $cantidad, $saldoAnterior, $saldoNuevo, $documentoTipo, $documentoId, $referencia, $usuarioId, $fecha, $observacion);

        return $existencia;
    }

    private function obtenerExistenciaBloqueada(int $bodegaId, int $productoId, ?int $loteId): InventarioExistencia
    {
        $query = InventarioExistencia::where('bodega_id', $bodegaId)
            ->where('producto_id', $productoId);

        $loteId === null ? $query->whereNull('lote_id') : $query->where('lote_id', $loteId);

        $existencia = $query->lockForUpdate()->first();

        if ($existencia) {
            return $existencia;
        }

        return InventarioExistencia::create([
            'bodega_id' => $bodegaId,
            'producto_id' => $productoId,
            'lote_id' => $loteId,
            'cantidad_disponible' => 0,
            'cantidad_reservada' => 0,
            'created_by' => auth()->id(),
        ]);
    }

    private function registrarKardex(int $bodegaId, int $productoId, ?int $loteId, string $tipoMovimiento, float $entrada, float $salida, float $saldoAnterior, float $saldoNuevo, ?string $documentoTipo, ?int $documentoId, ?string $referencia, int $usuarioId, mixed $fecha, ?string $observacion): void
    {
        InventarioKardex::create([
            'bodega_id' => $bodegaId,
            'producto_id' => $productoId,
            'lote_id' => $loteId,
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad_entrada' => $entrada,
            'cantidad_salida' => $salida,
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo' => $saldoNuevo,
            'documento_tipo' => $documentoTipo,
            'documento_id' => $documentoId,
            'referencia' => $referencia,
            'usuario_id' => $usuarioId,
            'fecha_movimiento' => $fecha,
            'observacion' => $observacion,
        ]);
    }

    private function validarSalidaManual(InventarioSalida $salida): void
    {
        if (! $salida->motivo || (! $salida->departamento_id && ! $salida->empleado_recibe_id) || ! $salida->responsable_entrega_id || ! $salida->observacion) {
            throw ValidationException::withMessages([
                'salida_manual' => 'La salida manual requiere motivo, receptor o departamento, observacion y responsable.',
            ]);
        }
    }

    private function validarSalidaDesdeActa(InventarioSalida $salida): void
    {
        $tieneDetalleActa = $salida->detalles->contains(fn (InventarioSalidaDetalle $detalle) => $detalle->detalle_acta_entrega_id !== null);

        if ($salida->acta_entrega_id === null) {
            if ($tieneDetalleActa) {
                throw ValidationException::withMessages([
                    'acta' => 'No se puede asociar un detalle de acta a una salida sin acta de entrega.',
                ]);
            }

            return;
        }

        $acta = ActaEntrega::with('tipoActaEntrega')
            ->lockForUpdate()
            ->findOrFail($salida->acta_entrega_id);

        if (mb_strtolower((string) $acta->tipoActaEntrega?->tipo) !== 'intermedia') {
            throw ValidationException::withMessages([
                'acta' => 'Solo las actas intermedias pueden respaldar una salida de inventario.',
            ]);
        }

        if ((int) $salida->requisicion_id !== (int) $acta->idRequisicion) {
            throw ValidationException::withMessages([
                'requisicion' => 'La requisición de la salida no corresponde al acta seleccionada.',
            ]);
        }

        if (! $tieneDetalleActa) {
            throw ValidationException::withMessages([
                'detalles' => 'Las salidas respaldadas por acta deben indicar su detalle de acta.',
            ]);
        }

        $detallesActa = DetalleActaEntrega::with('detalleRequisicion')
            ->where('idActaEntrega', $acta->id)
            ->whereIn('id', $salida->detalles->pluck('detalle_acta_entrega_id')->filter())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $cantidadNuevaPorDetalle = [];

        foreach ($salida->detalles as $detalleSalida) {
            $detalleActa = $detallesActa->get($detalleSalida->detalle_acta_entrega_id);

            if (! $detalleActa || ! $detalleActa->detalleRequisicion) {
                throw ValidationException::withMessages([
                    'detalles' => 'Uno de los productos no corresponde a un detalle válido del acta.',
                ]);
            }

            $recursoId = $detalleActa->detalleRequisicion->idRecurso;
            $productoValido = InventarioProducto::whereKey($detalleSalida->producto_id)
                ->whereHas('recursos', fn ($query) => $query->where('tareas_historicos.id', $recursoId))
                ->exists();

            if (! $productoValido) {
                throw ValidationException::withMessages([
                    'producto' => 'El producto seleccionado no está vinculado al recurso indicado en el acta.',
                ]);
            }

            $cantidadNuevaPorDetalle[$detalleActa->id] = ($cantidadNuevaPorDetalle[$detalleActa->id] ?? 0) + (float) $detalleSalida->cantidad;
        }

        foreach ($cantidadNuevaPorDetalle as $detalleActaId => $cantidadNueva) {
            $cantidadAnterior = InventarioSalidaDetalle::query()
                ->join('inventario_salidas', 'inventario_salidas.id', '=', 'inventario_salida_detalles.salida_id')
                ->where('inventario_salidas.estado', 'confirmado')
                ->whereNull('inventario_salidas.deleted_at')
                ->where('inventario_salida_detalles.detalle_acta_entrega_id', $detalleActaId)
                ->where('inventario_salida_detalles.salida_id', '!=', $salida->id)
                ->sum('inventario_salida_detalles.cantidad');

            $cantidadAutorizada = (float) $detallesActa[$detalleActaId]->log_cant_ejecutada;

            if (((float) $cantidadAnterior + $cantidadNueva) > $cantidadAutorizada) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad a despachar supera la cantidad autorizada por el acta.',
                ]);
            }
        }
    }
}
