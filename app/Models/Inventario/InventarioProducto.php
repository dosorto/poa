<?php

namespace App\Models\Inventario;

use App\Models\BaseModel;
use App\Models\Cubs\Cub;
use App\Models\GrupoGastos\ObjetoGasto;
use App\Models\Requisicion\UnidadMedida;
use App\Models\Tareas\TareaHistorico;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioProducto extends BaseModel
{
    protected $table = 'inventario_productos';

    protected $fillable = [
        'recurso_id',
        'idCubs',
        'idobjeto',
        'unidad_medida_id',
        'codigo_interno',
        'codigo_barra',
        'nombre',
        'descripcion',
        'marca',
        'presentacion',
        'stock_minimo',
        'maneja_lote',
        'maneja_vencimiento',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'stock_minimo' => 'decimal:2',
        'maneja_lote' => 'boolean',
        'maneja_vencimiento' => 'boolean',
        'activo' => 'boolean',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(TareaHistorico::class, 'recurso_id');
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(TareaHistorico::class, 'inventario_producto_recurso', 'producto_id', 'recurso_id')
            ->withTimestamps();
    }

    public function cub(): BelongsTo
    {
        return $this->belongsTo(Cub::class, 'idCubs', 'IDUNSPSC');
    }

    public function objetoGasto(): BelongsTo
    {
        return $this->belongsTo(ObjetoGasto::class, 'idobjeto', 'identificador');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(InventarioLote::class, 'producto_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(InventarioExistencia::class, 'producto_id');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(InventarioKardex::class, 'producto_id');
    }
}
