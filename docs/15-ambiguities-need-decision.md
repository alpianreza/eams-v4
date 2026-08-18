# 15 — Ambiguities / Need Human Decision (Final Decision List)

> **Aturan:** jangan memilih interpretasi sendiri. Setiap item di bawah butuh jawaban manusia sebelum/di saat rebuild Laravel.
> **Update Fase 0.5 (2026-08-18):** 8 item baru (Q-019 s/d Q-026) + pengelompokan prioritas.
> **Update Fase 0.6 (2026-08-18):** rekonsiliasi production schema (`eams_database.sql`, MariaDB 10.4.32) — **5 item RESOLVED BY PRODUCTION SCHEMA** (Q-001, Q-002, Q-003, Q-014, Q-023); 2 item fakta-DB-nya resolved tetapi keputusan bisnisnya tetap terbuka (Q-006, Q-007). History ambiguity dipertahankan.
> **Status kini: 26 item total → 5 RESOLVED · 21 masih membutuhkan keputusan manusia (Critical 3 · Important 11 · Minor 7).**

---

## Ringkasan Prioritas (setelah Fase 0.6)

| Prioritas | Kapan diputuskan | Item |
|---|---|---|
| **Critical** | **Harus** sebelum Laravel coding (memblokir schema & core engine) | Q-004, Q-006, Q-007 |
| **Important** | Sebaiknya sebelum module terkait dibuat | Q-005, Q-008, Q-009, Q-012, Q-013, Q-016, Q-017, Q-018, Q-019, Q-020, Q-024 |
| **Minor** | Bisa diputuskan saat implementation | Q-010, Q-011, Q-015, Q-021, Q-022, Q-025, Q-026 |
| **RESOLVED BY PRODUCTION SCHEMA** | Selesai via `eams_database.sql` (Fase 0.6) | Q-001, Q-002, Q-003, Q-014, Q-023 |

---

# CRITICAL — harus diputuskan sebelum Laravel coding

### Q-001 — ENUM `checklist_logs.status` vs nilai `not_ok` → ✅ RESOLVED BY PRODUCTION SCHEMA
- **Status:** RESOLVED BY PRODUCTION SCHEMA (2026-08-18, Fase 0.6).
- **Evidence (history):** Migration `2026-01-20-000003_CreateChecklistLogs` membuat `status ENUM('ok','ng','na') DEFAULT 'ok'`; tetapi `submitChecklist` & semua grid menulis `'not_ok'`; report/dashboard/evidence membaca `'not_ok'`.
- **Why ambiguous (history):** DDL di repo tidak bisa menghasilkan data yang kode tulis → DB produksi hampir pasti sudah diubah manual.
- **RESOLUTION EVIDENCE:** `eams_database.sql` (MariaDB 10.4.32) → `checklist_logs.status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'`. DB produksi memang sudah diubah dari migration awal; **kode konsisten penuh dengan DB produksi** (`ng` hanya mapping input legacy → `not_ok`). Lihat `docs/18` CONF-DB-001.
- **Consequence:** nilai sah = `ok | not_ok | na`. Tidak ada lagi keputusan yang dibutuhkan untuk item ini.

### Q-002 — DDL tabel dasar tidak ada di repo → ✅ RESOLVED BY PRODUCTION SCHEMA
- **Status:** RESOLVED BY PRODUCTION SCHEMA (2026-08-18, Fase 0.6).
- **Evidence (history):** tidak ada migration untuk 16 tabel dasar; struktur hanya INFERRED dari model/query.
- **RESOLUTION EVIDENCE:** `eams_database.sql` memuat DDL lengkap seluruh tabel dasar: `users, employees, areas, inventory_categories, compliance_inventory, assets, asset_categories, asset_assignments, it_devices, holidays, boiler_fuel_logs, ipal_logs, checklist_master` — semuanya ADA (lihat `docs/03-database.md` versi production-verified).
- **PENGECEKAN SILANG PENTING:** tabel yang ternyata **TIDAK ADA** di production: `it_device_logs`, `compliance_checklist_master`, `compliance_checklist_log_items` (+ `compliance_checklist_logs/schedules/templates`) → model/migration terkait adalah dead code (CONF-DB-002/003, Q-014).
- **Consequence:** schema Laravel dapat didasarkan pada `docs/03` (production-verified), bukan lagi rekonstruksi inferred.

