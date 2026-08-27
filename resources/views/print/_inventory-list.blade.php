<div class="table-responsive mt-1">
    @if($inventories->isEmpty())
        <p class="text-muted mb-0">Tidak ada inventory untuk item type ini.</p>
    @else
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:45%">Kode</th>
                    <th>Lokasi</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventories as $inv)
                    <tr>
                        <td>{{ $inv->asset_code }}</td>
                        <td>{{ $inv->specific_area ?: '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('compliance.report.pdf', $inv) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-printer me-1"></i> Print
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
