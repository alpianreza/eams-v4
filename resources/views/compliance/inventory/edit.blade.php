@extends('layouts.app')

@section('title', 'Edit Inventory')

@section('content')
@php
    $currentPics = old('pic_ids', $inventory->pics->pluck('id')->all());
@endphp

<div class="eams:mx-auto eams:max-w-4xl eams:space-y-4 eams:sm:space-y-5"
     data-eams-page="inventory-edit">
    <header class="eams:flex eams:flex-col eams:gap-3 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5">
        <div class="eams:min-w-0">
            <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">Compliance inventory</p>
            <h1 class="eams:m-0 eams:font-mono eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink">Edit {{ $inventory->asset_code }}</h1>
            <p class="eams:mb-0 eams:mt-1 eams:text-[13px] eams:text-muted">Perbarui kondisi, lokasi spesifik, PIC, dan dokumentasi tanpa mengubah identitas utama aset.</p>
        </div>
        <div class="eams:flex eams:flex-wrap eams:gap-2">
            <x-ui.button :href="route('compliance.inventory.detail', $inventory)" navigate variant="secondary" icon="eye">
                Lihat detail
            </x-ui.button>
        </div>
    </header>

    <form method="POST" action="{{ route('compliance.inventory.update', $inventory) }}" enctype="multipart/form-data"
          class="eams:space-y-4 eams:sm:space-y-5">
        @csrf
        @method('PUT')

        <x-ui.card title="Identitas terkunci" subtitle="Kategori, item, area utama, dan nomor inventory tidak dapat diubah setelah aset dibuat.">
            <dl class="eams:grid eams:grid-cols-1 eams:gap-3 eams:sm:grid-cols-2 eams:xl:grid-cols-4">
                <div>
                    <dt class="eams:text-xs eams:font-semibold eams:text-muted">Kategori</dt>
                    <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:font-medium eams:text-ink">{{ $inventory->category->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="eams:text-xs eams:font-semibold eams:text-muted">Item</dt>
                    <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:font-medium eams:text-ink">{{ $inventory->itemType->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="eams:text-xs eams:font-semibold eams:text-muted">Area utama</dt>
                    <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:font-medium eams:text-ink">{{ $inventory->area->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="eams:text-xs eams:font-semibold eams:text-muted">Nomor inventory</dt>
                    <dd class="eams:mb-0 eams:mt-0.5 eams:font-mono eams:text-sm eams:font-medium eams:text-ink">{{ $inventory->asset_code }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Kondisi & penempatan" subtitle="Data operasional berikut dapat diperbarui sesuai kondisi lapangan.">
            <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:md:grid-cols-2">
                <x-ui.input name="specific_area" label="Lokasi spesifik" placeholder="Contoh: Office Lt. 1 / Line A"
                            value="{{ old('specific_area', $inventory->specific_area) }}"
                            :error="$errors->first('specific_area')" />

                <x-ui.input name="type_description" label="Tipe / spesifikasi"
                            value="{{ old('type_description', $inventory->type_description) }}"
                            :error="$errors->first('type_description')" />

                <x-ui.select name="status" label="Status" required :error="$errors->first('status')">
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $inventory->status) === $status)>
                            {{ match($status) { 'good' => 'Baik', 'need_repair' => 'Perlu perbaikan', default => 'Tidak aktif' } }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.input name="qty" label="Jumlah (unit)" type="number" min="1"
                            value="{{ old('qty', $inventory->qty) }}" required
                            :error="$errors->first('qty')" />

                <x-ui.input name="expired_date" label="Tanggal kedaluwarsa" type="date"
                            value="{{ old('expired_date', $inventory->expired_date) }}"
                            :error="$errors->first('expired_date')" />

                <div class="eams:flex eams:items-end eams:gap-0">
                    <input type="hidden" name="active" value="0">
                    <x-ui.checkbox name="active" label="Inventory aktif" :checked="(bool) old('active', $inventory->active)"
                                   hint="Nonaktifkan untuk mengeluarkan aset dari monitoring aktif." />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="PIC & dokumentasi" subtitle="Perbarui penanggung jawab, foto, dan catatan pendukung.">
            <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:md:grid-cols-2">
                <div class="eams:grid eams:gap-1.5" data-eams-component="select">
                    <label for="pic_ids" class="eams:text-[13px] eams:font-semibold eams:text-ink">Person in charge</label>
                    <select id="pic_ids" name="pic_ids[]" multiple size="5"
                            class="eams:block eams:min-h-10 eams:w-full eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft">
                        @foreach($picUsers as $user)
                            <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', $currentPics), true))>{{ $user->name }} &middot; {{ $user->username }}</option>
                        @endforeach
                    </select>
                    <p class="eams:m-0 eams:text-xs eams:text-muted">Maksimal dua PIC dengan kedudukan setara.</p>
                    @error('pic_ids')<p class="eams:m-0 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="eams:grid eams:gap-1.5" data-eams-component="file-upload">
                    <label for="photo" class="eams:text-[13px] eams:font-semibold eams:text-ink">Ganti foto inventory</label>
                    <x-ui.file-upload name="photo" label="Pilih foto pengganti"
                                      accept="image/jpeg,image/png,image/webp"
                                      hint="JPG, PNG, atau WebP. Maksimum {{ number_format(config('eams.upload.max_kb', 5120) / 1024, 0) }} MB." />
                    @error('photo')<p class="eams:m-0 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                    @if($inventory->photo)
                        <p class="eams:m-0 eams:text-xs eams:text-muted">
                            Foto tersimpan tersedia.
                            <a href="{{ route('files.show', ['category' => 'inventory', 'path' => $inventory->photo]) }}" target="_blank" rel="noopener" class="eams:font-semibold eams:text-brand eams:no-underline eams:hover:underline">Lihat foto saat ini</a>.
                        </p>
                    @else
                        <p class="eams:m-0 eams:text-xs eams:text-muted">Belum ada foto. JPG, PNG, atau WebP.</p>
                    @endif
                </div>

                <div class="eams:md:col-span-2">
                    <x-ui.textarea name="remark" label="Catatan" rows="3"
                                   placeholder="Kondisi, petunjuk lokasi, atau informasi tambahan..."
                                   :error="$errors->first('remark')">{{ old('remark', $inventory->remark) }}</x-ui.textarea>
                </div>
            </div>
        </x-ui.card>

        <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-end eams:gap-2">
            <x-ui.button :href="route('compliance.inventory.detail', $inventory)" navigate variant="secondary">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary" icon="check-lg">Simpan perubahan</x-ui.button>
        </div>
    </form>
</div>
@endsection
