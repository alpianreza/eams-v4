@extends('layouts.app')

@section('title', 'Print Center')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/print.css') }}">
@endpush

@section('content')
<div class="print-center-page">

    <x-page-header
        variant="card"
        tone="utility"
        eyebrow="Compliance"
        eyebrow-icon="bi-printer"
        title="Print Center"
        lead="Cetak checklist per inventaris atau form batch bulanan beserta temuan." />

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <button type="button" id="btnPrintItem" class="print-mode-card">
                <span class="print-mode-icon"><i class="bi bi-printer"></i></span>
                <span class="print-mode-body">
                    <span class="print-mode-title">Print Per Inventory</span>
                    <span class="print-mode-desc">Rekap checklist per inventaris (reuse PDF report yang sudah ada).</span>
                </span>
                <i class="bi bi-chevron-right print-mode-arrow"></i>
            </button>
        </div>
        <div class="col-md-6">
            <button type="button" id="btnPrintBatch" class="print-mode-card">
                <span class="print-mode-icon"><i class="bi bi-layers"></i></span>
                <span class="print-mode-body">
                    <span class="print-mode-title">Print Batch</span>
                    <span class="print-mode-desc">Form kolektif bulanan per item type + finding "Tidak sesuai".</span>
                </span>
                <i class="bi bi-chevron-right print-mode-arrow"></i>
            </button>
        </div>
    </div>

    <!-- AJAX CONTAINER -->
    <div id="printContent"></div>

</div>
@endsection

@push('scripts')
<script>
    (function () {
        const container = document.getElementById('printContent');

        function loadPrintContent(url) {
            container.innerHTML = '<div class="text-center p-4 text-muted">Loading...</div>';

            fetch(url)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    // innerHTML tidak mengeksekusi <script> — jalankan ulang secara manual.
                    container.querySelectorAll('script').forEach(old => {
                        const script = document.createElement('script');
                        script.textContent = old.textContent;
                        old.replaceWith(script);
                    });
                })
                .catch(() => {
                    container.innerHTML = '<div class="text-center p-4 text-danger">Gagal memuat konten.</div>';
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
