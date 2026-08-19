@extends('layouts.app')

@section('title', 'Ranking PIC')

@section('content')
<h1 class="h4 mb-3">Ranking PIC</h1>
<p class="text-muted small">Skor = tepat waktu ×10 + terlambat ×3.</p>

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>PIC</th><th class="text-center">Tepat waktu</th><th class="text-center">Terlambat</th><th class="text-end">Skor</th></tr></thead>
        <tbody>
        @forelse($board as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['ontime'] }}</td>
                <td class="text-center">{{ $row['late'] }}</td>
                <td class="text-end fw-bold">{{ $row['score'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
