# 17 — EAMS Business Specification (Canonical)

> **Status dokumen:** SOURCE OF TRUTH perilaku bisnis EAMS — hasil konsolidasi & cross-check **Fase 0.5** (2026-08-18) atas seluruh dokumen audit Fase 0 (`docs/00`–`docs/16`), dengan evidence **production database** dari **Fase 0.6** (`eams_database.sql`, MariaDB 10.4.32 — lihat `docs/18`) dan **keputusan bisnis final** Project Owner dari **Fase 1** (`docs/19`).
> **Isi:** behavior `CONFIRMED` (dengan evidence kode) + keputusan stakeholder (field **Decision**); yang masih terbuka ditandai **NEED HUMAN DECISION** (dirujuk ke `docs/15`).
> **Penomoran:** `BR-01..BR-40` diwarisi dari `docs/09-business-rules.md` (traceability); `BR-41..BR-47` rule baru hasil review Fase 0.5. Kontradiksi dicatat sebagai `CONF-xxx` (Appendix A); keputusan manusia sebagai `Q-xxx` (`docs/15`/`docs/19`).
> **Aturan status:** `CONFIRMED` = terbukti dari kode/database; `AMBIGUOUS` = bukti saling konflik; `RESOLVED` = telah diputuskan (production schema atau Project Owner); aspek `INFERRED`/`UNKNOWN` dicatat sebagai Notes atau Q-item.
> **Update Fase 1 (2026-08-18):** keputusan bisnis final Project Owner (`docs/19-decision-log.md`) diintegrasikan ke rule terkait — field **Decision** ditambahkan pada rule yang diputuskan. Ringkasan status keputusan: §18.

---

## 1. System Overview

EAMS (Enterprise Asset & Compliance Management System) adalah aplikasi internal PT YHS (pabrik garmen, Sukabumi) yang dibangun sebagai **monolit CodeIgniter 4** dengan server-rendered views + AJAX.

**Inti bisnis (CONFIRMED):** compliance checklist fasilitas pabrik — inventory fasilitas → pertanyaan checklist per item type → log pengisian per periode (daily/weekly/monthly) → monitoring (home, progress, ranking) → report/PDF. Di sekelilingnya: patrol security (barcode + GPS + foto), IT asset & device monitoring (EAMS Agent Windows → `/api/agent/*`), utility logs (Boiler/IPAL/PDAM), EMS/GHG report, FDM data collection, kuesioner publik, thermal imaging, notifikasi multi-kanal (in-app/email/WA Fonnte), dan backup system.

**Stack (CONFIRMED):**

| Aspek | Nilai | Evidence |
|---|---|---|
| Framework | CodeIgniter 4 (folder `system/` di-commit ke repo) | `composer.json`, `app/Config/Autoload.php` |
| PHP | ^8.1 (production server 8.2.12) | `composer.json`, `eams_database.sql` |
| DB | MySQL/MariaDB via MySQLi; DB default `asset_compliance_system` (production: MariaDB 10.4.32) | `app/Config/Database.php`, `eams_database.sql` |
| Session | FileHandler, cookie `eams_session`, 28.800 dtk (8 jam), regenerate 3.600 dtk | `app/Config/Session.php` |
| Timezone / baseURL | Asia/Jakarta; `https://eams.ptyhs.com/` | `app/Config/App.php` |
| PDF | mPDF (library wrapper) → migrasi bertahap ke Dompdf | `composer.json`, `app/Libraries/` |
| QR | API eksternal `api.qrserver.com` (paket `endroid/qr-code` terpasang tapi tidak dipakai) | `app/Services/QrService.php` |
| Notifikasi | in-app (tabel `notifications`), email SMTP Google Workspace, WA Fonnte | `app/Libraries/NotificationService.php` |
| UI | Bootstrap 5 + Vanilla JS + Alpine.js + jQuery (campuran) | `app/Views/layouts/main.php`, `public/js/*` |

**Aktor (CONFIRMED):** role bawaan `admin`, `compliance`, `security`, `staff`, `auditor`, `office` + role custom via tabel `user_roles`.

---

## 2. Users & Authorization

### BR-41 — Model otorisasi tiga lapis
- **Status:** CONFIRMED
- **Rule:** akses pengguna ditentukan kombinasi tiga lapis: (1) **role** (`users.role` + role custom dari `user_roles`), (2) **permission** `users.permission` (`read` / `write`), (3) **page access** `users.page_access` (JSON daftar halaman dari katalog 28 menu).
- **Behavior:** role menentukan kapabilitas fitur (dicek via `hasRole([...])` di controller); permission `read` membuat user benar-benar read-only; page_access menyaring menu yang tampil.
- **Evidence:** `app/Helpers/access_helper.php`; `app/Controllers/UserController.php`; migration `user_roles` & kolom `page_access`; `app/Filters/WriteFilter.php`.
- **Affected Modules:** seluruh modul.
- **Notes:** nilai `permission` divalidasi `in_array('read','write')` di UserController (CONFIRMED); tipe kolom DB = `enum('read','write') DEFAULT 'read'` (production verified, Fase 0.6).

### BR-42 — WriteFilter global memblokir semua mutasi untuk user read-only
- **Status:** CONFIRMED
- **Rule:** setiap request non-GET (POST/PUT/DELETE) dari user dengan `permission = read` ditolak 403, kecuali path dalam whitelist publik (login, kuesioner publik, `/api/agent/*`).
- **Behavior:** penolakan terjadi di level filter global sebelum controller dieksekusi.
- **Evidence:** `app/Filters/WriteFilter.php` (didaftarkan global di `app/Config/Filters.php`); `app/Controllers/AuthController.php`.
- **Affected Modules:** seluruh modul (cross-cutting).
- **Notes:** efek samping pada self-service settings → BR-43 / Q-021.

### BR-43 — User read-only tidak dapat mengubah password/kontaknya sendiri
- **Status:** CONFIRMED (perilaku) — **intent AMBIGUOUS → Q-021 (masih NEED HUMAN DECISION)**
- **Rule (teramati):** karena halaman Settings self-service (ganti password, kontak, tandai-notifikasi-terbaca) memakai POST ke `settings/change-password` dan path `/settings` tidak masuk whitelist WriteFilter, user read-only ikut terblokir.
- **Behavior:** read-only user hanya bisa melihat; semua POST miliknya (termasuk milik dirinya sendiri) 403.
- **Evidence:** `app/Filters/WriteFilter.php` (publicPrefixes tidak memuat `/settings`); `app/Controllers/SettingController.php`.
- **Affected Modules:** Settings, Notifications.
- **Notes:** **NEED HUMAN DECISION** — by design atau oversight (Q-021). Tidak diputuskan di Fase 1; desain Laravel menyiapkan whitelist yang mudah dikonfigurasi.

### BR-44 — Visibilitas menu mengikuti page_access
- **Status:** CONFIRMED
- **Rule:** sidebar/menu hanya menampilkan halaman yang ada di `users.page_access` (JSON) terhadap katalog 28 menu di `access_helper`; admin selalu melihat semua.
- **Behavior:** penyaringan terjadi saat render layout; akses URL langsung tetap dijaga role/permission di controller/filter.
- **Evidence:** `app/Helpers/access_helper.php`; `app/Views/layouts/sidebar.php`; `app/Controllers/BaseController.php`.
- **Affected Modules:** seluruh modul (UI).
- **Notes:** katalog 28 menu terdokumentasi di `docs/12-auth-authorization.md`.

### BR-40 — Keamanan login (diwarisi dari audit)
- **Status:** CONFIRMED
- **Rule:** login via username **atau** email + password (bcrypt); throttle 5 percobaan/menit/IP; dummy hash untuk user tak dikenal (anti user-enumeration timing); session regenerate saat login; sesi idle > 8 jam ditandai kedaluwarsa; semua event auth dicatat (login/logout/failed/blocked + konteks device) ke `audit_logs` & `login_sessions`.
- **Behavior:** percobaan ke-6 dalam satu menit → diblokir; sesi kedaluwarsa tidak bisa dipakai lagi.
- **Evidence:** `app/Controllers/AuthController.php`; `app/Config/Session.php`; `app/Controllers/AuditLogController.php`; migration `audit_logs`, `login_sessions`.
- **Affected Modules:** Authentication, Administration.
- **Notes:** detail filter & guard per route: `docs/12-auth-authorization.md`.

---

## 3. Inventory (master data & identitas aset)

