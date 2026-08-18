# 20 — EAMS Laravel Architecture (DRAFT)

> **Status:** DRAFT DESIGN — **belum ada implementasi**. Dokumen ini adalah rancangan arsitektur, bukan kode. Tidak ada Laravel project/controller/model/migration/Blade/API/service yang dibuat pada fase ini.
> **Dasar desain (wajib diikuti):** Business Specification (`docs/17`) + Human Decisions (`docs/19`) + Production DB Evidence (`docs/18`, `docs/03`).
> **Prinsip pengikat:** ini **bukan** "terjemahkan CI4 controller → Laravel controller satu per satu". Ini arsitektur Laravel yang clean dengan **business behavior tetap kompatibel**.
> **Review gate:** dokumen ini menunggu persetujuan Project Owner **sebelum** implementasi Laravel boleh dimulai.

---

## 1. Architecture Principles

1. **Business-first, bukan framework-first.** Setiap keputusan arsitektur ditelusuri ke Business Rule (BR) / Decision (Q) / Production Fact. Tidak ada fitur tanpa asal.
2. **Behavior-compatible, structure-clean.** Perilaku bisnis legacy dipertahankan; struktur internal dibersihkan (lihat Q-002, Q-003).
3. **Laravel-native.** Gunakan fitur bawaan Laravel (Eloquent, Form Request, Policy, Notification, Filesystem, Scheduler, Queue) daripada membangun ulang.
4. **Modular monolith.** Satu aplikasi Laravel, dipartisi per domain (bukan microservice).
5. **Simple > clever.** Abstraksi hanya bila ada alasan nyata (lihat anti-over-engineering di bawah).
6. **Konfigurasi di luar kode.** Threshold, path storage, tanggal efektif, flag notifikasi — semua via config/env, bukan hard-code (Q-005, Q-012, Q-018, Q-022).
7. **Kompatibilitas eksternal dijaga.** Kontrak `/api/agent/*` dan payload QR URL harus identik dengan legacy (Q-021, BR-35/36).

**Anti-over-engineering (mengikat):** TIDAK membuat Repository untuk setiap Model, TIDAK membuat Service untuk setiap CRUD sederhana, TIDAK ada `GenericBaseEverything`, TIDAK ada domain layer berlebihan. Prioritas: **Readable · Maintainable · Testable · Laravel-native · Modular · Simple**.

---

## 2. Laravel Version

- **Rekomendasi:** **Laravel 11.x** (rilis stabil, dukungan panjang, ekosistem paket matang).
- **Alasan:** kompatibel PHP 8.2 (yang tersedia di server produksi), fitur modern (casts, enum), dan stabil untuk aplikasi internal jangka panjang.
- **Catatan:** versi pasti (11.x minor) dikunci saat implementasi dimulai agar memakai patch terbaru. Bukan keputusan bisnis.

## 3. PHP Version

- **PHP ^8.2** (mengikuti production server **PHP 8.2.12** yang terverifikasi di metadata `eams_database.sql`).
- Minimal 8.2 (match production); tidak perlu 8.3+ kecuali ada kebutuhan spesifik.

## 4. Database Strategy

- **Engine target:** tetap **MySQL/MariaDB** (production = MariaDB 10.4.32). Laravel mendukung keduanya via driver `mysql`.
- **Database BARU yang clean** (Q-002): Laravel memakai schema hasil rancangan, **bukan** menunjuk DB CI4 langsung. Alur: `EAMS CI4 DB → Legacy Data Import → EAMS Laravel DB`.
- **Collation/charset:** `utf8mb4`; pertahankan kompatibilitas data legacy (production `utf8mb4_general_ci`). Pilihan collation final (general_ci vs unicode_ci) = technical decision saat implementasi — yang penting tidak merusak data teks Indonesia/emoji.
- **Tipe ID dinormalisasi:** production punya signedness tak seragam (`users.id` SIGNED vs beberapa referensi UNSIGNED) — di schema baru gunakan satu konvensi (`id` BIGINT UNSIGNED auto-increment) agar FK bersih (menutup CONF-DB-017/024).
- **Foreign keys:** **technical decision** (Q-022 audit). Rekomendasi desain: terapkan FK pada relasi yang jelas kepemilikannya (master→detail) dan **hindari CASCADE berbahaya** yang di legacy menghapus histori (lihat §5). Relasi yang legacy-nya application-only tetap didokumentasikan; penerapan FK diputuskan per relasi pada fase implementasi schema.
- **Engine InnoDB** untuk semua tabel (mengikuti production).

