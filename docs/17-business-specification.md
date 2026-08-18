# 17 — EAMS Business Specification (Canonical)

> **Status dokumen:** SOURCE OF TRUTH perilaku bisnis EAMS — hasil konsolidasi & cross-check **Fase 0.5** (2026-08-18) atas seluruh dokumen audit Fase 0 (`docs/00`–`docs/16`), dengan evidence **production database** dari **Fase 0.6** (`eams_database.sql`, MariaDB 10.4.32 — lihat `docs/18`).
> **Isi:** hanya behavior `CONFIRMED` (dengan evidence kode) atau yang secara eksplisit ditandai **UNRESOLVED** (dirujuk ke register keputusan `docs/15`).
> **Penomoran:** `BR-01..BR-40` diwarisi dari `docs/09-business-rules.md` (traceability); `BR-41..BR-47` adalah rule baru hasil review Fase 0.5. Kontradiksi antar sumber dicatat sebagai `CONF-xxx` (Appendix A); keputusan manusia sebagai `Q-xxx` (`docs/15`).
> **Aturan status:** `CONFIRMED` = terbukti dari kode/database; `AMBIGUOUS` = bukti saling konflik, **tidak diputuskan di dokumen ini**; aspek `INFERRED`/`UNKNOWN` tidak dijadikan rule mandiri — dicatat sebagai Notes atau Q-item.

---

## 1. System Overview

EAMS (Enterprise Asset & Compliance Management System) adalah aplikasi internal PT YHS (pabrik garmen, Sukabumi) yang dibangun sebagai **monolit CodeIgniter 4** dengan server-rendered views + AJAX.

**Inti bisnis (CONFIRMED):** compliance checklist fasilitas pabrik — inventory fasilitas → pertanyaan checklist per item type → log pengisian per periode (daily/weekly/monthly) → monitoring (home, progress, ranking) → report/PDF. Di sekelilingnya: patrol security (barcode + GPS + foto), IT asset & device monitoring (EAMS Agent Windows → `/api/agent/*`), utility logs (Boiler/IPAL/PDAM), EMS/GHG report, FDM data collection, kuesioner publik, thermal imaging, notifikasi multi-kanal (in-app/email/WA Fonnte), dan backup system.

**Stack (CONFIRMED):**
| Aspek | Nilai | Evidence |
|---|---|---|
| Framework | CodeIgniter 4 (folder `system/` di-commit ke repo) | `composer.json`, `app/Config/Autoload.php` |
| PHP | ^8.1 | `composer.json` |
| DB | MySQL/MariaDB via MySQLi; DB default `asset_compliance_system` | `app/Config/Database.php` |
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
- **Status:** CONFIRMED (perilaku) — **intent AMBIGUOUS → Q-021**
- **Rule (teramati):** karena halaman Settings self-service (ganti password, kontak, tandai-notifikasi-terbaca) memakai POST ke `settings/change-password` dan path `/settings` tidak masuk whitelist WriteFilter, user read-only ikut terblokir.
- **Behavior:** read-only user hanya bisa melihat; semua POST miliknya (termasuk milik dirinya sendiri) 403.
- **Evidence:** `app/Filters/WriteFilter.php` (publicPrefixes tidak memuat `/settings`); `app/Controllers/SettingController.php`.
- **Affected Modules:** Settings, Notifications.
- **Notes:** **NEED HUMAN DECISION** — by design atau oversight (Q-021). Jangan diputuskan di sini.

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

### BR-20 — QR code = URL detail, gambar dari API eksternal
- **Status:** CONFIRMED
- **Rule:** QR berisi `base_url('compliance/inventory/detail/{id}')`; PNG 300×300 diunduh dari `api.qrserver.com`, lalu asset code ditulis di tengah (GD, font 5, box putih); file `public/uploads/qr/qr_inv_{id}.png`.
- **Behavior:** generate saat store; regenerate saat asset_code berubah / via tombol regenerate / regen per album.
- **Evidence:** `app/Services/QrService.php`; `ComplianceInventoryController::store/update/regenerateQr/qrAlbumRegen`.
- **Affected Modules:** Compliance Inventory, QR Center, Patrol (praktik serupa), mobile scanning.
- **Notes:** dependency eksternal; `endroid/qr-code` tersedia tapi tidak dipakai (kandidat pengganti di Laravel — catatan, bukan keputusan).

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
- **Status:** CONFIRMED
- **Rule:** maksimal 2 PIC per inventory; PIC pertama = primary (`is_primary`); perubahan PIC memicu notifikasi "Penugasan inventory baru" (dedupe `inventory_assignment:{inventory}:{user}`); relasi disimpan di `compliance_inventory_pics` dan di-backfill dari kolom teks `pic`.
- **Behavior:** penambahan/penggantian PIC → baris relasi + notifikasi in-app (+email/WA bila aktif).
- **Evidence:** `app/Models/ComplianceInventoryModel.php` (callbacks); migration `2026-08-07-000002` (tabel pics + backfill); `app/Libraries/NotificationService.php`.
- **Affected Modules:** Compliance Inventory, Home, Progress, Notifications.
- **Notes:** **konflik mekanisme PIC (teks vs relasi)** → CONF-004 / Q-007. **Production verified (Fase 0.6):** pics = UNIQUE(inventory_id,user_id), is_primary tinyint, TANPA FK (signedness mismatch); kolom `pic varchar(100)` masih ada & aktif ditulis. Keputusan sumber kebenaran tetap terbuka.

### BR-46 — PIC dipilih dari user aktif, digabung separator " - "
- **Status:** CONFIRMED (baru, Fase 0.5)
- **Rule:** dropdown PIC menampilkan user dengan `status='active'` (urut nama); PIC utama wajib, PIC kedua opsional dan harus berbeda; nilai digabung ke satu kolom teks `pic` dengan separator `" - "`.
- **Behavior:** JS modal men-sync dua dropdown → hidden input `pic` ("Nama1 - Nama2"); parse ulang saat modal dibuka memecah pada newline/koma/" - ", maks 2.
- **Evidence:** `app/Views/compliance/inventory/_modal_edit.php` (query `UserModel where status=active`, `<script>` parse/sync).
- **Affected Modules:** Compliance Inventory, Home, Progress, Reminder.
- **Notes:** inilah sumber mekanisme "PIC via nama" yang berkonflik dengan relasi pics (CONF-004 / Q-007).

### BR-22 — Status inventory: Good / Need Repair / Not Active
- **Status:** CONFIRMED — **ditingkatkan dari INFERRED pada Fase 0.5**
- **Rule:** nilai status yang sah di UI: `Good`, `Need Repair`, `Not Active` (badge: Baik / Perlu Perbaikan / Tidak Aktif; baris kuning untuk Need Repair, abu untuk Not Active).
- **Behavior:** dropdown edit hanya memuat 3 opsi tersebut; JS memberi warna baris sesuai status.
- **Evidence:** `app/Views/compliance/inventory/_modal_edit.php` (`<select name="status">` berisi persis 3 opsi); `public/js/inventory.js getStatusMeta/updateInventoryRowFromEditForm`.
- **Affected Modules:** Compliance Inventory, Dashboard, Report.
- **Notes:** **production verified (Fase 0.6):** `compliance_inventory.status varchar(50) DEFAULT NULL` — bebas teks (bukan ENUM) → penetapan enum resmi tetap Q-017.

### BR-23/24/25 — Reminder & notifikasi
Lihat §10 Notifications (penomoran dipertahankan di sana).

