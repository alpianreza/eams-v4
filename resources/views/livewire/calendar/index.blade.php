@php
    $monthName = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
    $prevMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
    $nextMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');
    $nextDisabled = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->isFuture();
@endphp

<div class="eams:mx-auto eams:max-w-7xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="calendar">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="calendar3"
                      title="Kalender Compliance"
                      :lead="'Bulan ' . $monthName">
        <x-slot:actions>
            <x-ui.month-nav :month="$monthStart->month" :year="$monthStart->year"
                            :prev-url="route('calendar.index', ['month' => $prevMonth])"
                            :next-url="$nextDisabled ? null : route('calendar.index', ['month' => $nextMonth])"
                            :disabled-next="$nextDisabled" />
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('status'))
        <x-ui.alert variant="success" title="Tersimpan" dismissible>{{ session('status') }}</x-ui.alert>
    @endif

    @can('write')
        <x-ui.card title="Tambah event" subtitle="Buat event kalender baru.">
            <form method="POST" action="{{ route('calendar.store') }}" class="eams:grid eams:grid-cols-1 eams:gap-3 sm:eams:grid-cols-6 eams:items-end">
                @csrf
                <div class="sm:eams:col-span-2">
                    <label class="eams:block eams:text-xs eams:font-medium eams:text-muted">Judul event</label>
                    <input type="text" name="title" required
                           class="eams:mt-1 eams:block eams:w-full eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft">
                </div>
                <div>
                    <label class="eams:block eams:text-xs eams:font-medium eams:text-muted">Mulai</label>
                    <input type="date" name="start_at" required
                           class="eams:mt-1 eams:block eams:w-full eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft">
                </div>
                <div>
                    <label class="eams:block eams:text-xs eams:font-medium eams:text-muted">Selesai</label>
                    <input type="date" name="end_at"
                           class="eams:mt-1 eams:block eams:w-full eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft">
                </div>
                <div>
                    <label class="eams:block eams:text-xs eams:font-medium eams:text-muted">Warna</label>
                    <input type="color" name="color" value="#0d6efd"
                           class="eams:mt-1 eams:block eams:h-9 eams:w-full eams:cursor-pointer eams:rounded-eams eams:border eams:border-border eams:bg-surface p-0.5">
                </div>
                <div class="sm:eams:col-span-1 eams:col-span-1">
                    <button type="submit"
                            class="eams:inline-flex eams:w-full eams:items-center eams:justify-center eams:rounded-eams eams:border eams:font-semibold eams:no-underline eams:shadow-eams-1 eams:transition-colors eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand eams:focus-visible:ring-offset-2 eams:border-brand eams:bg-brand eams:text-brand-contrast eams:hover:bg-brand-hover eams:hover:text-brand-contrast eams:min-h-9 eams:px-3 eams:py-2 eams:text-xs">
                        Tambah
                    </button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.card title="Agenda" :subtitle="$monthName" padding="false">
        <div class="eams:overflow-x-auto">
            <table class="eams:w-full eams:border-collapse eams:text-center eams:text-[13px]" style="table-layout:fixed">
                <thead>
                    <tr>
                        @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
                            <th class="eams:text-xs eams:font-semibold eams:uppercase eams:text-muted eams:py-2">{{ $d }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($weeks as $week)
                        <tr class="eams:h-24">
                            @foreach($week as $day)
                                <td class="eams:relative eams:p-1 eams:align-top
                                    @if($day['offday']) eams:bg-surface-sunk @elseif(! $day['in_month']) eams:text-muted @endif
                                    @if($day['today']) eams:ring-2 eams:ring-brand @endif" @if($day['offday']) data-offday="true" @endif>
                                    <div class="eams:flex eams:items-center eams:justify-between">
                                        <span class="eams:text-xs eams:font-medium @if($day['offday']) eams:text-danger @endif">{{ $day['day'] }}</span>
                                        @if($day['holiday'])
                                            <x-ui.badge variant="danger" size="sm" title="{{ $day['holiday'] }}">{{ $day['holiday'] }}</x-ui.badge>
                                        @endif
                                    </div>
                                    @foreach($day['events'] as $ev)
                                        <div class="eams:mt-1 eams:block eams:w-full eams:overflow-hidden eams:text-ellipsis eams:whitespace-nowrap"
                                             style="background:{{ $ev->color ?: '#0d6efd' }}" title="{{ $ev->title }}">
                                            <span class="eams:px-1 eams:py-0.5 eams:text-white eams:font-medium eams:text-[10px]">
                                                {{ $ev->sticker }} {{ \Str::limit($ev->title, 12) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
