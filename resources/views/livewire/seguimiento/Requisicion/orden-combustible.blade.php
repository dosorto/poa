<x-dialog-modal wire:model="showOrdenCombustibleModal" maxWidth="4xl">
    <x-slot name="title">
        <div class="flex flex-col gap-1">
            <span class="font-semibold text-zinc-800 dark:text-zinc-100">
                Orden de Combustible ({{ $ordenCombustibleRecursoNombre ?? '' }})
            </span>
        </div>
    </x-slot>
    <x-slot name="content">

        <div class="dark:bg-zinc-900 bg-white rounded-lg p-4">
            @if($combustibleEnModal)
                <div class="mb-4">
                    <x-label value="Cantidad" class="mb-1" />
                    <input
                        type="number"
                        min="1"
                        step="1"
                        inputmode="numeric"
                        placeholder="Ingrese la cantidad"
                        wire:key="cantidad-combustible-{{ $combustibleEnModal }}"
                        wire:model.live.debounce.300ms="cantidadesInput.{{ $combustibleEnModal }}"
                        class="block w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    @if(!empty($erroresCantidad[$combustibleEnModal]))
                        <span class="text-xs text-red-500">{{ $erroresCantidad[$combustibleEnModal] }}</span>
                    @endif
                </div>
            @endif
            {{-- Monto calculado automáticamente --}}
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-green-50 dark:bg-green-800 rounded p-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-700 dark:text-green-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                    </svg>
                    <div>
                        <x-label value="Monto calculado" class="mb-1" />
                        <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100">
                            L {{ number_format($ordenCombustibleData['monto'] ?? 0, 2) }}
                        </p>
                    </div>
                </div>
                <div class="bg-green-50 dark:bg-green-800 rounded p-3 flex items-center gap-2">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke="currentColor" class="w-6 h-6 text-green-700 dark:text-green-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                    <div>
                        <x-label value="Monto en letras" class="mb-1" />
                        <p class="text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $ordenCombustibleData['monto_en_letras'] ?? '' }} LEMPIRAS
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <x-label value="Modelo de Vehículo" class="mb-1" />
                    <x-input type="text" wire:model.defer="ordenCombustibleData.modelo_vehiculo" class="w-full" />
                    @error('ordenCombustibleData.modelo_vehiculo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="No. de Placa" class="mb-1" />
                    <x-input type="text" wire:model.defer="ordenCombustibleData.placa" class="w-full" />
                    @error('ordenCombustibleData.placa') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Lugar de salida" class="mb-1" />
                    <x-input type="text" wire:model.defer="ordenCombustibleData.lugar_salida" class="w-full" />
                    @error('ordenCombustibleData.lugar_salida') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <x-label value="Lugar de destino" class="mb-1" />
                    <x-input type="text" wire:model.defer="ordenCombustibleData.lugar_destino" class="w-full" />
                    @error('ordenCombustibleData.lugar_destino') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Recorrido en km" class="mb-1" />
                    <x-input type="number" wire:model.defer="ordenCombustibleData.recorrido_km" class="w-full" />
                    @error('ordenCombustibleData.recorrido_km') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Fecha a realizar" class="mb-1" />
                    <x-input type="date" wire:model.defer="ordenCombustibleData.fecha_actividad" class="w-full" />
                    @error('ordenCombustibleData.fecha_actividad') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mb-4">
                <x-searchable-select
                    wire:model="ordenCombustibleData.responsable"
                    wire:key="orden-combustible-responsable-{{ $ordenCombustibleData['responsable'] ?? 'empty' }}"
                    label="Empleado Responsable"
                    placeholder="Buscar empleado..."
                    defaultText="Seleccione un empleado"
                    clearText="Limpiar selección"
                    searchAction="searchEmpleadosOrdenCombustible"
                    :options="$empleados->map(fn($empleado) => [
                        'id' => $empleado->id,
                        'text' => trim($empleado->nombre . ' ' . $empleado->apellido) . ($empleado->num_empleado ? ' - #' . $empleado->num_empleado : ''),
                    ])->toArray()"
                    :error="$errors->first('ordenCombustibleData.responsable')"
                />
            </div>
            <div>
                <x-label value="Actividades a realizar" class="mb-1" />
                <x-textarea wire:model.defer="ordenCombustibleData.actividades_realizar" class="w-full"></x-textarea>
                @error('ordenCombustibleData.actividades_realizar') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <x-spinner-secondary-button wire:click="cerrarModalOrdenCombustibleActual" type="button" loadingTarget="cerrarModalOrdenCombustibleActual" loadingText="Cerrando...">
            Cancelar
        </x-spinner-secondary-button>
        @if ($modoOrdenCombustible === 'editar')
            <x-spinner-button wire:click="confirmarEdicionOrden" type="button" loadingTarget="confirmarEdicionOrden" loadingText="Guardando...">
                Confirmar
            </x-spinner-button>
        @else
            <x-spinner-button wire:click="confirmarOrdenYAgregar" type="button" loadingTarget="confirmarOrdenYAgregar" loadingText="Agregando...">
                Confirmar
            </x-spinner-button>
        @endif
    </x-slot>
</x-dialog-modal>
