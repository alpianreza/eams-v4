# 15 — Ambiguities / Need Human Decision (Final Decision List)

> **Aturan:** jangan memilih interpretasi sendiri. Setiap item butuh jawaban sebelum/di saat rebuild Laravel.
> **Update Fase 0.5 (2026-08-18):** 8 item baru (Q-019 s/d Q-026) + pengelompokan prioritas.
> **Update Fase 0.6 (2026-08-18):** rekonsiliasi production schema — 5 item RESOLVED BY PRODUCTION SCHEMA (Q-001, Q-002, Q-003, Q-014, Q-023).
> **Update Fase 1 (2026-08-18):** **Human Decisions dari Project Owner** — 10 item lagi RESOLVED (Q-004, Q-005, Q-006, Q-007, Q-008, Q-009, Q-012, Q-013, Q-017, Q-020). History ambiguity dipertahankan.
> **Sumber keputusan:** `Production Database` / `Legacy Source Code` / `Human Decision`. Detail keputusan final: **`docs/19-decision-log.md`**.

> **STATUS KINI: 26 item → 15 RESOLVED · 3 TECHNICAL DECISION (pindah ke architecture phase) · 8 NEED HUMAN DECISION.**

---

## Ringkasan Status (setelah Fase 1)

| Status | Jumlah | Item |
|---|---|---|
| **RESOLVED** | 15 | Q-001, Q-002, Q-003, Q-004, Q-005, Q-006, Q-007, Q-008, Q-009, Q-012, Q-013, Q-014, Q-017, Q-020, Q-023 |
| **TECHNICAL DECISION** (architecture phase) | 3 | Q-010, Q-022, Q-026 |
| **NEED HUMAN DECISION** (tersisa) | 8 | Q-011, Q-015, Q-016, Q-018, Q-019, Q-021, Q-024, Q-025 |

---

## RESOLVED

### Q-001 — ENUM `checklist_logs.status`
- **Status:** RESOLVED
- **Decision:** Nilai sah status checklist = `ok | not_ok | na`. Input legacy `ng` dinormalisasi ke `not_ok`.
- **Reason:** DB produksi sudah diubah dari migration awal; kode konsisten penuh dengan DB produksi.
- **Evidence:** **Production Database** — `eams_database.sql`: `checklist_logs.status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'`. **Legacy Source Code** — `submitChecklist` + semua `save*Grid` menulis `ok|not_ok|na` (`ng`→`not_ok`).
- **Impact:** Checklist, Report, Evidence, Ranking.
- **Laravel Implication:** kolom `status` memakai enum resmi `ok|not_ok|na`; normalisasi `ng`→`not_ok` saat import data historis.
- *(History: migration `2026-01-20-000003` membuat ENUM('ok','ng','na') yang tidak cocok dengan kode — terselesaikan oleh production schema, Fase 0.6.)*

### Q-002 — DDL tabel dasar tidak ada di repo
- **Status:** RESOLVED
- **Decision:** Schema Laravel didasarkan pada production-verified spec (docs/03), bukan rekonstruksi inferred.
- **Reason:** production export memuat DDL lengkap seluruh tabel dasar.
- **Evidence:** **Production Database** — `eams_database.sql` memuat DDL `users, employees, areas, inventory_categories, compliance_inventory, assets, asset_categories, asset_assignments, it_devices, holidays, boiler_fuel_logs, ipal_logs, checklist_master`. Tabel yang TIDAK ADA di production: `it_device_logs`, famili `compliance_checklist_*` → dead code.
- **Impact:** seluruh lapisan data.
- **Laravel Implication:** dasar schema = docs/03 (production-verified).

### Q-003 — Kolom tanpa migration
- **Status:** RESOLVED
- **Decision:** Semua kolom "dipakai tapi tak ada migration" kini diketahui DDL-nya dari production.
- **Reason:** tidak ada lagi kolom tanpa definisi.
- **Evidence:** **Production Database** — `checklist_frequency enum(...) DEFAULT 'monthly'`; `allow_na tinyint(1) DEFAULT 0`; `time_slot varchar(5)`; `follow_up_status enum('open','monitoring','closed') DEFAULT 'open'` + note/date; `permission enum('read','write') DEFAULT 'read'`; `wa_number varchar(20)`; `photo varchar(255)`; `active tinyint(1) DEFAULT 1`; `cpu longtext`.
- **Impact:** schema Laravel.
- **Laravel Implication:** kolom dibawa sesuai keputusan Q-003-decision-log (legacy columns policy).