## 5. Migration Strategy (Schema)

> "Migration" di sini = **Laravel migrations** untuk schema baru (bukan import data — itu §6).

- Satu migration per tabel, dinamai jelas (`create_compliance_inventories_table`, dst.).
- **ENUM vs lookup:** kolom status yang stakeholder tetapkan sebagai enum resmi → Laravel `enum` column atau PHP `enum` + string cast:
  - `checklist_logs.status` → `ok | not_ok | na` (Q-001, production-verified).
  - `compliance_inventories.status` → `good | need_repair | not_active` (Q-017). **Catatan mapping:** legacy `Good/Need Repair/Not Active` → lowercase snake.
  - `asset_item_types.checklist_frequency` → `daily | weekly | monthly` (production).
  - `follow_up_status` → `open | monitoring | closed` (production).
  - `users.permission` → `read | write`; `users.status` → `active | inactive` (production).
  - `it_devices.status` → `online | offline` (production).
- **Timestamps:**
  - `checklist_logs` di Laravel: **`created_at` + `updated_at`** aktif (Q-023) — berbeda dari legacy (yang tanpa `updated_at`).
  - `deleted_at` / soft delete: legacy **tidak punya** soft delete. Keputusan memakai `SoftDeletes` untuk entitas bisnis penting (inventory, asset, employee) = **technical decision** saat implementasi; default desain: tidak menambah soft delete kecuali diminta, agar sesuai legacy.
- **Hindari CASCADE destruktif dari legacy:** legacy `checklist_logs.inventory_id → compliance_inventory.id ON DELETE CASCADE` berarti hapus inventory = hapus histori checklist. Di Laravel **jangan** mewarisi CASCADE ini apa adanya untuk histori — gunakan `restrictOnDelete()`/`nullOnDelete()` atau guard agar histori audit tidak ikut terhapus. Ini adalah **koreksi desain sadar** (production fact: CASCADE; design decision: lindungi histori).
- **Indeks & unique yang wajib ada di schema baru** (dari production + business rule):
  - UNIQUE: `compliance_inventories.asset_code` (Q-020), `users.username`, `employees.employee_id`, `notifications.dedupe_key`, `assets.inventory_no`, `it_devices.asset_id`, `login_sessions.session_key`, PDAM/IPAL `log_date`, EMS `(year,month)`/`(year,section,month)`, patrol `code`/`slug`, questionnaire `slug`/`response_code`.
  - **BARU (perbaikan):** pertimbangkan UNIQUE komposit untuk dedup checklist `(inventory_id, period_key, time_slot)` — legacy hanya application-level (BR-09). Ini **technical decision**; menambahkannya menghilangkan risiko race. Ditandai candidate improvement, bukan perubahan behavior.

## 6. Legacy Data Import Strategy

> Pemisahan tegas: **schema baru (§5)** ≠ **import data (§6)**. Bukan "copy seluruh DB CI4" (Q-002).

