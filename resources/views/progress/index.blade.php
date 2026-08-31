@extends('layouts.app')

@section('title', 'Monitoring Progress')

@section('content')
<div class="eams:mx-auto eams:max-w-6xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="progress">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="bar-chart-line"
                      title="Monitoring Progress"
                      lead="Seberapa jauh tiap PIC menyelesaikan checklist per periode - diurutkan dari yang paling tertinggal." />

    <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-between eams:gap-3">
        <x-ui.month-nav :month="(int) substr($month, 5, 2)" :year="(int) substr($month, 0, 4)"
                        :prev-url="route('progress.index', ['month' => $prevMonth])"
                        :next-url="route('progress.index', ['month' => $nextMonth])" />
        <a href="{{ route('progress.export', ['month' => $month]) }}"
           class="eams:inline-flex eams:min-h-9 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3.5 eams:py-2 eams:text-[13px] eams:font-semibold eams:text-ink eams:shadow-eams-1 eams:no-underline eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
            <i class="bi bi-download" aria-hidden="true"></i> Export CSV
        </a>
    </div>

    @if($rows === [])
        <x-ui.empty-state icon="people" title="Belum ada PIC dengan inventory aktif"
                          description="User yang terdaftar sebagai PIC pada inventory aktif akan muncul di sini." />
    @else
        <x-ui.table label="Progress PIC {{ $monthLabel }}">
            <thead>
                <tr>
                    <th scope="col">PIC</th>
                    <th scope="col" class="eams:text-center">Inv.</th>
                    <th scope="col" class="eams:text-center">Periode wajib</th>
                    <th scope="col" class="eams:text-center">Done</th>
                    <th scope="col" class="eams:text-center">Pending</th>
                    <th scope="col" class="eams:text-center">Late</th>
                    <th scope="col" style="width:220px">Progress</th>
                    <th scope="col" class="eams:text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                <tr wire:key="progress-{{ $row['user']->id }}" data-eams-progress-row="{{ $row['user']->id }}">
                    <td class="eams:min-w-[10rem]">
                        <span class="eams:block eams:text-[13px] eams:font-semibold eams:text-ink">{{ $row['user']->name }}</span>
                        <span class="eams:block eams:text-[11px] eams:text-muted">{{ $row['user']->username }}</span>
                    </td>
                    <td class="eams:text-center eams:tabular-nums">{{ $row['totalInventory'] }}</td>
                    <td class="eams:text-center eams:tabular-nums">{{ $row['required'] }}</td>
                    <td class="eams:text-center eams:tabular-nums eams:text-success">{{ $row['done'] }}</td>
                    <td class="eams:text-center eams:tabular-nums {{ $row['pending'] > 0 ? 'eams:text-warning' : '' }}">{{ $row['pending'] }}</td>
                    <td class="eams:text-center eams:tabular-nums {{ $row['late'] > 0 ? 'eams:font-semibold eams:text-danger' : '' }}">{{ $row['late'] }}</td>
                    <td>
                        <div class="eams:flex eams:items-center eams:gap-2">
                            <div class="eams:flex-1">
                                <x-ui.progress :value="$row['done']" :max="max($row['required'], 1)" size="sm" />
                            </div>
                            <span class="eams:w-10 eams:text-right eams:text-xs eams:font-bold eams:tabular-nums eams:text-ink">{{ $row['progress'] }}%</span>
                        </div>
                    </td>
                    <td>
                        <div class="eams:flex eams:items-center eams:justify-end eams:gap-1.5">
                            <button type="button"
                                    x-on:click="$dispatch('open-modal', 'progress-detail-{{ $row['user']->id }}')"
                                    class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-2.5 eams:text-xs eams:font-semibold eams:text-muted eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                                    aria-label="Detail {{ $row['user']->name }}">
                                <i class="bi bi-list-check" aria-hidden="true"></i> Detail
                            </button>
                            @if($canRemind)
                                <form method="POST" action="{{ route('progress.remind', $row['user']) }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit"
                                            @if($row['pending'] === 0) disabled @endif
                                            class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-2.5 eams:text-xs eams:font-semibold eams:text-muted eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand eams:disabled:cursor-not-allowed eams:disabled:opacity-50"
                                            title="{{ $row['pending'] === 0 ? 'Tidak ada pending' : 'Kirim reminder checklist' }}">
                                        <i class="bi bi-bell" aria-hidden="true"></i> Remind
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-ui.table>
    @endif
</div>

@foreach($rows as $row)
    <x-ui.modal name="progress-detail-{{ $row['user']->id }}" :title="'Detail ' . $row['user']->name">
        @if($row['detailMissing'] === [])
            <p class="eams:m-0 eams:text-sm eams:text-muted">Tidak ada checklist yang belum diisi untuk periode ini.</p>
        @else
            <ul class="eams:m-0 eams:grid eams:gap-2 eams:p-0 eams:list-none">
                @foreach($row['detailMissing'] as $detail)
                    <li class="eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk/40 eams:p-3">
                        <p class="eams:m-0 eams:text-[13px] eams:font-semibold eams:text-ink">{{ $detail['inventory'] }}</p>
                        <p class="eams:mb-0 eams:mt-0.5 eams:text-[11px] eams:text-muted">
                            {{ $detail['frequency'] }} - missing:
                            <span class="eams:font-semibold eams:text-warning">{{ implode(', ', $detail['missing']) }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.modal>
@endforeach
@endsection
