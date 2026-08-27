@props(['align' => 'right', 'width' => 'w-56'])
@php($alignment = $align === 'left' ? 'eams:left-0' : 'eams:right-0')

<div x-data="eamsDropdown" @click.outside="close" @keydown.escape.stop="close" {{ $attributes->class('eams:relative eams:inline-block') }} data-eams-component="dropdown">
    <div @click="toggle" :aria-expanded="open" aria-haspopup="true">{{ $trigger }}</div>
    <div x-cloak x-show="open" x-transition.origin.top.right
         class="{{ $alignment }} {{ $width === 'w-72' ? 'eams:w-72' : 'eams:w-56' }} eams:absolute eams:top-[calc(100%+0.5rem)] eams:z-[130] eams:overflow-hidden eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:p-1.5 eams:text-ink eams:shadow-eams-3"
         role="menu" @click="close">
        {{ $slot }}
    </div>
</div>
