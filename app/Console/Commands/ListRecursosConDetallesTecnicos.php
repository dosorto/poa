<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListRecursosConDetallesTecnicos extends Command
{
    protected $signature = 'list:recursos-con-detalles';
    protected $description = 'Listar todos los recursos con sus detalles técnicos';

    public function handle()
    {
        $this->info('Recursos con Detalles Técnicos');
        $this->line(str_repeat('=', 80));
        
        $recursos = DB::table('tareas_historicos as t')
            ->join('recurso_detalles_tecnicos as rdt', 'rdt.id_tareas_historicos', '=', 't.id')
            ->select('t.id', 't.nombre', DB::raw('COUNT(rdt.id) as cantidad_detalles'))
            ->groupBy('t.id', 't.nombre')
            ->orderBy('cantidad_detalles', 'desc')
            ->get();
        
        if ($recursos->isEmpty()) {
            $this->warn('No se encontraron recursos con detalles técnicos');
            return Command::FAILURE;
        }
        
        $this->table(
            ['ID', 'Nombre del Recurso', 'Cantidad de Detalles'],
            $recursos->map(fn($r) => [$r->id, $r->nombre, $r->cantidad_detalles])->toArray()
        );
        
        $this->line('');
        $this->info('Para ver los detalles de un recurso, visita:');
        foreach ($recursos as $recurso) {
            $this->line("  http://tu-app.local/recurso-detalle-tecnico/{$recurso->id}  ({$recurso->nombre})");
        }
        
        return Command::SUCCESS;
    }
}
