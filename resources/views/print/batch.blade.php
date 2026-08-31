<x-ui.card title="Print Batch / Form Kolektif"
           subtitle="Pilih item type dan periode untuk mencetak PDF kolektif beserta temuan tidak sesuai.">
    <div class="eams:grid eams:gap-4">
        <x-ui.select name="batchItemTypeSelect" id="batchItemTypeSelect" label="Item Type" placeholder="Pilih item">
            @foreach($itemTypes as $it)
                <option value="{{ $it->id }}" data-frequency="{{ $it->checklist_frequency }}">{{ $it->name }}</option>
            @endforeach
        </x-ui.select>

        @php
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $currentMonth = (int) now()->month;
            $currentYear = (int) now()->year;
        @endphp

        <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:sm:grid-cols-2">
            <x-ui.select name="batchMonthSelect" id="batchMonthSelect" label="Bulan">
                @foreach($monthNames as $monthNumber => $monthLabel)
                    <option value="{{ $monthNumber }}" @selected($currentMonth === $monthNumber)>{{ $monthLabel }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="batchYearSelect" id="batchYearSelect" label="Tahun">
                @for($year = $currentYear - 1; $year <= $currentYear + 2; $year++)
                    <option value="{{ $year }}" @selected($currentYear === $year)>{{ $year }}</option>
                @endfor
            </x-ui.select>
        </div>

        <div class="eams:flex eams:justify-end">
            <x-ui.button id="btnPreviewBatchPrint" variant="primary" icon="printer">Preview Print</x-ui.button>
        </div>
    </div>
</x-ui.card>

<script>
    (function () {
        document.getElementById('btnPreviewBatchPrint').addEventListener('click', function () {
            const itemTypeId = document.getElementById('batchItemTypeSelect').value;
            const month = document.getElementById('batchMonthSelect').value;
            const year = document.getElementById('batchYearSelect').value;

            if (!itemTypeId) {
                if (typeof window.eamsToast === 'function') {
                    window.eamsToast('Pilih item type dulu', 'warning');
                }
                return;
            }

            const url = "{{ route('print.batch-preview') }}" +
                "?item_type_id=" + encodeURIComponent(itemTypeId) +
                "&month=" + encodeURIComponent(month) +
                "&year=" + encodeURIComponent(year);

            window.open(url, "_blank");
        });
    })();
</script>