Master data compliance (CONFIRMED): `areas`, `inventory_categories`, `asset_item_types` (memuat `code`, `checklist_frequency`, `allow_na`, `active`), dan `holidays`.

### BR-19 — Auto-generate asset_code
- **Status:** CONFIRMED
- **Rule:** `asset_code = KODEKATEGORI-KODEITEM-###` — prefix dari `inventory_categories.code` + `asset_item_types.code` (uppercase), nomor urut 3 digit dari record terakhir `LIKE 'prefix%'` ORDER BY id DESC.
- **Behavior:** bila user mengosongkan asset_code pada tambah/edit, sistem meng-generate otomatis; bila asset_code berubah saat edit → QR diregenerasi.
- **Evidence:** `ComplianceInventoryController::store()` dan `::update()`.
- **Affected Modules:** Compliance Inventory, QR, Checklist, Report.
- **Notes:** **koreksi Fase 0.6:** production punya `UNIQUE KEY uniq_asset_code (asset_code)` (CONF-DB-005). Pola check-then-insert tetap bisa race → hasilnya error insert (bukan duplikat).
- **Decision (Fase 1, docs/19 Q-020):** asset_code = BUSINESS IDENTIFIER — dipertahankan **PERSIS** saat migrasi (jangan regenerate); asset baru pakai generator format sama; duplikat = dilaporkan sebagai migration issue, bukan di-rename.

### BR-20 — QR code = URL detail, gambar dari API eksternal
- **Status:** CONFIRMED
- **Rule:** QR berisi `base_url('compliance/inventory/detail/{id}')`; PNG 300×300 diunduh dari `api.qrserver.com`, lalu asset code ditulis di tengah (GD, font 5, box putih); file `public/uploads/qr/qr_inv_{id}.png`.
- **Behavior:** generate saat store; regenerate saat asset_code berubah / via tombol regenerate / regen per album.
- **Evidence:** `app/Services/QrService.php`; `ComplianceInventoryController::store/update/regenerateQr/qrAlbumRegen`.
- **Affected Modules:** Compliance Inventory, QR Center, Patrol (praktik serupa), mobile scanning.
- **Notes:** dependency eksternal; `endroid/qr-code` tersedia tapi tidak dipakai.
- **Decision (Fase 1, docs/19 Q-021):** QR URL tetap **PERSIS** seperti legacy (`compliance/inventory/detail/{id}`) — Laravel menyediakan route kompatibel; QR image boleh diregenerate (paket lokal), payload/URL tidak berubah.

### BR-45 — Edit inventory mengunci kategori, area, dan item type
- **Status:** CONFIRMED (baru, Fase 0.5)
- **Rule:** saat edit, kategori / area / item tidak dapat diubah (field disabled, hanya hidden id yang terkirim); yang dapat diubah: asset_code, tipe/spesifikasi, specific area, PIC, expired, status, remark.
- **Behavior:** header modal: "Perbarui informasi aset **tanpa mengubah kategori, area, dan item**."
- **Evidence:** `app/Views/compliance/inventory/_modal_edit.php` (field `disabled` + hidden `category_id/area_id/item_type_id`); `ComplianceInventoryController::update()` tetap menerima id tsb tapi UI menguncinya.
- **Affected Modules:** Compliance Inventory.
- **Notes:** backend tetap membaca `category_id`/`area_id`/`item_type_id` dari POST — penguncian murni di UI (dicatat sebagai observasi, bukan konflik).

---

## 4. Compliance Inventory

### BR-21 — PIC maksimal 2 + notifikasi assignment
- **Status:** RESOLVED (Fase 1)
- **Rule:** maksimal 2 PIC per inventory; perubahan PIC memicu notifikasi "Penugasan inventory baru" (dedupe `inventory_assignment:{inventory}:{user}`); relasi disimpan di `compliance_inventory_pics`.
- **Behavior:** penambahan/penggantian PIC → baris relasi + notifikasi in-app (+email/WA bila aktif).
- **Evidence:** `app/Models/ComplianceInventoryModel.php` (callbacks); migration `2026-08-07-000002` (tabel pics + backfill); `app/Libraries/NotificationService.php`.
- **Affected Modules:** Compliance Inventory, Home, Progress, Notifications.
- **Notes:** **konflik mekanisme PIC (teks vs relasi)** → CONF-004 / Q-007. **Production verified (Fase 0.6):** pics = UNIQUE(inventory_id,user_id), is_primary tinyint, TANPA FK (signedness mismatch); kolom `pic varchar(100)` masih ada & aktif ditulis.
- **Decision (Fase 1, docs/19 Q-007):** `compliance_inventory_pics` = **SOURCE OF TRUTH**; maks 2 PIC per inventory, kedudukan **sama** (TANPA primary/secondary; `is_primary` bukan rule Laravel); kolom teks `pic` hanya untuk migration/backward-compat.

### BR-46 — PIC dipilih dari user aktif, digabung separator " - "
- **Status:** CONFIRMED (baru, Fase 0.5)
- **Rule:** dropdown PIC menampilkan user dengan `status='active'` (urut nama); PIC utama wajib, PIC kedua opsional dan harus berbeda; nilai digabung ke satu kolom teks `pic` dengan separator `" - "`.
- **Behavior:** JS modal men-sync dua dropdown → hidden input `pic` ("Nama1 - Nama2"); parse ulang saat modal dibuka memecah pada newline/koma/" - ", maks 2.
- **Evidence:** `app/Views/compliance/inventory/_modal_edit.php` (query `UserModel where status=active`, `<script>` parse/sync).
- **Affected Modules:** Compliance Inventory, Home, Progress, Reminder.
- **Notes:** **terkait Q-007 (Fase 1):** mekanisme teks ini hanya untuk input UI saat migrasi; business logic Laravel memakai relasi `compliance_inventory_pics` sebagai source of truth.

### BR-22 — Status inventory: Good / Need Repair / Not Active
- **Status:** RESOLVED (Fase 1)
- **Rule:** nilai status yang sah di UI legacy: `Good`, `Need Repair`, `Not Active` (badge: Baik / Perlu Perbaikan / Tidak Aktif; baris kuning untuk Need Repair, abu untuk Not Active).
- **Behavior:** dropdown edit hanya memuat 3 opsi tersebut; JS memberi warna baris sesuai status.
- **Evidence:** `app/Views/compliance/inventory/_modal_edit.php` (`<select name="status">` berisi persis 3 opsi); `public/js/inventory.js getStatusMeta/updateInventoryRowFromEditForm`.
- **Affected Modules:** Compliance Inventory, Dashboard, Report.
- **Notes:** **production verified (Fase 0.6):** `compliance_inventory.status varchar(50) DEFAULT NULL` — bebas teks di legacy.
- **Decision (Fase 1, docs/19 Q-017):** status kondisi inventory resmi = **GOOD / NEED_REPAIR / NOT_ACTIVE** — **berbeda** dari checklist status (jangan dicampur).

### BR-23/24/25 — Reminder & notifikasi
Lihat §10 Notifications (penomoran dipertahankan di sana).

**Atribut lain (CONFIRMED, dari audit):** `expired_date` opsional (dipakai untuk highlight & print APAR — **Decision Fase 1 Q-018:** expiry terutama untuk APAR, tidak auto-mengubah status, visibilitas via konfigurasi); `qty` pada tambah; foto inventory via `updatePhoto`; QR Center: album per item type, download zip, print album, regen massal; `qrBatch` (zip by ids) **method ada tapi tidak ter-rute**.

---

## 5. Checklist

> Engine periode & aturan pengisian. Detail penuh: `docs/10-checklist-rules.md`. Penomoran BR-01..BR-18 diwarisi dari `docs/09`.
> **Decision (Fase 1):** (a) dua engine status periode legacy digabung jadi **satu** engine dengan status kanonik **DONE/OPEN/LATE/FUTURE/HOLIDAY** (docs/19 Q-004); (b) perilaku khusus item type memakai **`asset_item_types.code`**, bukan hard-coded id (docs/19 Q-015); (c) checklist punya **dua mode resmi: STANDARD & GRID** (docs/19 Q-016).

### BR-01 — Format period_key
- **Status:** CONFIRMED
- **Rule:** daily `YYYY-MM-DD`; weekly `YYYY-MM-Wn` (n=1..4); monthly `YYYY-MM`.
- **Behavior:** semua log, kalender, report, dan dedup memakai kunci ini.
- **Evidence:** `app/Helpers/period_helper.php generate_period_key()`.
- **Affected Modules:** Checklist, Dashboard, Progress, Ranking, Reminder, Report/PDF.
- **Notes:** kandidat value object `ChecklistPeriod` di Laravel (catatan migrasi, bukan keputusan).

