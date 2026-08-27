@props(['src' => null, 'alt' => 'Pratinjau gambar', 'ratio' => 'video', 'emptyText' => 'Belum ada gambar'])

<div x-data="{ failed: false }" {{ $attributes->class('eams:relative eams:overflow-hidden eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface-sunk') }} data-eams-component="image-preview">
    @if($src)
        <img x-show="! failed" x-on:error="failed = true" src="{{ $src }}" alt="{{ $alt }}"
             @class(['eams:w-full eams:object-cover', 'eams:aspect-video' => $ratio === 'video', 'eams:aspect-square' => $ratio === 'square'])>
        <div x-cloak x-show="failed"
             @class(['eams:flex eams:w-full eams:flex-col eams:items-center eams:justify-center eams:p-6 eams:text-center eams:text-muted', 'eams:aspect-video' => $ratio === 'video', 'eams:aspect-square' => $ratio === 'square'])>
            <i class="bi bi-image eams:text-2xl eams:text-subtle" aria-hidden="true"></i>
            <span class="eams:mt-2 eams:text-xs">{{ $emptyText }}</span>
        </div>
    @else
        <div @class(['eams:flex eams:w-full eams:flex-col eams:items-center eams:justify-center eams:p-6 eams:text-center eams:text-muted', 'eams:aspect-video' => $ratio === 'video', 'eams:aspect-square' => $ratio === 'square'])>
            <i class="bi bi-image eams:text-2xl eams:text-subtle" aria-hidden="true"></i>
            <span class="eams:mt-2 eams:text-xs">{{ $emptyText }}</span>
        </div>
    @endif
</div>