**Atribut lain (CONFIRMED, dari audit):** `expired_date` opsional (dipakai untuk highlight & print APAR); `qty` pada tambah; foto inventory via `updatePhoto` (validasi mime jpeg/png/webp, **tanpa** cek ukuran → CONF-013); QR Center: album per item type, download zip, print album, regen massal; `qrBatch` (zip by ids) **method ada tapi tidak ter-rute** (CONF-020 terkait, Appendix C).

---

## 5. Checklist

> Engine periode & aturan pengisian. Detail penuh: `docs/10-checklist-rules.md`. Penomoran BR-01..BR-18 diwarisi dari `docs/09`.

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
- **Notes:** definisi "late" di dashboard KPI **berbeda** (history-based) → CONF-014 / Q-019.

### BR-04 — Future terkunci & jendela editable
- **Status:** CONFIRMED
- **Rule:** periode future tidak bisa diisi; daily editable selama tidak future; **monthly selalu editable** (backfill tanpa batas — ada komentar "tetap bisa dibuka untuk backfill"); weekly editable bila selisih ≤ 3 bulan (`graceMonths=3`).
- **Behavior:** form/grid menolak periode di luar jendela.
- **Evidence:** `period_helper.php is_period_editable()/is_period_future()`.
- **Affected Modules:** Checklist (form & grid).
- **Notes:** asimetri weekly vs monthly → Q-011 (Minor).

### BR-05 — Status periode untuk kalender UI (implementasi yang menang)
- **Status:** CONFIRMED (perilaku runtime)
- **Rule:** prioritas status: `done` (ada log) > `future` > `late` > `pending`.
- **Behavior:** sel kalender diwarnai sesuai status ini.
- **Evidence:** `period_helper.php resolve_period_status()`.
- **Affected Modules:** Checklist UI, Detail inventory.
- **Notes:** ada implementasi ganda dengan semantik lain → BR-06 / CONF-002 / Q-004.

### BR-06 — ⚠️ Dua implementasi resolve_period_status yang berbeda
- **Status:** CONFIRMED (duplikasi ada) — **semantik AMBIGUOUS → Q-004**
- **Rule:** `period_status_helper.php` mendefinisikan fungsi bernama sama dengan hasil berbeda: `done` > daily(`holiday`/`locked`/`open`), weekly/monthly(`locked` bila future, else `open`).
- **Behavior:** karena keduanya di-autoload (`Config\Autoload::$helpers`), yang termuat lebih dulu yang menang (`period` sebelum `period_status`) — perilaku bergantung urutan load.
- **Evidence:** `app/Config/Autoload.php`; `app/Helpers/period_helper.php`; `app/Helpers/period_status_helper.php`.
- **Affected Modules:** Checklist UI.
- **Notes:** **NEED HUMAN DECISION** — pilih satu mesin status (Q-004). Tidak diputuskan di sini.

### BR-07 — Hari non-kerja (offday)
- **Status:** CONFIRMED
- **Rule:** Minggu selalu libur; Sabtu libur **hanya untuk tanggal ≥ 2026-04-01**; plus tanggal pada tabel `holidays`.
- **Behavior:** pengisian daily pada offday diblokir; kalender menandai offday; export PDAM/boiler mewarnai hari libur.
- **Evidence:** `checklist_helper.php is_weekend_offday()/is_date_offday()/holiday_dates_between()` (`dayOfWeek===0 → true; dayOfWeek===6 && date>='2026-04-01' → true`).
- **Affected Modules:** Checklist daily, Home, Progress, PDAM/Boiler export, Calendar.
- **Notes:** **konflik** dgn `holiday_helper.php is_holiday()` (Sabtu+Minggu selalu libur, tanpa tanggal efektif) → CONF-003 / Q-005.

### BR-08 — Blokir pengisian pada hari libur (daily)
- **Status:** CONFIRMED
- **Rule:** checklist daily tidak dapat diisi pada offday.
- **Behavior:** submit/grid-save mengembalikan error "Checklist tidak dapat diisi pada hari libur."; form menampilkan lock `offday`.
- **Evidence:** `ComplianceInventoryController::submitChecklist`, `saveCctvGrid`, `saveFirstAidContentGrid`, `saveGenericGrid`; `checklist()` (`lockReason='offday'`).
- **Affected Modules:** Checklist.
- **Notes:** —

### BR-09 — Anti-duplikat periode
- **Status:** CONFIRMED
- **Rule:** 1 log-set per `(inventory_id, period_key[, time_slot])`; submit ulang ditolak ("sudah diisi").
- **Behavior:** form full-submit menolak; grid boleh **update sel existing** (= koreksi).
- **Evidence:** `submitChecklist` (`$existsQuery->first()`); `checklist()` (`lockReason='done'`); seluruh `save*Grid`.
- **Affected Modules:** Checklist.
- **Notes:** **production verified (Fase 0.6):** memang tidak ada UNIQUE(inventory_id, period_key[, time_slot]) di DB → **APPLICATION-LEVEL CONSTRAINT** (docs/18 §7.2); risiko race tetap ada.

### BR-10 — not_ok wajib bukti
- **Status:** CONFIRMED
- **Rule:** status `not_ok` wajib remark **atau** foto (minimal salah satu).
- **Behavior:** validasi server + client menolak submit bila keduanya kosong.
- **Evidence:** `submitChecklist` (`in_array($status,['not_ok','ng']) && remark==='' && !photo → error`); `public/js/checklist.js validateChecklistForm`.
- **Affected Modules:** Checklist, Evidence.
- **Notes:** flag `require_photo` di master pertanyaan **tidak** ditegakkan → Q-013.

### BR-11 — Nilai status: ok / not_ok / na (ng dipetakan ke not_ok)
- **Status:** CONFIRMED (kode + production DB) — **Q-001 RESOLVED BY PRODUCTION SCHEMA (Fase 0.6)**
- **Rule:** kode/UI memakai `ok`, `not_ok`, `na`; input legacy `ng` dipetakan ke `not_ok` saat submit; nilai tak dikenal → default `na`.
- **Behavior:** `match($status){ 'ok'=>'ok','not_ok'=>'not_ok','ng'=>'not_ok','na'=>'na', default=>'na' }`.
- **Evidence:** `submitChecklist`; seluruh `save*Grid`.
- **Affected Modules:** Checklist, Report, Evidence, Ranking.
- **Notes:** CONF-001 terselesaikan — **Production DB** (`eams_database.sql`, 2026-08-18): `checklist_logs.status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'` — konsisten dengan kode; migration lama sudah diubah di production. Q-001 RESOLVED.

### BR-12 — NA hanya bila diizinkan item type
- **Status:** CONFIRMED
- **Rule:** opsi NA pada form tampil hanya bila `asset_item_types.allow_na` truthy.
- **Behavior:** form menyembunyikan/menampilkan opsi NA per item.
- **Evidence:** `app/Views/compliance/checklist/_form.php` (`!empty($inventory['allow_na'])`); kolom di-select di `checklist()`/`genericGrid()`.
- **Affected Modules:** Checklist.
- **Notes:** **production verified (Fase 0.6):** `allow_na tinyint(1) DEFAULT 0` (Q-003 RESOLVED). Dukungan NA **per kanal** tidak konsisten → BR-47 / Q-020.

### BR-13 — checked_by = nama user (string)
- **Status:** CONFIRMED
- **Rule:** `checklist_logs.checked_by` diisi `session()->get('name')` (string), bukan user id.
- **Behavior:** laporan & ranking mengelompokkan berdasarkan string nama.
- **Evidence:** `submitChecklist`, semua `save*Grid`, `markAll*`.
- **Affected Modules:** Checklist, Report, Ranking.
- **Notes:** tabel lain (patrol) memakai INT user id → Q-006. **Production verified (Fase 0.6):** `checked_by varchar(100) DEFAULT NULL` — LEGACY DATABASE BEHAVIOR; keputusan migrasi ke FK tetap terbuka (Q-006).

