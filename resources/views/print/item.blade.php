<x-ui.card title="Print Per Inventory"
           subtitle="Pilih item type, lalu klik Print pada inventory untuk membuka PDF checklist periode berjalan.">
    <div class="eams:grid eams:gap-4">
        <x-ui.select name="itemTypeSelect" id="itemTypeSelect" label="Item Type" placeholder="Pilih item">
            @foreach($itemTypes as $it)
                <option value="{{ $it->id }}" data-frequency="{{ $it->checklist_frequency }}">{{ $it->name }}</option>
            @endforeach
        </x-ui.select>

        <div id="inventoryContainer"></div>
    </div>
</x-ui.card>

<script>
    (function () {
        const select = document.getElementById('itemTypeSelect');
        const container = document.getElementById('inventoryContainer');

        select.addEventListener('change', function () {
            const id = select.value;

            if (!id) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = '<div class="eams:flex eams:items-center eams:gap-2 eams:p-3 eams:text-[13px] eams:text-muted"><span class="eams:size-3.5 eams:animate-spin eams:rounded-full eams:border-2 eams:border-current eams:border-r-transparent"></span> Memuat inventory...</div>';

            fetch("{{ route('print.index') }}/inventory/" + encodeURIComponent(id))
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.text();
                })
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(() => {
                    container.innerHTML = '<div class="eams:rounded-eams eams:border eams:border-danger/30 eams:bg-danger-soft eams:p-3 eams:text-[13px] eams:text-danger">Gagal memuat inventory.</div>';
                });
        });
    })();
</script>
