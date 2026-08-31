@extends('layouts.app')

@section('title', 'Print Center')

@section('content')
<div class="eams:mx-auto eams:max-w-5xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="print-center">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="printer"
                      title="Print Center"
                      lead="Cetak checklist per inventaris atau form batch bulanan beserta temuan." />

    <div class="eams:grid eams:grid-cols-1 eams:gap-3 eams:md:grid-cols-2">
        <button type="button" id="btnPrintItem"
                class="eams:group eams:flex eams:items-center eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:text-left eams:shadow-eams-1 eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
            <span class="eams:inline-flex eams:size-11 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-brand-soft eams:text-xl eams:text-brand">
                <i class="bi bi-printer" aria-hidden="true"></i>
            </span>
            <span class="eams:min-w-0 eams:flex-1">
                <span class="eams:block eams:text-sm eams:font-bold eams:text-ink">Print Per Inventory</span>
                <span class="eams:block eams:text-[12px] eams:leading-4 eams:text-muted">Rekap checklist per inventaris (PDF report yang sudah ada).</span>
            </span>
            <i class="bi bi-chevron-right eams:text-muted eams:transition-transform eams:group-hover:translate-x-0.5 eams:group-hover:text-brand" aria-hidden="true"></i>
        </button>

        <button type="button" id="btnPrintBatch"
                class="eams:group eams:flex eams:items-center eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:text-left eams:shadow-eams-1 eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
            <span class="eams:inline-flex eams:size-11 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-info-soft eams:text-xl eams:text-info">
                <i class="bi bi-layers" aria-hidden="true"></i>
            </span>
            <span class="eams:min-w-0 eams:flex-1">
                <span class="eams:block eams:text-sm eams:font-bold eams:text-ink">Print Batch</span>
                <span class="eams:block eams:text-[12px] eams:leading-4 eams:text-muted">Form kolektif bulanan per item type + temuan tidak sesuai.</span>
            </span>
            <i class="bi bi-chevron-right eams:text-muted eams:transition-transform eams:group-hover:translate-x-0.5 eams:group-hover:text-brand" aria-hidden="true"></i>
        </button>
    </div>

    <div id="printContent" aria-live="polite"></div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const container = document.getElementById('printContent');

        const skeleton = '<div class="eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:shadow-eams-1 eams:grid eams:gap-2">'
            + '<div class="eams:h-4 eams:w-40 eams:animate-pulse eams:rounded eams:bg-surface-sunk"></div>'
            + '<div class="eams:h-3 eams:w-full eams:animate-pulse eams:rounded eams:bg-surface-sunk"></div>'
            + '<div class="eams:h-3 eams:w-full eams:animate-pulse eams:rounded eams:bg-surface-sunk"></div>'
            + '<div class="eams:h-3 eams:w-3/5 eams:animate-pulse eams:rounded eams:bg-surface-sunk"></div>'
            + '</div>';

        function notifyError(message) {
            if (typeof window.eamsToast === 'function') {
                window.eamsToast(message, 'error');
            }
        }

        function loadPrintContent(url) {
            container.innerHTML = skeleton;

            fetch(url)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    container.querySelectorAll('script').forEach(old => {
                        const script = document.createElement('script');
                        script.textContent = old.textContent;
                        old.replaceWith(script);
                    });
                })
                .catch(() => {
                    container.innerHTML = '<div class="eams:rounded-eams eams:border eams:border-danger/30 eams:bg-danger-soft eams:p-4 eams:text-[13px] eams:text-danger">Gagal memuat konten cetak.</div>';
                    notifyError('Gagal memuat konten cetak. Silakan coba lagi.');
                });
        }

        document.getElementById('btnPrintItem').addEventListener('click', function () {
            loadPrintContent("{{ route('print.item') }}");
        });

        document.getElementById('btnPrintBatch').addEventListener('click', function () {
            loadPrintContent("{{ route('print.batch') }}");
        });
    })();
</script>
@endpush