### Q-004 — Semantik status periode
- **Status:** RESOLVED
- **Decision:** Gabungkan dua legacy period-status engine menjadi **satu engine** dengan canonical status **DONE / OPEN / LATE / FUTURE / HOLIDAY**. Behavior legacy dipertahankan sedekat mungkin; jangan buat dua engine lagi.
- **Reason:** menghapus duplikasi engine (CONF-002/022) yang membuat status bergantung urutan autoload.
- **Evidence:** **Human Decision** (Project Owner, docs/19 Q-004) + **Legacy Source Code** — `period_helper` (done/future/late/pending) vs `period_status_helper` (done/holiday/locked/open).
- **Impact:** Checklist engine, Calendar UI, Dashboard, Home, Progress, Reminder.
- **Laravel Implication:** satu Period Engine dengan 5 status kanonik (docs/20 §18).

### Q-005 — Aturan Sabtu sebelum 2026-04-01
- **Status:** RESOLVED
- **Decision:** Effective date policy — **sebelum 1 April 2026** Sabtu = working day; **mulai 1 April 2026** Sabtu = holiday. Tidak retroaktif; histori konsisten dgn policy saat itu.
- **Reason:** menjaga kebenaran hitung late/pending historis.
- **Evidence:** **Human Decision** (docs/19 Q-005) + **Legacy Source Code** — `checklist_helper::is_weekend_offday` (Sabtu ≥ 2026-04-01).
- **Impact:** Checklist daily, Report historis, migrasi data.
- **Laravel Implication:** offday engine memakai tanggal efektif sebagai konfigurasi.

### Q-006 — `checked_by` string nama vs FK user
- **Status:** RESOLVED
- **Decision:** Laravel memakai **user relationship + snapshot**: `checked_by_user_id` (FK `users.id`) **dan** `checked_by_name` (nama saat checklist dilakukan).
- **Reason:** string nama rentan duplikat & kehilangan jejak saat rename; histori harus tetap tertelusur walau user berubah/inactive.
- **Evidence:** **Human Decision** (docs/19 Q-006) + **Production Database** — legacy `checklist_logs.checked_by varchar(100)` (LEGACY DATABASE BEHAVIOR).
- **Impact:** schema `checklist_logs`, Ranking, Report, migrasi data.
- **Laravel Implication:** dua kolom (FK + snapshot); import memetakan nama→user_id bila bisa sambil menyimpan nama asli.

### Q-007 — Dua mekanisme PIC (nama vs relasi)
- **Status:** RESOLVED
- **Decision:** `compliance_inventory_pics` menjadi **SOURCE OF TRUTH**. PIC berdasarkan `user_id`, **maksimal 2**, **kedudukan sama — TIDAK ADA primary/secondary**. `is_primary` legacy bukan business rule Laravel. Kolom teks `pic` bukan source of truth; hanya untuk migration/backward-compat.
- **Reason:** menghapus dual-mechanism yang berisiko tidak sinkron (CONF-004).
- **Evidence:** **Human Decision** (docs/19 Q-007) + **Production Database** — `compliance_inventory_pics` (UNIQUE(inventory_id,user_id)); `compliance_inventory.pic varchar(100)`.
- **Impact:** Compliance Inventory, Home, Progress, Reminder, Notifications.
- **Laravel Implication:** relasi many-to-many (maks 2) tanpa flag primary; business logic memakai relasi.

