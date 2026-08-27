@props(['name' => null, 'id' => null, 'label' => null, 'value' => '1', 'checked' => false, 'hint' => null])
@php($fieldId = $id ?: ($name ?: 'checkbox-'.\Illuminate\Support\Str::uuid()))

<label for="{{ $fieldId }}" class="eams:inline-flex eams:cursor-pointer eams:items-start eams:gap-2.5" data-eams-component="checkbox">
    <input id="{{ $fieldId }}" type="checkbox" @if($name) name="{{ $name }}" @endif value="{{ $value }}" @checked($checked)
           {{ $attributes->class('eams:mt-0.5 eams:size-4 eams:shrink-0 eams:rounded eams:border-border-strong eams:text-brand eams:accent-[var(--c-primary)] eams:focus:ring-2 eams:focus:ring-brand-soft eams:disabled:cursor-not-allowed eams:disabled:opacity-60') }}>
    <span class="eams:grid eams:gap-0.5">
        <span class="eams:text-[13px] eams:font-medium eams:text-ink">{{ $label ?? $slot }}</span>
        @if($hint)<span class="eams:text-xs eams:text-muted">{{ $hint }}</span>@endif
    </span>
</label>
