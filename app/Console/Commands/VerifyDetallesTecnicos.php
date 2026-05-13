<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDetallesTecnicos extends Command
{
    protected $signature = 'verify:detalles-tecnicos';
    protected $description = 'Verificar si existen detalles técnicos en la BD';

    public function handle()
    {
        $this->info('Verificando detalles técnicos...');
        
        // Contar detalles técnicos
        $detalleCount = DB::table('recurso_detalles_tecnicos')->count();
        $this->info("Total detalles técnicos: " . $detalleCount);
        
        if ($detalleCount > 0) {
            $this->line("\n✓ Primeros 5 detalles técnicos:");
            $detalles = DB::table('recurso_detalles_tecnicos')
                ->select('id', 'id_tareas_historicos', 'nombre', 'estado')
                ->limit(5)
                ->get();
            
            foreach ($detalles as $detalle) {
                $recurso = DB::table('tareas_historicos')
                    ->where('id', $detalle->id_tareas_historicos)
                    ->first();
                
                $nombreRecurso = $recurso ? $recurso->nombre : 'NO ENCONTRADO';
                $this->line("  - {$detalle->nombre} (Recurso ID: {$detalle->id_tareas_historicos} - {$nombreRecurso})");
            }
        } else {
            $this->warn("No se encontraron detalles técnicos en la BD");
            
            // Verificar si existen los recursos necesarios
            $this->line("\nVerificando si existen los recursos necesarios:");
            $recursos = [
                'PAPEL BOND T/C',
                'PAPEL BOND T/O',
                'CARTULINA IRIS',
                'FOLDER T/C',
                'FOLDER T/O',
                'SOBRES MANILA T/C',
                'SOBRES MANILA T/O',
                'GUANTES DE LIMPIEZA AMARILLOS UNITALLA',
                'VASOS TÉRMICOS # 6',
                'GASOLINA',
                'DIESEL',
            ];
            
            foreach ($recursos as $recurso) {
                $existe = DB::table('tareas_historicos')
                    ->where('nombre', $recurso)
                    ->exists();
                
                $estado = $existe ? '✓' : '✗';
                $this->line("  {$estado} {$recurso}");
            }
        }
        
        return Command::SUCCESS;
    }
}