- **Pendekatan:** one-time ETL/import (bukan sinkronisasi live). Baca DB CI4 → transformasi → tulis ke DB Laravel. Dapat dijalankan ulang (idempotent) sampai cutover.
- **Pemetaan kolom eksplisit** (Q-003): setiap kolom legacy punya salah satu status: `CARRY` / `TRANSFORM` / `DROP` / `REVIEW` (obsolete tapi berisi data → didokumentasikan, tidak dihapus otomatis).
- **Pemetaan kunci (contoh wajib dari keputusan):**
  - `checklist_logs.checked_by` (string) → `checked_by_user_id` (cocokkan nama ke `users.id` bila bisa) **+** `checked_by_name` (simpan nama asli sebagai snapshot) (Q-006).
  - `compliance_inventory.pic` (teks "A - B") → pecah ke `compliance_inventory_pics` (user_id, maks 2, tanpa is_primary) (Q-007). Kolom teks `pic` hanya dipertahankan sementara untuk backward-compat import, lalu tidak dipakai business logic.
  - `checklist_logs.status`: nilai legacy `ng` (bila ada di data historis) → dipetakan ke `not_ok` saat import (konsisten BR-11).
  - Dead/legacy columns (mis. `checklist_master.frequency`) → `DROP` (CONF-DB-023).
  - `asset_code` → dipertahankan **PERSIS**; duplikat/konflik **dilaporkan** sebagai migration issue, **jangan** auto-rename (Q-020).
- **File/evidence:** migrasikan semua file legacy (inventory photo, checklist photo, evidence, QR image, attachment) ke storage baru (§17) (Q-022).
- **Migration issues log:** setiap anomali (nama checked_by tak cocok user mana pun, asset_code duplikat, PIC > 2) dicatat ke tabel/log import untuk ditinjau manusia — tidak diam-diam diubah.
- **Validasi pasca-import:** rekonsiliasi count/sum per tabel vs production; sampling histori checklist; verifikasi QR masih resolve.

## 7. Authentication

- **Laravel session-based auth** (aplikasi internal server-rendered) — pengganti CI4 session. Pertahankan perilaku: login via **username ATAU email** + password (bcrypt) (BR-40).
- **Session:** lifetime 8 jam (28.800 dtk) mengikuti legacy; regenerate saat login; timeout idle ditandai kedaluwarsa (BR-40).
- **Keamanan login yang dipertahankan:** throttle percobaan (5/menit/IP), respons seragam untuk user tak dikenal (anti enumeration), pencatatan event auth ke audit log + login session (BR-40).
- **login_sessions & audit:** dipertahankan sebagai fitur (viewer admin) — Laravel events/listeners menulisnya.
- **Agent API auth:** terpisah dari session web — token device (lihat §24), karena agent adalah machine client (BR-35/36).

## 8. Authorization / Permission

- **Model otorisasi dipertahankan tiga lapis** (BR-41): role + permission (`read`/`write`) + page_access.
- **Implementasi Laravel-native:**
  - **Permission `read`** → middleware global yang menolak semua request non-GET untuk user read (setara WriteFilter, BR-42), dengan whitelist publik (login, kuesioner publik, `/api/agent/*`).
  - **Page access** → menyaring menu (BR-44) + guard akses.
  - **Role** → dicek via **Policy/Gate** (bukan hard-code tersebar).
- **Permission-based untuk fitur spesifik:** PDF Compliance → gate "akses Compliance" atau admin (Q-008), diimplementasikan sebagai **Policy/permission**, bukan `if role=='admin'` tersebar.
- **Roles:** bawaan `admin/compliance/security/staff/auditor/office` + role custom (`user_roles`) (BR-41). Otorisasi via Gate yang membaca role/permission/page_access.
- **Catatan terbuka (bukan keputusan di sini):** read-only self-service settings (Q-021 audit) tetap NEED HUMAN DECISION — desain middleware menyiapkan whitelist yang mudah dikonfigurasi agar keputusan itu tinggal diisi.

## 9. Domain / Module Structure

Struktur modular monolith, dipartisi per domain bisnis (bukan per tabel). Contoh pemetaan domain (mengikuti halaman 02 modules):

```
app/
  Domain/  (atau App\Modules/)
    Auth/            — login, session, audit auth
    User/            — users, roles, page_access
    MasterData/      — areas, inventory_categories, asset_item_types, holidays
    Compliance/      — inventory, PIC, QR, checklist master
    Checklist/       — period engine, logs, channels (standard+grid), evidence, follow-up
    Monitoring/      — home, dashboard, progress, ranking
    Report/          — print, PDF, export
    Notification/    — in-app, email, WA, reminders
    Calendar/        — holidays + events
    Thermal/         — thermal imaging
    Questionnaire/   — kuesioner publik
    Utility/         — boiler, ipal, pdam
    Ems/             — EMS/GHG
    Fdm/             — FDM production section
    ItAsset/         — assets, employees, assignments
    ItDevice/        — devices, agent API, commands
    Patrol/          — routes, checkpoints, sessions, logs
    Admin/           — settings, backup, audit log viewer
```

