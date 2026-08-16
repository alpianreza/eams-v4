# 08 — JavaScript & AJAX

30 file JS di `public/js/`. Pola: Vanilla JS + fetch (header `X-Requested-With: XMLHttpRequest`), Alpine.js untuk halaman kompleks (EMS/FDM/device/inventory sort), jQuery dipakai terbatas (sparkline), SweetAlert2 untuk konfirmasi, cropperjs untuk foto. **CSRF:** `CsrfAssetFilter` menyuntik meta token + bootstrap yang otomatis melampirkan token ke fetch/XHR/form mutating; beberapa respons AJAX membawa `csrfHash` baru untuk rotasi token.

## Peta UI → JS → AJAX → Route → Controller → DB (per file)

### app.js (21KB, dimuat global)
Shell navigation (view-transition), tema (light/dark/system), palette/style/density + sidebar position (cookie), `safeToast` global, dsb. Dipanggil di semua halaman.

### checklist.js — halaman form checklist per item
- UI: radio status per pertanyaan → toggle `not_ok-row` (remark+foto), progress bar, tombol tandai semua OK.
- Validasi client: semua pertanyaan wajib; not_ok wajib remark/foto → cocok dgn server `submitChecklist`.
- AJAX navigasi kalender: klik link `.calendar-grid/.calendar-nav/.checklist-slot-links` → fetch partial (`checklist()` AJAX → `_calendar`+`_form`), pushState, re-bind.
- Upload foto: kompresi canvas + burn-in tanggal & nama user (`window.CHECKLIST_USER`).
- Endpoint: `POST /compliance/checklist/submit` (form biasa, redirect).

### checklist-master.js — CRUD pertanyaan (AJAX penuh)
- `POST compliance/checklist/master/store|update/{id}|delete/{id}` (FormData), ganti frekuensi: `POST …/item-frequency/{id}` (URLSearchParams). SweetAlert konfirmasi hapus. Reload setelah sukses.

### checklist-calender.js — kalender kecil di form checklist.

### inventory.js (22KB) — inventory list
- Alpine `complianceInventoryPage` (sort state) + event `inventory-sort-change`.
- Filter AJAX (kategori/area/search/perPage, debounce 250ms, AbortController) → `GET /compliance/inventory?…` (full HTML → ambil `#inventoryAjax`), pushState, re-bind pagination+tooltip.
- Edit modal: `GET /compliance/inventory/get/{id}` (JSON) → isi form → `POST /compliance/inventory/update/{id}` → update baris DOM tanpa reload.
- Add modal: `POST /compliance/inventory/store`.
- Delete: SweetAlert → `POST …/delete/{id}` → hapus baris/kartu dari DOM.
- QR modal: tampil, download (blob), regenerate `POST …/regenerate-qr/{id}`.

### inventory-detail.js — halaman detail inventory (navigasi bulan AJAX).

### Grid files (per item type): cctv-grid.js, emergency-light-grid.js, fire-extinguisher-grid.js, first-aid-box-grid.js, first-aid-content-grid.js, gate-grid.js, hydrant-grid.js, intrusion-alarm-grid.js, smoke-detector-grid.js, generic-grid.js
- Pola seragam: klik sel → siklus status (ok → not_ok → (na) → clear) → `POST …-grid/save` (JSON: inventory_id, period_key, template_id, mode[, time_slot]) → update sel; tombol "centang semua" → `POST …-grid/mark-all` (hanya isi sel kosong; response `inserted`).
- Off-day (libur/weekend) ditandai non-klik di daily grid (data dari server).

### dashboard.js (29KB) — compliance dashboard
Fetch 6–8 endpoint `compliance/dashboard/*` (trend, progress-trend, status-pie, total-inventory, risk-insight, pending-checklist) → render grafik (sparkline/chart) + tabel pending. ⚠️ memanggil `risk-trend`/`data` yang method-nya tidak ada (lihat 14/15).

### compliance-report.js — laporan: dependent dropdown kategori→item→inventory (`GET compliance/report/item-by-category`, `inventory-by-type`) → `GET compliance/report/load` → partial tabel; tombol export PDF (`export/pdf/...`).

