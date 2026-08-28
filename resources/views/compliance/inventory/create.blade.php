@extends('layouts.app')

@section('title', 'Tambah Inventory')

@section('content')
@php
    $selectedItem = $itemTypes->first(fn ($item) => (string) $item->id === (string) old('asset_item_type_id'));
    $showExpiry = $selectedItem?->code === 'APAR';
@endphp

<div x-data="{ itemCode: @js($selectedItem?->code ?? ''), showExpiry: @js($showExpiry) }"
     class="eams:mx-auto eams:max-w-4xl eams:space-y-4 eams:sm:space-y-5"
     data-eams-page="inventory-create">
    <header class="eams:flex eams:flex-col eams:gap-3 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5">
        <div class="eams:min-w-0">
            <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">Compliance inventory</p>
            <h1 class="eams:m-0 eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink">Tambah inventory baru</h1>
            <p class="eams:mb-0 eams:mt-1 eams:text-[13px] eams:text-muted">Lengkapi identitas dan penempatan aset. Kode dapat dibuat otomatis dengan format legacy.</p>
        </div>
        <x-ui.button :href="route('compliance.inventory.index')" navigate variant="secondary" icon="arrow-left">
            Kembali
        </x-ui.button>
    </header>

    <form method="POST" action="{{ route('compliance.inventory.store') }}" enctype="multipart/form-data"
          class="eams:space-y-4 eams:sm:space-y-5">
        @csrf

        <x-ui.card title="Identitas inventory" subtitle="Kategori dan jenis item menentukan struktur kode serta checklist aset.">
            <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:md:grid-cols-2">
                <x-ui.select name="inventory_category_id" label="Kategori compliance"
                             placeholder="Pilih kategori" required
                             :error="$errors->first('inventory_category_id')">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('inventory_category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="asset_item_type_id" label="Nama item / inventory"
                             placeholder="Pilih item" required
                             x-on:change="itemCode = $event.target.selectedOptions[0]?.dataset.code ?? ''; showExpiry = itemCode === 'APAR'"
                             :error="$errors->first('asset_item_type_id')">
                    @foreach($itemTypes as $item)
                        <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                                @selected((string) old('asset_item_type_id') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input name="asset_code" label="Nomor inventory" placeholder="Contoh: APAR-001"
                            value="{{ old('asset_code') }}"
                            hint="Kosongkan untuk membuat kode otomatis. Kode manual dipertahankan persis dan duplikat akan ditolak."
                            :error="$errors->first('asset_code')" />

                <x-ui.input name="type_description" label="Tipe / spesifikasi" placeholder="Contoh: 3,5 Kg / CO2 / Thermatic"
                            value="{{ old('type_description') }}"
                            :error="$errors->first('type_description')" />
            </div>
        </x-ui.card>

        <x-ui.card title="Penempatan & kondisi" subtitle="Tentukan lokasi fisik, kondisi operasional, jumlah, dan masa berlaku.">
            <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:md:grid-cols-2">
                <x-ui.select name="area_id" label="Area" placeholder="Pilih area"
                             :error="$errors->first('area_id')">
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input name="specific_area" label="Lokasi spesifik" placeholder="Contoh: Office Lt. 1 / Line A"
                            value="{{ old('specific_area') }}"
                            :error="$errors->first('specific_area')" />

                <x-ui.select name="status" label="Status" required :error="$errors->first('status')">
                    <option value="good" @selected(old('status', 'good') === 'good')>Baik</option>
                    <option value="need_repair" @selected(old('status') === 'need_repair')>Perlu perbaikan</option>
                    <option value="not_active" @selected(old('status') === 'not_active')>Tidak aktif</option>
                </x-ui.select>

                <x-ui.input name="qty" label="Jumlah (unit)" type="number" min="1" value="{{ old('qty', 1) }}" required
                            :error="$errors->first('qty')" />

                <div x-show="showExpiry" x-cloak class="eams:md:col-span-2" data-eams-expiry-field>
                    <x-ui.input name="expired_date" label="Tanggal kedaluwarsa (APAR)" type="date"
                                value="{{ old('expired_date') }}"
                                hint="Masa berlaku terutama untuk APAR."
                                :error="$errors->first('expired_date')" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="PIC & dokumentasi" subtitle="Pilih maksimal dua PIC dengan kedudukan setara dan tambahkan dokumentasi awal bila tersedia.">
            <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:md:grid-cols-2">
                <div class="eams:grid eams:gap-1.5" data-eams-component="select">
                    <label for="pic_ids" class="eams:text-[13px] eams:font-semibold eams:text-ink">Person in charge</label>
                    <select id="pic_ids" name="pic_ids[]" multiple size="5"
                            class="eams:block eams:min-h-10 eams:w-full eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft">
                        @foreach($picUsers as $user)
                            <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('pic_ids', [])), true))>{{ $user->name }} &middot; {{ $user->username }}</option>
                        @endforeach
                    </select>
                    <p class="eams:m-0 eams:text-xs eams:text-muted">Gunakan Ctrl/Cmd untuk memilih dua nama. Tidak ada PIC primer.</p>
                    @error('pic_ids')<p class="eams:m-0 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="eams:grid eams:gap-1.5" data-eams-component="file-upload">
                    <label for="photo" class="eams:text-[13px] eams:font-semibold eams:text-ink">Foto inventory</label>
                    <x-ui.file-upload name="photo" label="Pilih foto inventory"
                                      accept="image/jpeg,image/png,image/webp"
                                      hint="JPG, PNG, atau WebP. Maksimum {{ number_format(config('eams.upload.max_kb', 5120) / 1024, 0) }} MB." />
                    @error('photo')<p class="eams:m-0 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="eams:md:col-span-2">
                    <x-ui.textarea name="remark" label="Catatan" rows="3"
                                   placeholder="Kondisi, petunjuk lokasi, atau informasi tambahan..."
                                   :error="$errors->first('remark')">{{ old('remark') }}</x-ui.textarea>
                </div>
            </div>
        </x-ui.card>

        <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-end eams:gap-2">
            <x-ui.button :href="route('compliance.inventory.index')" navigate variant="secondary">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary" icon="check-lg">Simpan inventory</x-ui.button>
        </div>
    </form>
</div>
@endsection
