<div>
    <div class="mx-auto rounded-lg mt-8 sm:mt-6 lg:mt-4 mb-6">
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-4 sm:p-6">

            @if (session()->has('message'))
                @include('rk.default.notifications.notification-alert', [
                    'type' => 'success',
                    'dismissible' => true,
                    'icon' => true,
                    'duration' => 5,
                    'slot' => session('message')
                ])
            @endif

            @if (session()->has('error'))
                @include('rk.default.notifications.notification-alert', [
                    'type' => 'error',
                    'dismissible' => true,
                    'icon' => true,
                    'duration' => 8,
                    'slot' => session('error')
                ])
            @endif

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Administración de CUBS') }}</h2>

                <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-3 sm:space-y-0 sm:space-x-2">
                    <div class="relative w-full sm:w-auto">
                        <x-input wire:model.live="search" type="text" placeholder="Buscar CUBS..." class="w-full pl-10 pr-4 py-2" />
                        <div class="absolute left-3 top-2.5">
                            <svg class="h-5 w-5 text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="w-full sm:w-auto">
                        <x-select
                            id="perPage"
                            wire:model.live="perPage"
                            :options="[
                                ['value' => '10', 'text' => '10 por página'],
                                ['value' => '25', 'text' => '25 por página'],
                                ['value' => '50', 'text' => '50 por página'],
                                ['value' => '100', 'text' => '100 por página'],
                            ]"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>

            <x-table
                sort-field="{{ $sortField }}"
                sort-direction="{{ $sortDirection }}"
                :columns="[
                    ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                    ['key' => 'IDUNSPSC', 'label' => 'UNSPSC', 'sortable' => true],
                    ['key' => 'descripcion_esp', 'label' => 'Descripción (ES)', 'sortable' => true],
                    ['key' => 'descripcion_regional', 'label' => 'Descripción regional', 'sortable' => true],
                    ['key' => 'ue', 'label' => 'Unidad Ejecutora'],
                ]"
                empty-message="{{ __('No se encontraron CUBS') }}"
                class="mt-6"
            >
                <x-slot name="desktop">
                    @forelse ($cubs as $cub)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-zinc-300">
                                {{ $cub->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-zinc-300">
                                {{ $cub->IDUNSPSC }}
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-zinc-300">
                                {{ $cub->descripcion_esp }}
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-zinc-300">
                                {{ $cub->descripcion_regional }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-zinc-300">
                                {{ $cub->unidadEjecutora?->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No se encontraron CUBS') }}
                            </td>
                        </tr>
                    @endforelse
                </x-slot>

                <x-slot name="footer">
                    {{ $cubs->links() }}
                </x-slot>
            </x-table>
        </div>
    </div>
</div>