### Q-008 — Penegakan PDF access
- **Status:** RESOLVED
- **Decision:** PDF Compliance hanya untuk **Admin** dan **user dengan akses Compliance**; user lain tidak boleh. Authorization memakai **permission/access control**, bukan hard-code role bila memungkinkan.
- **Reason:** menutup celah `pdfAccess` yang didefinisikan tapi tak pernah dipasang (CONF-005).
- **Evidence:** **Human Decision** (docs/19 Q-008) + **Legacy Source Code** — `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']`; route `export/pdf/*` hanya `auth`.
- **Impact:** Reports & PDF, Authorization.
- **Laravel Implication:** Gate/Policy permission-based untuk export PDF.

### Q-009 — Route dashboard yang method-nya tidak ada (Risk Trend)
- **Status:** RESOLVED
- **Decision:** Legacy Risk Trend endpoint/dashboard yang dead **TIDAK dibawa** ke Laravel. Jangan mereplikasi dead/unused feature.
- **Reason:** route menunjuk method yang tidak ada (fitur mati).
- **Evidence:** **Human Decision** (docs/19 Q-009) + **Legacy Source Code** — route `risk-trend`/`data` → method hilang (CONF-006).
- **Impact:** Dashboard (scope sesuai fitur aktif).
- **Laravel Implication:** dashboard hanya widget yang terbukti aktif.

### Q-012 — Threshold online device
- **Status:** RESOLVED
- **Decision:** Device **ONLINE** bila heartbeat terakhir **≤ 10 menit**; > 600 detik → OFFLINE. Satu **centralized configuration**; jangan threshold berbeda antar lapisan.
- **Reason:** menghapus inkonsistensi 600s vs 48 jam (CONF-008).
- **Evidence:** **Human Decision** (docs/19 Q-012) + **Legacy Source Code** — `Commands/DeviceStatusCheck` (600s) vs `device_helper::device_is_online` (≥48 jam).
- **Impact:** IT Device Monitoring (UI, status command, dashboard).
- **Laravel Implication:** satu config `device_online_threshold_seconds` dipakai semua lapisan.

### Q-013 — `require_photo` / bukti NOT_OK
- **Status:** RESOLVED
- **Decision:** Default rule: **NOT_OK wajib minimal salah satu remark ATAU photo**. Standard checklist: keduanya kosong → tidak boleh selesai. **EXCEPTION: GRID CHECKLIST boleh bypass** untuk fast entry (penting untuk P3K harian). `require_photo` tetap sebagai konfigurasi master untuk kebutuhan khusus — bukan berarti semua checklist wajib foto.
- **Reason:** menegakkan akuntabilitas temuan sambil menjaga kecepatan grid.
- **Evidence:** **Human Decision** (docs/19 Q-013) + **Legacy Source Code** — `submitChecklist` (not_ok wajib remark/foto); grid `save*Grid` (bypass); `checklist_master.require_photo`.
- **Impact:** Checklist (standard + grid), Evidence, Report.
- **Laravel Implication:** validator evidence berbeda per mode (standard ketat, grid bypass).

### Q-014 — Tabel & model `compliance_checklist_*`
- **Status:** RESOLVED
- **Decision:** Jangan bawa famili `compliance_checklist_master/log_items/logs/schedules/templates` ke Laravel — tidak ada di production DB, bukan runtime aktif, tidak diperlukan stakeholder.
- **Reason:** membersihkan dead code.
- **Evidence:** **Production Database** (kelimanya TIDAK ADA — CONF-DB-002) + **Human Decision** (docs/19 Q-014) + **Legacy Source Code** (model/migration tidak ter-rute).
- **Impact:** scope schema Laravel.
- **Laravel Implication:** tidak ada migration untuk famili ini.

### Q-017 — Nilai `status` asset & inventory
- **Status:** RESOLVED
- **Decision:** Inventory condition = **GOOD / NEED_REPAIR / NOT_ACTIVE** (3 status). Ini **berbeda** dengan checklist result — jangan dicampur.
- **Reason:** menetapkan enum resmi menggantikan free text.
- **Evidence:** **Human Decision** (docs/19 Q-017) + **Legacy Source Code** (`_modal_edit.php`: Good/Need Repair/Not Active) + **Production Database** (`compliance_inventory.status varchar(50)` free text).
- **Impact:** Compliance Inventory, Dashboard, Report.
- **Laravel Implication:** enum/lookup resmi untuk status kondisi inventory.

