<x-ui.table label="Inventory untuk item type terpilih">
    <thead>
        <tr>
            <th scope="col">Kode</th>
            <th scope="col">Lokasi</th>
            <th scope="col" class="eams:text-right">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inventories as $inv)
            <tr>
                <td class="eams:font-mono eams:text-[13px] eams:font-semibold eams:text-ink">{{ $inv->asset_code }}</td>
                <td class="eams:text-[13px] eams:text-muted">{{ $inv->specific_area ?: '-' }}</td>
                <td class="eams:text-right">
                    <a href="{{ route('compliance.report.pdf', $inv) }}" target="_blank" rel="noopener"
                       class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-brand/40 eams:bg-brand-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-brand eams:no-underline eams:transition-colors eams:hover:bg-brand eams:hover:text-brand-contrast eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                        <i class="bi bi-printer" aria-hidden="true"></i> Print
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="eams:py-4 eams:text-center eams:text-[13px] eams:text-subtle">Tidak ada inventory untuk item type ini.</td></tr>
        @endforelse
    </tbody>
</x-ui.table>