- Setiap domain menampung Model/Controller/Request/Policy/Action miliknya. **Domain layer tipis** — tidak over-engineered (lihat §1).
- **Identifier bisnis by `code`, bukan id** (Q-015): perilaku khusus item type (grid khusus, print form) di-resolve via `asset_item_types.code` (APAR/CCTV/P3K/TOILET/…), bukan `if item_type_id==N`.
- **Konfigurasi perilaku per item type** (mis. butuh expiry → Q-018; allow_na → Q-001; grid khusus) disimpan sebagai konfigurasi/atribut pada `asset_item_types` atau config map by `code` — bukan konstanta id.

## 10. Models

- **Eloquent models per tabel bisnis.** Relasi didefinisikan eksplisit (hasMany/belongsTo/belongsToMany).
- **Relasi penting:**
  - `ComplianceInventory` belongsTo `Area`, `AssetItemType`, `InventoryCategory`; belongsToMany `User` (PIC, via `compliance_inventory_pics`, **maks 2, tanpa is_primary** — Q-007).
  - `ChecklistLog` belongsTo `ComplianceInventory`, `AssetItemType`, `ChecklistMaster` (question), `User` (via `checked_by_user_id`) (Q-006).
  - `AssetItemType` belongsTo `InventoryCategory`; atribut `checklist_frequency`, `allow_na`.
  - `ItDevice` belongsTo `Asset`; `ItDeviceCommand` belongsTo `ItDevice`.
  - dst. mengikuti peta relasi halaman 03.
- **Casts:** date/datetime, JSON (`page_access`, `cpu` state, `monthly_values`, `options_json`), boolean (`allow_na`, `active`).
- **Accessor/mutator minimal** — logika bisnis ditaruh di Service/Action (§13), bukan membengkak di model.
- **Global scope:** hindari scope tersembunyi; gunakan query eksplisit.

## 11. Controllers

- **Resource controllers ramping** per domain; method hanya orkestrasi (validasi → panggil action/service → response).
- **Bukan** terjemahan 1:1 controller CI4. Controller CI4 yang membawa banyak logika (mis. `ComplianceInventoryController` raksasa) dipecah menjadi beberapa controller/action fokus.
- **Route model binding** memakai id; untuk QR/print publik memakai identifier yang sesuai (lihat §21).
- **Dead endpoints tidak dibawa** (Q-009): risk-trend/data, `compliance/calendar/events` standalone, `inventory/create`/`edit` page (legacy memakai modal), `qrBatch` yang tak ter-rute, `exportPeriodePage`.

## 12. Form Requests

- **Form Request per operasi tulis** (create/update/submit) untuk validasi terpusat.
- Menampung aturan bisnis yang tervalidasi di input, mis.:
  - Submit checklist standard: `status ∈ ok/not_ok/na`; `na` hanya bila `allow_na` (Q-001); `not_ok` wajib remark ATAU foto (Q-013).
  - Inventory: `status ∈ good/need_repair/not_active` (Q-017); kategori/area/item terkunci saat edit (BR-45).
- Authorize logic diletakkan di `authorize()` (memanggil Policy) agar validasi & otorisasi satu tempat.

## 13. Services / Actions

- **Action classes tunggal-tanggung-jawab** untuk operasi bisnis non-trivial (bukan service untuk tiap CRUD):
  - `SubmitChecklist`, `SaveGridChecklist`, `MarkAllGrid`, `AssignPic`, `GenerateAssetCode`, `RegenerateQr`, `ProcessAgentHeartbeat`, `QueueRemoteCommand`, `RecordAuditLog`, dsb.