### BR-14 — Toilet checklist 3 slot waktu
- **Status:** CONFIRMED
- **Rule:** item_type_id=52 = checklist toilet harian dengan 3 slot `PG` (Pagi) / `SI` (Siang) / `SO` (Sore); slot wajib dipilih; lock & dedup dilakukan per (inventory, periode, slot).
- **Behavior:** tanpa slot → form terkunci (`lockReason='slot'`); grid toilet menampilkan baris per slot.
- **Evidence:** konstanta `TOILET_CHECKLIST_ITEM_TYPE_ID=52`; `checklist()`, `submitChecklist`, `genericGrid`; `ComplianceReportController`.
- **Affected Modules:** Checklist, Report.
- **Notes:** **production verified (Fase 0.6):** `time_slot varchar(5) DEFAULT NULL` (Q-003 RESOLVED).

### BR-15 — Grid "mark all" tidak menimpa data (dengan satu pengecualian)
- **Status:** CONFIRMED
- **Rule:** mark-all hanya mengisi sel kosong (skip existing); pesan: "Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa."
- **Behavior:** insert hanya untuk kombinasi (inventory, periode, pertanyaan) yang belum punya log.
- **Evidence:** semua `markAll*Grid` (guard `isset($existingMap[...])`).
- **Affected Modules:** Checklist grid.
- **Notes:** **PENGECUALIAN:** `markAllHeatDetectorGrid` **meng-update** existing ke `ok` (tidak skip) → CONF-009 / Q-024.

### BR-16 — Grid "clear" menghapus log
- **Status:** CONFIRMED
- **Rule:** mode `clear` = hard delete baris log sel tsb; khusus CCTV, clear hanya menghapus status `ok`/`not_ok` — sel `na` ditolak (409, diarahkan ke halaman detail).
- **Behavior:** clear mengosongkan sel (kembali "belum diisi").
- **Evidence:** `saveCctvGrid`, `save*Grid` lainnya.
- **Affected Modules:** Checklist grid.
- **Notes:** —

### BR-17 — check_date ≠ selalu tanggal pengisian
- **Status:** CONFIRMED
- **Rule:** form submit → `check_date = hari ini`; grid daily (CCTV/FAC/gate) → `check_date = period_key`; grid weekly → `check_date = bulan-01` (`preg_replace('/-W[1-4]$/','-01',…)`); grid monthly → `check_date = period_key.'-01'`.
- **Behavior:** nilai check_date berbeda tergantung kanal pengisian.
- **Evidence:** `submitChecklist` (`'check_date'=>date('Y-m-d')`); `saveGenericGrid` (cabang per frekuensi); `saveCctvGrid`; `markAllIntrusionAlarmGrid`.
- **Affected Modules:** Checklist, Ranking, Report.
- **Notes:** memengaruhi perhitungan on-time (BR-18) — dampaknya pada deviasi ranking form-vs-grid berstatus INFERRED (dicatat, bukan rule).

### BR-18 — On-time & skor ranking
- **Status:** CONFIRMED
- **Rule:** daily on-time bila `check_date ≤ period_key`; weekly on-time bila `check_date ≤ hari ke-7 minggu`; monthly on-time bila `check_date ≤ akhir bulan`; **skor = ontime×10 + late×3**.
- **Behavior:** leaderboard PIC diurutkan dari skor.
- **Evidence:** `ComplianceRankingController::index` + `weekEndDate`.
- **Affected Modules:** Ranking, Dashboard.
- **Notes:** —

### BR-47 — Dukungan status NA tidak konsisten antar kanal pengisian
- **Status:** CONFIRMED (perilaku) — **konsistensi AMBIGUOUS → Q-020** (baru, Fase 0.5)
- **Rule (teramati):** (a) form per-item menerima `na` bila `allow_na`; (b) grid Emergency Light & Emergency Exit Light menerima mode `na`; (c) grid CCTV **menolak** mengubah sel `na` (409); (d) grid lain (First Aid Box, First Aid Content, APAR, Intrusion Alarm, Hydrant, Heat Detector, Smoke Detector, Gate, generic) **tidak** menerima mode `na` sama sekali (mode dibatasi `ok|not_ok|clear`).
- **Behavior:** NA hanya bisa lewat kanal tertentu tergantung item type.
- **Evidence:** `saveEmergencyLightGrid`/`saveEmergencyExitLightGrid` (mode ∈ ok/not_ok/na/clear); `saveCctvGrid` (409 utk na); `saveGenericGrid` & grid lain (mode ∈ ok/not_ok/clear); `_form.php` (allow_na).
- **Affected Modules:** Checklist.
- **Notes:** **NEED HUMAN DECISION** — disengaja per kanal atau belum dirapikan (Q-020).

**Kanal pengisian (CONFIRMED):** 4 kanal — (1) **form per-item** (`compliance/checklist/{id}`), (2) **12 grid khusus** per item type (CCTV=13, Emergency Light=4, Emergency Exit Light=59, First Aid Box=10, First Aid Content=33, APAR=1, Intrusion Alarm=8, Hydrant=2, Heat Detector=6, Smoke Detector=7, Gate=40, Toilet=52), (3) **generic grid** (item lain, dipakai staff), (4) **form toilet 3-slot**. Kolom grid khusus dipetakan dari **teks pertanyaan** (resolve*GridColumns) — rapuh, dicatat sebagai legacy (§17).

---

## 6. Evidence & Follow-up

**Aturan (CONFIRMED):**
- Evidence center menampilkan temuan `not_ok` (beserta foto & remark) dari `checklist_logs`.
- Setiap temuan punya **follow-up**: `follow_up_status` (`open` → `monitoring` → `closed`), `follow_up_note`, `follow_up_date` — diupdate via `evidence/update-followup`.
- Foto disimpan di `public/uploads/checklist/` dengan nama acak.

- **Evidence:** `app/Controllers/ComplianceEvidenceController.php` (ajax/detail/updateFollowup); `app/Models/ChecklistLogModel.php` (allowedFields follow_up_*); `public/js/evidence.js`.
- **Affected Modules:** Checklist, Evidence, Report.
- **Notes:** **production verified (Fase 0.6):** `follow_up_status enum('open','monitoring','closed') DEFAULT 'open'`, `follow_up_note text`, `follow_up_date date` (Q-003 RESOLVED). Upload foto checklist **tanpa validasi mime/size** → CONF-012 / Q-026.

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
  - Definisi "late" pada KPI dashboard = **history-based** (punya histori tapi belum ada log periode aktif) ≠ `is_period_late` time-based → CONF-014 / Q-019.
  - Route `compliance/dashboard/risk-trend` & `.../data` menunjuk method yang **tidak ada** (`getRiskTrendAjax`, `ajaxData`) → CONF-006 / Q-009.
  - View `compliance/dashboard/overdue.php` yatim (tak ada yang merender; link internalnya menuju route tak terdaftar) → CONF-028.

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
- **Status:** CONFIRMED (keduanya ada) — **inkonsisten → Q-012**
- **Rule:** helper UI `device_is_online` = online bila `now − last_seen ≤ max(172800 dtk, 2×interval)` (default ≥48 jam); command `it:status` menandai offline bila `> 600 dtk`.
- **Evidence:** `app/Helpers/device_helper.php`; `app/Commands/DeviceStatusCheck.php`.
- **Affected Modules:** IT Devices.
- **Notes:** **NEED HUMAN DECISION** — satu sumber kebenaran (Q-012).

