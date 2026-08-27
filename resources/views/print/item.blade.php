<div class="card border-0 shadow-sm">
    <div class="card-body">

        <h5>Print Per Inventory</h5>
        <p class="text-muted mb-3">Pilih item type, lalu klik "Print" pada inventory untuk membuka PDF checklist periode berjalan.</p>

        <div class="mb-3">
            <label for="itemTypeSelect" class="form-label">Item Type</label>
            <select id="itemTypeSelect" class="form-select">
                <option value="">-- pilih item --</option>
                @foreach($itemTypes as $it)
                    <option value="{{ $it->id }}" data-frequency="{{ $it->checklist_frequency }}">{{ $it->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="inventoryContainer"></div>

    </div>
</div>

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

            container.innerHTML = '<div class="text-center p-3 text-muted">Loading...</div>';

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
                    container.innerHTML = '<div class="text-center p-3 text-danger">Gagal memuat inventory.</div>';
                });
        });
    })();
</script>
