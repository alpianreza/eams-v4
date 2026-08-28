# 21 — EAMS UI/UX Design System

> **Status:** Phase 2B aktif + blueprint menyeluruh. Milestone A, Dashboard, dan blueprint UI/UX page-by-page telah ditetapkan (lihat `## EAMS Page-by-Page UI/UX Blueprint` dan `docs/22-ui-ux-page-map.md`). Implementasi modul mengikuti urutan rekomendasi blueprint.
> **Sumber aturan:** `docs/17-business-specification.md`, `docs/19-decision-log.md`, dan `docs/20-laravel-architecture.md`.
> **Prinsip utama:** **USABILITY > DECORATION** — compact, information-dense, cepat, aksesibel, dan konsisten untuk operasi harian.

## 1. Stack frontend target

- Laravel 12 + PHP `^8.2`
- Livewire 4
- Blade
- Tailwind CSS 4
- Alpine.js yang dibundel oleh Livewire
- Vite 6
- JavaScript biasa untuk camera, QR scanner, `MediaDevices`, canvas, chart, file preview, dan browser API

React, Vue, Inertia SPA, AdminLTE, dan Bootstrap bukan target akhir. Bootstrap 5 tetap tersedia **sementara** sampai semua halaman dan komponen penggantinya selesai serta lolos regression test.

## 2. Strategi coexistence Bootstrap → Tailwind

1. Tailwind memakai prefix wajib `eams:`.
2. Tailwind Preflight tidak dimuat selama Bootstrap legacy masih aktif.
3. Bootstrap dan Bootstrap Icons tetap dibundel Vite.
4. Shell dan komponen baru tidak memakai hook JavaScript Bootstrap.
5. Halaman legacy boleh tetap memakai class Bootstrap sampai dimigrasikan per modul.
6. Bootstrap baru boleh dihapus setelah pencarian penggunaan, browser regression test, build, dan seluruh test suite hijau.
7. Wrapper halaman tidak boleh meninggalkan `transform`/stacking context persisten setelah animasi, karena modal legacy berada di dalam wrapper sedangkan backdrop ditempel ke `<body>`.

Entry point:

- `resources/css/app.css`
- `resources/css/tailwind.css`
- `resources/js/app.js`
- `vite.config.js`

## 3. Design tokens

Token visual berada di `resources/css/tokens.css` dan dijembatani ke Tailwind di `resources/css/tailwind.css`.

Kelompok token:

- canvas, surface, sunk surface, hover surface
- text, muted text, subtle text
- border dan strong border
- primary/accent + soft/contrast
- status success, warning, danger, info, neutral
- spacing, radius, elevation, typography
- sidebar dan layout dimensions
- transition dan reduced-motion behavior

Mode tema: light, dark, system. Preset aksen: indigo, emerald, violet, amber, rose, ocean. Nilai status tidak boleh ditulis ulang secara acak di halaman.

## 4. Semantic status

Presentasi terpusat di `App\Support\Ui\StatusPresentation`.

- Checklist: `OK`, `NOT_OK`, `NA`
- Period: `DONE`, `OPEN`, `LATE`, `FUTURE`, `HOLIDAY`
- Inventory: `GOOD`, `NEED_REPAIR`, `NOT_ACTIVE`
- APAR: `VALID`, `NEAR_EXPIRY`, `EXPIRED`
- Device: `ONLINE`, `OFFLINE`

Pemetaan ini hanya mengatur label, warna, dan ikon. Komponen UI tidak boleh menghitung atau mengubah status domain. Khusus APAR, expiry tidak otomatis mengubah Inventory menjadi `NOT_ACTIVE`.

## 5. Application shell

Shell di `resources/views/layouts/app.blade.php` menyediakan:

- sidebar fixed dan collapsible pada desktop
- drawer + backdrop pada tablet/mobile
- topbar, notification access, theme picker, dan user menu
- breadcrumb
- skip link
- global toast host
- loading indicator
- `wire:navigate` untuk link GET internal yang aman
- lifecycle `livewire:navigate`, `livewire:navigating`, dan `livewire:navigated`
- fallback navigasi klasik untuk link yang belum dimigrasikan

Download, export, file, PDF, form submit, dan link dengan opt-out tidak dipaksa melalui `wire:navigate`.

## 6. Component catalog