### Q-003 — Kolom tanpa migration → ✅ RESOLVED BY PRODUCTION SCHEMA
- **Status:** RESOLVED BY PRODUCTION SCHEMA (2026-08-18, Fase 0.6).
- **Evidence (history):** kolom dipakai kode tapi tidak dibuat migration mana pun.
- **RESOLUTION EVIDENCE (per kolom, dari `eams_database.sql`):**
  - `asset_item_types.checklist_frequency` = `enum('daily','weekly','monthly') NOT NULL DEFAULT 'monthly'`
  - `asset_item_types.allow_na` = `tinyint(1) DEFAULT 0`
  - `checklist_logs.time_slot` = `varchar(5) DEFAULT NULL`
  - `checklist_logs.follow_up_status` = `enum('open','monitoring','closed') DEFAULT 'open'`
  - `checklist_logs.follow_up_note` = `text NULL`; `follow_up_date` = `date NULL`
  - `users.permission` = `enum('read','write') DEFAULT 'read'`
  - `users.wa_number` = `varchar(20) NULL`; `users.photo` = `varchar(255) NULL`
  - `compliance_inventory.active` = `tinyint(1) NOT NULL DEFAULT 1`
  - `it_devices.cpu` = `longtext NULL` (wadah state JSON dari agent)
- **Consequence:** tidak ada lagi kolom "dipakai tapi tidak diketahui DDL-nya" pada daftar ini.

### Q-004 — Semantik status periode mana yang benar?
- **Status:** NEED HUMAN DECISION (tidak dapat diselesaikan oleh schema — ini keputusan **behavior**).
- **Evidence:** `period_helper::resolve_period_status` → done/future/late/pending; `period_status_helper::resolve_period_status` → done/holiday/locked/open; keduanya di-autoload (urutan menentukan pemenang). (Terkait CONF-022: `generate_calendar_periods` juga ganda.)
- **Why ambiguous:** dua bahasa status untuk UI kalender; tidak jelas mana yang disepakati bisnis (apakah "late" dan "pending" dibedakan? apakah "holiday" status tersendiri?).
- **Recommendation:** NEED HUMAN DECISION — pilih satu mesin status; dokumentasikan matrix status final untuk Laravel.
- **Kenapa Critical:** engine periode adalah jantung checklist; harus tunggal sebelum coding.

### Q-006 — `checked_by` string nama vs FK user
- **Status:** **fakta DB RESOLVED (Fase 0.6) — keputusan migrasi TETAP: NEED HUMAN DECISION.**
- **LEGACY DATABASE BEHAVIOR (production verified):** `checklist_logs.checked_by varchar(100) DEFAULT NULL` — string nama (`session name`), ditulis semua kanal pengisian; laporan/ranking group by string ini. Tabel lain (patrol, dst.) memakai INT user id.
- **Why ambiguous (tetap):** dua user bernama sama akan tercampur; rename user merusak histori tampilan.
- **LARAVEL MIGRATION DECISION:** A. Tetap string (audit trail beku). B. Migrasi ke user_id FK + simpan snapshot nama. → **NEED HUMAN DECISION** (keputusan arsitektur/bisnis, bukan fakta schema).
- **Kenapa Critical:** menentukan kolom & relasi tabel checklist_logs di Laravel + strategi migrasi data histori.

### Q-007 — Dua mekanisme PIC (nama vs relasi)
- **Status:** **fakta DB RESOLVED (Fase 0.6) — keputusan sumber kebenaran TETAP: NEED HUMAN DECISION.**
- **LEGACY DATABASE BEHAVIOR (production verified):**
  - `compliance_inventory.pic varchar(100) DEFAULT NULL` — kolom teks MASIH ADA; ditulis callback model (gabungan 2 dropdown user aktif, separator `" - "`) dan masih dibaca `ProgressController` + reminder WA (parsing nama).
  - `compliance_inventory_pics`: `id bigint(20) UNSIGNED AI`, `inventory_id int(11) NOT NULL` (SIGNED — mismatch dgn `compliance_inventory.id int(10) UNSIGNED` → FK mustahil tanpa normalisasi), `user_id int(11) NOT NULL`, `is_primary tinyint(1) NOT NULL DEFAULT 0`, `created_at NOT NULL DEFAULT current_timestamp()`; **UNIQUE(inventory_id,user_id)** + KEY(user_id,inventory_id); **TANPA FK**.
  - Relasi pics dipakai: `assignedToUser` (home/email reminder), notifikasi assignment (dedupe `inventory_assignment:{inv}:{user}`).