- **Service** hanya untuk integrasi eksternal/kompleks: `NotificationService` (email/WA/in-app), `QrService` (generator), `PdfService`, `BackupManager`, `FonnteClient`, `AgentPushClient`.
- **CRUD sederhana** (areas, holidays, master data) → langsung controller + model, tanpa service.

## 14. Policies

- **Policy per model utama** (`ComplianceInventoryPolicy`, `ReportPolicy`, `ItAssetPolicy`, dst.) untuk otorisasi aksi (view/create/update/delete/export).
- **Gate khusus:** `accessCompliancePdf` (admin atau punya akses Compliance — Q-008); `managePatrolLayout` (admin — BR-37); `writeAccess` (permission write — BR-42).
- Policy membaca kombinasi role + permission + page_access (BR-41).

## 15. Events / Jobs

- **Events** untuk efek samping yang harus async/terpisah: notifikasi assignment PIC (BR-21), notifikasi temuan, audit log.
- **Queue + Jobs** untuk pekerjaan berat/async: kirim email/WA (Fonnte), generate PDF besar, zip QR album, proses push command agent, backup. Driver queue: `database` (cukup untuk skala internal; mudah di Windows on-prem).
- **Scheduler (Laravel)** menggantikan cron/schtasks legacy (Q-015 audit tetap NHD untuk inventarisasi jadwal aktual, tapi mekanismenya = Laravel Scheduler):
  - `notify:weekly-checklist` (WA) & email reminder (BR-23).
  - `device:status-check` (threshold terpusat, Q-012).
  - `backup:daily` (BR-39).

## 16. Notifications

- **Multi-kanal idempoten dipertahankan** (BR-24): in-app (tabel `notifications` + `dedupe_key` unik), email (SMTP Google Workspace), WA (Fonnte webhook).
- Laravel **Notification** class + custom channels (database, mail, Fonnte). Dedupe via `dedupe_key` unik agar idempoten.
- **Template pesan** dari `app_settings` dengan placeholder (`{{name}}/{{title}}/{{message}}/{{url}}/{{company}}/{{date}}`) (BR-24). Flag `notification_email_enabled`/`notification_whatsapp_enabled` mengontrol kanal.
- **Reminder mingguan** (BR-23): penerima = PIC pending checklist (memakai relasi PIC — Q-007); maks 15 item/pesan; dedupe `weekly_email_reminder:{date}:{userId}`.

## 17. File Storage Architecture

- **Configurable storage (Q-022):** gunakan Laravel **Filesystem disks** per kategori logis: `inventory_photos`, `checklist_evidence`, `qr_images`, `attachments`. Path/root tiap disk via **config/env** — TIDAK hard-code `storage/app/...`.
- **Target deployment:** Local Disk / Network Share / Custom Path (mis. `D:\EAMS\files` atau `\\SERVER-FILE\EAMS`). Aplikasi tidak bergantung absolute path yang ditanam di kode.
- **Nama file:** tetap pola nama acak (seperti legacy) untuk bukti; QR image boleh diregenerate (Q-021) tapi payload/URL sama.
- **Migrasi file:** semua file legacy (inventory photo, checklist photo, evidence, QR, attachment) dipindahkan ke disk baru saat import (§6).
- **Validasi upload terpusat (technical, menutup Q-026 audit):** satu komponen validasi (mime + size) dipakai semua modul — menghapus inkonsistensi legacy (foto checklist tanpa validasi).

## 18. Checklist Architecture

- **Unified Period Engine (Q-004):** satu komponen (service/value object) `ChecklistPeriod` yang menghasilkan:
  - `period_key`: daily `YYYY-MM-DD`, weekly `YYYY-MM-Wn` (irisan bulan W1=1–7 … W4=22–akhir, **bukan ISO week** — BR-02), monthly `YYYY-MM` (BR-01).
  - `status` kanonik: **DONE / OPEN / LATE / FUTURE / HOLIDAY** (Q-004) — DONE bila semua pertanyaan punya hasil valid (OK/NOT_OK/NA jika diizinkan, Q-001).
  - `late` (time-based, BR-03) & `editable`/`future` (BR-04).
  - `offday`/`holiday`: Minggu selalu libur; **Sabtu libur mulai 2026-04-01 (configurable effective date)** (Q-005); + tabel `holidays`. Pengisian daily diblokir pada holiday (BR-08).
