<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioImportacion extends Model
{
    protected $table = 'inventario_importaciones';

    protected $fillable = [
        'archivo',
        'usuario_id',
        'fecha',
        'estado',
        'total_filas',
        'filas_importadas',
        'errores',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'errores' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