- **Why ambiguous (tetap):** mana sumber kebenaran PIC untuk progres/reminder? Kedua mekanisme hidup berdampingan.
- **LARAVEL MIGRATION DECISION:** A. Migrasi penuh ke relasi pics (+normalisasi tipe id). B. Pertahankan kompatibilitas ganda sementara. → **NEED HUMAN DECISION**.
- **Kenapa Critical:** memengaruhi schema (simpan/buang kolom `pic`), relasi, dan seluruh logika penugasan/notifikasi.

### Q-023 — Kolom `updated_at` pada checklist_logs → ✅ RESOLVED BY PRODUCTION SCHEMA
- **Status:** RESOLVED BY PRODUCTION SCHEMA (2026-08-18, Fase 0.6).
- **Evidence (history):** 12+ lokasi `save*Grid` menulis `'updated_at'`, tetapi `ChecklistLogModel::$allowedFields` tidak memuatnya & migration tidak membuat kolomnya.
- **RESOLUTION EVIDENCE:** `eams_database.sql` → `checklist_logs` **tidak memiliki kolom `updated_at`** (hanya `created_at`). Terbukti dua lapis (model + DB).
- **Conclusion:** **LEGACY CODE WRITES NON-PERSISTED FIELD** — penulisan dibuang diam-diam sejak awal; tidak ada data yang hilang karena kolom tidak pernah ada.
- **Consequence (bukan keputusan baru):** **jangan otomatis** menambahkan `updated_at` ke tabel checklist_logs di Laravel hanya karena kode legacy menulisnya — bila jejak koreksi grid diinginkan, itu keputusan fitur baru (catat di fase architecture).

---

# IMPORTANT — sebaiknya diputuskan sebelum module terkait dibuat

### Q-005 — Aturan Sabtu sebelum 2026-04-01
- **Status:** NEED HUMAN DECISION (behavior — schema tidak menjawab).
- **Evidence:** `is_weekend_offday`: Sabtu libur hanya `>= 2026-04-01`; `is_holiday` (legacy): Sabtu selalu libur.
- **Why ambiguous:** untuk histori checklist sebelum April 2026, apakah Sabtu dianggap hari kerja wajib? Memengaruhi hitung late/pending historis.
- **Recommendation:** NEED HUMAN DECISION — konfirmasi kebijakan & tanggal efektif; putuskan apakah re-kalkulasi histori diperlukan saat migrasi data.
- **Modul terkait:** Checklist (engine periode), Report historis.

### Q-008 — Penegakan PDF access
- **Status:** NEED HUMAN DECISION.
- **Evidence:** `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']` ada; route `export/pdf/*` hanya pakai `auth`; `pdfAccess` tidak dipasang di mana pun.
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Reports & PDF.

### Q-009 — Route dashboard yang method-nya tidak ada
- **Status:** NEED HUMAN DECISION.
- **Evidence:** Routes `compliance/dashboard/risk-trend → getRiskTrendAjax` dan `.../data → ajaxData`; kedua method tidak ada; `dashboard.js` berpotensi memanggilnya.
- **Recommendation:** NEED HUMAN DECISION — hapus route atau kembalikan fitur di Laravel.
- **Modul terkait:** Dashboard.

### Q-012 — Threshold online device: 600 dtk vs 48 jam
- **Status:** NEED HUMAN DECISION. (Fase 0.6: `it_devices.status enum('online','offline') DEFAULT 'offline'` verified — kolomnya pasti; yang terbuka = definisi perhitungannya.)
- **Evidence:** `Commands/DeviceStatusCheck`: offline bila last_seen > 600 dtk; `device_helper::device_is_online`: `max(172800, interval×2)` (≥48 jam).
- **Recommendation:** NEED HUMAN DECISION — satu sumber kebenaran status online.
- **Modul terkait:** IT Device Monitoring.