### BR-02 — Minggu = irisan bulan (BUKAN ISO week)
- **Status:** CONFIRMED
- **Rule:** W1=tgl 1–7, W2=8–14, W3=15–21, W4=22–akhir bulan; reset setiap bulan.
- **Behavior:** period_key weekly `YYYY-MM-Wn` mengikuti irisan tanggal tsb.
- **Evidence:** `period_helper.php generate_period_key`; `calender_period_helper.php` & `checklist_helper.php generate_calendar_periods`; `ComplianceRankingController::weekEndDate`.
- **Affected Modules:** semua perhitungan weekly.
- **Notes:** **JANGAN** `Carbon::weekOfYear` di Laravel.

### BR-03 — Ambang late (keterlambatan)
- **Status:** CONFIRMED
- **Rule:** daily late bila `tanggal periode + 21 hari < now`; weekly late bila `hari pertama minggu + 28 hari < now`; monthly late bila `tgl 1 bulan tsb + 3 bulan < now`.
- **Behavior:** periode kosong yang melewati ambang dihitung "late" di home/progress/reminder.
- **Evidence:** `period_helper.php is_period_late()` (`+21 days`, `+28 days // 4 minggu`, `+3 months`).
- **Affected Modules:** Home, Progress, Reminder, Sidebar badge.
- **Notes:** definisi "late" di dashboard KPI **berbeda** (history-based) → CONF-014 / Q-019 (masih NEED HUMAN DECISION).

### BR-04 — Future terkunci & jendela editable
- **Status:** CONFIRMED
- **Rule:** periode future tidak bisa diisi; daily editable selama tidak future; **monthly selalu editable** (backfill tanpa batas — ada komentar "tetap bisa dibuka untuk backfill"); weekly editable bila selisih ≤ 3 bulan (`graceMonths=3`).
- **Behavior:** form/grid menolak periode di luar jendela.
- **Evidence:** `period_helper.php is_period_editable()/is_period_future()`.
- **Affected Modules:** Checklist (form & grid).
- **Notes:** asimetri weekly vs monthly → Q-011 (masih NEED HUMAN DECISION).

### BR-05 — Status periode untuk kalender UI (implementasi yang menang)
- **Status:** CONFIRMED (perilaku runtime legacy)
- **Rule:** prioritas status legacy: `done` (ada log) > `future` > `late` > `pending`.
- **Behavior:** sel kalender diwarnai sesuai status ini.
- **Evidence:** `period_helper.php resolve_period_status()`.
- **Affected Modules:** Checklist UI, Detail inventory.
- **Notes:** ada implementasi ganda dengan semantik lain → BR-06 / CONF-002 / Q-004.
- **Decision (Fase 1, docs/19 Q-004):** status kanonik tunggal = **DONE / OPEN / LATE / FUTURE / HOLIDAY** (lihat BR-06).

### BR-06 — Engine status periode digabung jadi satu
- **Status:** RESOLVED (Fase 1) — duplikasi engine ditiadakan
- **Rule (legacy):** `period_status_helper.php` mendefinisikan fungsi bernama sama dengan hasil berbeda: `done` > daily(`holiday`/`locked`/`open`), weekly/monthly(`locked` bila future, else `open`).
- **Behavior (legacy):** karena keduanya di-autoload, yang termuat lebih dulu yang menang — perilaku bergantung urutan load.
- **Evidence:** `app/Config/Autoload.php`; `app/Helpers/period_helper.php`; `app/Helpers/period_status_helper.php`.
- **Affected Modules:** Checklist UI.
- **Decision (Fase 1, docs/19 Q-004):** satu engine status periode dengan canonical status **DONE/OPEN/LATE/FUTURE/HOLIDAY**; behavior legacy dipertahankan sedekat mungkin; jangan buat dua engine lagi.

### BR-07 — Hari non-kerja (offday)
- **Status:** RESOLVED (Fase 1)
- **Rule:** Minggu selalu libur; Sabtu libur **hanya untuk tanggal ≥ 2026-04-01**; plus tanggal pada tabel `holidays`.
- **Behavior:** pengisian daily pada offday diblokir; kalender menandai offday; export PDAM/boiler mewarnai hari libur.
- **Evidence:** `checklist_helper.php is_weekend_offday()/is_date_offday()/holiday_dates_between()`.
- **Affected Modules:** Checklist daily, Home, Progress, PDAM/Boiler export, Calendar.
- **Decision (Fase 1, docs/19 Q-005):** effective date policy — **sebelum 1 April 2026** Sabtu = working day; **mulai 1 April 2026** Sabtu = holiday; **tidak retroaktif**; histori konsisten dengan policy saat itu. (Menutup CONF-003 / Q-005.)

### BR-08 — Blokir pengisian pada hari libur (daily)
- **Status:** CONFIRMED
- **Rule:** checklist daily tidak dapat diisi pada offday.
- **Behavior:** submit/grid-save mengembalikan error "Checklist tidak dapat diisi pada hari libur."; form menampilkan lock `offday`.
- **Evidence:** `ComplianceInventoryController::submitChecklist`, `saveCctvGrid`, `saveFirstAidContentGrid`, `saveGenericGrid`; `checklist()` (`lockReason='offday'`).
- **Affected Modules:** Checklist.
- **Notes:** status HOLIDAY kini bagian dari engine kanonik (Q-004).

### BR-09 — Anti-duplikat periode
- **Status:** CONFIRMED
- **Rule:** 1 log-set per `(inventory_id, period_key[, time_slot])`; submit ulang ditolak ("sudah diisi").
- **Behavior:** form full-submit menolak; grid boleh **update sel existing** (= koreksi).
- **Evidence:** `submitChecklist` (`$existsQuery->first()`); `checklist()` (`lockReason='done'`); seluruh `save*Grid`.
- **Affected Modules:** Checklist.
- **Notes:** **production verified (Fase 0.6):** tidak ada UNIQUE(inventory_id, period_key[, time_slot]) di DB → **APPLICATION-LEVEL CONSTRAINT**; risiko race tetap ada (candidate UNIQUE di Laravel, `docs/20` §5).

### BR-10 — not_ok wajib bukti
- **Status:** RESOLVED (Fase 1)
- **Rule:** status `not_ok` wajib remark **atau** foto (minimal salah satu).
- **Behavior:** validasi server + client menolak submit bila keduanya kosong.
- **Evidence:** `submitChecklist`; `public/js/checklist.js validateChecklistForm`.
- **Affected Modules:** Checklist, Evidence.
- **Decision (Fase 1, docs/19 Q-013):** default rule ditegaskan — NOT_OK wajib remark ATAU foto. **Standard checklist:** keduanya kosong → tidak boleh selesai. **EXCEPTION: GRID checklist boleh bypass** untuk fast entry (mis. P3K harian). `require_photo` tetap konfigurasi master untuk kebutuhan khusus (bukan berarti semua wajib foto). (Menutup CONF-012/013 terkait validasi, & Q-013.)

### BR-11 — Nilai status: ok / not_ok / na (ng dipetakan ke not_ok)
- **Status:** RESOLVED (kode + production DB + human decision)
- **Rule:** kode/UI memakai `ok`, `not_ok`, `na`; input legacy `ng` dipetakan ke `not_ok` saat submit; nilai tak dikenal → default `na`.
- **Behavior:** `match($status){ 'ok'=>'ok','not_ok'=>'not_ok','ng'=>'not_ok','na'=>'na', default=>'na' }`.
- **Evidence:** `submitChecklist`; seluruh `save*Grid`.
- **Affected Modules:** Checklist, Report, Evidence, Ranking.
- **Notes:** CONF-001 terselesaikan — **Production DB** (`eams_database.sql`, 2026-08-18): `checklist_logs.status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'` — konsisten dengan kode; migration lama sudah diubah di production. Q-001 RESOLVED.
- **Decision (Fase 1, docs/19 Q-001):** nilai status sah = `ok | not_ok | na`; NA = hasil valid bila `allow_na` (bukan pending/failure/late); periode = DONE bila seluruh pertanyaan punya hasil valid.

