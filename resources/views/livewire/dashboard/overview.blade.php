@php
    $kpis = [
        [
            'key' => 'total',
            'label' => 'Inventory aktif',
            'value' => $total,
            'icon' => 'boxes',
            'iconClass' => 'eams:bg-brand-soft eams:text-brand',
            'valueClass' => 'eams:text-ink',
            'hint' => 'Aset aktif yang masuk monitoring compliance.',
        ],
        [
            'key' => 'open',
            'label' => 'Checklist open',
            'value' => $open,
            'icon' => 'clock-history',
            'iconClass' => 'eams:bg-info-soft eams:text-info',
            'valueClass' => 'eams:text-info',
            'hint' => 'Periode berjalan yang masih terbuka.',
        ],
        [
            'key' => 'late',
            'label' => 'Checklist late',
            'value' => $late,
            'icon' => 'exclamation-circle',
            'iconClass' => 'eams:bg-danger-soft eams:text-danger',
            'valueClass' => 'eams:text-danger',
            'hint' => 'Periode terlambat menurut period engine.',
        ],
        [
            'key' => 'expired',
            'label' => 'Expired (mis. APAR)',
            'value' => $expired,
            'icon' => 'calendar2-x',
            'iconClass' => 'eams:bg-warning-soft eams:text-warning',
            'valueClass' => 'eams:text-warning',
            'hint' => 'Inventory aktif dengan tanggal expiry terlewati.',
        ],
    ];

    $statusRows = [
        ['status' => 'GOOD', 'count' => $byStatus['good'], 'description' => 'Kondisi operasional baik'],
        ['status' => 'NEED_REPAIR', 'count' => $byStatus['need_repair'], 'description' => 'Memerlukan perbaikan'],
        ['status' => 'NOT_ACTIVE', 'count' => $byStatus['not_active'], 'description' => 'Berstatus tidak aktif'],
    ];
@endphp

