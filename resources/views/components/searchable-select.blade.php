{{-- resources/views/components/searchable-select.blade.php --}}

@props([
    'label'        => null,
    'options'      => [],
    'searchAction' => null,
    'placeholder'  => 'Buscar...',
    'defaultText'  => 'Seleccione una opción',
    'clearText'    => 'Ninguno',
    'required'     => false,
    'disabled'     => false,
    'error'        => null,
])

@php
    $wireModel   = $attributes->get('wire:model');
    $isLive      = !is_null($searchAction);

    $normalizedOptions = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return ['id' => $option['value'] ?? $option['id'] ?? null, 'text' => $option['text'] ?? $option['label'] ?? ''];
        }
        if (is_object($option)) {
            return ['id' => $option->value ?? $option->id ?? null, 'text' => $option->text ?? $option->label ?? $option->nombre ?? ''];
        }
        return ['id' => $option, 'text' => (string) $option];
    })->filter(fn($i) => $i['id'] !== null)->values()->toJson();
@endphp

<div {{ $attributes->except('wire:model') }}>

    @if ($label)
        <label class="block mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div
        x-data="{
            open: false,
            search: '',
            loading: false,
            selected: @entangle($wireModel),
            selectedLabel: '{{ $defaultText }}',
            isLive: {{ $isLive ? 'true' : 'false' }},
            options: {{ $normalizedOptions }},
            results: [],
            debounceTimer: null,

            init() {
                if (this.isLive) {
                    $wire.{{ $searchAction ?? 'search' }}('').then(r => {
                        if (typeof r === 'string') {
                            try { r = JSON.parse(r); } catch(e) {}
                        }
                        this.results = Array.isArray(r) ? r : [];
                        if (this.selected) {
                            const found = this.results.find(o => o.id == this.selected) || this.options.find(o => o.id == this.selected);
                            if (found) this.selectedLabel = found.text;
                        }
                    }).catch(e => {
                        this.results = [{id: -1, text: 'Error init: ' + (e.message || e)}];
                    });
                }
            },

            get displayOptions() {
                if (this.isLive) return this.results;
                if (this.search === '') return this.options;
                return this.options.filter(o => o.text.toLowerCase().includes(this.search.toLowerCase()));
            },

            get selectedText() {
                if (!this.selected) return '{{ $defaultText }}';
                let found = this.options.find(o => o.id == this.selected) || this.results.find(o => o.id == this.selected);
                if (found) return found.text;
                if (this.isLive && this.selectedLabel !== '{{ $defaultText }}') return this.selectedLabel;
                return '{{ $defaultText }}';
            },

            doSearch(q) {
                if (!this.isLive) return;
                
                this.loading = true;
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    $wire.{{ $searchAction ?? 'search' }}(q).then(r => {
                        if (typeof r === 'string') {
                            try { r = JSON.parse(r); } catch(e) {}
                        }
                        this.results = Array.isArray(r) ? r : [];
                        this.loading = false;
                    }).catch(e => {
                        this.results = [{id: -1, text: 'Error search: ' + (e.message || e)}];
                        this.loading = false;
                    });
                }, 300);
            },

            selectOption(id, text) {
                this.selected = id;
                this.selectedLabel = text;
                this.open = false;
                this.search = '';
                this.results = [];
            },

            clearSelection() {
                this.selected = null;
                this.selectedLabel = '{{ $defaultText }}';
                this.search = '';
                this.results = [];
            },
        }"
        @click.away="open = false"
        class="relative"
    >
        {{-- Botón principal --}}
        <button
            type="button"
            @click="if (!{{ $disabled ? 'true' : 'false' }}) { open = !open; if(open && isLive && results.length === 0 && search === '') doSearch(''); }"
            :disabled="{{ $disabled ? 'true' : 'false' }}"
            class="relative w-full bg-white dark:bg-zinc-900 border rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 sm:text-sm
                {{ $error    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                             : 'border-zinc-300 dark:border-zinc-700 focus:ring-indigo-500 focus:border-indigo-500' }}
                {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        >
            <span class="block truncate" x-text="selectedText"
                :class="!selected ? 'text-zinc-400' : 'text-zinc-900 dark:text-zinc-300'">
            </span>
            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </span>
        </button>

        {{-- Mensaje de error de validación --}}
        @if ($error)
            <p class="mt-1 text-sm text-red-500">{{ $error }}</p>
        @endif

        {{-- Dropdown --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-zinc-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
            style="display: none;"
        >
            {{-- Input de búsqueda --}}
            <div class="sticky top-0 z-10 bg-white dark:bg-zinc-800 px-2 py-2 border-b border-zinc-200 dark:border-zinc-700">
                <input
                    x-model="search"
                    @input="doSearch(search)"
                    type="text"
                    @click.stop
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="{{ $placeholder }}"
                />
            </div>

          
            {{-- Limpiar selección --}}
            <div
                @click="clearSelection()"
                class="cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-400 dark:text-zinc-500 italic text-sm"
            >
                {{ $clearText }}
            </div>

            {{-- Loading (solo server-side) --}}
            <div x-show="isLive && loading" class="px-3 py-2 text-zinc-400 text-sm italic">
                Buscando...
            </div>

            {{-- Hint mínimo eliminado porque ahora cargamos opciones iniciales --}}

            {{-- Lista de opciones --}}
            <template x-for="option in displayOptions" :key="option.id">
                <div
                    @click="selectOption(option.id, option.text)"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                    :class="selected == option.id
                        ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-100'
                        : 'text-zinc-900 dark:text-zinc-300'"
                >
                    <span class="block truncate" x-text="option.text"></span>
                    <span x-show="selected == option.id"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 dark:text-indigo-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </div>
            </template>

            {{-- Sin resultados --}}
            <div
                x-show="!loading && displayOptions.length === 0"
                class="px-3 py-2 text-zinc-500 dark:text-zinc-400 text-sm italic"
            >
                No se encontraron opciones
            </div>
        </div>
    </div>
</div>