### Q-013 — `require_photo` pada master pertanyaan tidak ditegakkan
- **Status:** NEED HUMAN DECISION. (Fase 0.6: kolom `require_photo tinyint(1) DEFAULT 0` verified ada di production.)
- **Evidence:** submitChecklist hanya mewajibkan foto/remark untuk `not_ok`; tidak ada validasi "foto wajib bila require_photo=1".
- **Possible interpretations:** A. by design (foto hanya utk temuan). B. validasi hilang (bug).
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Master & Execution.

### Q-014 — Tabel & model `compliance_checklist_master` / `compliance_checklist_log_items` → ✅ RESOLVED BY PRODUCTION SCHEMA
- **Status:** RESOLVED BY PRODUCTION SCHEMA (2026-08-18, Fase 0.6).
- **Evidence (history):** model ada; migration tidak ada (untuk master/log_items); route aktif tidak memakai; `ComplianceChecklistLogModel` tidak cocok dgn migration-nya sendiri.
- **RESOLUTION EVIDENCE:** `eams_database.sql` → **tidak satu pun** tabel `compliance_checklist_*` (master, log_items, logs, schedules, templates) ada di production. Yang direferensikan kode hanyalah 3 model + migration legacy = **dead code** (CONF-DB-002).
- **Consequence:** tidak perlu dibuat di Laravel; sisa tindakan = housekeeping penghapusan dead code (bukan keputusan bisnis).

### Q-016 — `category_id=1` untuk asset otomatis dari agent
- **Status:** NEED HUMAN DECISION (membutuhkan **data** produksi — structure-only tidak menjawab).
- **Evidence:** `Api\AgentController::heartbeat` membuat asset baru dgn `category_id=1` + `inventory_no IT-PC-###`. (Fase 0.6: `asset_categories` ADA — `id int(11) AI, category_name varchar(50), sub_category varchar(50)`; `assets.category_id` ber-FK ke tabel ini.)
- **Recommendation:** NEED HUMAN DECISION — cek isi `asset_categories` produksi (apakah id=1 = IT); di Laravel: lookup by kode, bukan id.
- **Modul terkait:** IT Device Monitoring / Agent API.

### Q-017 — Nilai `status` asset & inventory bebas teks
- **Status:** NEED HUMAN DECISION (fakta DB kini lengkap; keputusan enum resmi tetap terbuka).
- **Fakta production (Fase 0.6):** `assets.status enum('aktif','rusak','mutasi') DEFAULT 'aktif'` (nilai `mutasi` tidak dipakai kode aktif — CONF-DB-019); `compliance_inventory.status varchar(50) DEFAULT NULL` (free text; UI memakai Good/Need Repair/Not Active — CONFIRMED via `_modal_edit.php`).
- **Recommendation:** NEED HUMAN DECISION — jadikan enum resmi di Laravel (nilai mana yang sah untuk masing-masing tabel; apakah `mutasi` dipertahankan).
- **Modul terkait:** IT Assets, Compliance Inventory.

### Q-018 — `app_settings` produksi & template pesan aktual
- **Status:** NEED HUMAN DECISION (membutuhkan **data** produksi).
- **Evidence:** nilai default diseed migration; nilai aktual berada di DB produksi. (Fase 0.6: struktur `app_settings` verified — `setting_key varchar(120) UNIQUE`, `setting_value text`, `is_secret tinyint(1)`, `updated_by`, `updated_at`.)
- **Recommendation:** NEED HUMAN DECISION — ekspor app_settings produksi saat migrasi (tanpa secret di repo).
- **Modul terkait:** Settings, Notifications (cutover).

### Q-019 — Definisi "late" pada KPI dashboard
- **Status:** NEED HUMAN DECISION (behavior).
- **Evidence:** `period_helper::is_period_late` = time-based (+21 hari / +28 hari / +3 bulan); `ComplianceDashboardController` KPI late = history-based.
- **Possible interpretations:** A. Time-based standar tunggal. B. History-based khusus dashboard. C. Keduanya dgn label berbeda.
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Dashboard, Home, Progress, Reminder.

