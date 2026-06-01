<?php

namespace App\Models\UnidadEjecutora;
use App\Models\BaseModel;
use App\Models\Instituciones\Institucion;
use App\Models\Departamento\Departamento;
use App\Models\User;

class UnidadEjecutora extends BaseModel
{
    protected $table = 'unidad_ejecutora';

    protected $fillable = [
        'name',
        'descripcion',
        'estructura',
        'idInstitucion',
        'idAsistenteEstrategico',
        'idAdministrador',
        'idEncargadoCompra',
        'idDirectorDecano',
        // Los campos de auditoría ya están en BaseModel
    ];

    // Relación con Institucion
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'idInstitucion');
    }

    // Relación con Departamentos
    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'idUnidadEjecutora');
    }

    // Relación con TechoUes
    public function techoUes()
    {
        return $this->hasMany(\App\Models\TechoUes\TechoUe::class, 'idUE');
    }

    // Relación con Empleados
    public function empleados()
    {
        return $this->hasMany(\App\Models\Empleados\Empleado::class, 'idUnidadEjecutora');
    }

    public function asistenteEstrategico()
    {
        return $this->belongsTo(User::class, 'idAsistenteEstrategico');
    }

    public function administrador()
    {
        return $this->belongsTo(User::class, 'idAdministrador');
    }

    public function encargadoCompra()
    {
        return $this->belongsTo(User::class, 'idEncargadoCompra');
    }

    public function directorDecano()
    {
        return $this->belongsTo(User::class, 'idDirectorDecano');
    }
}