Blade primitives berada di `resources/views/components/ui/`:

- Button
- Input
- Textarea
- Select
- Checkbox
- Radio
- Switch
- Badge
- Card
- Table
- Modal
- Drawer
- Toast
- Alert
- Dropdown
- Tabs
- Pagination
- Skeleton
- Empty State
- Confirm Dialog
- File Upload
- Image Preview
- Status Indicator

Breadcrumb berada di `resources/views/components/breadcrumb.blade.php`. Semua komponen harus mendukung atribut tambahan, focus-visible, disabled/loading/error state bila relevan, dark mode, dan ukuran responsif.

Nilai Blade dinamis yang digunakan dalam ekspresi Alpine pada atribut forwarded harus lebih dulu dimasukkan ke state `x-data`; jangan meneruskan literal directive seperti `@js(...)` melalui atribut child component.

## 7. Livewire dan Alpine boundaries

Gunakan Livewire untuk state server dan interaksi data: filter, search, pagination, CRUD, checklist, batch save, dashboard, notification, dan upload bila sesuai.

Gunakan Alpine untuk state UI lokal: drawer, dropdown, modal visibility, theme, toast, filename preview, dan confirm dialog. Jangan membuat duplikasi state Livewire–Alpine tanpa kebutuhan. Jangan memanggil `Alpine.start()`; runtime Alpine tunggal berasal dari bundle Livewire.

Komponen Livewire harus kecil berdasarkan tanggung jawab. Business rule tetap berada pada Action/Service/domain support yang sudah ada, bukan dipindah ke view.

### Dashboard pattern yang telah diterapkan

- `DashboardController` tetap menghitung snapshot KPI dengan canonical period engine existing.
- `App\Livewire\Dashboard\Overview` menerima property snapshot terkunci; tidak query dan tidak menghitung ulang KPI.
- View menggunakan `x-ui.card`, `x-ui.status-indicator`, `x-ui.button`, `x-ui.alert`, dan `x-ui.empty-state`.
- Seluruh utility baru memakai prefix `eams:`; view Dashboard tidak memakai hook/class Bootstrap.
- Alpine hanya mengelola disclosure penjelasan status lokal.
- Empat link GET internal memakai `wire:navigate`.

Pattern ini menjadi acuan presentation boundary, bukan alasan memindahkan business rule ke Livewire.

## 8. Accessibility dan responsive behavior

- Landmark, label, `aria-current`, `aria-expanded`, dan dialog role wajib benar.
- Semua kontrol dapat digunakan dengan keyboard.
- Focus ring tidak boleh dihilangkan.
- Drawer/modal menutup dengan Escape dan tombol close.
- Target sentuh minimum sekitar 36–44 px sesuai kepadatan konteks.
- Tabel lebar memakai horizontal overflow; kolom penting/sticky digunakan bila membantu operasi.
- Hormati `prefers-reduced-motion`.
- Tidak boleh ada horizontal overflow pada viewport 390 px setelah transisi responsif selesai.

## 9. Migration status

| Area | Status | Catatan |
|---|---|---|
| Livewire 4 + Vite | Implemented | ESM manual bundle; Alpine tunggal |
| Tailwind 4 | Implemented | Prefix `eams:`, tanpa Preflight |
| Tokens/theme | Implemented | Light/dark/system + enam aksen |
| Application shell | Implemented | Sidebar, topbar, breadcrumb, navigation |
| Component primitives | Implemented | Catalog Milestone A tersedia |
| Browser QA automation | Implemented | Chromium 5/5; console/hydration bersih |
| Bootstrap legacy | Remaining, guarded | Modal regression lulus; dipertahankan untuk halaman legacy |
| Dashboard | Implemented | Livewire presentation boundary + Tailwind + Alpine + `wire:navigate` |
| Inventory | Next | Milestone aktif berikutnya |
| Checklist standard/grid | Pending | Setelah Inventory |
| Modul lain | Pending | Bertahap |
| Bootstrap removal | Last | Hanya setelah replacement lengkap |

## 10. Browser QA gate

Gate Dashboard pada commit `4bdea862` telah lulus:

- desktop shell, sidebar collapse persistence, `wire:navigate`, back/forward;
- Dashboard ter-hydrate sebagai Livewire component setelah navigasi;
- empat KPI dan empat quick link tampil, serta Alpine disclosure berfungsi;
- mobile drawer/backdrop, Escape, theme persistence, dan viewport 390 px tanpa page-level overflow setelah transisi responsif stabil;
- dropdown, modal, drawer, confirm dialog, file upload, image preview fallback, serta toast;
- console error, Alpine expression error, page error, dan Livewire hydration error kosong;
- modal Bootstrap legacy di `/users` terbukti open, memiliki Bootstrap instance, close, dan memancarkan `hidden.bs.modal`;
- `php artisan test`: **194 passed / 634 assertions / exit 0**;
- `npm run build`: **exit 0**;
- Playwright Chromium: **5 passed / exit 0**.

Assertion tetap fail-fast dan error tidak di-whitelist. Pemeriksaan overflow melakukan polling terbatas agar tidak membaca margin shell di tengah transisi desktop-ke-mobile; overflow yang menetap tetap menggagalkan test.

## 11. Known issues / batas saat ini

- Banyak halaman modul masih memakai grid, form, table, modal, dropdown, alert, badge, dan pagination Bootstrap.
- Browser-specific JavaScript pada Print Center, camera/QR, dan preview tidak boleh dihapus sampai ada pengganti teruji.
- Komponen baru tersedia tetapi adopsinya dilakukan per halaman; keberadaan komponen bukan bukti seluruh modul sudah termigrasi.
- Upload Inventory masih memerlukan hardening terpisah agar file invalid divalidasi sebelum mutasi database.

## 12. Definition of done per halaman

Sebuah halaman dianggap termigrasi jika:

1. Visual dan interaksi utama memakai komponen Tailwind/Livewire yang disepakati.
2. Tidak mengubah business rule, authorization, route/API, QR payload, atau storage contract.
3. Loading, empty, error, disabled, dan success state tersedia.
4. Keyboard, mobile, overflow, back/forward, dan console diperiksa.
5. Test feature/component/browser hijau.
6. Bootstrap usage halaman tersebut sudah tidak diperlukan atau tersisa dengan alasan terdokumentasi.

---

## EAMS Page-by-Page UI/UX Blueprint

> **Status:** DESIGN BLUEPRINT (Design-First Phase). Merinci layout, hierarchy, state, dan interaksi tiap halaman sebelum implementasi modul berikutnya.
> **Sumber:** docs/17 (BR-01..BR-47), docs/19 (Q-001..Q-023), docs/20 (arsitektur), docs/22 (page map).
> **Aturan main:** blueprint tidak mengubah business rule, authorization, route kontrak (QR legacy), atau storage. Tidak ada module implementation baru di phase ini.

### BP.0 Pola universal (berlaku semua halaman)

Semua halaman memakai design language yang sama agar konsisten:

1. **Page header**: eyebrow (grup menu, uppercase 11px brand) → H1 (bold ink) → lead 1 kalimat (muted) → actions di kanan. Implementasi: `PageHeader` component Tailwind (baru; menggantikan `<x-page-header>` Bootstrap pada halaman legacy).
2. **Empat state wajib**: loading (Skeleton), empty (Empty State + CTA), error (Alert danger inline), success (Toast). Halaman tanpa data tetap harus punya empty state bermakna + CTA.
3. **Filter**: `Filter Bar` component (BARU): search (debounce 300ms), dropdown filter, chip filter aktif + tombol reset; state di URL (shareable).
4. **Tabel**: `x-ui.table`; aksi baris di kanan; row utama bisa diklik ke detail; horizontal scroll di layar sempit; list padat berubah jadi card stack <640px.
5. **Form**: dibagi per section Card; grid 2 kolom desktop / 1 kolom mobile; action bar sticky di bawah; error inline per field via `x-ui.input/error`.
6. **Status**: hanya lewat `x-ui.status-indicator` / `x-ui.badge` dari `StatusPresentation` — view tidak pernah menghitung status.
7. **Modal/drawer**: `x-ui.modal` / `x-ui.drawer` (Alpine); konfirmasi destruktif via `x-ui.confirm-dialog` (event `eams-confirm` / `eams-confirmed`).
8. **Toast**: `window.eamsToast` + flash session mapping di shell.
9. **Responsif**: tidak ada horizontal overflow pada 390px; touch target ≥ 36px; `prefers-reduced-motion` dihormati.
10. **Navigasi**: `wire:navigate` untuk GET internal; form/PDF/download klasik (docs/21 §5).