### Q-020 — Dukungan status NA tidak konsisten antar kanal
- **Status:** NEED HUMAN DECISION (behavior). (Fase 0.6: `allow_na tinyint(1) DEFAULT 0` verified; ketidakkonsistenan antar kanal adalah fakta kode, bukan schema.)
- **Evidence:** form per-item menerima `na` bila `allow_na`; grid EL/EEL menerima `na`; grid CCTV menolak ubah sel `na` (409); grid lain tidak menerima `na`.
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Execution (semua grid).

### Q-024 — Mark-all Heat Detector menimpa status existing
- **Status:** NEED HUMAN DECISION (behavior).
- **Evidence:** semua `markAll*Grid` skip sel terisi; `markAllHeatDetectorGrid` meng-update existing menjadi `ok`.
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Execution (grid Heat Detector).

---

# MINOR — bisa diputuskan saat implementation

### Q-010 — `/unauthorized` tidak terdaftar
- **Status:** NEED HUMAN DECISION.
- **Evidence:** banyak controller `redirect()->to('/unauthorized')`; route tidak ada di Routes.php → CI4 404.
- **Recommendation:** NEED HUMAN DECISION — di Laravel sediakan halaman 403 resmi.

### Q-011 — Weekly editable window 3 bulan vs bulanan tanpa batas
- **Status:** NEED HUMAN DECISION.
- **Evidence:** `is_period_editable`: weekly grace 3 bulan, monthly selalu true (backfill), daily selama tidak future.
- **Recommendation:** NEED HUMAN DECISION.

### Q-015 — Jadwal produksi nyata (cron)
- **Status:** NEED HUMAN DECISION (butuh inventarisasi server).
- **Evidence:** README menyarankan cron Senin untuk reminder; `it:status` tidak terdokumentasi; auto-backup via schtasks Windows.
- **Recommendation:** NEED HUMAN DECISION — inventarisasi scheduler aktual di server produksi sebelum migrasi.

### Q-021 — User read-only tidak dapat self-service settings
- **Status:** NEED HUMAN DECISION.
- **Evidence:** `WriteFilter` memblokir semua POST non-whitelist untuk `permission=read`; self-service settings (POST `settings/change-password`) ikut terblokir. (Fase 0.6: `users.permission enum('read','write') DEFAULT 'read'` verified.)
- **Possible interpretations:** A. By design. B. Kecualikan self-service.
- **Recommendation:** NEED HUMAN DECISION.

### Q-022 — Kebijakan foreign key ke tabel users
- **Status:** NEED HUMAN DECISION. **Diperkaya Fase 0.6:** di production, `audit_logs` ternyata **TANPA FK** ke users (meski migration mendeklarasikannya) dan `notifications` sengaja tanpa FK → production konsisten tanpa FK ke users; mismatch signedness id juga terbukti (`users.id int(11)` SIGNED vs beberapa referensi UNSIGNED).
- **Possible interpretations:** A. FK penuh setelah normalisasi users. B. Tanpa FK (konvensi aplikasi) demi kompatibilitas.
- **Recommendation:** NEED HUMAN DECISION.

### Q-025 — Agent API menerima mutasi via GET
- **Status:** NEED HUMAN DECISION.
- **Evidence:** `Api\AgentController::resolvePayload` membaca payload dari query GET; heartbeat/command mengubah state.
- **Recommendation:** NEED HUMAN DECISION — cek implementasi agent aktual di lapangan.

### Q-026 — Validasi upload foto tidak konsisten
- **Status:** NEED HUMAN DECISION.
- **Evidence:** foto checklist tanpa validasi mime/size; `updatePhoto` inventory validasi mime saja; IT asset & employee validasi mime + size 2MB.
- **Recommendation:** NEED HUMAN DECISION (rekomendasi teknis: seragamkan).

---

> **Total: 26 item — 5 RESOLVED BY PRODUCTION SCHEMA (Q-001, Q-002, Q-003, Q-014, Q-023) · 21 NEED HUMAN DECISION (Critical 3: Q-004, Q-006, Q-007 · Important 11 · Minor 7).**
> Untuk Q-006 & Q-007: fakta database sudah final (production verified) — yang tersisa adalah **keputusan arsitektur/bisnis**, bukan ketidakpastian evidence.
> Setiap keputusan yang sudah dijawab manusia harus dicatat kembali ke dokumen ini (tambahkan `Decision:` + `Decided by/date:` per item) sebelum Laravel coding dimulai.
