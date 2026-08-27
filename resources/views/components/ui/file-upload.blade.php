@props(['name', 'id' => null, 'label' => 'Pilih file', 'accept' => null, 'hint' => null, 'multiple' => false])
@php($fieldId = $id ?: $name)

<label for="{{ $fieldId }}" x-data="{ files: [] }" class="eams:block eams:cursor-pointer" data-eams-component="file-upload">
    <input id="{{ $fieldId }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" type="file" @if($accept) accept="{{ $accept }}" @endif @if($multiple) multiple @endif
           class="eams:sr-only" @change="files = Array.from($event.target.files).map(file => file.name)" {{ $attributes }}>
    <span class="eams:flex eams:min-h-28 eams:flex-col eams:items-center eams:justify-center eams:rounded-eams-lg eams:border eams:border-dashed eams:border-border-strong eams:bg-surface eams:p-4 eams:text-center eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft">
        <i class="bi bi-cloud-arrow-up eams:text-2xl eams:text-brand" aria-hidden="true"></i>
        <span class="eams:mt-2 eams:text-[13px] eams:font-semibold eams:text-ink">{{ $label }}</span>
        @if($hint)<span class="eams:mt-1 eams:text-xs eams:text-muted">{{ $hint }}</span>@endif
        <template x-if="files.length"><span class="eams:mt-2 eams:max-w-full eams:truncate eams:text-xs eams:font-medium eams:text-brand" x-text="files.join(', ')"></span></template>
    </span>
</label>