### BR-35 — Identitas agent & auto-register
- **Status:** CONFIRMED
- **Rule:** device dikenali berurutan: `device_token` → `mac_address` → `hostname`; heartbeat pertama membuat device + asset `IT-PC-###` (category_id=1, status 'aktif').
- **Evidence:** `Api\AgentController::heartbeat/findDeviceByIdentity/generateInventoryNo`.
- **Affected Modules:** IT Devices, IT Assets.
- **Notes:** asumsi `category_id=1` = kategori IT tak terverifikasi → Q-016. Agent API publik (CSRF-exempt); **menerima payload via GET** → CONF-025 / Q-025. State besar disimpan di kolom JSON `it_devices.cpu`.

### BR-36 — Remote command queue
- **Status:** CONFIRMED
- **Rule:** 1 command antrian per device; `remote_lock_until = now + 25 dtk`; push ke `{{http://{ip}}}:8765/command` dengan device_token; gagal → fallback polling; whitelist aksi: restart, shutdown, update, sync, restart_agent, lock, logoff, popup_message.
- **Evidence:** `ITDeviceController::queueRemoteCommand/remoteAction`; `Api\AgentController::command/popQueuedCommand`.
- **Affected Modules:** IT Devices.
- **Notes:** installer agent `EAMSAgent[Setup][-win7|-xp]-X.Y.Z.exe` di `public/downloads/agent`.

**Gap (CONFIRMED, diperkuat Fase 0.6):** `ItDeviceLogModel` menunjuk tabel `it_device_logs` yang **TIDAK ADA di production DB** (`eams_database.sql`) dan tidak dipakai controller mana pun → dead model (CONF-DB-003). Tabel ini tidak perlu dibuat di Laravel.

---

## 9. Reports & PDF

**Aturan (CONFIRMED):**
- Print/report per item type via `print_by_item/{type}` dengan **12 form print khusus + generic**, memakai pemetaan kolom dari teks pertanyaan.
- Batch print per kategori/periode; export Excel tersedia pada beberapa report (progress, dsb.).
- PDF: mPDF via wrapper, transisi ke Dompdf; template di `app/Views/pdf/`.
- Report checklist membaca status `ok`/`not_ok`/`na` (lihat BR-11 untuk ENUM production).
- Export PDAM/Boiler mewarnai hari libur (aturan offday BR-07) + total bulanan.

- **Evidence:** `ComplianceReportController`; `app/Views/pdf/*`; `app/Libraries/` (PDF wrapper); `docs/11-reports-pdf.md`.
- **Affected Modules:** Report, PDF, Export.
- **Notes:**
  - `PdfAccessFilter` + `Config\PdfPermission` (allowedRoles=['admin']) ada tapi **tidak dipasang** ke route `export/pdf/*` (hanya `auth`) → CONF-005 / Q-008.
  - Pemetaan kolom print dari **teks pertanyaan** (bukan id stabil) → legacy rapuh (§17).
  - Method `exportPeriodePage` tidak ter-rute & view `checklist/export_periode.php` tidak ada → Appendix C.

---

## 10. Notifications

### BR-23 — Reminder mingguan (WA & email)
- **Status:** CONFIRMED
- **Rule:** command CLI `notify:weekly-checklist` (WA Fonnte) & `notify:weekly-checklist-email` (in-app + email) mengirim ke user aktif yang punya pending checklist pada periode berjalan; maks 15 item/pesan; email command membuat notifikasi `reminder` dgn dedupe `weekly_email_reminder:{date}:{userId}` (tidak kirim WA ulang).
- **Behavior:** penerima ditentukan dari PIC pending checklist.
- **Evidence:** `app/Commands/WeeklyChecklistWhatsappReminder.php`, `WeeklyChecklistEmailReminder.php`, `app/Libraries/NotificationService.php`.
- **Affected Modules:** Notifications, Home, Progress.
- **Notes:** **konflik mekanisme PIC** — WA command mencocokkan **nama** (parsing, min 1 kata cocok); email command memakai **relasi pics** (`assignedToUser`) → CONF-004 / Q-007.

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

**FDM (CONFIRMED):** sub-fitur "Production Section" aktif (input per section/line); 2 sub-fitur lain **placeholder "soon"**. **Perilaku khusus (legacy):** `ensureYears()` **men-INSERT** baris tahun berjalan s.d. +4 **saat GET** (write-on-read) → CONF-023 / §17.

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
- **Notes:** tabel `boiler_fuel_logs` tanpa migration → Q-002.

### BR-30 — IPAL upsert per tanggal
- **Status:** CONFIRMED
- **Rule:** upsert per tanggal; field: start/stop meter, pemakaian, keterangan.
- **Evidence:** `IpalController::save`; **Production DB (Fase 0.6):** `UNIQUE KEY unique_log_date (log_date)`.
- **Affected Modules:** IPAL.
- **Notes:** **koreksi Fase 0.6** — audit awal menyebut "tanpa UNIQUE DB"; production ternyata MENEGAKKAN unique per tanggal (CONF-DB-004).

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
- **Notes:** bergantung Windows/schtasks → di Laravel ganti Laravel Scheduler (catatan migrasi).

---

## 15. Settings

**Aturan (CONFIRMED):**
- `app_settings` key-value menyimpan: profil perusahaan (nama, logo), SMTP (Google Workspace), WhatsApp/Fonnte (token, nomor, webhook), template pesan (placeholder `{{name}}/{{title}}/{{message}}/{{url}}/{{company}}/{{date}}`), flag `notification_email_enabled` / `notification_whatsapp_enabled`.
- Self-service user: ganti password & kontak, tandai notifikasi terbaca — lewat halaman Settings (POST).
- Nilai default diseed migration; nilai aktual produksi (token, template) ada di DB produksi → Q-018.

- **Evidence:** `SettingController`; migration seeder `app_settings`; `docs/13-dependencies.md`.
- **Affected Modules:** seluruh modul (konfigurasi).
- **Notes:** self-service untuk user read-only terblokir WriteFilter → BR-43 / Q-021.

---

## 16. Shared Business Rules (lintas modul)

Aturan yang dipakai banyak modul sekaligus (CONFIRMED):

| # | Rule | Evidence | Dipakai oleh |
|---|---|---|---|
| S-01 | Period engine: period_key + late + editable + offday (BR-01..BR-08) | `period_helper.php`, `checklist_helper.php` | Checklist, Home, Progress, Ranking, Reminder, Report |
| S-02 | Anti-duplikat per periode (BR-09) | `submitChecklist`, `save*Grid` | Checklist |
| S-03 | not_ok wajib remark/foto (BR-10) | `submitChecklist`, `checklist.js` | Checklist, Evidence |
| S-04 | Nilai status ok/not_ok/na, ng→not_ok (BR-11) | `submitChecklist` | Checklist, Report, Evidence, Ranking |
| S-05 | checked_by = nama string (BR-13) | semua penulis log | Checklist, Report, Ranking |
| S-06 | Notifikasi idempoten via dedupe_key (BR-24) | `NotificationService` | Semua modul |
| S-07 | WriteFilter global utk read-only (BR-42) | `WriteFilter` | Semua modul |
| S-08 | Otorisasi tiga lapis (BR-41) & menu via page_access (BR-44) | `access_helper` | Semua modul |
| S-09 | Offday & holidays untuk warna/perhitungan (BR-07) | `checklist_helper` | Checklist, PDAM, Boiler, Calendar |
| S-10 | Session 8 jam & audit auth (BR-40) | `AuthController`, `Session.php` | Global |
| S-11 | CSRF aktif; pengecualian: `/api/agent/*`, kuesioner publik | `Filters.php`, `Security` config | Agent API, Questionnaire |
| S-12 | Upload foto disimpan dgn nama acak di `public/uploads/...` | semua controller upload | Inventory, Checklist, Users, Thermal, Patrol |
| S-13 | Pagination default 20 (bisa dioverride `perPage`) | `index()` berbagai controller | Listing |
| S-14 | Timezone Asia/Jakarta & format tanggal `Y-m-d`/`Y-m` konsisten | `App.php`, helpers | Global |