- **Status checklist:** `ok | not_ok | na`; `na` hanya bila `allow_na` (Q-001); input legacy `ng` dinormalisasi ke `not_ok`.
- **Evidence rule:** NOT_OK wajib remark ATAU foto (Q-013) pada mode standard.
- **Dua mode (Q-016):** STANDARD dan GRID (detail grid di §19).
- **checked_by:** `checked_by_user_id` + `checked_by_name` snapshot (Q-006).
- **Item type by code (Q-015):** perilaku khusus (slot toilet, grid khusus, print form) di-resolve via `asset_item_types.code`.
- **Toilet 3-slot:** konfigurasi slot (`PG/SI/SO`) untuk item type berkode TOILET; dedup per (inventory, periode, slot) (BR-14).
- **Anti-duplikat:** 1 log-set per (inventory, period[, slot]) (BR-09) — ditegakkan aplikasi + candidate UNIQUE DB (§5).
- **Expiry visibility:** field expiry tampil terutama untuk APAR (by config), tidak auto-mengubah status (Q-018).

## 19. Grid Checklist Architecture

- **Grid = fitur resmi** (Q-016): fast/mass entry, menghasilkan OK/NOT_OK/NA, NA tunduk `allow_na`, memakai unified period engine.
- **Perbedaan mode (Q-013):** grid **boleh bypass** validasi NOT_OK-evidence untuk kecepatan (mis. P3K harian). Standard menegakkannya.
- **Behavior grid yang dipertahankan:** mark-all mengisi sel kosong (tidak menimpa) (BR-15); clear menghapus sel (BR-16); koreksi sel mengubah log existing (dengan audit trail baru, §20).
- **Perilaku khusus per item type by code** (bukan id hard-code): grid khusus (CCTV, EL, EEL, First Aid Box/Content, APAR, Intrusion Alarm, Hydrant, Heat/Smoke Detector, Gate, Toilet) di-resolve via `code` → definisi kolom gridnya.
- **Catatan legacy yang diperbaiki:** kolom grid legacy dipetakan dari **teks pertanyaan** (rapuh). Di Laravel, kolom grid di-resolve via relasi/id stabil ke `checklist_master` — bukan teks (perbaikan desain, behavior tetap sama).
- **Keputusan terbuka (bukan di sini):** mark-all Heat Detector yang menimpa existing (Q-024 audit) tetap NEED HUMAN DECISION — desain menyiapkan flag per-grid agar keputusan itu tinggal diisi.

## 20. Audit Trail

- **Dua lapis:**
  1. **Auth/aksi audit** (legacy `audit_logs` + `login_sessions`) dipertahankan — login/logout/failed/blocked + konteks device (BR-40).
  2. **Checklist change audit (LARAVEL IMPROVEMENT — Q-023):** tabel `checklist_log_histories` mencatat perubahan log checklist: `checklist_log_id`, `changed_by_user_id`, `changed_by_name`, `old_status`, `new_status`, `old_remark`, `new_remark`, `old_photo`, `new_photo`, `changed_at`.
- **Implementasi:** model observer pada `ChecklistLog` menulis history saat update. Ini **bukan** legacy behavior (legacy tanpa jejak koreksi) — dipisahkan jelas sebagai improvement.
- `checklist_logs` di Laravel punya `created_at` + `updated_at` (Q-023).

## 21. QR Compatibility

- **Payload/URL identik legacy (Q-021):** QR berisi `base_url('compliance/inventory/detail/{id}')`. Laravel **menyediakan route kompatibel** `compliance/inventory/detail/{id}` agar QR fisik lama tetap valid.
- **asset_code tidak berubah** (Q-020) — QR lama menunjuk inventory yang sama.
- **QR image boleh diregenerate** (Q-021), idealnya memakai paket lokal (`endroid/qr-code` yang sudah terpasang tapi tak dipakai legacy) untuk menghapus dependency eksternal `api.qrserver.com` (BR-20) — **perbaikan desain**, payload tetap sama.
- **Route model binding** pada detail dapat memakai id (sesuai URL legacy).