### BP.1 Authentication — Login

- Layout: centered card di atas canvas, tanpa shell; brand di atas form.
- Komponen: Card, Input (username/email + password), Button primary full-width, Alert error.
- State: error kredensial inline di atas form (tanpa toast); loading pada tombol saat submit.
- Aksesibilitas: label eksplisit, autofokus username, tombol mata pada password.

### BP.2 Home (Beranda)

- Layout: header sambutan + 2 kolom (task personal 2/3, ringkasan 1/3).
- Isi: daftar checklist pending milik user (per periode, progress clamp 0–100), link cepat ke Checklist Fill.
- Komponen: Card, Status Indicator (periode), Badge (due), Progress bar (BARU, primitif kecil).
- State: empty = "tidak ada tugas pending" + CTA ke Dashboard.

### BP.3 Dashboard Compliance (implemented — pattern reference)

- Sudah diimplementasi (`App\Livewire\Dashboard\Overview`): header + 4 KPI + panel status kondisi + tindakan cepat + disclosure penjelasan.
- Menjadi acuan presentation boundary: controller menghitung, Livewire hanya merender.

### BP.4 Inventory — List

- Layout: PageHeader (eyebrow Compliance) + Filter Bar + toolbar jumlah + Table + Pagination.
- Kolom: Inventory (code mono + tipe), Item & kategori, Lokasi (area + specific_area), Status (GOOD/NEED_REPAIR/NOT_ACTIVE), PIC (maks 2, avatar inisial), Masa berlaku (merah bila expired), QR indikator, Aksi (detail/edit/hapus).
- Filter: search asset_code (debounce), area, status — state URL.
- Aksi hapus: confirm-dialog; pagination 20/halaman Livewire.
- State: empty tanpa filter = "Belum ada compliance inventory" + CTA tambah; empty dengan filter = "tidak ditemukan" + reset.

### BP.5 Inventory — Detail

- Layout: header (code mono + item · kategori) + 4 stat mini (kondisi, area, qty, aktif) + grid 2 kolom: kiri identitas (DL grid) & penempatan & PIC & catatan; kanan QR + foto.
- QR: sumber `files.show` (disk `qr`), caption "URL kompatibel legacy"; expired → badge merah di field tanggal; PDF button (access-compliance-pdf) & Edit (manage-inventory) di header.
- State: foto/QR kosong = placeholder ikon; bukan error.

### BP.6 Inventory — Create/Edit

- Tiga section: Identitas (kategori, item, asset_code optional + hint auto-generate BR-19, tipe), Penempatan & kondisi (area, specific_area Q-019, status, qty, expired khusus APAR tampil-sembunyi via Alpine), PIC & dokumentasi (multi-select maks 2 Q-007, foto ≤5MB Q-022/Q-026, catatan).
- Edit (BR-45): kategori/item/area/nomor tampil sebagai read-only definition list — tidak ada input terkunci palsu.
- Action bar: Batal (wire:navigate) + Simpan.

### BP.7 Checklist Fill — STANDARD (workflow utama)

Struktur satu layar kerja, atas ke bawah:

1. **Asset bar**: code mono + nama item + area/specific_area + status periode aktif (`status-indicator`).
2. **Period strip** (primitif BARU `PeriodStrip`): navigasi ‹ bulan ›; chip periode (daily = tanggal; weekly = W1–W4 irisan bulan BR-02; monthly = bulan). Chip berwarna kanonik DONE/OPEN/LATE/FUTURE/HOLIDAY; chip non-editable disabled + tooltip alasan (`done`, `future`, `offday` BR-07/08, `slot` BR-14).
3. **Progress bar** isian n/m pertanyaan.
4. **Daftar pertanyaan** (dari Checklist Master aktif, di-resolve per `code` Q-015): tiap baris = pertanyaan + trio radio OK / NOT_OK / NA (NA hanya bila `allow_na` BR-12). NOT_OK membuka panel remark + foto (salah satu wajib, BR-10) + preview foto.
5. **Action bar sticky**: Simpan (full submit). Submit ulang periode sama ditolak (BR-09) dengan CTA "lihat hasil".
- Client validation mirror server; error per-pertanyaan di-highlight; `check_date` mengikuti BR-17 (tidak tampil sebagai input).

