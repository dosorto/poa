<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioKardex extends Model
{
    protected $table = 'inventario_kardex';

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'lote_id',
        'tipo_movimiento',
        'cantidad_entrada',
        'cantidad_salida',
        'saldo_anterior',
        'saldo_nuevo',
        'documento_tipo',
        'documento_id',
        'referencia',
        'usuario_id',
        'fecha_movimiento',
        'observacion',
    ];

    protected $casts = [
        'cantidad_entrada' => 'decimal:2',
        'cantidad_salida' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
    ];

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(InventarioBodega::class, 'bodega_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(InventarioProducto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(InventarioLote::class, 'lote_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