### BR-12 — NA hanya bila diizinkan item type
- **Status:** RESOLVED (Fase 1)
- **Rule:** opsi NA pada form tampil hanya bila `asset_item_types.allow_na` truthy.
- **Behavior:** form menyembunyikan/menampilkan opsi NA per item.
- **Evidence:** `app/Views/compliance/checklist/_form.php` (`!empty($inventory['allow_na'])`); kolom di-select di `checklist()`/`genericGrid()`.
- **Affected Modules:** Checklist.
- **Notes:** **production verified (Fase 0.6):** `allow_na tinyint(1) DEFAULT 0` (Q-003 RESOLVED).
- **Decision (Fase 1, docs/19 Q-001):** `allow_na=true` → OK/NOT_OK/NA diperbolehkan; `allow_na=false` → NA tidak diperbolehkan; NA diterima di **semua kanal** selama `allow_na` mengizinkan (menutup CONF-027 / Q-020 audit).

### BR-13 — checked_by = nama user (string)
- **Status:** RESOLVED (Fase 1)
- **Rule (legacy):** `checklist_logs.checked_by` diisi `session()->get('name')` (string), bukan user id.
- **Behavior (legacy):** laporan & ranking mengelompokkan berdasarkan string nama.
- **Evidence:** `submitChecklist`, semua `save*Grid`, `markAll*`.
- **Affected Modules:** Checklist, Report, Ranking.
- **Notes:** tabel lain (patrol) memakai INT user id → Q-006. **Production verified (Fase 0.6):** `checked_by varchar(100) DEFAULT NULL` — LEGACY DATABASE BEHAVIOR.
- **Decision (Fase 1, docs/19 Q-006):** Laravel memakai `checked_by_user_id` (FK `users.id`) + `checked_by_name` (snapshot nama saat checklist) — histori tetap tertelusur walau user berubah/inactive.

### BR-14 — Toilet checklist 3 slot waktu
- **Status:** CONFIRMED
- **Rule:** item toilet = checklist harian dengan 3 slot `PG` (Pagi) / `SI` (Siang) / `SO` (Sore); slot wajib dipilih; lock & dedup dilakukan per (inventory, periode, slot).
- **Behavior:** tanpa slot → form terkunci (`lockReason='slot'`); grid toilet menampilkan baris per slot.
- **Evidence:** konstanta `TOILET_CHECKLIST_ITEM_TYPE_ID=52`; `checklist()`, `submitChecklist`, `genericGrid`; `ComplianceReportController`.
- **Affected Modules:** Checklist, Report.
- **Notes:** **production verified (Fase 0.6):** `time_slot varchar(5) DEFAULT NULL` (Q-003 RESOLVED). **Decision terkait (Q-015):** identifikasi item toilet memakai `asset_item_types.code='TOILET'`, bukan id hard-coded 52.

### BR-15 — Grid "mark all" tidak menimpa data (dengan satu pengecualian)
- **Status:** CONFIRMED
- **Rule:** mark-all hanya mengisi sel kosong (skip existing); pesan: "Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa."
- **Behavior:** insert hanya untuk kombinasi (inventory, periode, pertanyaan) yang belum punya log.
- **Evidence:** semua `markAll*Grid` (guard `isset($existingMap[...])`).
- **Affected Modules:** Checklist grid.
- **Notes:** **PENGECUALIAN:** `markAllHeatDetectorGrid` **meng-update** existing ke `ok` (tidak skip) → CONF-009 / Q-024 (masih NEED HUMAN DECISION — desain Laravel menyiapkan flag per-grid).

### BR-16 — Grid "clear" menghapus log
- **Status:** CONFIRMED
- **Rule:** mode `clear` = hard delete baris log sel tsb; khusus CCTV, clear hanya menghapus status `ok`/`not_ok` — sel `na` ditolak (409, diarahkan ke halaman detail).
- **Behavior:** clear mengosongkan sel (kembali "belum diisi").
- **Evidence:** `saveCctvGrid`, `save*Grid` lainnya.
- **Affected Modules:** Checklist grid.
- **Notes:** —

### BR-17 — check_date ≠ selalu tanggal pengisian
- **Status:** CONFIRMED
- **Rule:** form submit → `check_date = hari ini`; grid daily (CCTV/FAC/gate) → `check_date = period_key`; grid weekly → `check_date = bulan-01`; grid monthly → `check_date = period_key.'-01'`.
- **Behavior:** nilai check_date berbeda tergantung kanal pengisian.
- **Evidence:** `submitChecklist`; `saveGenericGrid` (cabang per frekuensi); `saveCctvGrid`; `markAllIntrusionAlarmGrid`.
- **Affected Modules:** Checklist, Ranking, Report.
- **Notes:** memengaruhi perhitungan on-time (BR-18).

### BR-18 — On-time & skor ranking
- **Status:** CONFIRMED
- **Rule:** daily on-time bila `check_date ≤ period_key`; weekly on-time bila `check_date ≤ hari ke-7 minggu`; monthly on-time bila `check_date ≤ akhir bulan`; **skor = ontime×10 + late×3**.
- **Behavior:** leaderboard PIC diurutkan dari skor.
- **Evidence:** `ComplianceRankingController::index` + `weekEndDate`.
- **Affected Modules:** Ranking, Dashboard.
- **Notes:** —

### BR-47 — Dukungan status NA (konsisten di semua kanal)
- **Status:** RESOLVED (Fase 1)
- **Rule (teramati legacy):** (a) form per-item menerima `na` bila `allow_na`; (b) grid EL/EEL menerima mode `na`; (c) grid CCTV menolak ubah sel `na` (409); (d) grid lain tidak menerima mode `na` (mode dibatasi `ok|not_ok|clear`).
- **Evidence:** `saveEmergencyLightGrid`/`saveEmergencyExitLightGrid`; `saveCctvGrid` (409 utk na); `saveGenericGrid` & grid lain; `_form.php` (allow_na).
- **Affected Modules:** Checklist.
- **Decision (Fase 1, docs/19 Q-001):** NA diterima di **semua kanal** (standard + grid) selama `allow_na` item mengizinkan — ketidakkonsistenan legacy (CONF-027) ditiadakan.

**Kanal pengisian (CONFIRMED):** 4 kanal — (1) **form per-item** (STANDARD), (2) **12 grid khusus** per item type, (3) **generic grid**, (4) **form toilet 3-slot**. **Decision (Fase 1):** dua mode resmi **STANDARD & GRID** (Q-016); perilaku khusus item type by `code` (Q-015); kolom grid di-resolve via relasi/id stabil ke `checklist_master`, bukan teks pertanyaan (perbaikan desain, behavior sama).

---

## 6. Evidence & Follow-up

**Aturan (CONFIRMED):**
- Evidence center menampilkan temuan `not_ok` (beserta foto & remark) dari `checklist_logs`.
- Setiap temuan punya **follow-up**: `follow_up_status` (`open` → `monitoring` → `closed`), `follow_up_note`, `follow_up_date` — diupdate via `evidence/update-followup`.
- Foto disimpan di `public/uploads/checklist/` dengan nama acak.

- **Evidence:** `app/Controllers/ComplianceEvidenceController.php` (ajax/detail/updateFollowup); `app/Models/ChecklistLogModel.php` (allowedFields follow_up_*); `public/js/evidence.js`.
- **Affected Modules:** Checklist, Evidence, Report.
- **Notes:** **production verified (Fase 0.6):** `follow_up_status enum('open','monitoring','closed') DEFAULT 'open'`, `follow_up_note text`, `follow_up_date date` (Q-003 RESOLVED). Upload foto checklist tanpa validasi legacy → validasi terpusat di Laravel (Q-026 technical). **Decision terkait (Q-023):** Laravel menambah `checklist_log_histories` untuk audit trail perubahan.

---

## 7. Dashboard & Monitoring

**Aturan (CONFIRMED):**
- Home menampilkan tugas personal (pending checklist milik user) + notifikasi; badge sidebar = unread notifikasi + jumlah periode pending (cache 300 dtk) — BR-25.
- Progress monitoring: progres per inventory/PIC/periode; aksi "remind" mengirim pengingat.
- Ranking: skor ontime×10 + late×3 (BR-18).
- KPI dashboard: total inventory, status pie, trend, risk insight, pending checklist.

- **Evidence:** `HomeController`, `ComplianceProgressController`, `ComplianceRankingController`, `ComplianceDashboardController`; `BaseController::loadNotifications/calculateChecklistReminders`.
- **Affected Modules:** Home, Dashboard, Progress, Ranking.
- **Notes (konflik/gap):**
  - Definisi "late" pada KPI dashboard = **history-based** ≠ `is_period_late` time-based → CONF-014 / Q-019 (masih NEED HUMAN DECISION).
  - Route `compliance/dashboard/risk-trend` & `.../data` menunjuk method yang tidak ada → CONF-006.
  - View `compliance/dashboard/overdue.php` yatim → CONF-028.
