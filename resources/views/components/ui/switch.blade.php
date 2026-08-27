@props(['name' => null, 'id' => null, 'label' => null, 'checked' => false, 'hint' => null])
@php($fieldId = $id ?: ($name ?: 'switch-'.\Illuminate\Support\Str::uuid()))

<label for="{{ $fieldId }}" class="eams:inline-flex eams:cursor-pointer eams:items-center eams:gap-3" data-eams-component="switch" x-data="{ enabled: @js((bool) $checked) }">
    <input id="{{ $fieldId }}" type="checkbox" @if($name) name="{{ $name }}" @endif value="1" @checked($checked)
           class="eams:sr-only" role="switch" x-model="enabled" {{ $attributes }}>
    <span :class="enabled ? 'eams:bg-brand' : 'eams:bg-border-strong'"
          class="eams:relative eams:inline-flex eams:h-5.5 eams:w-10 eams:shrink-0 eams:rounded-full eams:transition-colors eams:focus-within:ring-2 eams:focus-within:ring-brand-soft">
        <span :class="enabled ? 'eams:translate-x-5' : 'eams:translate-x-0.5'"
              class="eams:pointer-events-none eams:absolute eams:top-0.5 eams:size-4.5 eams:rounded-full eams:bg-white eams:shadow-eams-1 eams:transition-transform"></span>
    </span>
    <span class="eams:grid eams:gap-0.5">
        <span class="eams:text-[13px] eams:font-medium eams:text-ink">{{ $label ?? $slot }}</span>
        @if($hint)<span class="eams:text-xs eams:text-muted">{{ $hint }}</span>@endif
    </span>
</label>