---

## 17. Known Legacy Behaviors

Pemisahan tegas **BUSINESS RULE vs LEGACY IMPLEMENTATION vs TECHNICAL DEBT**. Technical debt **bukan** business requirement dan **tidak** dibawa ke Laravel apa adanya.

| Area | BUSINESS RULE (dipertahankan) | LEGACY IMPLEMENTATION (cara CI4 mengimplementasikan) | TECHNICAL DEBT (jangan diwarisi) |
|---|---|---|---|
| Periode weekly | Weekly dibagi W1–W4 per bulan | Helper memakai rentang tanggal 1–7 / 8–14 / 15–21 / 22–akhir | Dua helper `generate_calendar_periods` (rentang tetap vs kursor) — CONF-022 |
| Status periode | Periode punya status tampil di kalender | `period_helper::resolve_period_status` (done/future/late/pending) | Fungsi ganda `period_status_helper` (done/holiday/locked/open) — CONF-002/Q-004 |
| Status checklist | ok / not_ok / na; ng legacy dipetakan ke not_ok | Kode menulis `not_ok` | Migration ENUM `ok/ng/na` tidak cocok — **terselesaikan: production ENUM ok/not_ok/na** (CONF-001/Q-001 RESOLVED) |
| Offday | Minggu libur; Sabtu libur mulai tgl efektif; + holidays | `is_weekend_offday` dgn tanggal efektif 2026-04-01 | `is_holiday` legacy (Sabtu+Min selalu libur) masih terautoload — CONF-003/Q-005 |
| checked_by | Pencatat checklist tercatat | Disimpan sebagai **string nama** session | Bukan FK user; rentan duplikat nama — Q-006 |
| PIC | Maks 2 PIC, PIC1 primary, notifikasi assignment | Kolom teks `pic` separator " - " + relasi `compliance_inventory_pics` | Dua mekanisme hidup berdampingan (reminder WA parse nama) — CONF-004/Q-007 |
| QR | QR berisi URL detail + label kode aset | Gambar dari `api.qrserver.com` + overlay GD | Dependency eksternal; `endroid/qr-code` nganggur |
| Koreksi grid | Koreksi sel mengubah log existing | `save*Grid` menulis `updated_at` | `updated_at` dibuang model & **kolom terbukti tidak ada di production** (Fase 0.6) — CONF-010/Q-023 RESOLVED |
| Mark-all | Isi massal hanya sel kosong | Guard `isset($existingMap)` | `markAllHeatDetectorGrid` **menimpa** ok — CONF-009/Q-024 |
| NA | NA bila item mengizinkan | Form via `allow_na` | Grid EL/EEL terima na; CCTV proteksi na; grid lain tolak — CONF-027/Q-020 |
| Late | Periode lewat ambang dihitung late | `is_period_late` (+21/+28/+3 bln) | KPI dashboard pakai definisi history-based berbeda — CONF-014/Q-019 |
| Grid khusus | 12 item type punya grid kustom | Konstanta `item_type_id` hard-coded (CCTV=13, dst.) | Pemetaan kolom dari **teks pertanyaan** (rename pertanyaan merusak grid/print) |
| Inventory list | Daftar + filter + sort | Join kategori/item/area | `getBaseQuery` join item via `item_name` (bukan item_type_id) — CONF-018 |
| Frekuensi | Frekuensi checklist per item type | `asset_item_types.checklist_frequency` | Kolom `checklist_master.frequency` ditulis seeder tapi diabaikan query aktif — CONF-021 |
| PDF | Report dapat dicetak PDF | mPDF → Dompdf | `PdfAccessFilter` tidak terpasang; route `export/pdf/*` hanya `auth` — CONF-005/Q-008 |
| Error 403 | Akses ditolak dialihkan | `redirect('/unauthorized')` di banyak controller | Route `/unauthorized` tidak terdaftar → 404 — CONF-007/Q-010 |
| Kalender standalone | Kalender compliance menampilkan event | `ComplianceCalendarController` + view | Controller tak ter-rute; view memanggil `compliance/calendar/events` yang tak ada — CONF-019 |
| Dashboard | KPI + grafik | `ComplianceDashboardController` | Route `risk-trend`/`data` → method tak ada; view `overdue.php` yatim — CONF-006/CONF-028 |
| FDM | Data produksi per tahun | `ensureYears()` mengisi tahun berjalan s.d. +4 | INSERT terjadi **saat GET** (write-on-read) — CONF-023 |
| Kuesioner | 2 template default tersedia | Bootstrap di **constructor** controller | Tulis DB saat construct (write-on-read) — CONF-024 |
| Agent API | Agent heartbeat/command/update | Endpoint publik CSRF-exempt, token device plaintext | Menerima payload via **GET** (mutasi via GET) — CONF-025/Q-025 |
| Read-only | User read-only tidak bisa mutasi | `WriteFilter` global | Self-service settings ikut terblokir — CONF-026/Q-021 |
| Upload foto | Bukti foto disimpan | Validasi mime/size di beberapa modul | Foto checklist tanpa validasi; updatePhoto tanpa cek size — CONF-012/013/Q-026 |
| Integritas DB | Relasi antar tabel | FK pada migration baru (audit_logs) | `notifications` sengaja tanpa FK ke users; kebijakan tak konsisten — CONF-011/Q-022 |
| Famili checklist | (tidak ada — legacy murni) | — | Famili `compliance_checklist_*` **terbukti tidak ada di production** (Fase 0.6) → dead code — CONF-016/017/Q-014 RESOLVED |

**Dead code terkonfirmasi (tidak dibawa ke Laravel):** `Home.php`, `ComplianceChecklistController`, `ComplianceCalendarController`, `AssetItemTypeController::byCategory`, `exportPeriodePage`, `qrBatch`, view `dashboard.php` (0 byte), `welcome_message.php`, `overdue.php`, route `compliance/inventory/create` & `edit/(:num)` (method tak ada — UI memakai modal), route `compliance/calendar/events`, filter `pdfAccess` (tak terpasang), tabel `it_device_logs` (tanpa writer), kolom `checklist_master.frequency`, helper `it_health_helper` & `period_status_helper` & `calender_period_helper` (duplikat).

---

## 18. Unresolved Decisions

Seluruh keputusan manusia terdaftar di **`docs/15-ambiguities-need-decision.md`** (Final Decision List). Ringkasan prioritas:

| Prioritas | Jumlah | Item |
|---|---|---|
| **Critical** (wajib sebelum Laravel coding) | 3 | Q-004, Q-006, Q-007 |
| **Important** (sebelum modul terkait dibuat) | 11 | Q-005, Q-008, Q-009, Q-012, Q-013, Q-016, Q-017, Q-018, Q-019, Q-020, Q-024 |
| **Minor** (bisa saat implementasi) | 7 | Q-010, Q-011, Q-015, Q-021, Q-022, Q-025, Q-026 |
| **RESOLVED BY PRODUCTION SCHEMA** (Fase 0.6, `eams_database.sql`) | 5 | Q-001, Q-002, Q-003, Q-014, Q-023 |