- **Decision (Fase 1, docs/19 Q-009):** Risk Trend endpoint/dashboard yang dead **TIDAK dibawa** ke Laravel; jangan mereplikasi dead feature. (Menutup Q-009.)

---

## 8. IT Asset & Device Monitoring

### BR-31 — Assignment asset IT
- **Status:** CONFIRMED
- **Rule:** assign = tutup assignment aktif (`returned_at=now`) → insert assignment baru; employee harus aktif; butuh permission write. Bila status asset di-update menjadi `rusak` → semua assignment aktif di-return otomatis.
- **Evidence:** `ITAssetController::assignSave/update`.
- **Affected Modules:** IT Assets, Employees.
- **Notes:** —

### BR-32 — Employee tidak bisa dihapus bila punya assignment aktif
- **Status:** CONFIRMED
- **Rule:** delete employee ditolak bila masih ada assignment aktif; bila ada riwayat (non-aktif) disarankan ubah status jadi nonaktif.
- **Evidence:** `EmployeeController::delete`.
- **Affected Modules:** Employees, IT Assets.
- **Notes:** —

### BR-33 — Device health score
- **Status:** CONFIRMED
- **Rule:** skor 100 − penalti: pending_updates >20→−30, ≥5→−15, ≥1→−5; OS not activated →−25; storage free <10%→−25, <20%→−10; cpu_usage>90→−10; last_seen >72 jam→−25, >24 jam→−10. Label: ≥80 Sehat, ≥50 Waspada, <50 Kritis.
- **Evidence:** `app/Helpers/device_helper.php device_risk_score/device_risk_label`.
- **Affected Modules:** IT Devices (Device Control UI).
- **Notes:** legacy alternatif `it_health_helper` (Win7 −40 dst.) → §17.

### BR-34 — Ambang online/offline device
- **Status:** RESOLVED (Fase 1)
- **Rule (legacy, inkonsisten):** helper UI `device_is_online` = online bila `now − last_seen ≤ max(172800 dtk, 2×interval)` (default ≥48 jam); command `it:status` menandai offline bila `> 600 dtk`.
- **Evidence:** `app/Helpers/device_helper.php`; `app/Commands/DeviceStatusCheck.php`.
- **Affected Modules:** IT Devices.
- **Decision (Fase 1, docs/19 Q-012):** device **ONLINE** bila heartbeat terakhir ≤ **10 menit** (> 600 dtk → OFFLINE); satu **centralized configuration**, tanpa threshold berbeda antara dashboard/helper/status-checker. (Menutup CONF-008 / Q-012.)

### BR-35 — Identitas agent & auto-register
- **Status:** CONFIRMED
- **Rule:** device dikenali berurutan: `device_token` → `mac_address` → `hostname`; heartbeat pertama membuat device + asset `IT-PC-###` (category_id=1, status 'aktif').
- **Evidence:** `Api\AgentController::heartbeat/findDeviceByIdentity/generateInventoryNo`.
- **Affected Modules:** IT Devices, IT Assets.
- **Notes:** asumsi `category_id=1` = kategori IT → Q-016 (masih NEED HUMAN DECISION — butuh data produksi; Laravel lookup by kode). Agent API publik (CSRF-exempt); **menerima payload via GET** → CONF-025 / Q-025 (masih NEED HUMAN DECISION). State besar disimpan di kolom JSON `it_devices.cpu`.

### BR-36 — Remote command queue
- **Status:** CONFIRMED
- **Rule:** 1 command antrian per device; `remote_lock_until = now + 25 dtk`; push ke `{{http://{ip}}}:8765/command` dengan device_token; gagal → fallback polling; whitelist aksi: restart, shutdown, update, sync, restart_agent, lock, logoff, popup_message.
- **Evidence:** `ITDeviceController::queueRemoteCommand/remoteAction`; `Api\AgentController::command/popQueuedCommand`.
- **Affected Modules:** IT Devices.
- **Notes:** installer agent `EAMSAgent[Setup][-win7|-xp]-X.Y.Z.exe` di `public/downloads/agent`.

**Gap (CONFIRMED, Fase 0.6 + Fase 1):** `ItDeviceLogModel` menunjuk tabel `it_device_logs` yang **TIDAK ADA di production DB** dan tidak dipakai controller → dead model (CONF-DB-003). **Decision (Q-014):** tidak dibawa ke Laravel.

---

## 9. Reports & PDF

**Aturan (CONFIRMED):**
- Print/report per item type via `print_by_item/{type}` dengan **12 form print khusus + generic**.
- Batch print per kategori/periode; export Excel pada beberapa report.
- PDF: mPDF via wrapper, transisi ke Dompdf; template di `app/Views/pdf/`.
- Report checklist membaca status `ok`/`not_ok`/`na` (BR-11).
- Export PDAM/Boiler mewarnai hari libur (aturan offday BR-07) + total bulanan.

- **Evidence:** `ComplianceReportController`; `app/Views/pdf/*`; `app/Libraries/` (PDF wrapper); `docs/11-reports-pdf.md`.
- **Affected Modules:** Report, PDF, Export.
- **Decision (Fase 1):**
  - **Q-008:** PDF Compliance hanya untuk **Admin** + **user dengan akses Compliance**, via **permission-based** Gate/Policy (bukan hard-code role). (Menutup CONF-005 / Q-008.)
  - **Q-015:** form print di-resolve via `asset_item_types.code` (bukan konstanta id); kolom via relasi stabil (bukan teks pertanyaan).
  - **Q-018:** expiry field tampil terutama untuk APAR (by config), tidak auto-mengubah status.
  - **Q-022:** file/attachment di storage configurable.

---

## 10. Notifications

### BR-23 — Reminder mingguan (WA & email)
- **Status:** CONFIRMED
- **Rule:** command CLI `notify:weekly-checklist` (WA Fonnte) & `notify:weekly-checklist-email` (in-app + email) mengirim ke user aktif yang punya pending checklist pada periode berjalan; maks 15 item/pesan; email command membuat notifikasi `reminder` dgn dedupe `weekly_email_reminder:{date}:{userId}` (tidak kirim WA ulang).
- **Behavior:** penerima ditentukan dari PIC pending checklist.
- **Evidence:** `app/Commands/WeeklyChecklistWhatsappReminder.php`, `WeeklyChecklistEmailReminder.php`, `app/Libraries/NotificationService.php`.
- **Affected Modules:** Notifications, Home, Progress.
- **Notes:** **Decision terkait (Q-007):** penerima PIC memakai **relasi `compliance_inventory_pics`** sebagai source of truth (bukan parsing nama).

### BR-24 — Notifikasi in-app idempoten multi-kanal
- **Status:** CONFIRMED
- **Rule:** tabel `notifications` dengan `dedupe_key` unik (idempoten); setiap notifikasi mencoba email (bila `notification_email_enabled=1` & SMTP valid) & WA webhook (bila `notification_whatsapp_enabled=1` & nomor ada); status kanal dicatat (`sent|failed|disabled|missing_target|skipped`).
- **Evidence:** `app/Libraries/NotificationService.php`; migration `notifications`.
- **Affected Modules:** seluruh modul (cross-cutting).
- **Notes:** template pesan di `app_settings` dgn placeholder `{{name}}/{{title}}/{{message}}/{{url}}/{{company}}/{{date}}`.

### BR-25 — Badge sidebar = unread + pending checklist
- **Status:** CONFIRMED
- **Rule:** badge = unreadCount + jumlah periode pending milik user (cache 300 dtk).
- **Evidence:** `BaseController::loadNotifications/calculateChecklistReminders`.
- **Affected Modules:** UI global.
- **Notes:** —

---

## 11. EMS / FDM

### BR-26 — Faktor emisi GHG
- **Status:** CONFIRMED
- **Rule:** Listrik 0.87 kgCO2e/kWh; Solar 2.69 kg/L; LPG 2.984 kg/kg; Scrap 1.8 kg/kg; Petrol 2.28 kg/L. Emission (ton) = konsumsi × faktor ÷ 1000. Scope 1 = stationary + mobile; Scope 2 = listrik (market-based); grand total = S1 + S2.
- **Evidence:** `EmsReportController` konstanta faktor.
- **Affected Modules:** EMS.
- **Notes:** —

