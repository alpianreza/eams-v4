# 20 — EAMS Laravel Architecture

> **Status:** IMPLEMENTED — Laravel 12 modular monolith aktif dan dimigrasikan bertahap dari CI4 dengan business behavior tetap kompatibel.
> **Runtime:** PHP `^8.2`, MySQL 8, Blade, Livewire 4, Tailwind CSS 4, Alpine.js, dan Vite 6.
> **Dasar mengikat:** Business Specification (`docs/17-business-specification.md`) + Human Decisions (`docs/19-decision-log.md`) + Production DB Evidence (`docs/18-production-database-reconciliation.md`, `docs/03`).
> **Batas:** frontend tidak boleh mengubah business rule, authorization, route/API, storage, QR payload, importer, atau backend teruji.

---

## 1. Architecture principles

1. Business-first dan behavior-compatible.
2. Laravel-native, modular monolith, serta sederhana dan dapat diuji.
3. Jangan membuat Repository/Service generik untuk CRUD sederhana.
4. Konfigurasi threshold, storage, tanggal efektif, dan integrasi berada di config/env.
5. Kontrak eksternal `/api/agent/*` dan QR legacy harus tetap kompatibel.
6. Refactor struktur boleh dilakukan bila hasil bisnisnya tetap sama dan dilindungi test.

## 2. Runtime dan dependency

- Laravel 12.x, dikunci di `composer.lock`.
- PHP `^8.2`.
- Livewire 4.4.2 untuk stateful server UI.
- Tailwind CSS 4.3.3 dan `@tailwindcss/vite` 4.3.3.
- Vite 6.0.11.
- Alpine menggunakan runtime tunggal dari bundle Livewire.
- Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 masih tersedia untuk halaman legacy.
- Versi aktual selalu mengikuti lockfile; jangan mengarang atau mengedit lockfile manual.

## 3. Database strategy

- Target Laravel memakai database baru yang clean; legacy dibaca melalui proses import.
- Runtime produksi target MySQL 8. Driver `mysql` tetap kompatibel untuk sumber MariaDB legacy.
- Charset `utf8mb4`, InnoDB, dan konvensi ID/FK konsisten.
- Hindari cascade yang dapat menghapus histori checklist.
- Schema dibuat melalui Laravel migrations; data lama melalui importer idempoten.

## 4. Legacy import dan reconciliation

- Alur: `EAMS CI4 DB → Legacy Import → EAMS Laravel DB`.
- Mapping kolom eksplisit: `CARRY`, `TRANSFORM`, `DROP`, atau `REVIEW`.
- `checked_by` legacy menjadi `checked_by_user_id` dan `checked_by_name` snapshot.
- PIC legacy menjadi pivot maksimal dua orang, setara, tanpa primary/secondary.
- `asset_code` dipertahankan persis; konflik dilaporkan, bukan diubah diam-diam.
- Nilai anomali dilaporkan oleh importer/reconciler.
- Import produksi wajib melalui dry-run, import, lalu `eams:reconcile`.

## 5. Authentication dan authorization

- Session authentication mempertahankan login username atau email, bcrypt, throttle, session regeneration, audit, dan login session.
- Otorisasi menggabungkan role, permission `read|write`, dan `page_access`.
- User read-only diblokir dari mutasi kecuali self-service yang memang diputuskan boleh.
- Policy/Gate digunakan untuk akses fitur spesifik, termasuk PDF Compliance.
- Menu hanya menyembunyikan item; route tetap wajib dilindungi authorization server-side.

## 6. Domain/module structure

Backend saat ini dipisahkan secara praktis melalui:

- `app/Http/Controllers/*`
- `app/Actions/*`
- `app/Models/*`
- `app/Services/*`
- `app/Support/*`
- `app/Livewire/*`

Domain utama: Auth, User/Admin, Master Data, Compliance Inventory, Checklist, Monitoring, Report, Notification, Calendar, Thermal, Questionnaire, Utility, EMS, FDM, IT Asset, IT Device, Patrol, Import, dan Storage.