<div x-data="{ explanationOpen: false }"
     data-eams-livewire="dashboard-overview"
     class="eams:space-y-4 eams:sm:space-y-5">
    <header class="eams:flex eams:flex-col eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5">
        <div class="eams:min-w-0">
            <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">Monitoring compliance</p>
            <h1 class="eams:m-0 eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink eams:sm:text-2xl">Dashboard Compliance</h1>
            <p class="eams:mb-0 eams:mt-1.5 eams:max-w-2xl eams:text-[13px] eams:leading-5 eams:text-muted">
                Ringkasan inventory aktif dan status checklist periode berjalan dari perhitungan backend yang sudah ada.
            </p>
        </div>
        <div class="eams:flex eams:flex-wrap eams:gap-2">
            <x-ui.button :href="route('compliance.inventory.index')" navigate variant="secondary" icon="boxes">
                Inventory
            </x-ui.button>
            <x-ui.button :href="route('progress.index')" navigate icon="clipboard2-check">
                Progress checklist
            </x-ui.button>
        </div>
    </header>

    <section aria-label="Ringkasan KPI" class="eams:grid eams:grid-cols-1 eams:gap-3 eams:sm:grid-cols-2 eams:xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <x-ui.card data-dashboard-kpi="{{ $kpi['key'] }}" aria-label="{{ $kpi['label'] }}: {{ $kpi['value'] }}">
                <div class="eams:flex eams:items-start eams:justify-between eams:gap-3">
                    <div class="eams:min-w-0">
                        <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">{{ $kpi['label'] }}</p>
                        <strong data-dashboard-kpi-value class="eams:mt-1 eams:block eams:text-2xl eams:font-extrabold eams:leading-none eams:tabular-nums {{ $kpi['valueClass'] }}">
                            {{ number_format($kpi['value']) }}
                        </strong>
                    </div>
                    <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams {{ $kpi['iconClass'] }}">
                        <i class="bi bi-{{ $kpi['icon'] }}" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="eams:mb-0 eams:mt-3 eams:text-[11px] eams:leading-4 eams:text-subtle">{{ $kpi['hint'] }}</p>
            </x-ui.card>
        @endforeach
    </section>

    <div class="eams:grid eams:grid-cols-1 eams:gap-4 eams:lg:grid-cols-2">
        <x-ui.card title="Status kondisi inventory" subtitle="Distribusi inventory aktif berdasarkan status tersimpan.">
            @if($total === 0)
                <x-ui.empty-state
                    icon="boxes"
                    title="Belum ada inventory aktif"
                    description="Ringkasan kondisi akan tersedia setelah inventory aktif ditambahkan."
                    :boxed="false"
                >
                    <x-ui.button :href="route('compliance.inventory.index')" navigate variant="secondary" size="sm">
                        Buka inventory
                    </x-ui.button>
                </x-ui.empty-state>
            @else
                <div class="eams:divide-y eams:divide-border">
                    @foreach($statusRows as $row)
                        <div data-dashboard-status="{{ $row['status'] }}" class="eams:flex eams:items-center eams:gap-3 eams:py-3 eams:first:pt-0 eams:last:pb-0">
                            <div class="eams:min-w-0 eams:flex-1">
                                <x-ui.status-indicator :status="$row['status']" size="sm" />
                                <p class="eams:mb-0 eams:mt-1 eams:text-[11px] eams:text-muted">{{ $row['description'] }}</p>
                            </div>
                            <strong class="eams:text-lg eams:font-extrabold eams:tabular-nums eams:text-ink">{{ number_format($row['count']) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Tindakan cepat" subtitle="Buka area operasional terkait tanpa reload penuh.">
            @if($late > 0)
                <x-ui.alert variant="danger" title="Perlu perhatian">
                    Ada {{ number_format($late) }} checklist berstatus Late pada ringkasan saat ini.
                </x-ui.alert>
            @else
                <x-ui.alert variant="success" title="Checklist terkendali">
                    Tidak ada checklist Late pada ringkasan saat ini.
                </x-ui.alert>
            @endif

            <nav aria-label="Tautan cepat Dashboard" class="eams:mt-3 eams:grid eams:grid-cols-1 eams:gap-2 eams:sm:grid-cols-2">
                <a data-dashboard-link="inventory" href="{{ route('compliance.inventory.index') }}" wire:navigate
                   class="eams:flex eams:items-center eams:gap-3 eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk eams:p-3 eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                    <i class="bi bi-boxes eams:text-base" aria-hidden="true"></i>
                    <span class="eams:min-w-0"><strong class="eams:block eams:text-xs">Inventory</strong><span class="eams:block eams:text-[10px] eams:text-muted">Lihat aset compliance</span></span>
                </a>
                <a data-dashboard-link="progress" href="{{ route('progress.index') }}" wire:navigate
                   class="eams:flex eams:items-center eams:gap-3 eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk eams:p-3 eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                    <i class="bi bi-clipboard2-check eams:text-base" aria-hidden="true"></i>
                    <span class="eams:min-w-0"><strong class="eams:block eams:text-xs">Progress</strong><span class="eams:block eams:text-[10px] eams:text-muted">Pantau periode checklist</span></span>
                </a>
                <a data-dashboard-link="evidence" href="{{ route('evidence.index') }}" wire:navigate
                   class="eams:flex eams:items-center eams:gap-3 eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk eams:p-3 eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                    <i class="bi bi-camera eams:text-base" aria-hidden="true"></i>
                    <span class="eams:min-w-0"><strong class="eams:block eams:text-xs">Evidence</strong><span class="eams:block eams:text-[10px] eams:text-muted">Tinjau temuan Not OK</span></span>
                </a>
                <a data-dashboard-link="ranking" href="{{ route('ranking.index') }}" wire:navigate
                   class="eams:flex eams:items-center eams:gap-3 eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk eams:p-3 eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                    <i class="bi bi-bar-chart eams:text-base" aria-hidden="true"></i>
                    <span class="eams:min-w-0"><strong class="eams:block eams:text-xs">Ranking</strong><span class="eams:block eams:text-[10px] eams:text-muted">Lihat skor ketepatan</span></span>
                </a>
            </nav>
        </x-ui.card>
    </div>

    <section class="eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:shadow-eams-1" aria-labelledby="dashboard-explanation-title">
        <button type="button"
                data-dashboard-toggle="explanation"
                @click="explanationOpen = ! explanationOpen"
                :aria-expanded="explanationOpen.toString()"
                aria-controls="dashboard-explanation"
                class="eams:flex eams:w-full eams:items-center eams:justify-between eams:gap-3 eams:rounded-eams-lg eams:border-0 eams:bg-transparent eams:px-4 eams:py-3 eams:text-left eams:text-ink eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
            <span>
                <strong id="dashboard-explanation-title" class="eams:block eams:text-sm">Penjelasan status</strong>
                <span class="eams:mt-0.5 eams:block eams:text-[11px] eams:text-muted">Buka definisi singkat tanpa mengubah perhitungan Dashboard.</span>
            </span>
            <i class="bi bi-chevron-down eams:text-xs eams:text-muted eams:transition-transform"
               :class="{ 'eams:rotate-180': explanationOpen }" aria-hidden="true"></i>
        </button>
        <div id="dashboard-explanation" data-dashboard-explanation x-cloak x-show="explanationOpen" x-transition.opacity
             class="eams:grid eams:grid-cols-1 eams:gap-3 eams:border-t eams:border-border eams:px-4 eams:py-4 eams:md:grid-cols-3">
            <div class="eams:space-y-1.5">
                <x-ui.status-indicator status="OPEN" size="sm" />
                <p class="eams:m-0 eams:text-[11px] eams:leading-4 eams:text-muted">Periode berjalan yang belum memiliki hasil checklist.</p>
            </div>
            <div class="eams:space-y-1.5">
                <x-ui.status-indicator status="LATE" size="sm" />
                <p class="eams:m-0 eams:text-[11px] eams:leading-4 eams:text-muted">Keterlambatan ditentukan oleh canonical period engine.</p>
            </div>
            <div class="eams:space-y-1.5">
                <x-ui.status-indicator status="EXPIRED" size="sm" />
                <p class="eams:m-0 eams:text-[11px] eams:leading-4 eams:text-muted">Expiry tidak otomatis mengubah status inventory menjadi Not Active.</p>
            </div>
        </div>
    </section>
</div>
