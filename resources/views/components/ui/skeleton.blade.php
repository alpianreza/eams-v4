@props(['lines' => 1, 'variant' => 'text'])

<div {{ $attributes->class('eams:grid eams:gap-2') }} data-eams-component="skeleton" aria-hidden="true">
    @for($line = 0; $line < max(1, (int) $lines); $line++)
        <span @class([
            'skeleton eams:block eams:animate-pulse eams:bg-surface-sunk',
            'eams:h-3 eams:rounded' => $variant === 'text',
            'eams:h-6 eams:w-2/5 eams:rounded-eams-sm' => $variant === 'title',
            'eams:aspect-video eams:w-full eams:rounded-eams' => $variant === 'image',
            'eams:size-10 eams:rounded-full' => $variant === 'avatar',
            'eams:w-4/5' => $variant === 'text' && $line === max(1, (int) $lines) - 1 && $lines > 1,
        ])></span>
    @endfor
</div>