Komponen Livewire harus kecil per tanggung jawab; jangan membuat giant component.

## 7. Models dan data rules

- Eloquent relations didefinisikan eksplisit.
- Inventory status: `good | need_repair | not_active`.
- Checklist status: `ok | not_ok | na`; `na` hanya bila `allow_na=true`.
- PIC maksimal dua, setara, tanpa primary/secondary.
- User checker disimpan sebagai ID dan snapshot nama.
- Perilaku item type di-resolve berdasarkan business `code`, bukan ID hard-coded.

## 8. Controller, Action, Service, dan Form Request

- Controller mengorkestrasi request dan response.
- Action digunakan hanya untuk operasi bisnis non-trivial.
- Service digunakan untuk integrasi/kompleksitas nyata seperti importer, notification, QR, PDF, storage, dan backup.
- Validasi request dan authorization tetap server-side.
- Business calculation yang sudah teruji tidak dipindah ke Blade atau JavaScript.

## 9. Checklist canonical period engine

`App\Support\Checklist\ChecklistPeriod` adalah single source of truth untuk:

- daily `YYYY-MM-DD`
- weekly irisan bulan W1=1–7, W2=8–14, W3=15–21, W4=22–akhir
- monthly `YYYY-MM`
- status `DONE`, `OPEN`, `LATE`, `FUTURE`, `HOLIDAY`
- editable/future/late/offday

Minggu selalu libur. Sabtu menjadi libur mulai 2026-04-01. Holiday table ikut diperhitungkan. Dashboard, progress, checklist standard, dan grid tidak boleh menghitung period dengan algoritme lain.

## 10. Checklist modes

- STANDARD: `NOT_OK` wajib remark atau foto.
- GRID: boleh bypass evidence sesuai keputusan Q-013.
- Mark-all mengisi sel kosong dan tidak menimpa nilai existing.
- Clear menghapus sel yang dimaksud.
- Koreksi log menghasilkan audit trail.
- Toilet mempertahankan tiga slot `PG`, `SI`, `SO`.

## 11. Inventory dan QR

- Inventory status tidak dicampur dengan checklist status.
- Expiry terutama untuk APAR dan tidak otomatis membuat inventory `NOT_ACTIVE`.
- QR payload/route legacy dipertahankan: `compliance/inventory/detail/{id}`.
- QR image boleh diregenerate, tetapi URL dan asset mapping tetap kompatibel.
- File upload mengikuti kategori storage terpusat dan aturan validasi image maksimal 5 MB.

## 12. File storage

Kategori storage: `inventory`, `checklist`, `qr`, dan `attachments`.

Root/disk dapat diarahkan ke local disk, custom path, atau network share melalui config/env. File private disajikan melalui controller terautentikasi; path traversal dan kategori tidak dikenal ditolak.

## 13. Notifications

- In-app notification disimpan di tabel `notifications`.
- Email dan WhatsApp mengikuti settings/env dan dapat dinonaktifkan.
- Dedupe key menjaga idempotensi reminder.
- Notification indicator Livewire hanya menghitung unread milik user aktif.

## 14. Device, API, dan browser API

- Device online bila heartbeat terakhir ≤600 detik.
- `/api/agent/*` mempertahankan kontrak legacy dan token device.
- Keputusan GET legacy yang masih diperlukan dipertahankan.
- Camera, QR scanner, `MediaDevices`, canvas, chart, dan file preview tetap memakai JavaScript/browser API biasa bila Livewire bukan alat yang tepat.

## 15. PDF, export, dan print

- PDF memakai Dompdf.
- Authorization tetap permission-based.
- Print khusus item type di-resolve berdasarkan `code`.
- Download, PDF, export, dan file response tidak dipaksa memakai `wire:navigate`.

## 16. Audit trail dan observability

- Laravel daily log untuk error teknis.
- Audit log dan login session untuk kejadian keamanan/operasional.
- `checklist_log_histories` mencatat perubahan status/remark/photo.
- Failed jobs dan scheduler perlu dimonitor di deployment.

