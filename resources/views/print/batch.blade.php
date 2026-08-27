<div class="card border-0 shadow-sm">
    <div class="card-body">

        <h5>Print Batch / Form Kolektif</h5>
        <p class="text-muted mb-3">
            Format ini terpisah dari laporan. Pilih item type untuk mencetak PDF kolektif beserta finding yang berstatus Tidak sesuai.
        </p>

        <div class="mb-3">
            <label for="batchItemTypeSelect" class="form-label">Item Type</label>
            <select id="batchItemTypeSelect" class="form-select">
                <option value="">-- pilih item --</option>
                @foreach($itemTypes as $it)
                    <option value="{{ $it->id }}" data-frequency="{{ $it->checklist_frequency }}">{{ $it->name }}</option>
                @endforeach
            </select>
        </div>

        @php
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $currentMonth = (int) now()->month;
            $currentYear = (int) now()->year;
        @endphp

        <div class="row mt-3">
            <div class="col-md-6">
                <label for="batchMonthSelect" class="form-label">Bulan</label>
                <select id="batchMonthSelect" class="form-select">
                    @foreach($monthNames as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" @selected($currentMonth === $monthNumber)>{{ $monthLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="batchYearSelect" class="form-label">Tahun</label>
                <select id="batchYearSelect" class="form-select">
                    @for($year = $currentYear - 1; $year <= $currentYear + 2; $year++)
                        <option value="{{ $year }}" @selected($currentYear === $year)>{{ $year }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button id="btnPreviewBatchPrint" class="btn btn-primary">
                <i class="bi bi-printer"></i> Preview Print
            </button>
        </div>

    </div>
</div>

<script>
    (function () {
        document.getElementById('btnPreviewBatchPrint').addEventListener('click', function () {
            const itemTypeId = document.getElementById('batchItemTypeSelect').value;
            const month = document.getElementById('batchMonthSelect').value;
            const year = document.getElementById('batchYearSelect').value;

            if (!itemTypeId) {
                alert('Pilih item type dulu');
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