### BP.8 Grid Checklist (workflow utama)

- Layout: header pilih item type + bulan; toolbar [Mark all] [Clear] [Legend]; matriks full-width.
- **Matriks**: baris = inventory (code + area), kolom = periode sesuai frekuensi (daily 1–31 dengan tint offday; weekly W1–W4; monthly 12 bulan).
- **Sel**: kosong (dashed), OK (hijau), NOT_OK (merah + ikon kamera bila ada foto), NA (abu). Klik sel → popover mini: radio OK/NOT_OK/NA (bila allow_na) + remark + simpan instan (Livewire).
- **Aturan per grid** (tampil sebagai konfigurasi, bukan hard-code id — Q-015): CCTV sel NA terkunci (409 → toast arahkan ke detail, BR-47); mark-all skip existing (BR-15) kecuali HEAT `ok`-overwrite dengan checkbox konfirmasi eksplisit (Q-024); clear = hard delete (BR-16).
- **Lock overlay** per kolom/baris: future (BR-04), offday (BR-07/08), done (BR-09) — sel disabled + tooltip alasan.
- **Toilet** (BR-14): 3 sub-baris PG/SI/SO per inventory; slot wajib.
- State: loading = skeleton matriks; aksi massal menampilkan progress + toast hasil ("N sel diisi, M dilewati").

### BP.9 Calendar

- Grid bulanan + agenda samping; sel berwarna kanonik (DONE/OPEN/LATE/FUTURE/HOLIDAY) via period engine; hari libur merah (Minggu selalu; Sabtu ≥ 2026-04-01 Q-005).
- Klik sel editable → Checklist Fill; klik event → detail. Mini kalender navigasi bulan.

### BP.10 Progress

- Tabel per PIC/inventory/periode dengan progress bar dan status; aksi "Remind" (hanya write access, `data-can-remind`).
- Filter: periode (month nav), PIC. Empty state untuk periode tanpa assignment.

### BP.11 Evidence

- Kartu temuan `not_ok` (foto + remark) dengan badge follow-up open/monitoring/closed; modal detail + form update follow-up (note + tanggal).
- Filter: status follow-up, periode, area. Empty state ramah (temuan = hal baik).

### BP.12 Ranking

- Leaderboard bulanan: nama, ontime, late, skor (ontime×10 + late×3 BR-18); nav ‹ bulan › ; bulan depan disabled.
- Baris user sendiri di-highlight.

### BP.13 QR Center

- Kamera scanner (MediaDevices via JS biasa, bukan Livewire) + hasil lookup → detail inventory.
- Fallback input manual asset_code; hasil menampilkan status & PIC.

### BP.14 Print Center

- Tabs: Per Item / Batch. Form pilih kategori → item type → inventory → periode; tombol buka preview/PDF di tab baru (navigasi klasik).
- Batch = form kolektif (multi inventory). Preview menampilkan frame dokumen.

### BP.15 Device Monitoring

- Table live: hostname, asset code, status ONLINE/OFFLINE (threshold 10 menit Q-012), last seen, health label (Sehat/Waspada/Kritis BR-33).
- Auto-refresh fragment via JS biasa; modal remote command. Filter status + search.

### BP.16 Reports

- Filter bertingkat dependen: kategori → item type → inventory → periode.
- Output: grid rekap (daily/weekly/monthly) + tombol export/PDF (kanal lama, buka tab baru).
- Sel mengikuti warna status kanonik.

### BP.17 Users & Roles/Permissions

- Users: table (nama, username, role, permission, status, last login) + form create/edit (data, password, role, permission) + **page-access matrix** (checkbox per grup menu; grup Admin tidak diberikan).
- Roles & permissions = bagian form Users (matrix) + default pages per role; tiga lapis BR-41.
- Anti self-deactivate/self-demote: guard di server, UI men-disable opsi tersebut untuk akun sendiri.

### BP.18 Master Data (6 halaman)

- Satu template untuk semua: PageHeader + table + modal add/edit + confirm delete + pagination.
- Checklist Master punya drill-down 3 level (kategori → item type → pertanyaan) sebagai halaman tersendiri (bukan modal) karena kedalaman kontennya; form pertanyaan + frekuensi + allow_na.

