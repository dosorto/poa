@if($departamentoId)
   <livewire:revision.actividades-revision 
    :departamento-id="$departamentoId" 
    :poa-year="$poaYear"
    :key="'revision-actividades-' . $departamentoId . '-' . ($poaYear ?? 'sin-poa')" />
@endif
