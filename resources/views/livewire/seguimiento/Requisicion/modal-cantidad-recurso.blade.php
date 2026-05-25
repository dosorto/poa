<x-dialog-modal wire:model="modalCantidad" maxWidth="md">
    <x-slot name="title">
        <div class="flex flex-col gap-1">
            <span class="font-semibold text-zinc-800 dark:text-zinc-100">Agregar recurso</span>
            @if ($recursoEnModalCantidad)
                <span class="text-sm font-normal text-zinc-500 dark:text-zinc-400">
                    {{ $recursoEnModalCantidad['nombre'] ?? '' }}
                </span>
            @endif
        </div>
    </x-slot>

    <x-slot name="content">
        @if ($recursoEnModalCantidad)
            @php
                $cantidad = (int) ($cantidadTemporal ?: 0);
                $precioUnitario = (float) ($recursoEnModalCantidad['precio_unitario'] ?? 0);
                $totalTemporal = max(0, $cantidad) * $precioUnitario;
                $recursoId = $recursoEnModalCantidad['id'];
            @endphp

            <div class="space-y-4">
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $recursoEnModalCantidad['nombre'] ?? '-' }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $recursoEnModalCantidad['actividad'] ?? '-' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <span class="block text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Disponible</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $recursoEnModalCantidad['cantidad_disponible'] ?? 0 }}
                        </span>
                    </div>
                    <div class="rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <span class="block text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Total</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                            L {{ number_format($totalTemporal, 2) }}
                        </span>
                    </div>
                </div>

                <div>
                    <x-label value="Cantidad" class="mb-1" />
                    <x-input type="number"
                        min="1"
                        step="1"
                        max="{{ $recursoEnModalCantidad['cantidad_disponible'] ?? '' }}"
                        inputmode="numeric"
                        wire:model.live.debounce.300ms="cantidadTemporal"
                        class="w-full" />
                    @if (!empty($erroresCantidad[$recursoId]))
                        <span class="text-xs text-red-500">{{ $erroresCantidad[$recursoId] }}</span>
                    @endif
                </div>
            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-spinner-secondary-button wire:click="cerrarModalCantidad" type="button" loadingTarget="cerrarModalCantidad" loadingText="Cerrando...">
            Cancelar
        </x-spinner-secondary-button>
        <x-spinner-button wire:click="confirmarCantidad" type="button" loadingTarget="confirmarCantidad" loadingText="Agregando...">
            Confirmar
        </x-spinner-button>
    </x-slot>
</x-dialog-modal>