## 17. Deployment

- PHP 8.2, MySQL 8, Node 22 untuk build, dan web server sesuai lingkungan.
- Docker web default `:8080`; `php artisan serve` default `:8000`.
- Queue worker dan scheduler dijalankan sebagai service/Task Scheduler.
- Cutover dilakukan setelah import dan reconciliation data produksi lulus.

## 18. Backup dan rollback

- Database, files, dan full backup dengan retensi 30 hari.
- Importer dapat dijalankan ulang dengan aman sesuai dokumentasi importer.
- Legacy tetap read-only sebagai sumber sampai cutover diterima.
- Snapshot dibuat sebelum cutover; rollback tidak boleh menulis balik secara serampangan ke DB legacy.

## 19. Frontend target

Target final:

- Blade
- Livewire 4
- Tailwind CSS 4
- Alpine.js
- Vite
- JavaScript biasa untuk browser API

Tidak menggunakan React, Vue, Inertia SPA, AdminLTE, atau Bootstrap sebagai target final.

## 20. Tailwind/Bootstrap coexistence

- Tailwind wajib memakai prefix `eams:`.
- Preflight tidak dimuat selama coexistence.
- Bootstrap JS/CSS tetap dibundel untuk halaman legacy.
- Shell dan komponen baru tidak menggunakan hook Bootstrap.
- Bootstrap baru boleh dihapus setelah seluruh replacement bekerja, pencarian penggunaan bersih, browser QA hijau, dan test suite tidak regression.
- Wrapper halaman yang dapat memuat modal legacy tidak boleh mempertahankan `transform` atau stacking context dari animation fill mode setelah transisi selesai.

## 21. Application shell

Shell menyediakan topbar, sidebar fixed/collapsible, drawer mobile, breadcrumb, notification, theme picker, user menu, toast host, skip link, dan loading indicator.

Link GET internal yang aman memakai `wire:navigate`. Lifecycle `livewire:navigate`, `livewire:navigating`, dan `livewire:navigated` menangani transisi tanpa menambah SPA framework.

## 22. Component system

Reusable Blade components berada di `resources/views/components/ui/`:

Button, Input, Textarea, Select, Checkbox, Radio, Switch, Badge, Card, Table, Modal, Drawer, Toast, Alert, Dropdown, Tabs, Pagination, Skeleton, Empty State, Confirm Dialog, File Upload, Image Preview, dan Status Indicator.

Detail token, states, accessibility, migration matrix, dan QA gate ada di `docs/21-ui-ux-design-system.md`.

Nilai Blade dinamis yang dipakai oleh ekspresi Alpine pada atribut forwarded harus dikompilasi pada root component (misalnya ke state `x-data`) sebelum atribut diteruskan; literal directive Blade tidak boleh sampai ke browser.

## 23. Semantic status presentation

`App\Support\Ui\StatusPresentation` hanya memetakan label/tone/ikon untuk:

- Checklist: `OK`, `NOT_OK`, `NA`
- Period: `DONE`, `OPEN`, `LATE`, `FUTURE`, `HOLIDAY`
- Inventory: `GOOD`, `NEED_REPAIR`, `NOT_ACTIVE`
- APAR: `VALID`, `NEAR_EXPIRY`, `EXPIRED`
- Device: `ONLINE`, `OFFLINE`

Komponen presentasi tidak boleh menghitung status domain.

## 24. Livewire/Alpine boundary

- Livewire: state server, query/filter/search/pagination, CRUD, checklist, grid, dashboard, notification, dan upload bila sesuai.
- Alpine: state UI lokal seperti drawer, dropdown, modal, theme, toast, confirm, dan preview.
- Jangan memanggil `Alpine.start()`; Alpine berasal dari Livewire.
- Hindari duplikasi state Livewire–Alpine tanpa kebutuhan.

## 25. Dashboard architecture — implemented

Dashboard mempertahankan data dan aturan backend existing untuk:

- inventory aktif
- checklist `OPEN`
- checklist `LATE`
- expiry
- kondisi `GOOD`, `NEED_REPAIR`, `NOT_ACTIVE`

