<?php

namespace App\Models\Inventario;

use App\Models\BaseModel;
use App\Models\Requisicion\Requisicion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioEntrada extends BaseModel
{
    protected $table = 'inventario_entradas';

    protected $fillable = [
        'numero_entrada',
        'numero_factura',
        'proveedor',
        'fecha_factura',
        'orden_compra_referencia',
        'requisicion_id',
        'bodega_id',
        'fecha_entrada',
        'usuario_id',
        'observacion',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_factura' => 'date',
        'fecha_entrada' => 'date',
    ];

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(InventarioBodega::class, 'bodega_id');
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioEntradaDetalle::class, 'entrada_id');
    }
}