**Total: 26 item — 5 terselesaikan oleh production schema, 21 masih membutuhkan keputusan manusia.** Tidak ada satu pun yang diputuskan oleh dokumen ini.

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
6. Kanal pengisian: form → generic grid → grid khusus (prioritas daily: CCTV, First Aid Content, Gate)
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

Setiap konflik ditemukan saat cross-check Fase 0.5. Format: Topic / Source A / Source B / Conflict / Current Runtime Behavior / Business Meaning. **Tidak ada yang diputuskan di sini.**

---

**CONF-001**
- **Topic:** nilai status `checklist_logs`
- **Source A:** migration `2026-01-20-000003` → `status ENUM('ok','ng','na') DEFAULT 'ok'`
- **Source B:** `submitChecklist` + semua `save*Grid` menulis `'not_ok'`; report/evidence membaca `'not_ok'`
- **Conflict:** DDL repo tidak bisa menampung nilai yang kode tulis
- **Current Runtime Behavior:** production DB = `enum('ok','not_ok','na')` (terbukti Fase 0.6)
- **Business Meaning:** **RESOLVED BY PRODUCTION SCHEMA (Fase 0.6):** production `status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'` — kode konsisten; DB sudah diubah dari migration awal (docs/18 CONF-DB-001)

**CONF-002**
- **Topic:** semantik status periode kalender
- **Source A:** `period_helper::resolve_period_status` → done/future/late/pending
- **Source B:** `period_status_helper::resolve_period_status` → done/holiday/locked/open
- **Conflict:** dua fungsi bernama sama, hasil berbeda, keduanya terautoload
- **Current Runtime Behavior:** yang termuat lebih dulu yang menang (`period`)
- **Business Meaning:** **NEED HUMAN DECISION** (Q-004)

**CONF-003**
- **Topic:** aturan Sabtu
- **Source A:** `checklist_helper::is_weekend_offday` → Sabtu libur hanya ≥ 2026-04-01
- **Source B:** `holiday_helper::is_holiday` → Sabtu+Minggu selalu libur
- **Conflict:** dua aturan offday berbeda untuk tanggal historis
- **Current Runtime Behavior:** jalur checklist memakai Source A; `period_status_helper` memakai Source B
- **Business Meaning:** **NEED HUMAN DECISION** (Q-005)

**CONF-004**
- **Topic:** sumber kebenaran PIC
- **Source A:** relasi `compliance_inventory_pics` (is_primary, notifikasi, `assignedToUser`)
- **Source B:** kolom teks `pic` diparse nama (REGEXP/LIKE) oleh `ProgressController` & reminder WA
- **Conflict:** dua mekanisme PIC aktif untuk progres/reminder
- **Current Runtime Behavior:** keduanya berjalan; hasil bisa berbeda bila nama berubah/duplikat
- **Business Meaning:** **NEED HUMAN DECISION** (Q-007)

**CONF-005**
- **Topic:** pembatasan akses PDF
- **Source A:** `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']` ada
- **Source B:** route `export/pdf/*` hanya memakai filter `auth`
- **Conflict:** filter PDF didefinisikan tapi tidak pernah dipasang
- **Current Runtime Behavior:** semua user login bisa akses PDF
- **Business Meaning:** **NEED HUMAN DECISION** (Q-008)

**CONF-006**
- **Topic:** endpoint dashboard
- **Source A:** Routes `compliance/dashboard/risk-trend → getRiskTrendAjax`, `.../data → ajaxData`
- **Source B:** `ComplianceDashboardController` tidak memiliki kedua method tsb
- **Conflict:** route menunjuk method yang tidak ada
- **Current Runtime Behavior:** memanggilnya → error 500; `dashboard.js` berpotensi memanggil
- **Business Meaning:** **NEED HUMAN DECISION** (Q-009)

**CONF-007**
- **Topic:** halaman akses ditolak
- **Source A:** banyak controller `redirect()->to('/unauthorized')`
- **Source B:** Routes.php tidak mendaftarkan `/unauthorized`
- **Conflict:** target redirect tidak ada
- **Current Runtime Behavior:** 404 (bukan halaman 403)
- **Business Meaning:** **NEED HUMAN DECISION** (Q-010)

**CONF-008**
- **Topic:** ambang online device
- **Source A:** `device_helper::device_is_online` → ≥ 48 jam
- **Source B:** `Commands/DeviceStatusCheck` → offline bila > 600 dtk
- **Conflict:** dua definisi online/offline
- **Current Runtime Behavior:** UI & command tidak sinkron
- **Business Meaning:** **NEED HUMAN DECISION** (Q-012)

**CONF-009**
- **Topic:** perilaku mark-all
- **Source A:** semua `markAll*Grid` → skip sel terisi ("tidak ditimpa")
- **Source B:** `markAllHeatDetectorGrid` → **meng-update** existing ke `ok`
- **Conflict:** satu grid menimpa, yang lain tidak
- **Current Runtime Behavior:** Heat Detector menimpa status ok existing
- **Business Meaning:** **NEED HUMAN DECISION** (Q-024)

**CONF-010**
- **Topic:** kolom `updated_at` pada checklist_logs
- **Source A:** 12+ lokasi `save*Grid` menulis `'updated_at' => date('Y-m-d H:i:s')` (terverifikasi via grep)
- **Source B:** `ChecklistLogModel::$allowedFields` tidak memuat `updated_at`; migration hanya membuat `created_at`
- **Conflict:** penulisan yang dibuang diam-diam (CI4 memfilter ke allowedFields)
- **Current Runtime Behavior:** tidak error; nilai `updated_at` hilang
- **Business Meaning:** **RESOLVED BY PRODUCTION SCHEMA (Fase 0.6):** kolom `updated_at` TIDAK ADA di production → **LEGACY CODE WRITES NON-PERSISTED FIELD** (docs/18 CONF-DB-015); jangan otomatis menambah kolom di Laravel

**CONF-011**
- **Topic:** foreign key ke tabel users
- **Source A:** migration `audit_logs` membuat FK ke `users(id)`
- **Source B:** migration `notifications` sengaja tanpa FK — komentar "legacy users table differs between installations (signedness, engine, id type)"
- **Conflict:** kebijakan berlawanan terhadap tabel legacy yang sama
- **Current Runtime Behavior (koreksi Fase 0.6):** di production, `audit_logs` **TANPA FK** ke users (hanya PK) — FK yang dideklarasikan migration tidak terbentuk/di-drop (docs/18 CONF-DB-006); notifications memang tanpa FK → production konsisten tanpa FK ke users
- **Business Meaning:** **NEED HUMAN DECISION** (Q-022) — keputusan kebijakan FK di Laravel tetap terbuka

**CONF-012**
- **Topic:** validasi foto checklist
- **Source A:** `submitChecklist` memindahkan foto tanpa validasi mime/size
- **Source B:** upload user/asset/thermal/employee divalidasi mime (+ size)
- **Conflict:** satu jalur upload tanpa validasi
- **Current Runtime Behavior:** sembarang file bisa terunggah sebagai "foto" checklist
- **Business Meaning:** **NEED HUMAN DECISION** (Q-026)

**CONF-013**
- **Topic:** validasi ukuran foto inventory
- **Source A:** `ComplianceInventoryController::updatePhoto` validasi mime saja
- **Source B:** ITAsset/Employee validasi mime + size 2MB
- **Conflict:** standar validasi upload tidak seragam
- **Current Runtime Behavior:** foto inventory berukuran besar diterima
- **Business Meaning:** **NEED HUMAN DECISION** (Q-026)