### BR-27 — Rentang laporan EMS/FDM
- **Status:** CONFIRMED
- **Rule:** tahun 2026–2030 (diperluas hingga tahun berjalan); baseline 2026; target label "-2% s/d -5%" (hard-coded); Water punya seed 2025 (data nyata) sebagai pembanding.
- **Evidence:** `EmsReportController`; migration/seed EMS.
- **Affected Modules:** EMS, FDM.
- **Notes:** label target hard-coded → kandidat setting di Laravel (catatan).

**FDM (CONFIRMED):** sub-fitur "Production Section" aktif; 2 sub-fitur lain **placeholder "soon"**. **Legacy:** `ensureYears()` INSERT tahun berjalan s.d. +4 **saat GET** (write-on-read) → CONF-023 / §17 (hilangkan di Laravel).

---

## 12. Utility / Boiler / IPAL / PDAM

### BR-28 — PDAM 1 entri per tanggal
- **Status:** CONFIRMED
- **Rule:** `pdam_water_logs.log_date` & `pdam_water_boiler_logs.log_date` UNIQUE; save = upsert by tanggal; role: admin/compliance/office.
- **Evidence:** migration PDAM; `PdamWaterController`, `PdamWaterBoilerController`.
- **Affected Modules:** PDAM.
- **Notes:** —

### BR-29 — Boiler multi-entri per hari
- **Status:** CONFIRMED
- **Rule:** beberapa entri per hari; index menampilkan SUM(polybag), SUM(kg) per tanggal; export bulanan menandai hari libur merah + total.
- **Evidence:** `BoilerFuelController`.
- **Affected Modules:** Boiler.
- **Notes:** tabel `boiler_fuel_logs` verified di production (Q-002 RESOLVED Fase 0.6).

### BR-30 — IPAL upsert per tanggal
- **Status:** CONFIRMED
- **Rule:** upsert per tanggal; field: start/stop meter, pemakaian, keterangan.
- **Evidence:** `IpalController::save`; **Production DB (Fase 0.6):** `UNIQUE KEY unique_log_date (log_date)`.
- **Affected Modules:** IPAL.
- **Notes:** **koreksi Fase 0.6** — production MENEGAKKAN unique per tanggal (CONF-DB-004).

---

## 13. Patrol Security

### BR-37 — Sesi patrol
- **Status:** CONFIRMED
- **Rule:** 1 sesi aktif per user per hari; rute baru ditolak (409) selama ada sesi aktif; sesi `completed` otomatis saat semua checkpoint dicek; `canceled` manual.
- **Evidence:** `PatrolController::startSession/scanCheckpoint/cancelSession`.
- **Affected Modules:** Patrol.
- **Notes:** rute CP1–CP4 (Sukabumi), forward/reverse; editor layout hanya admin.

### BR-38 — Scan checkpoint
- **Status:** CONFIRMED
- **Rule:** wajib urut sesuai rute (`nextCheckpoint`); barcode cocok (normalize uppercase tanpa spasi); **foto wajib ≥1**; **GPS wajib dalam `radius_m`** (default 10 m, haversine); status ok/not_ok + note opsional.
- **Evidence:** `PatrolController::scanCheckpoint`.
- **Affected Modules:** Patrol.
- **Notes:** —

---

## 14. Backup

