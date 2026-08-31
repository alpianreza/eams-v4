@extends('layouts.app')

@section('title', 'Ranking PIC')

@section('content')
<div class="eams:mx-auto eams:max-w-4xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="ranking">
    <x-ui.page-header eyebrow="Monitoring" eyebrow-icon="trophy"
                      title="Ranking PIC"
                      lead="Skor = tepat waktu &times;10 + terlambat &times;3 (BR-18)." />

    @if($board === [])
        <x-ui.empty-state icon="trophy" title="Belum ada data"
                          description="Skor ranking akan muncul setelah ada checklist yang diisi." />
    @else
        <x-ui.table label="Peringkat PIC" striped>
            <thead>
                <tr>
                    <th scope="col" class="eams:w-14">#</th>
                    <th scope="col">PIC</th>
                    <th scope="col" class="eams:text-center">Tepat waktu</th>
                    <th scope="col" class="eams:text-center">Terlambat</th>
                    <th scope="col" class="eams:text-right">Skor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($board as $i => $row)
                @php($rank = $i + 1)
                <tr wire:key="rank-{{ $rank }}" data-eams-rank="{{ $rank }}">
                    <td>
                        @if($rank <= 3)
                            <x-ui.badge :variant="match ($rank) { 1 => 'success', 2 => 'info', default => 'neutral' }" size="sm">{{ $rank }}</x-ui.badge>
                        @else
                            <span class="eams:text-sm eams:text-muted">{{ $rank }}</span>
                        @endif
                    </td>
                    <td class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $row['name'] }}</td>
                    <td class="eams:text-center eams:tabular-nums eams:text-success">{{ $row['ontime'] }}</td>
                    <td class="eams:text-center eams:tabular-nums {{ $row['late'] > 0 ? 'eams:text-danger' : '' }}">{{ $row['late'] }}</td>
                    <td class="eams:text-right eams:font-extrabold eams:tabular-nums eams:text-ink">{{ $row['score'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </x-ui.table>
    @endif
</div>
@endsection