Canonical period engine tetap menjadi sumber status. `DashboardController` tetap melakukan query dan menghitung seluruh KPI; route, authorization, model, dan business rule tidak diubah.

`App\Livewire\Dashboard\Overview` hanya menjadi boundary presentasi kecil: menerima snapshot controller sebagai property terkunci, menormalisasi shape input, tidak menjalankan query, dan tidak menghitung ulang KPI. View `livewire.dashboard.overview` menggunakan Tailwind ber-prefix `eams:`, komponen design system, Alpine hanya untuk disclosure lokal, serta `wire:navigate` pada link GET internal. Loading/empty/actionable states tetap presentation-only.

## 26. Testing strategy

Gate minimum:

1. `php artisan test`
2. `npm run build`
3. Livewire component test untuk state/query penting
4. Browser QA untuk shell, komponen, `wire:navigate`, console/hydration, responsive overflow, dan Bootstrap legacy
5. Authorization test pada route sensitif

Test bisnis utama meliputi period engine, status/evidence, PIC, QR, device threshold, importer/reconciliation, authorization, PDF, dan storage.

## 27. Browser QA

Browser QA otomatis memakai Playwright 1.55.0 dan Chromium. Gate Dashboard pada commit `4bdea862` lulus dengan:

- lima dari lima skenario browser lulus;
- desktop shell, collapse persistence, `wire:navigate`, dan back/forward;
- Dashboard ter-hydrate sebagai Livewire component setelah `wire:navigate`;
- empat KPI, empat quick link, dan Alpine disclosure Dashboard berfungsi;
- mobile drawer, Escape, theme persistence, dan viewport 390 px tanpa page-level horizontal overflow setelah transisi responsif stabil;
- dropdown, modal, drawer, confirm dialog, file upload, image preview, dan toast;
- tidak ada console error, Alpine expression error, page error, atau hydration error;
- modal Bootstrap legacy `/users#roleModal` tetap dapat dibuka, memiliki instance Bootstrap, lalu ditutup sampai event `hidden.bs.modal`;
- `php artisan test`: 194 test / 634 assertion / exit 0;
- `npm run build`: exit 0;
- browser QA: 5 passed / exit 0.

Pemeriksaan overflow memakai polling terbatas hingga layout selesai bertransisi; overflow yang menetap tetap menggagalkan gate. Smoke test manual tetap diperlukan sebelum deployment produksi.

## 28. Phase 2B migration status

| Area | Status |
|---|---|
| Livewire/Tailwind/Vite foundation | Implemented |
| Tokens/theme | Implemented |
| Application shell | Implemented |
| Reusable components | Implemented |
| Browser QA automation | Implemented — Dashboard gate 5/5 passed |
| Dashboard | Implemented |
| Inventory | Next milestone |
| Checklist STANDARD/GRID | Pending |
| Other modules | Pending |
| Bootstrap removal | Last |

## 29. Known issues

- Halaman legacy masih memakai Bootstrap dan dimigrasikan per modul.
- Browser-specific print/camera/QR behavior harus dipertahankan sampai pengganti teruji.
- Hardening upload Inventory agar invalid file ditolak sebelum mutasi DB masih merupakan pekerjaan terpisah.
- Validasi data produksi tetap membutuhkan dry-run/import/reconciliation terhadap database XAMPP pengguna.

## 30. Traceability dan definition of done

Setiap perubahan tunduk pada `docs/17` dan `docs/19`. Sebuah milestone selesai hanya bila:

1. Backend/business rule tetap kompatibel.
2. Test PHP/Livewire hijau.
3. Vite build hijau.
4. Browser QA hijau tanpa regression Bootstrap legacy.
5. Responsive, keyboard, loading, empty, error, dan console state diperiksa.
6. Dokumentasi repo dan Notion disinkronkan.

> **Rantai:** Legacy → Audit → Production Verification → Human Decision → Business Specification → Architecture → Implementation → Automated QA → Deployment.
