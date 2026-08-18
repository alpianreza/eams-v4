<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Checklist {{ $inventory->asset_code }} — {{ $periodKey }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .meta { color: #555; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    th { background: #eee; }
    .ok { color: #0a7a2f; } .not_ok { color: #b00020; } .na { color: #666; }
    .head td { border: none; padding: 1px 0; }
</style>
</head>
<body>
    <h1>Checklist Compliance — {{ $inventory->itemType->name ?? '—' }}</h1>
    <div class="meta">EAMS · digenerate {{ $generatedAt->format('Y-m-d H:i') }} · Periode {{ $periodKey }}</div>

    <table class="head" style="margin-bottom:10px">
        <tr><td><strong>No Inventaris:</strong> {{ $inventory->asset_code }}</td>
            <td><strong>Area:</strong> {{ $inventory->area->name ?? '—' }}@if($inventory->specific_area) / {{ $inventory->specific_area }}@endif</td></tr>
        <tr><td><strong>Status:</strong> {{ str_replace('_',' ',$inventory->status) }}</td>
            <td><strong>PIC:</strong> {{ $inventory->pics->pluck('name')->join(' - ') ?: '—' }}</td></tr>
        @if($inventory->expired_date)
        <tr><td colspan="2"><strong>Expired:</strong> {{ $inventory->expired_date }}@if($inventory->isExpired()) (EXPIRED)@endif</td></tr>
        @endif
    </table>

    <table>
        <thead>
            <tr><th style="width:6%">#</th><th>Pertanyaan</th><th style="width:12%">Hasil</th><th style="width:30%">Keterangan</th><th style="width:16%">Diperiksa oleh</th></tr>
        </thead>
        <tbody>
        @forelse($logs as $i => $log)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $log->question->question ?? '—' }}</td>
                <td class="{{ $log->status }}">{{ strtoupper(str_replace('_',' ',$log->status)) }}</td>
                <td>{{ $log->remark ?? '—' }}</td>
                <td>{{ $log->checked_by_name }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#777">Belum ada hasil untuk periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
