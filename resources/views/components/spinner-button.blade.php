@props([
    'loadingTarget' => null,
    'loadingText' => 'Cargando...',
    'type' => 'submit',
    'disabled' => false,
    'icon' => null, // Slot para el icono
])

@php
    $classes = 'inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-800 dark:border-indigo-700 dark:text-white dark:hover:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 dark:focus:bg-indigo-900 active:bg-zinc-900 dark:active:bg-indigo-800 focus:outline-none focus:ring-2 dark:focus:ring-indigo-500 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-indigo-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed';
@endphp

<button 
    {{ $attributes->merge([
        'type' => $type,
        'class' => $classes,
        'wire:loading.attr' => $loadingTarget ? 'disabled' : null
    ]) }}
    @if($disabled) disabled @endif
>
    @if($loadingTarget)
        <!-- Estado normal - oculto durante loading -->
        <span wire:loading.remove wire:target="{{ $loadingTarget }}" class="flex flex-row items-center">
            @if($icon)
                <!-- Icono personalizado -->
                {{ $icon }}
            @endif
            {{ $slot }}
        </span>
        
        <!-- Estado loading - visible durante loading -->
        <span wire:loading wire:target="{{ $loadingTarget }}" class="flex flex-row items-center gap-2">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            {{ $loadingText }}
        </span>
    @else
        <!-- Sin loading target, mostrar contenido normal -->
        <span class="flex flex-row items-center">
            @if($icon)
                {{ $icon }}
            @endif
            {{ $slot }}
        </span>
    @endif
</button>