### BR-39 — Backup & retensi
- **Status:** CONFIRMED
- **Rule:** 3 jenis (database/files/full); penamaan `backup-{database|file|penuh|harian}-Ymd-His`; **retensi 30 hari**; full zip = `database.sql` + `manifest.json` + uploads; restore DB via mysqli multi_query; auto harian 01:00 via Windows `schtasks` "EAMS Daily Backup"; path Windows `D:\EAMS-Backups`.
- **Evidence:** `app/Libraries/BackupManager.php`; `BackupController`; `app/Commands/DailyBackup.php`.
- **Affected Modules:** Administration.
- **Notes:** **Decision terkait (Q-022):** path backup **configurable** (tidak hard-code `D:\`); mekanisme di Laravel = Scheduler (menggantikan schtasks).

---

## 15. Settings

**Aturan (CONFIRMED):**
- `app_settings` key-value menyimpan: profil perusahaan (nama, logo), SMTP (Google Workspace), WhatsApp/Fonnte (token, nomor, webhook), template pesan (placeholder `{{name}}/{{title}}/{{message}}/{{url}}/{{company}}/{{date}}`), flag `notification_email_enabled` / `notification_whatsapp_enabled`.
- Self-service user: ganti password & kontak, tandai notifikasi terbaca — lewat halaman Settings (POST).
- Nilai default diseed migration; nilai aktual produksi (token, template) ada di DB produksi → Q-018 (masih NEED HUMAN DECISION — butuh data produksi).

- **Evidence:** `SettingController`; migration seeder `app_settings`; `docs/13-dependencies.md`.
- **Affected Modules:** seluruh modul (konfigurasi).
- **Notes:** self-service untuk user read-only terblokir WriteFilter → BR-43 / Q-021 (masih NEED HUMAN DECISION).

---

## 16. Shared Business Rules (lintas modul)

Aturan yang dipakai banyak modul sekaligus (CONFIRMED):

| # | Rule | Evidence | Dipakai oleh |
|---|---|---|---|
| S-01 | Period engine: period_key + late + editable + offday | `period_helper.php`, `checklist_helper.php` | Checklist, Home, Progress, Ranking, Reminder, Report |
| S-02 | Anti-duplikat per periode (BR-09) | `submitChecklist`, `save*Grid` | Checklist |
| S-03 | not_ok wajib remark/foto (BR-10; grid bypass — Q-013) | `submitChecklist`, `checklist.js` | Checklist, Evidence |
| S-04 | Nilai status ok/not_ok/na, ng→not_ok (BR-11) | `submitChecklist` | Checklist, Report, Evidence, Ranking |
| S-05 | checked_by = user_id + snapshot nama (Q-006) | semua penulis log | Checklist, Report, Ranking |
| S-06 | Notifikasi idempoten via dedupe_key (BR-24) | `NotificationService` | Semua modul |
| S-07 | WriteFilter global utk read-only (BR-42) | `WriteFilter` | Semua modul |
| S-08 | Otorisasi tiga lapis (BR-41) & menu via page_access (BR-44) | `access_helper` | Semua modul |
| S-09 | Offday & holidays (BR-07; Saturday effective 2026-04-01 — Q-005) | `checklist_helper` | Checklist, PDAM, Boiler, Calendar |
| S-10 | Session 8 jam & audit auth (BR-40) | `AuthController`, `Session.php` | Global |
| S-11 | CSRF aktif; pengecualian: `/api/agent/*`, kuesioner publik | `Filters.php`, `Security` config | Agent API, Questionnaire |
| S-12 | Upload foto → storage configurable, nama acak (Q-022) | semua controller upload | Inventory, Checklist, Users, Thermal, Patrol |
| S-13 | Pagination default 20 (bisa dioverride `perPage`) | `index()` berbagai controller | Listing |
| S-14 | Timezone Asia/Jakarta & format tanggal `Y-m-d`/`Y-m` konsisten | `App.php`, helpers | Global |
| S-15 | Unified period status DONE/OPEN/LATE/FUTURE/HOLIDAY (Q-004) | engine periode (Fase 1) | Checklist, Dashboard, Calendar, Home |
| S-16 | Item type behavior by `code`, bukan hard-id (Q-015) | `asset_item_types.code` | Checklist, Report, Grid |

---

## 17. Known Legacy Behaviors

Pemisahan tegas **BUSINESS RULE vs LEGACY IMPLEMENTATION vs TECHNICAL DEBT**. Technical debt **bukan** business requirement dan **tidak** dibawa ke Laravel apa adanya.

| Area | BUSINESS RULE (dipertahankan) | LEGACY IMPLEMENTATION | TECHNICAL DEBT (jangan diwarisi) |
|---|---|---|---|
| Periode weekly | Weekly dibagi W1–W4 per bulan | Helper rentang tanggal 1–7/8–14/15–21/22–akhir | Dua helper `generate_calendar_periods` — CONF-022 (digabung, Q-004) |
| Status periode | 1 engine status kanonik | `period_helper::resolve_period_status` | Fungsi ganda `period_status_helper` — CONF-002 (RESOLVED Q-004) |
| Status checklist | ok / not_ok / na; ng→not_ok | Kode menulis `not_ok` | Migration ENUM `ok/ng/na` tidak cocok — RESOLVED (Q-001) |
| Offday | Minggu libur; Sabtu libur mulai 2026-04-01; + holidays | `is_weekend_offday` dgn tanggal efektif | `is_holiday` legacy masih terautoload — CONF-003 (RESOLVED Q-005) |
| checked_by | Pencatat checklist tercatat (user + nama) | String nama session | Bukan FK user — RESOLVED Q-006 (user_id + snapshot) |
| PIC | Maks 2 PIC, kedudukan sama, notifikasi assignment | Kolom teks `pic` " - " + relasi pics | Dua mekanisme — RESOLVED Q-007 (pics = SoT, tanpa is_primary) |
| QR | QR = URL detail + label kode aset | Gambar dari `api.qrserver.com` + overlay GD | Dependency eksternal — Q-021 (URL tetap; image boleh regenerate via paket lokal) |
| Koreksi grid | Koreksi sel mengubah log existing | `save*Grid` menulis `updated_at` | `updated_at` dibuang & kolom tak ada — RESOLVED Q-023 (tambah updated_at + history table) |
| Mark-all | Isi massal hanya sel kosong | Guard `isset($existingMap)` | `markAllHeatDetectorGrid` menimpa ok — Q-024 (masih NHD) |
| NA | NA bila item mengizinkan (allow_na) | Form via `allow_na` | Grid tak konsisten terima na — RESOLVED Q-001 (NA di semua kanal bila allow_na) |
| Late | Periode lewat ambang dihitung late | `is_period_late` (+21/+28/+3 bln) | KPI dashboard history-based — Q-019 (masih NHD) |
| Grid khusus | Item type punya grid kustom | Konstanta `item_type_id` hard-coded | Pemetaan kolom dari teks pertanyaan — Q-015 (by code; kolom via relasi) |
| Inventory list | Daftar + filter + sort | Join kategori/item/area | `getBaseQuery` join via `item_name` — CONF-018 (perbaiki di Laravel) |
| Frekuensi | Frekuensi per item type | `asset_item_types.checklist_frequency` | Kolom `checklist_master.frequency` diabaikan — CONF-021 (drop) |
| PDF | Report dapat dicetak PDF (admin + akses Compliance) | mPDF → Dompdf | `PdfAccessFilter` tak terpasang — RESOLVED Q-008 (permission-based) |
| Error 403 | Akses ditolak dialihkan | `redirect('/unauthorized')` | Route tak terdaftar → 404 — Q-010 (technical: Laravel 403 view) |
| Kalender standalone | Kalender menampilkan event | `ComplianceCalendarController` + view | Controller tak ter-rute; feed tak ada — CONF-019 (tabel events AKTIF via Holidays) |
| Dashboard | KPI + grafik (fitur aktif saja) | `ComplianceDashboardController` | Route risk-trend/data → method tak ada — RESOLVED Q-009 (tidak dibawa) |
| FDM | Data produksi per tahun | `ensureYears()` isi tahun s.d. +4 | INSERT saat GET (write-on-read) — CONF-023 (hilangkan) |
| Kuesioner | 2 template default | Bootstrap di constructor | Tulis DB saat construct — CONF-024 (pindah ke seeder) |
| Agent API | Agent heartbeat/command/update | Endpoint publik CSRF-exempt, token plaintext | Mutasi via GET — Q-025 (masih NHD) |
| Read-only | User read-only tidak bisa mutasi | `WriteFilter` global | Self-service ikut terblokir — Q-021 (masih NHD) |
| Upload foto | Bukti foto disimpan (storage configurable) | Validasi mime/size di beberapa modul | Foto checklist tanpa validasi — Q-026 (technical: validasi terpusat) |
| Integritas DB | Relasi antar tabel | FK pada migration baru | `notifications` tanpa FK; kebijakan tak konsisten — Q-022 (technical: strategi FK schema baru) |
| Famili checklist | (tidak ada — legacy murni) | — | Famili `compliance_checklist_*` tak ada di production — RESOLVED Q-014 (tidak dibawa) |

**Dead code terkonfirmasi (tidak dibawa ke Laravel):** `Home.php`, `ComplianceChecklistController`, `ComplianceCalendarController`, `AssetItemTypeController::byCategory`, `exportPeriodePage`, `qrBatch`, view `dashboard.php` (0 byte), `welcome_message.php`, `overdue.php`, route `compliance/inventory/create` & `edit/(:num)`, route `compliance/calendar/events`, route `risk-trend`/`data` (Q-009), filter `pdfAccess` (diganti permission-based), tabel `it_device_logs`, famili `compliance_checklist_*` (Q-014), kolom `checklist_master.frequency`, helper `it_health_helper` & `period_status_helper` & `calender_period_helper`.

---

## 18. Unresolved Decisions

Seluruh keputusan manusia terdaftar di **`docs/15-ambiguities-need-decision.md`** (Final Decision List); keputusan final lengkap di **`docs/19-decision-log.md`**. Ringkasan status **setelah Fase 1**:

| Status | Jumlah | Item |
|---|---|---|
| **RESOLVED** | 15 | Q-001, Q-002, Q-003, Q-004, Q-005, Q-006, Q-007, Q-008, Q-009, Q-012, Q-013, Q-014, Q-017, Q-020, Q-023 |
| **TECHNICAL DECISION** (architecture phase, `docs/20`) | 3 | Q-010, Q-022, Q-026 |
| **NEED HUMAN DECISION** (tersisa) | 8 | Q-011, Q-015, Q-016, Q-018, Q-019, Q-021, Q-024, Q-025 |

**Total: 26 item — 15 terselesaikan (5 via production schema Fase 0.6 + 10 via human decision Fase 1), 3 dipindahkan ke architecture phase, 8 masih membutuhkan keputusan manusia.** Rule yang diputuskan stakeholder telah diberi field **Decision** pada bagian masing-masing.

---

## 19. Module Dependency & Rebuild Order

> Murni dari dependency hasil audit (bukan preferensi framework). Detail: `docs/16-laravel-migration-considerations.md`.

```
Authentication & Users
  ↓
Authorization (roles, permission, page_access) + Audit Logs + Settings (app_settings)
  ↓
Master Data (areas, inventory_categories, asset_item_types, holidays)
  ↓                                                                     (holidays dipakai engine periode!)
Compliance Inventory (+ PIC, QR)
  ↓
Checklist Master + Period Engine + Checklist Logs
  ↓
Checklist Channels (form per-item → generic grid → 12 grid khusus)
  ↓
Evidence & Follow-up
  ↓
Home / Dashboard / Progress / Ranking
  ↓
Reports & PDF / Print / Export
  ↓
Notifications & Reminders (WA/email, dedupe)
  ↓
Modul independen: Calendar, Thermal Imaging, Questionnaire, Boiler, IPAL, PDAM, EMS, FDM, IT Assets, Patrol
  ↓
IT Device Monitoring + Agent API (kontrak /api/agent/* WAJIB identik)
  ↓
Backup & Admin tools (schtasks → Laravel Scheduler)
```

**Urutan implementasi yang aman (berdasarkan graph di atas):**
1. Fondasi: users, roles, auth/session, page_access → middleware; audit_logs, login_sessions
2. Settings & branding (app_settings) + NotificationService
3. Master data: areas, inventory_categories, asset_item_types, **holidays**
4. Compliance inventory + PIC + QR
5. Checklist master + engine periode + checklist_logs
6. Kanal pengisian: form (standard) → generic grid → grid khusus (prioritas daily: CCTV, First Aid Content, Gate)
7. Home, dashboard, progress, ranking, evidence
8. Report + PDF/print + export Excel
9. Reminder & notifikasi (WA/email, dedupe)
10. Kalender, thermal imaging, kuesioner
11. Boiler / IPAL / PDAM
12. EMS / FDM
13. IT assets / employees
14. IT devices + agent API (kontrak identik!)
15. Audit logs viewer, login sessions, backup

---

## Appendix A — Contradiction Register

Setiap konflik ditemukan saat cross-check Fase 0.5. Format: Topic / Source A / Source B / Conflict / Current Runtime Behavior / Business Meaning. (Fase 0.6 & Fase 1: banyak yang terselesaikan — ditandai RESOLVED dengan sumbernya.)

Ringkasan 28 konflik (CONF-001 s/d CONF-028) dengan status setelah Fase 1:

| ID | Topic | Status (setelah Fase 1) |
|---|---|---|
| CONF-001 | ENUM `checklist_logs.status` (ok/ng/na vs not_ok) | ✅ RESOLVED — production ENUM ok/not_ok/na (Q-001) |
| CONF-002 | Engine status periode ganda | ✅ RESOLVED — satu engine DONE/OPEN/LATE/FUTURE/HOLIDAY (Q-004) |
| CONF-003 | Aturan Sabtu (is_weekend_offday vs is_holiday) | ✅ RESOLVED — Saturday effective 2026-04-01, tidak retroaktif (Q-005) |
| CONF-004 | Sumber kebenaran PIC (relasi vs nama) | ✅ RESOLVED — pics = SoT, maks 2, tanpa primary (Q-007) |
| CONF-005 | pdfAccess tak terpasang | ✅ RESOLVED — PDF utk admin + akses Compliance, permission-based (Q-008) |
| CONF-006 | Route dashboard → method hilang (risk trend) | ✅ RESOLVED — tidak dibawa (Q-009) |
| CONF-007 | `/unauthorized` tidak terdaftar | TECHNICAL — Laravel halaman 403 (Q-010) |
| CONF-008 | Threshold online 600s vs 48 jam | ✅ RESOLVED — 10 menit, centralized config (Q-012) |
| CONF-009 | Mark-all Heat Detector menimpa | NEED HUMAN DECISION (Q-024) |
| CONF-010 | `updated_at` dead write di grid | ✅ RESOLVED — kolom tak ada di production; Laravel tambah updated_at + history (Q-023) |
| CONF-011 | FK ke users (audit_logs vs notifications) | TECHNICAL — strategi FK schema baru (Q-022) |
| CONF-012 | Foto checklist tanpa validasi | TECHNICAL — validasi upload terpusat (Q-026) |
| CONF-013 | updatePhoto tanpa cek size | TECHNICAL — validasi upload terpusat (Q-026) |
| CONF-014 | Definisi late (time vs history) | NEED HUMAN DECISION (Q-019) |
| CONF-015 | check_date per kanal | terdokumentasi (BR-17) — ikut desain ranking |
| CONF-016 | Model vs migration compliance_checklist | ✅ RESOLVED — famili tak ada di production (Q-014) |
| CONF-017 | Dua famili tabel checklist | ✅ RESOLVED — hanya `checklist_*` di production (Q-014) |
| CONF-018 | getBaseQuery join via item_name | jelas (legacy bug-risk) — perbaiki di Laravel (by id/code) |
| CONF-019 | Feed kalender standalone tanpa route | jelas (dead); tabel events AKTIF via Holidays (koreksi Fase 0.6) |
| CONF-020 | Route create/edit → method hilang | jelas (dead routes) — kanonik = modal workflow |
| CONF-021 | Kolom checklist_master.frequency tak dipakai | jelas (legacy column) — drop (Q-003 policy) |
| CONF-022 | generate_calendar_periods ganda | ✅ RESOLVED — satu engine periode (Q-004) |
| CONF-023 | FDM write-on-read (ensureYears) | terdokumentasi (legacy) — hilangkan di Laravel |
| CONF-024 | Kuesioner bootstrap di constructor | terdokumentasi (legacy) — pindah ke seeder |
| CONF-025 | Agent API mutasi via GET | NEED HUMAN DECISION (Q-025) |
| CONF-026 | Read-only self-service terblokir | NEED HUMAN DECISION (Q-021) |
| CONF-027 | Dukungan NA per kanal | ✅ RESOLVED — NA di semua kanal bila allow_na (Q-001) |
| CONF-028 | View overdue yatim | jelas (dead view) |

> Detail lengkap tiap CONF (Source A/B, Conflict, Runtime Behavior) tersedia di repo `eams-v4` → `docs/17-business-specification.md` Appendix A (versi lengkap).

---

## Appendix B — Module Completeness Checklist (cross-check Fase 0.5, diperbarui Fase 1)

| Module | Lengkap? | Catatan gap (setelah Fase 1) |
|---|---|---|
| M-01 Authentication | ✅ | — |
| M-02 Home & Notifications | ✅ | — |
| M-03 Master Data | ✅ | kolom verified di production (Q-003) |
| M-04 Compliance Inventory + QR | ⚠️ | route create/edit dead (modal kanonik); QR URL tetap (Q-021) |
| M-05 Checklist Master | ⚠️ | kolom `frequency` tak dipakai (drop — Q-003) |
| M-06 Checklist Execution | ⚠️ | engine periode digabung (Q-004); NA konsisten (Q-001); 2 mode resmi (Q-016) |
| M-07 Compliance Dashboard | ⚠️ | risk-trend dead tidak dibawa (Q-009); definisi late (Q-019 NHD); view overdue yatim |
| M-08 Progress Monitoring | ✅ | PIC via relasi (Q-007) |
| M-09 Ranking | ✅ | checked_by user_id+snapshot (Q-006) |
| M-10 Evidence & Follow-up | ✅ | kolom verified (Q-003); + history table (Q-023) |
| M-11 Report & PDF/Print | ⚠️ | PDF permission-based (Q-008); kolom via code/relasi (Q-015) |
| M-12 Calendar | ⚠️ | controller standalone dead; tabel events AKTIF via Holidays (koreksi Fase 0.6) |
| M-13 Thermal Imaging | ✅ | — |
| M-14 Questionnaire | ⚠️ | bootstrap di constructor → pindah seeder (CONF-024) |
| M-15 EMS / GHG | ✅ | — |
| M-16 FDM | ⚠️ | 2/3 sub-fitur placeholder "soon"; write-on-read (CONF-023) |
| M-17 Boiler / IPAL / PDAM | ✅ | tabel verified (Q-002) |
| M-18 IT Assets & Employees | ✅ | — |
| M-19 IT Device Monitoring | ⚠️ | it_device_logs tidak dibawa (Q-014); threshold 10 mnt (Q-012); category_id=1 (Q-016 NHD); GET mutating (Q-025 NHD) |
| M-20 Patrol Security | ✅ | — |
| M-21 Backup | ⚠️ | schtasks → Laravel Scheduler; path configurable (Q-022) |
| M-22 Administration (Users/Audit/Settings) | ✅ | — |

**Ringkasan (Fase 1):** 16 modul lengkap, 6 ⚠️, 0 ❌ (M-07 & M-19 naik dari ❌ ke ⚠️ setelah keputusan menutup gap utama). Tidak ada modul hilang total.

---

## Appendix C — Database & Route Gap Register (diperbarui Fase 1)

### C.1 Database gaps
1. **Tabel dasar — RESOLVED:** production export memverifikasi DDL lengkap; `it_device_logs` & famili `compliance_checklist_*` terbukti tidak ada (Q-002/Q-014).
2. **Kolom tanpa migration — RESOLVED:** seluruhnya verified dari production (Q-003); `checklist_logs.updated_at` tidak ada → Laravel menambah `updated_at` + history table (Q-023).
3. **ENUM:** `checklist_logs.status` RESOLVED (Q-001); `compliance_inventory.status` → enum GOOD/NEED_REPAIR/NOT_ACTIVE (Q-017).
4. **FK:** kebijakan tak konsisten legacy → strategi FK schema baru (Q-022 technical).
5. **Unique:** `asset_code`, `users.username`, `ipal_logs.log_date` UNIQUE verified; dedup checklist tetap application-level (candidate UNIQUE di Laravel, `docs/20` §5).
6. **Nullable/default:** seluruhnya diketahui dari production (docs/03).

### C.2 Route/controller gaps
1. **Route → method hilang:** `risk-trend`, `data` (tidak dibawa — Q-009), `inventory/create`, `edit/(:num)` (modal kanonik).
2. **Method tanpa route:** `qrBatch`, `exportPeriodePage`, `ComplianceChecklistController`, `ComplianceCalendarController`, `byCategory`, `Home::index` (tidak dibawa).
3. **UI → endpoint tak ada:** `dashboard.js` → risk-trend/data; kalender → `calendar/events`; `overdue.php` → `inventory/{id}`.
4. **View yatim:** `overdue.php`, `dashboard.php` (0 byte), `welcome_message.php`, `export_periode.php`.
5. **Inkonsistensi permission:** PDF → permission-based (Q-008); `/unauthorized` → Laravel 403 (Q-010 technical).

---

> **Traceability:** setiap BR merujuk file+method sumber; setiap konflik merujuk dua sumber; setiap keputusan mengarah ke `docs/15`/`docs/19`. Tidak ada rule tanpa asal.
> **Prinsip:** Legacy Code → Audit → Cross-check → Production Verification → **Human Decision (`docs/19`)** → **Business Specification (dokumen ini)** → Architecture Design (`docs/20`) → Architecture Review → Implementation. **Jangan melompati tahapan.**