**CONF-014**
- **Topic:** definisi "late"
- **Source A:** `period_helper::is_period_late` → time-based (+21 hari / +28 hari / +3 bulan)
- **Source B:** `ComplianceDashboardController` KPI late → history-based (punya histori, belum ada log periode aktif)
- **Conflict:** dua definisi late menghasilkan angka berbeda
- **Current Runtime Behavior:** KPI dashboard ≠ badge/reminder
- **Business Meaning:** **NEED HUMAN DECISION** (Q-019)

**CONF-015**
- **Topic:** nilai check_date
- **Source A:** `submitChecklist` → `check_date = hari ini`
- **Source B:** grid daily (CCTV) → `check_date = period_key`; weekly → `bulan-01`; monthly → `period-01`
- **Conflict:** tanggal pemeriksaan berbeda per kanal → memengaruhi on-time (BR-18)
- **Current Runtime Behavior:** ranking bisa berbeda tergantung kanal pengisian
- **Business Meaning:** terdokumentasi (BR-17); **tidak butuh keputusan terpisah** — ikut keputusan desain ranking di Laravel

**CONF-016**
- **Topic:** model vs migration (famili compliance_checklist)
- **Source A:** `ComplianceChecklistLogModel` mendefinisikan kolom `inspection_week/month/year`
- **Source B:** migration `compliance_checklist_logs` membuat `schedule_id/template_id/check_date`
- **Conflict:** model dan migration tidak cocok (dead code path)
- **Current Runtime Behavior:** jalur ini tidak ter-rute (tidak dieksekusi)
- **Business Meaning:** **RESOLVED BY PRODUCTION SCHEMA (Fase 0.6):** famili `compliance_checklist_*` TIDAK ADA di production → dead code (Q-014 RESOLVED, docs/18 CONF-DB-002)

**CONF-017**
- **Topic:** dua famili tabel checklist
- **Source A:** `checklist_*` (checklist_logs, checklist_master) — dipakai route aktif
- **Source B:** `compliance_checklist_*` (master, logs, log_items) — model ada, route tidak memakai
- **Conflict:** dua skema paralel untuk domain yang sama
- **Current Runtime Behavior:** hanya `checklist_*` yang hidup
- **Business Meaning:** **RESOLVED BY PRODUCTION SCHEMA (Fase 0.6):** hanya famili `checklist_*` yang ada di production; famili `compliance_checklist_*` tidak ada → dead code (Q-014 RESOLVED)

**CONF-018**
- **Topic:** join item pada daftar inventory
- **Source A:** `ComplianceInventoryModel::getBaseQuery` join `asset_item_types` via `item_name`
- **Source B:** seluruh kode lain join via `item_type_id`
- **Conflict:** join berbasis nama (rentan duplikat/rename) vs id
- **Current Runtime Behavior:** listing tetap jalan selama nama unik
- **Business Meaning:** jelas (legacy bug-risk); **tidak butuh keputusan bisnis** — perbaiki di Laravel

**CONF-019**
- **Topic:** feed kalender standalone
- **Source A:** view kalender memakai `data-events-url="compliance/calendar/events"`
- **Source B:** route tsb tidak terdaftar; `ComplianceCalendarController` tidak ter-rute
- **Conflict:** UI memanggil endpoint yang tidak ada
- **Current Runtime Behavior:** kalender standalone tidak dapat memuat event (juga tidak dapat diakses)
- **Business Meaning:** jelas (dead feature); **tidak butuh keputusan bisnis** — kalender aktif = Holidays + kalender checklist. (Koreksi Fase 0.6: tabel `compliance_calendar_events` AKTIF via HolidayController — CONF-DB-022)

**CONF-020**
- **Topic:** route create/edit inventory
- **Source A:** Routes `compliance/inventory/create → create`, `compliance/inventory/edit/(:num) → edit`
- **Source B:** `ComplianceInventoryController` **tidak memiliki** method `create()`/`edit()` (terverifikasi via grep, Fase 0.5)
- **Conflict:** route menunjuk method yang tidak ada
- **Current Runtime Behavior:** mengaksesnya → error; UI nyata memakai modal (bukan halaman)
- **Business Meaning:** jelas (dead routes); kanonik = **modal workflow** (BR-45/BR-46); tidak butuh keputusan bisnis

**CONF-021**
- **Topic:** kolom `checklist_master.frequency`
- **Source A:** seeder menulis `frequency` per pertanyaan; kolom ada di allowedFields
- **Source B:** query aktif hanya memfilter `item_type_id + active`; frekuensi efektif = `asset_item_types.checklist_frequency`
- **Conflict:** kolom frekuensi per pertanyaan tidak dipakai
- **Current Runtime Behavior:** frekuensi per item type yang berlaku (kolom frequency ADA di production — CONF-DB-023, tapi tak dibaca)
- **Business Meaning:** jelas (legacy column); **tidak butuh keputusan bisnis**

**CONF-022**
- **Topic:** generator kalender periode
- **Source A:** `period_helper`/`checklist_helper generate_calendar_periods` → rentang tetap (W1:1–7 dst.)
- **Source B:** `calender_period_helper generate_calendar_periods` → model kursor 7-hari
- **Conflict:** dua generator untuk hal yang sama
- **Current Runtime Behavior:** yang terautoload lebih dulu menang
- **Business Meaning:** **NEED HUMAN DECISION** — diselesaikan bersama Q-004 (satu engine periode)

**CONF-023**
- **Topic:** efek samping tulis pada GET (FDM)
- **Source A:** `FdmDataCollectionController::productionSection` adalah endpoint GET
- **Source B:** `ensureYears()` di dalamnya **INSERT** baris tahun berjalan s.d. +4
- **Conflict:** read menimbulkan write
- **Current Runtime Behavior:** membuka halaman FDM menambah baris tahun
- **Business Meaning:** terdokumentasi (legacy); **tidak butuh keputusan bisnis** — hilangkan di Laravel

**CONF-024**
- **Topic:** efek samping tulis pada construct (kuesioner)
- **Source A:** controller kuesioner seharusnya murni melayani request
- **Source B:** constructor me-bootstrap 2 template default ke DB bila belum ada
- **Conflict:** tulis DB saat construct
- **Current Runtime Behavior:** kunjungan pertama ke halaman kuesioner men-seed template
- **Business Meaning:** terdokumentasi (legacy); pindah ke seeder di Laravel

**CONF-025**
- **Topic:** metode HTTP agent API
- **Source A:** mutasi state seharusnya POST
- **Source B:** `Api\AgentController::resolvePayload` menerima payload dari **query GET** (bila device_token/hostname/mac ada) untuk heartbeat/command
- **Conflict:** mutasi state dapat dipicu GET
- **Current Runtime Behavior:** GET `/api/agent/heartbeat?...` mengubah last_seen/state
- **Business Meaning:** **NEED HUMAN DECISION** (Q-025 — cek kompatibilitas agent lama)

**CONF-026**
- **Topic:** self-service untuk user read-only
- **Source A:** halaman Settings menyediakan ganti password/kontak untuk semua user
- **Source B:** `WriteFilter` memblokir semua POST non-whitelist untuk permission `read` (termasuk `/settings`)
- **Conflict:** fitur self-service tidak dapat dipakai user read-only
- **Current Runtime Behavior:** read-only user mendapat 403 saat ganti password / tandai notifikasi
- **Business Meaning:** **NEED HUMAN DECISION** (Q-021)