### Q-020 — Dukungan status NA per kanal
- **Status:** RESOLVED
- **Decision:** NA adalah hasil checklist **valid** bila item mengizinkan (`allow_na=true` → OK/NOT_OK/NA diperbolehkan; `allow_na=false` → NA tidak diperbolehkan). NA **bukan** pending/failure/late. Bila seluruh pertanyaan periode punya hasil valid, periode = DONE.
- **Reason:** menyamakan semantik NA di semua kanal.
- **Evidence:** **Human Decision** (docs/19 Q-001) + **Production Database** (`checklist_logs.status` enum berisi `na`; `allow_na tinyint(1)`).
- **Impact:** Checklist (semua kanal), Ranking, Dashboard, Report.
- **Laravel Implication:** satu validasi status terpusat; NA diterima di semua kanal selama `allow_na` mengizinkan.

### Q-023 — Kolom `updated_at` pada checklist_logs
- **Status:** RESOLVED
- **Decision:** Laravel `checklist_logs` memakai **`created_at` + `updated_at`**, DAN ditambah **history/audit table `checklist_log_histories`** (mencatat perubahan status/remark/photo + siapa & kapan). **Ini IMPROVEMENT ARSITEKTUR LARAVEL, bukan legacy behavior** — pisahkan dengan jelas.
- **Reason:** legacy menulis `updated_at` yang dibuang (kolom tak ada) → koreksi grid tanpa jejak; stakeholder menginginkan audit trail.
- **Evidence:** **Production Database** (`checklist_logs` tanpa `updated_at` — CONF-DB-015) + **Human Decision** (docs/19 Q-023) + **Legacy Source Code** (grid `save*Grid` menulis `updated_at`).
- **Impact:** schema `checklist_logs` (+tabel history), Evidence, Audit.
- **Laravel Implication:** timestamps aktif + model observer menulis `checklist_log_histories`.

---

## TECHNICAL DECISION (dipindahkan ke architecture phase — `docs/20`)

> Bukan business behavior; implementation detail. Diselesaikan sebagai bagian desain teknis, **bukan** NEED HUMAN DECISION.

### Q-010 — `/unauthorized` tidak terdaftar
- **Status:** TECHNICAL DECISION
- **Evidence (history):** banyak controller `redirect()->to('/unauthorized')`; route tidak ada → 404.
- **Disposisi:** Laravel menyediakan halaman **403 resmi** (error view) — tidak lagi redirect ke route 404. Ditangani di `docs/20` §23.

### Q-022 — Kebijakan foreign key ke tabel users
- **Status:** TECHNICAL DECISION
- **Evidence (history):** `notifications` sengaja tanpa FK; production `audit_logs` pun tanpa FK ke users; signedness id tak seragam.
- **Disposisi:** strategi FK/constraint pada schema baru (normalisasi tipe id, FK selektif, hindari CASCADE destruktif). Ditangani di `docs/20` §4–§5.

### Q-026 — Validasi upload foto tidak konsisten
- **Status:** TECHNICAL DECISION
- **Evidence (history):** foto checklist tanpa validasi; `updatePhoto` validasi mime saja; IT asset & employee validasi mime+size 2MB.
- **Disposisi:** satu komponen **validasi upload terpusat** (mime+size) untuk semua modul. Ditangani di `docs/20` §17, §29.

---

## NEED HUMAN DECISION (tersisa — 8 item)

> Tidak terjawab oleh keputusan stakeholder Fase 1. Tetap membutuhkan jawaban manusia / data produksi.

### Q-011 — Weekly editable window 3 bulan vs bulanan tanpa batas
- **Status:** NEED HUMAN DECISION (business behavior — kebijakan backfill).
- **Evidence:** `is_period_editable`: weekly grace 3 bulan, monthly selalu true (backfill), daily selama tidak future.
- **Pertanyaan untuk owner:** apakah asimetri weekly vs monthly disengaja? Samakan jendela backfill?
- **Modul terkait:** Checklist (engine periode).

