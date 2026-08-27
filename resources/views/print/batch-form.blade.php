<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Batch {{ $itemType->name }} — {{ $periodLabel }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    .subtitle { font-size: 10px; color: #555; margin-bottom: 4px; }
    .meta { color: #555; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 3px 5px; text-align: left; }
    th { background: #eee; }
    .ok { color: #0a7a2f; } .not_ok { color: #b00020; } .na { color: #666; }
    h2 { font-size: 12px; margin: 14px 0 4px; }
    .footer { margin-top: 14px; font-size: 9px; color: #555; }
</style>
</head>
<body>
    <h1>Checklist {{ strtoupper($itemType->name) }}</h1>
    <div class="subtitle">Form Kolektif Bulanan ({{ $frequency }})</div>
    <div class="meta">
        {{ $companyName }}@if($companyAddress) / {{ $companyAddress }}@endif
        &nbsp;·&nbsp; Bulan: {{ $periodLabel }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:16%">Lokasi</th>
                <th style="width:12%">PIC</th>
                @foreach($masters as $master)
                    <th>{{ \Illuminate\Support\Str::limit($master->question, 20) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @forelse($inventories as $i => $inv)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $inv->specific_area ?: '—' }}</td>
                <td>{{ $inv->pics->pluck('name')->join(', ') ?: '—' }}</td>
                @foreach($masters as $master)
                    @php($status = $matrix[$inv->id][$master->id] ?? '')
                    <td class="{{ $status }}">
                        @if($status === 'ok')OK
                        @elseif($status === 'not_ok')X
                        @elseif($status === 'na')NA
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ 3 + $masters->count() }}" style="text-align:center;color:#777">Tidak ada inventory aktif untuk item type ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Temuan (Tidak Sesuai)</h2>
    <table>
        <thead>
            <tr>
                <th style="width:14%">Kode</th>
                <th style="width:28%">Pertanyaan</th>
                <th style="width:26%">Keterangan</th>
                <th style="width:12%">Diperiksa</th>
                <th style="width:10%">Tanggal</th>
                <th style="width:10%">Periode</th>
            </tr>
        </thead>
        <tbody>
        @forelse($findings as $finding)
            <tr>
                <td>{{ $finding['asset_code'] }}</td>
                <td>{{ $finding['question'] }}</td>
                <td>{{ $finding['remark'] ?: '—' }}</td>
                <td>{{ $finding['checked_by_name'] }}</td>
                <td>{{ $finding['check_date'] }}</td>
                <td>{{ $finding['period_key'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#777">Tidak ada temuan.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">EAMS · digenerate {{ $generatedAt->format('d-m-Y H:i') }}</div>
</body>
</html>
