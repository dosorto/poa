<?php

namespace App\Models\Cubs;
use App\Models\BaseModel;

class Cub extends BaseModel
{
    protected $table = 'cubs';

    protected $fillable = [
        'IDUNSPSC',
        'descripcion_esp',
        'descripcion_regional',
        // Los campos de auditoría ya están en BaseModel
    ];
}