### BP.19 Settings

- Empat section Card: User (password sendiri — route self-service), Company, Email, WhatsApp; secret tidak pernah di-echo kembali (flag saved).
- Akses edit dibatasi gate manage-settings; read-only user melihat disabled.

### BP.20 Utility (Boiler/PDAM Water/PDAM Boiler/IPAL)

- Grid harian per bulan: baris = field pengukuran, kolom = tanggal; tint offday; sel input numerik + autosave.
- Month nav ‹ › ; export PDF periode; holiday fill konsisten BR-07.

### BP.21 EMS / GHG

- Matriks bulan × tahun per kategori (water/electric/stationary/mobile) + panel ringkasan & faktor emisi; autosave Alpine; boot data via JSON terkunci.

### BP.22 FDM

- Editor retailer + FTE bulanan (Alpine) + tabel rekap; pola sama dengan EMS (grid editor ringan).

### BP.23 Patrol

- List sesi + peta rute (JS biasa) + editor checkpoint (drag) + halaman sesi (timeline scan + foto).
- Kamera/geolokasi tetap browser API (docs/20 §24).

### BP.24 Thermal Imaging

- List laporan + form multi-baris (measurement repeater) + multi foto + halaman print khusus.
- Default inspector = user login; facility default dari settings.

### BP.25 Kuesioner

- Admin: list + builder pertanyaan (section, tipe jawaban, opsi, wajib) + response detail + analytics.
- Publik: fill + thanks TANPA shell (layout terpisah, tanpa sidebar/topbar).

### BP.26 Admin (Audit Logs / Login Sessions / Backups)

- Audit: table + filter q/action/status + badge aksi.
- Sessions: table per user dengan revoke.
- Backups: table + upload + tombol aksi (restore/download) dengan confirm-dialog; badge tipe backup.

### BP.27 Komponen yang perlu dibuat (gap → action)

| Komponen baru | Dipakai oleh | Catatan |
|---|---|---|
| `PageHeader` (Tailwind) | semua halaman legacy | menggantikan `<x-page-header>` Bootstrap |
| `FilterBar` | list modul (Inventory, Evidence, Users, Audit, …) | state URL + chip aktif |
| `StatCard` | Dashboard (refactor kecil), Home, Detail | angka besar + ikon + hint |
| `PeriodStrip` + `PeriodChip` | Checklist Fill, Calendar, Grid, Progress | render status dari period engine |
| `MonthNav` | Grid, Calendar, Ranking, Utility, EMS | ‹ bulan › konsisten |
| `DataGrid` interaktif | Grid Checklist, Utility, EMS | sel toggle/input + lock overlay + mass action |
| `Progress bar` | Home, Checklist, Progress | kecil, aksesibel |
| `Timeline` | Patrol session, Evidence follow-up, checklist histories | vertikal sederhana |
| `Repeater` | Thermal, Kuesioner builder | baris dinamis + remove |
| `CascadingSelect` | Report, Print Center | dependent dropdown |
| Print utilities | Print Center, PDF pages | `eams:print:*` + stylesheet cetak terpisah |

Komponen yang sudah tersedia (tidak perlu dibuat): Button, Input, Textarea, Select, Checkbox, Radio, Switch, Badge, Card, Table, Modal, Drawer, Toast, Alert, Dropdown, Tabs, Pagination, Skeleton, Empty State, Confirm Dialog, File Upload, Image Preview, Status Indicator, Breadcrumb.

### BP.28 Rekomendasi urutan implementasi

1. **Fondasi komponen baru** (BP.27) — tanpa menyentuh modul.
2. **Checklist Fill (STANDARD)** → **Grid Checklist** (workflow inti EAMS).
3. **Calendar, Progress, Evidence, Ranking** (famili monitoring, memakai komponen yang sama).
4. **Inventory Detail v2** (integrasi histori periode + QR).
5. **Print Center + Reports**.
6. **Master Data** (6 halaman, satu template — cepat).
7. **Users, Settings, Admin**.
8. **Utility, EMS, FDM, Patrol, Thermal, Kuesioner**.
9. **Bootstrap removal** (terakhir, sesuai docs/20 §20).

Setiap langkah tunduk pada definition of done docs/21 §12 (visual + state + aksesibilitas + test + browser QA hijau).
