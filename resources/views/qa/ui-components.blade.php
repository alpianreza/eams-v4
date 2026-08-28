@extends('layouts.app')

@section('title', 'UI Component QA')

@section('content')
<div class="eams:space-y-5" data-qa="component-showcase">
    <header>
        <p class="eams:m-0 eams:text-xs eams:font-bold eams:uppercase eams:tracking-wider eams:text-brand">Environment-only QA</p>
        <h1 class="eams:mb-0 eams:mt-1 eams:text-xl eams:font-bold eams:text-ink">UI Component Browser QA</h1>
    </header>

    <x-ui.card title="Overlay controls">
        <div class="eams:flex eams:flex-wrap eams:gap-2">
            <x-ui.button data-qa="open-modal" x-on:click="$dispatch('open-modal', 'qa-modal')">Buka modal</x-ui.button>
            <x-ui.button data-qa="open-drawer" variant="secondary" x-on:click="$dispatch('open-drawer', 'qa-drawer')">Buka drawer</x-ui.button>
            <x-ui.button data-qa="open-confirm" variant="danger" x-on:click="window.dispatchEvent(new CustomEvent('eams-confirm', { detail: { name: 'qa-confirm', message: 'Konfirmasi browser QA', id: 21 } }))">Buka konfirmasi</x-ui.button>
            <x-ui.button data-qa="show-toast" variant="secondary" x-on:click="window.eamsToast('QA toast berhasil', 'success')">Tampilkan toast</x-ui.button>

            <x-ui.dropdown>
                <x-slot:trigger>
                    <button type="button" data-qa="open-dropdown" class="eams:inline-flex eams:min-h-9 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:text-sm eams:font-semibold eams:text-ink">Dropdown <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                </x-slot:trigger>
                <button type="button" role="menuitem" class="eams:w-full eams:rounded-eams-sm eams:border-0 eams:bg-transparent eams:px-3 eams:py-2 eams:text-left eams:text-sm eams:text-ink">Menu QA aktif</button>
            </x-ui.dropdown>
        </div>
    </x-ui.card>

    <div class="eams:grid eams:gap-5 eams:lg:grid-cols-2">
        <x-ui.card title="File upload">
            <x-ui.file-upload name="qa_photo" accept="image/png,image/jpeg" label="Pilih gambar QA" hint="Nama file harus tampil tanpa submit." />
        </x-ui.card>

        <x-ui.card title="Image preview fallback">
            <x-ui.image-preview empty-text="Fallback gambar QA" />
        </x-ui.card>
    </div>

    <x-ui.alert tone="info" title="Coexistence gate">Halaman ini memakai shell Tailwind sementara browser QA juga memeriksa modal Bootstrap pada halaman User legacy.</x-ui.alert>
</div>

<x-ui.modal name="qa-modal" title="Modal QA">
    <p class="eams:m-0 eams:text-sm eams:text-muted">Konten modal berhasil dirender.</p>
</x-ui.modal>

<x-ui.drawer name="qa-drawer" title="Drawer QA">
    <p class="eams:m-0 eams:text-sm eams:text-muted">Konten drawer berhasil dirender.</p>
</x-ui.drawer>

<x-ui.confirm-dialog name="qa-confirm" title="Konfirmasi QA" confirm-label="Ya, lanjutkan" />
@endsection