### compliance-calendar.js (16KB) — kalender
Month feed `GET holidays?month=Y-m` (events+offdays) → render grid + agenda; CRUD event `POST holidays/store|update/{id}|delete/{id}`; kelola libur nasional `POST holidays/national/*`; mini calendar; klik hari → tambah.

### progress-monitoring.js — monitoring progress
`GET compliance/progress/ajax?month=` → tabel per user; modal detail `GET …/detail?user_id=&month=`; tombol remind `POST …/remind` (role-gated, SweetAlert hasil); export CSV link `…/export?month=`.

### evidence.js (dimuat GLOBAL dari layout) — Evidence Center
`GET compliance/evidence/ajax` (filter tahun/item/follow-up, paginasi 12) → `_grid`; modal `GET …/detail/{id}`; `POST …/update-followup` (status+note).

### qr-center.js — QR gallery: album per item (`GET compliance/inventory/qr-album/{item}`), download zip (`qr-album-download`), regen (`qr-album-regen`), print (`qr-album-print`).

### users-management.js + user-access-form.js — manajemen user
Form user: role select → default page access (dari `roleDefaults` boot); checkbox matrix page_access per grup; permission read/write; validasi client mirror server.

### it-devices.js / it-device-live.js / device-remote.js — Device Control
List AJAX (`GET /it/devices/ajax`, stats), detail auto-refresh (`GET /it/devices/{id}/fragment`), aksi remote (`POST /it/device/remote` + konfirmasi; popup_message dgn input pesan), push update (`POST /it/device/push-update`).

### it-suite-alpine.js — halaman IT Center (kartu workspace, Alpine).

### patrol.js (32KB) — Patrol
Boot dari `window.*BOOT`; mulai sesi (`POST patrol/sessions/start`), scan barcode (input scanner/kamera), foto wajib (getUserMedia / upload), geolokasi (`navigator.geolocation`), kirim `POST patrol/sessions/scan` (session_id, barcode, status ok/not_ok, note, latitude, longitude, photos[]) → update progres & next checkpoint; cancel (`sessions/cancel`); dashboard & editor layout (`POST patrol/layout/save` dgn checkpoints_json + gambar + transform).

### ems-report.js (18KB) — EMS (Alpine)
Editor matriks bulan×tahun per jenis report; autosave `POST ems-reports/{jenis}/save` (report_year, production_output, months[]/sections[][]) → response dataset + summaryHtml (panel diganti) + csrfHash; perhitungan intensity/emission/perubahan dirender ulang dari dataset server.

### fdm-data-collection.js — FDM (Alpine)
Editor retailer dinamis (tambah/hapus baris), nilai bulanan, FTE; autosave `POST fdm-data-collection/production-section/save` (retailers_json, workforce_json) → payload dataset baru.

### table-stack.js — responsif tabel→kartu di layar kecil (global).
### home-dashboard.js — interaksi home (daftar tugas).

## CDN & library pihak ketiga di frontend

| Library | Versi | Sumber | Pemakaian |
|---|---|---|---|
| Bootstrap | 5.3.2 | jsDelivr | UI dasar |
| Bootstrap Icons | latest | jsDelivr | ikon |
| SweetAlert2 | 11 | jsDelivr | konfirmasi/toast |
| cropperjs | 1.6.1 | cdnjs | crop foto inventory/user |
| Alpine.js | 3.x | jsDelivr | halaman kompleks |
| jQuery | 3.7.1 | code.jquery.com | sparkline & legacy |
| jquery-sparklines | 2.1.2 | cdnjs | mini chart dashboard |
| AdminLTE 4 | lokal | public/adminlte4 | shell admin |

## Catatan arsitektur JS

- **Tidak ada build step** (package.json hanya stylelint untuk CSS). Semua JS polos per-halaman, dimuat via `<script>` per view atau global di layout.
- Duplikasi: pola grid save/mark-all ditulis ulang di ±11 file grid (kandidat konsolidasi di Laravel/Livewire/API).
- `evidence.js` dimuat global walau hanya dipakai 1 halaman (payload kecil tapi tidak perlu global).
