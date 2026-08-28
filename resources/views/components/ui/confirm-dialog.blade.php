@props(['name' => 'global-confirm', 'title' => 'Konfirmasi', 'confirmLabel' => 'Lanjutkan', 'cancelLabel' => 'Batal'])

<div x-data="{ open: false, message: '', requestId: null, dialogName: @js($name) }"
     @eams-confirm.window="if (!$event.detail?.name || $event.detail.name === dialogName) { message = $event.detail?.message || 'Apakah Anda yakin?'; requestId = $event.detail?.id || null; open = true }"
     @keydown.escape.window="open = false" x-cloak x-show="open"
     class="eams:fixed eams:inset-0 eams:z-[160] eams:flex eams:items-center eams:justify-center eams:p-4"
     role="alertdialog" aria-modal="true" aria-labelledby="{{ $name }}-title" data-eams-component="confirm-dialog">
    <button type="button" @click="open = false" class="eams:absolute eams:inset-0 eams:border-0 eams:bg-black/55" tabindex="-1" aria-label="Batal"></button>
    <section x-transition.scale.95 class="eams:relative eams:w-full eams:max-w-md eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-5 eams:shadow-eams-3">
        <span class="eams:mb-3 eams:inline-flex eams:size-10 eams:items-center eams:justify-center eams:rounded-full eams:bg-warning-soft eams:text-warning"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></span>
        <h2 id="{{ $name }}-title" class="eams:m-0 eams:text-base eams:font-bold eams:text-ink">{{ $title }}</h2>
        <p class="eams:mb-0 eams:mt-2 eams:text-[13px] eams:leading-5 eams:text-muted" x-text="message"></p>
        <div class="eams:mt-5 eams:flex eams:justify-end eams:gap-2">
            <x-ui.button variant="secondary" @click="open = false">{{ $cancelLabel }}</x-ui.button>
            <x-ui.button variant="danger" @click="open = false; $dispatch('eams-confirmed', { name: dialogName, id: requestId })">{{ $confirmLabel }}</x-ui.button>
        </div>
    </section>
</div>
