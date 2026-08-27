@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'rows' => 4,
    'hint' => null,
    'error' => null,
])

@php
    $fieldId = $id ?: ($name ?: 'textarea-'.\Illuminate\Support\Str::uuid());
    $errorMessage = $error ?: (($name && isset($errors)) ? $errors->first($name) : null);
    $describedBy = $errorMessage ? $fieldId.'-error' : ($hint ? $fieldId.'-hint' : null);
@endphp

<div class="eams:grid eams:gap-1.5" data-eams-component="textarea">
    @if($label)<label for="{{ $fieldId }}" class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $label }}</label>@endif
    <textarea id="{{ $fieldId }}" @if($name) name="{{ $name }}" @endif rows="{{ $rows }}"
              @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
              @if($errorMessage) aria-invalid="true" @endif
              {{ $attributes->class([
                  'eams:block eams:w-full eams:resize-y eams:rounded-eams eams:border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:placeholder:text-subtle eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft eams:disabled:cursor-not-allowed eams:disabled:bg-surface-sunk eams:disabled:opacity-70',
                  'eams:border-danger' => $errorMessage,
                  'eams:border-border-strong' => ! $errorMessage,
              ]) }}>{{ $slot }}</textarea>
    @if($errorMessage)
        <p id="{{ $fieldId }}-error" class="eams:m-0 eams:text-xs eams:text-danger">{{ $errorMessage }}</p>
    @elseif($hint)
        <p id="{{ $fieldId }}-hint" class="eams:m-0 eams:text-xs eams:text-muted">{{ $hint }}</p>
    @endif
</div>
