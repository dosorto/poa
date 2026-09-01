@props([
    'node' => null,
])
@if(!isset($node) || $node->getIsHidden())
    @php
        return;
    @endphp
@endif

<flux:navbar.item
    :href="$node->getUrl()"
    :icon="$node->getHeroIcon()"
    :current="$node->isActive()"
    class="cursor-pointer transition active:translate-y-px {{ $node->isActive() ? 'bg-blue-600/10 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-500/20 dark:text-blue-100 dark:ring-blue-400/30' : '' }}"
    
     badge="{{ $node->getFinalBage() }}" badge-color="pink"
    

    wire:navigate>
    {{ $node->label }}

</flux:navbar.item>