### Q-015 — Jadwal produksi nyata (cron/scheduler)
- **Status:** NEED HUMAN DECISION (butuh inventarisasi server).
- **Evidence:** README menyarankan cron Senin untuk reminder; `it:status` tak terdokumentasi; auto-backup via schtasks Windows.
- **Pertanyaan untuk owner:** inventarisasi scheduler aktual di server produksi sebelum migrasi (apa saja yang berjalan & jam berapa).
- **Modul terkait:** Deployment, Notifications, IT Device, Backup.

### Q-016 — `category_id=1` untuk asset otomatis dari agent
- **Status:** NEED HUMAN DECISION (butuh **data** produksi).
- **Evidence:** `Api\AgentController::heartbeat` membuat asset baru dgn `category_id=1`. (Fase 0.6: `asset_categories` ADA — `id, category_name, sub_category`.)
- **Pertanyaan untuk owner:** cek isi `asset_categories` produksi — apakah id=1 = kategori IT? (Di Laravel: lookup by kode, bukan id — sudah diputuskan di Q-015 decision-log.)
- **Modul terkait:** IT Device Monitoring / Agent API.

### Q-018 — `app_settings` produksi & template pesan aktual
- **Status:** NEED HUMAN DECISION (butuh **data** produksi).
- **Evidence:** nilai default diseed migration; nilai aktual (token, template, nama perusahaan) di DB produksi. (Fase 0.6: struktur `app_settings` verified.)
- **Pertanyaan untuk owner:** ekspor `app_settings` produksi saat cutover (tanpa secret di repo) — siapa yang mengekspor & kapan.
- **Modul terkait:** Settings, Notifications (cutover).

### Q-019 — Definisi "late" pada KPI dashboard
- **Status:** NEED HUMAN DECISION (business behavior — definisi report).
- **Evidence:** `period_helper::is_period_late` = time-based (+21 hari / +28 hari / +3 bulan); `ComplianceDashboardController` KPI late = history-based (punya histori, belum ada log periode aktif).
- **Pertanyaan untuk owner:** samakan definisi late di dashboard dengan engine periode (time-based), atau dashboard memakai definisi tersendiri?
- **Modul terkait:** Dashboard, Home, Progress, Reminder.

### Q-021 — User read-only & self-service settings
- **Status:** NEED HUMAN DECISION (business behavior — hak akses).
- **Evidence:** `WriteFilter` memblokir semua POST non-whitelist untuk `permission=read`; self-service settings (ganti password) ikut terblokir.
- **Pertanyaan untuk owner:** apakah read-only user boleh mengubah password/kontaknya sendiri (kecualikan self-service dari WriteFilter)?
- **Modul terkait:** Settings, Authorization.

### Q-024 — Mark-all Heat Detector menimpa status existing
- **Status:** NEED HUMAN DECISION (business behavior — checklist execution).
- **Evidence:** semua `markAll*Grid` skip sel terisi; `markAllHeatDetectorGrid` meng-update existing menjadi `ok`.
- **Pertanyaan untuk owner:** apakah mark-all Heat Detector memang boleh menimpa, atau harus konsisten skip seperti grid lain?
- **Modul terkait:** Checklist Execution (grid Heat Detector).

### Q-025 — Agent API menerima mutasi via GET
- **Status:** NEED HUMAN DECISION (butuh verifikasi agent lapangan).
- **Evidence:** `Api\AgentController::resolvePayload` membaca payload dari query GET; heartbeat/command mengubah state.
- **Pertanyaan untuk owner:** cek implementasi agent aktual di lapangan — apakah agent memakai GET (sehingga Laravel harus tetap menerima GET) atau aman dibatasi ke POST?
- **Modul terkait:** IT Device Monitoring / Agent API, Security.

---

> **Total: 26 item — 15 RESOLVED · 3 TECHNICAL DECISION · 8 NEED HUMAN DECISION.**
> Keputusan final (dengan Reason/Evidence/Impact/Laravel Implication) terdokumentasi penuh di **`docs/19-decision-log.md`**. Desain teknis di **`docs/20-laravel-architecture.md`**.
> Item NEED HUMAN DECISION yang tersisa harus dijawab Project Owner (sebagian butuh data produksi) sebelum modul terkait diimplementasikan.
