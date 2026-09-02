<?php

namespace App\Models\Inventario;

use App\Models\Actas\DetalleActaEntrega;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioSalidaDetalle extends Model
{
    protected $table = 'inventario_salida_detalles';

    protected $fillable = [
        'salida_id',
        'detalle_acta_entrega_id',
        'producto_id',
        'lote_id',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    public function salida(): BelongsTo
    {
        return $this->belongsTo(InventarioSalida::class, 'salida_id');
    }

    public function detalleActaEntrega(): BelongsTo
    {
        return $this->belongsTo(DetalleActaEntrega::class, 'detalle_acta_entrega_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(InventarioProducto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(InventarioLote::class, 'lote_id');
    }
}
