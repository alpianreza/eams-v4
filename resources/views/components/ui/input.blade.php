@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'leadingIcon' => null,
])

@php
    $fieldId = $id ?: ($name ?: 'input-'.\Illuminate\Support\Str::uuid());
    $errorMessage = $error ?: (($name && isset($errors)) ? $errors->first($name) : null);
    $describedBy = $errorMessage ? $fieldId.'-error' : ($hint ? $fieldId.'-hint' : null);
@endphp

<div class="eams:grid eams:gap-1.5" data-eams-component="input">
    @if($label)
        <label for="{{ $fieldId }}" class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $label }}</label>
    @endif
    <div class="eams:relative">
        @if($leadingIcon)
            <i class="bi bi-{{ $leadingIcon }} eams:pointer-events-none eams:absolute eams:left-3 eams:top-1/2 eams:-translate-y-1/2 eams:text-sm eams:text-subtle" aria-hidden="true"></i>
        @endif
        <input id="{{ $fieldId }}" @if($name) name="{{ $name }}" @endif type="{{ $type }}"
               @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if($errorMessage) aria-invalid="true" @endif
               {{ $attributes->class([
                    'eams:block eams:min-h-10 eams:w-full eams:rounded-eams eams:border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:placeholder:text-subtle eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft eams:disabled:cursor-not-allowed eams:disabled:bg-surface-sunk eams:disabled:opacity-70',
                    'eams:pl-9' => $leadingIcon,
                    'eams:border-danger' => $errorMessage,
                    'eams:border-border-strong' => ! $errorMessage,
               ]) }}>
    </div>
    @if($errorMessage)
        <p id="{{ $fieldId }}-error" class="eams:m-0 eams:text-xs eams:text-danger">{{ $errorMessage }}</p>
    @elseif($hint)
        <p id="{{ $fieldId }}-hint" class="eams:m-0 eams:text-xs eams:text-muted">{{ $hint }}</p>
    @endif
</div>