**CONF-027**
- **Topic:** dukungan status NA per kanal
- **Source A:** form per-item menerima `na` bila `allow_na`; grid EL/EEL menerima mode `na`
- **Source B:** grid CCTV menolak ubah sel `na` (409); grid lain (APAR, Hydrant, dst., generic) tidak menerima mode `na`
- **Conflict:** NA hanya bisa lewat kanal tertentu
- **Current Runtime Behavior:** dukungan NA tergantung item type & kanal
- **Business Meaning:** **NEED HUMAN DECISION** (Q-020)

**CONF-028**
- **Topic:** view overdue yatim
- **Source A:** `app/Views/compliance/dashboard/overdue.php` (tabel "Checklist Overdue") ada di repo
- **Source B:** tidak ada controller yang merendernya; link internalnya menuju `compliance/inventory/{id}` yang juga tidak terdaftar
- **Conflict:** view nyata tanpa jalur render & dengan link rusak
- **Current Runtime Behavior:** tidak pernah tampil
- **Business Meaning:** jelas (dead view); tidak butuh keputusan bisnis

---

## Appendix B — Module Completeness Checklist (cross-check Fase 0.5)

Kolom wajib: purpose, actor, workflow, business rules, database, controller, model, view, JS/AJAX, permission, report/output, dependency.

| Module | Lengkap? | Catatan gap |
|---|---|---|
| M-01 Authentication | ✅ | — |
| M-02 Home & Notifications | ✅ | — |
| M-03 Master Data | ✅ | kolom `checklist_frequency`/`allow_na` verified di production (Q-003 RESOLVED Fase 0.6) |
| M-04 Compliance Inventory + QR | ⚠️ | route create/edit → method hilang (CONF-020); `qrBatch` tak ter-rute |
| M-05 Checklist Master | ⚠️ | kolom `frequency` tak dipakai (CONF-021; kolom ADA di production, CONF-DB-023) |
| M-06 Checklist Execution | ⚠️ | engine periode ganda (CONF-002/022); NA per kanal (CONF-027) |
| M-07 Compliance Dashboard | ❌ | 2/8 endpoint AJAX mati (CONF-006); definisi late (CONF-014); view overdue yatim (CONF-028) |
| M-08 Progress Monitoring | ✅ | — |
| M-09 Ranking | ✅ | — |
| M-10 Evidence & Follow-up | ✅ | kolom `follow_up_*` verified di production (Q-003 RESOLVED Fase 0.6) |
| M-11 Report & PDF/Print | ⚠️ | pdfAccess tak terpasang (CONF-005); pemetaan kolom dari teks pertanyaan |
| M-12 Calendar | ⚠️ | controller standalone mati (CONF-019); tabel `compliance_calendar_events` AKTIF via Holidays (koreksi CONF-DB-022, Fase 0.6) |
| M-13 Thermal Imaging | ✅ | — |
| M-14 Questionnaire | ⚠️ | bootstrap di constructor (CONF-024) |
| M-15 EMS / GHG | ✅ | — |
| M-16 FDM | ⚠️ | 2/3 sub-fitur placeholder "soon"; write-on-read (CONF-023) |
| M-17 Boiler / IPAL / PDAM | ✅ | tabel boiler & ipal verified di production (Q-002 RESOLVED Fase 0.6) |
| M-18 IT Assets & Employees | ✅ | — |
| M-19 IT Device Monitoring | ❌ | tabel `it_device_logs` TIDAK ADA di production (dead model, CONF-DB-003); threshold ganda (CONF-008); GET mutating (CONF-025); category_id=1 (Q-016) |
| M-20 Patrol Security | ✅ | — |
| M-21 Backup | ⚠️ | bergantung Windows schtasks + path `D:\` (deployment-specific) |
| M-22 Administration (Users/Audit/Settings) | ✅ | — |

**Ringkasan:** 16 modul lengkap, 5 modul dengan gap parsial (⚠️), 2 modul dengan gap signifikan (❌: M-07, M-19). Tidak ada modul yang hilang total — semua terdokumentasi di `docs/02-modules.md`. (Fase 0.6: M-12 turun dari ❌ ke ⚠️ karena tabel events ternyata aktif via HolidayController.)

---

## Appendix C — Database & Route Gap Register (cross-check Fase 0.5, diperbarui Fase 0.6)

### C.1 Database gaps
1. **Tabel dasar tanpa migration — RESOLVED Fase 0.6:** production export (`eams_database.sql`) memverifikasi seluruh tabel dasar ADA dengan DDL lengkap (lihat docs/03 versi production-verified); **kecuali** `it_device_logs` & famili `compliance_checklist_*` yang terbukti TIDAK ADA di production (dead code) → Q-002 RESOLVED.
2. **Kolom dipakai kode tanpa migration — RESOLVED Fase 0.6:** seluruhnya terverifikasi ADA di production dengan tipe pasti (`checklist_frequency` ENUM default 'monthly', `allow_na` tinyint, `time_slot` varchar(5), `follow_up_status` ENUM default 'open', `permission` ENUM('read','write'), `wa_number`, `photo`, `active`, `cpu` longtext) → Q-003 RESOLVED. Pengecualian: `checklist_logs.updated_at` terbukti **tidak ada** (dead write, CONF-DB-015).
3. **ENUM konflik:** `checklist_logs.status` (CONF-001) — RESOLVED Fase 0.6 (production ENUM ok/not_ok/na).
4. **FK diasumsikan tanpa constraint:** hampir semua relasi lintas tabel hanya konvensi; kebijakan tak konsisten (CONF-011); di production `audit_logs` pun TANPA FK ke users (CONF-DB-006).
5. **Unique constraint — production verified (Fase 0.6):** `asset_code` UNIQUE ADA (`uniq_asset_code`, koreksi CONF-DB-005); `users.username` UNIQUE ADA (CONF-DB-016); `ipal_logs.log_date` UNIQUE ADA (CONF-DB-004). Yang tetap **application-level**: `checklist_logs (inventory_id, period_key[, time_slot])`, `holidays.holiday_date`, `users.email`, `it_device_commands.command_id` (KEY non-unique), `asset_item_types.code`.
6. **Nullable/default tak diketahui — RESOLVED Fase 0.6:** seluruh nullable/default kini diketahui dari production export (docs/03).

### C.2 Route/controller gaps
1. **Route → method hilang (4):** `compliance/dashboard/risk-trend`, `compliance/dashboard/data`, `compliance/inventory/create`, `compliance/inventory/edit/(:num)`.
2. **Method tanpa route:** `qrBatch`, `exportPeriodePage`, seluruh `ComplianceChecklistController`, seluruh `ComplianceCalendarController`, `AssetItemTypeController::byCategory`, `Home::index`.
3. **UI memanggil endpoint tak ditemukan:** `dashboard.js` → risk-trend/data; view kalender → `compliance/calendar/events`; `overdue.php` → `compliance/inventory/{id}`.
4. **View yatim (4):** `compliance/dashboard/overdue.php`, `dashboard.php` (0 byte), `welcome_message.php`, `checklist/export_periode.php` (direferensikan method dead).
5. **Inkonsistensi permission:** `pdfAccess` tak terpasang (Q-008); `/unauthorized` 404 (Q-010); grid CCTV membolehkan staff/auditor membaca sedangkan grid lain admin/compliance; hanya `employees/unassign` yang memakai filter `write` eksplisit (sisanya bergantung WriteFilter global).

---

> **Traceability:** setiap BR merujuk file+method sumber; setiap konflik merujuk dua sumber; setiap keputusan mengarah ke `docs/15`. Tidak ada rule tanpa asal.
> **Prinsip:** Legacy Code → Audit → Cross-check → **Business Specification (dokumen ini)** → Human Decision (`docs/15`) → Laravel Architecture → Laravel Implementation. **Jangan melompati tahapan.**