## 22. PDF Architecture

- **Library:** lanjutkan arah legacy menuju **Dompdf** (wrapper `barryvdh/laravel-dompdf`) — menghapus dual mPDF/Dompdf (halaman 13).
- **Print/report per item type:** 12 form print khusus + generic, di-resolve via `code` item type (Q-015), kolom via relasi stabil (bukan teks pertanyaan).
- **Authorization:** PDF Compliance dibatasi **admin atau user dengan akses Compliance** via **permission-based Gate/Policy** (Q-008) — bukan hard-code role.
- **Batch print per kategori/periode** dipertahankan; export Excel via `maatwebsite/excel` untuk report yang membutuhkan (progress, dsb.).
- Export PDAM/Boiler mewarnai hari libur (offday engine, Q-005) + total bulanan (BR-29/30).

## 23. Frontend Architecture

- **Server-rendered (Blade) + progressive enhancement** — sesuai karakter aplikasi internal. Pertahankan Bootstrap 5 (kesinambungan UI).
- **Interaktivitas:** grid checklist & form memakai JavaScript ringan (Vanilla/Alpine) via AJAX — menggantikan campuran jQuery/Alpine legacy secara bertahap, tanpa SPA penuh.
- **Halaman 403 resmi** (menutup Q-010 audit): Laravel menyediakan error view 403; controller tidak lagi redirect ke `/unauthorized` yang 404.
- **Menu** disaring oleh page_access (BR-44); badge sidebar = unread notif + pending checklist (BR-25).
- **Modal workflow** untuk create/edit inventory dipertahankan (legacy kanonik = modal, CONF-020) — bukan halaman terpisah.

## 24. API/AJAX Architecture

- **Dua kelas endpoint:**
  1. **Internal AJAX** (session auth + CSRF) untuk grid/form/dashboard — JSON, dilindungi middleware auth + write.
  2. **Agent API publik** `/api/agent/*` (CSRF-exempt) untuk EAMS Agent Windows — **kontrak identik legacy** (BR-35/36): heartbeat, command poll/ack, update. Auth via `device_token` (bukan session).
- **Perbaikan keamanan (technical):** agent endpoint idealnya hanya menerima **POST** untuk mutasi (legacy menerima GET — Q-025 audit). **Catatan:** Q-025 tetap NEED HUMAN DECISION karena harus cek kompatibilitas agent lapangan; desain menyiapkan agar pembatasan ke POST mudah diterapkan setelah diputuskan.
- **Rate limiting** pada agent API + endpoint publik kuesioner.
- **Response konsisten:** JSON envelope `{ status, message, data }` untuk AJAX internal.

## 25. Testing Strategy

- **PHPUnit + Laravel Feature/Unit tests.** Prioritas pada logika bisnis kritikal:
  - **Period engine** (period_key, W1–W4 irisan bulan, late, editable, offday, Saturday effective date) — unit test menyeluruh (ini jantung sistem).
  - **Status checklist** (ok/not_ok/na, allow_na, ng→not_ok).
  - **Evidence rule** (not_ok butuh remark/foto; grid bypass).
  - **Unified period status** (DONE/OPEN/LATE/FUTURE/HOLIDAY).
  - **PIC rules** (maks 2, tanpa primary), **asset_code generator**, **device online threshold**.
  - **Otorisasi** (read-only blocked, PDF gate, page_access menu).
- **Migration/import test:** fixture data legacy → import → assert mapping (checked_by, pics, status normalization, asset_code preserved).
- **Target:** logika bisnis inti ter-cover; tidak mengejar 100% pada CRUD trivial.

## 26. Deployment

