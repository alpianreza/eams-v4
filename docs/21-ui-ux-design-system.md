# 21 — EAMS UI/UX Design System

> **Status:** Phase 2B aktif. Milestone A (foundation + application shell + reusable primitives) telah diimplementasikan dan lulus browser gate 4/4; Dashboard menjadi milestone aktif berikutnya.
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

## 8. Accessibility dan responsive behavior

- Landmark, label, `aria-current`, `aria-expanded`, dan dialog role wajib benar.
- Semua kontrol dapat digunakan dengan keyboard.
- Focus ring tidak boleh dihilangkan.
- Drawer/modal menutup dengan Escape dan tombol close.
- Target sentuh minimum sekitar 36–44 px sesuai kepadatan konteks.
- Tabel lebar memakai horizontal overflow; kolom penting/sticky digunakan bila membantu operasi.
- Hormati `prefers-reduced-motion`.
- Tidak boleh ada horizontal overflow pada viewport 390 px.

## 9. Migration status

| Area | Status | Catatan |
|---|---|---|
| Livewire 4 + Vite | Implemented | ESM manual bundle; Alpine tunggal |
| Tailwind 4 | Implemented | Prefix `eams:`, tanpa Preflight |
| Tokens/theme | Implemented | Light/dark/system + enam aksen |
| Application shell | Implemented | Sidebar, topbar, breadcrumb, navigation |
| Component primitives | Implemented | Catalog Milestone A tersedia |
| Browser QA automation | Implemented | Chromium 4/4; console/hydration bersih |
| Bootstrap legacy | Remaining, guarded | Modal regression lulus; dipertahankan untuk halaman legacy |
| Dashboard | Active | Migrasi pertama setelah Milestone A hijau |
| Inventory | Pending | Setelah Dashboard |
| Checklist standard/grid | Pending | Setelah Inventory |
| Modul lain | Pending | Bertahap |
| Bootstrap removal | Last | Hanya setelah replacement lengkap |

## 10. Browser QA gate

Gate Milestone A pada commit `0f401c61` telah lulus:

- desktop shell, sidebar collapse persistence, `wire:navigate`, back/forward;
- mobile drawer/backdrop, Escape, theme persistence, dan viewport 390 px tanpa page-level overflow;
- dropdown, modal, drawer, confirm dialog, file upload, image preview fallback, serta toast;
- console error, Alpine expression error, page error, dan Livewire hydration error kosong;
- satu modal Bootstrap legacy di `/users` terbukti open, memiliki Bootstrap instance, close, dan memancarkan `hidden.bs.modal`;
- `php artisan test`: **192 passed / 601 assertions / exit 0**;
- `npm run build`: **exit 0**;
- Playwright Chromium: **4 passed / exit 0**.

Browser assertion tetap fail-fast pada setiap checkpoint interaksi; error tidak di-whitelist atau diabaikan.

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