- **Target:** Windows on-prem (mengikuti legacy `eams.ptyhs.com`), PHP 8.2, MariaDB 10.4.32. Web server IIS/Apache/Nginx (sesuai infra existing).
- **Konfigurasi via `.env`:** DB, storage paths (§17), SMTP, Fonnte, threshold (Q-012), tanggal efektif Sabtu (Q-005), flag notifikasi.
- **Storage** menunjuk disk/network share yang dikonfigurasi (Q-022).
- **Queue worker + Scheduler** berjalan sebagai Windows service / Task Scheduler (menggantikan schtasks legacy secara native Laravel).
- **Cutover:** DB baru + import (§6) divalidasi dulu, lalu DNS/switch. QR lama tetap valid (§21) sehingga cutover tidak merusak label fisik.

## 27. Backup / Restore

- **3 jenis dipertahankan** (database / files / full) dengan **retensi 30 hari** (BR-39).
- **Mekanisme:** Laravel **Scheduler** harian (menggantikan Windows schtasks legacy) memanggil backup action; **path configurable** (tidak hard-code `D:\EAMS-Backups`).
- **Full backup** = dump DB + manifest + uploads (dari disk storage terkonfigurasi).
- **Restore:** prosedur terdokumentasi (import DB + kembalikan files), diuji berkala.

## 28. Migration Rollback Strategy

- **Import data dapat diulang (idempotent)** — kegagalan import tidak merusak DB target; bisa bersihkan & ulangi.
- **Laravel migrations** punya `down()` yang benar per tabel untuk rollback schema saat development.
- **Cutover rollback:** legacy CI4 tetap berjalan sampai Laravel dinyatakan live; bila cutover gagal, kembali ke CI4 (data sumber tidak diubah oleh import).
- **Snapshot/backup DB target** sebelum cutover agar bisa restore.
- **Migration issues log** (§6) memungkinkan identifikasi & perbaikan tanpa rollback penuh.

## 29. Security

- **CSRF** aktif (pengecualian: `/api/agent/*`, kuesioner publik — seperti legacy).
- **Password** bcrypt; throttle login; anti user-enumeration (respons seragam) (BR-40).
- **Authorization** via Policy/Gate + middleware write (read-only blocked) + page_access (BR-41/42/44).
- **Upload:** validasi mime + size terpusat (menutup Q-026 audit); simpan di luar webroot atau disajikan via controller; nama file acak.
- **Agent API:** token device; pertimbangkan batasi ke POST (menunggu Q-025); rate limit.
- **SQL injection:** Eloquent/Query Builder binding (legacy sudah prepared statements).
- **XSS:** Blade escaping default; hati-hati rendering konten kuesioner/remark.
- **Secrets** (SMTP, Fonnte token) di `.env`, tidak di repo (Q-018 audit: export settings produksi tanpa secret di repo).

## 30. Observability / Logging

- **Laravel Log** (channel harian) untuk error & event penting.
- **Audit log bisnis** (§20) terpisah dari log teknis — dapat dilihat admin di UI (legacy `AuditLogController`).
- **Queue/Job monitoring:** failed jobs table + notifikasi admin bila job kritikal (backup, reminder) gagal.
- **Health checks:** endpoint sederhana untuk DB/queue/storage agar deployment termonitor.
- **Login session & device status** (Q-012) terlihat di UI admin untuk operasional.

---

## Traceability

Setiap bagian di atas merujuk keputusan/aturan sumber: Business Rules (`BR-xx`, `docs/17`) · Human Decisions (`Q-xx`, `docs/19`) · Production Facts (`CONF-DB-xx`, `docs/18`). Tidak ada keputusan desain tanpa asal.

**Keputusan yang disengaja DITUNDA (bukan dibuat di sini):** detail collation final, pemakaian SoftDeletes per tabel, penambahan UNIQUE komposit checklist, FK per relasi, pembatasan agent ke POST, dan seluruh business behavior yang masih NEED HUMAN DECISION (lihat `docs/15`: Q-011, Q-015, Q-016, Q-018, Q-019, Q-021, Q-024, Q-025).

> **Status:** DRAFT — menunggu **Architecture Review** oleh Project Owner. Implementasi Laravel **belum** boleh dimulai.
> **Rantai:** Legacy → Audit → Production Verification → Human Decision → Business Specification → **Architecture Design (dokumen ini)** → Architecture Review → Implementation.
