# 19 — EAMS Decision Log

> **Fase 1 (2026-08-18).** Register seluruh keputusan bisnis final yang telah disetujui **Project Owner / stakeholder**.
> **Sumber keputusan:** Human Decision (Project Owner), dikombinasikan dengan evidence Production Database (`eams_database.sql`) dan Legacy Source Code (`alpianreza/eams`).
> **Decision maker:** Project Owner. **Tanggal keputusan:** 2026-08-18.
> Dokumen ini adalah **satu-satunya sumber keputusan final**. Business behavior Laravel mengacu ke sini.

---

## Mapping: Keputusan Stakeholder ↔ Ambiguity Audit

Penomoran keputusan stakeholder (dokumen ini) **berbeda** dari penomoran ambiguity audit di `docs/15-ambiguities-need-decision.md`. Tabel ini menjembatani keduanya agar tidak ada kebingungan:

| Decision Log (dokumen ini) | Menjawab Ambiguity Audit (docs/15) | Topik |
|---|---|---|
| Q-001 (NA) | Q-020 | NA per kanal checklist |
| Q-002 (DB Migration) | — (baru, arsitektur) | Database baru + import |
| Q-003 (Legacy Columns) | — (kebijakan; melengkapi Q-002/Q-003 audit) | Kolom mana yang dibawa |
| Q-004 (Period Status) | Q-004 | Engine status periode tunggal |
| Q-005 (Saturday) | Q-005 | Tanggal efektif Sabtu libur |
| Q-006 (Checked By) | Q-006 | checked_by → user_id + snapshot |
| Q-007 (PIC) | Q-007 | pics = source of truth |
| Q-008 (PDF Access) | Q-008 | Pembatasan akses PDF |
| Q-009 (Risk Trend) | Q-009 | Endpoint dead tidak dibawa |
| Q-012 (Device Online) | Q-012 | Threshold online = 10 menit |
| Q-013 (NOT_OK Evidence) | Q-013 | not_ok wajib remark/foto + bypass grid |
| Q-014 (Dead Tables) | Q-014 | Famili compliance_checklist_* tidak dibawa |
| Q-015 (Item Type Identifier) | — (technical debt, docs/14) | Gunakan code, bukan hard-coded id |
| Q-016 (Grid Checklist) | — (konfirmasi fitur) | Grid = fitur resmi, 2 mode |
| Q-017 (Inventory Status) | Q-017 | GOOD / NEED_REPAIR / NOT_ACTIVE |
| Q-018 (Expiry) | — (baru) | expired_date terutama APAR |
| Q-019 (Specific Area) | — (baru) | area + specific_area dipertahankan |
| Q-020 (Asset Code) | — (baru; lihat BR-19) | asset_code dipertahankan persis |
| Q-021 (QR URL) | — (baru; lihat BR-20) | QR URL kompatibel legacy |
| Q-022 (File Storage) | — (baru) | storage configurable |
| Q-023 (Checklist History) | Q-023 (audit) | updated_at + checklist_log_histories (Laravel improvement) |

**Ambiguity audit yang TIDAK terjawab oleh keputusan stakeholder** (tetap terbuka, lihat §Akhir): Q-011, Q-015, Q-016, Q-018, Q-019, Q-021, Q-024, Q-025.
**Ambiguity audit yang dipindahkan ke architecture phase (technical):** Q-010, Q-022, Q-026.

---

## Q-001 — NA (Checklist Result)
- **Status:** RESOLVED
- **Decision:** NA adalah hasil checklist yang **valid** apabila item mengizinkannya (`asset_item_types.allow_na = true`). Rule: `allow_na=true` → OK, NOT_OK, atau NA diperbolehkan; `allow_na=false` → NA tidak diperbolehkan. NA **bukan** pending / failure / late — dianggap hasil valid. Jika seluruh pertanyaan suatu periode memiliki hasil valid (OK / NOT_OK / NA jika diizinkan), periode dapat dianggap **DONE**.
- **Reason:** menyamakan semantik NA di semua kanal; NA legacy sudah ada di ENUM production (`checklist_logs.status`).
- **Evidence:** Production DB `checklist_logs.status enum('ok','not_ok','na')`; `asset_item_types.allow_na tinyint(1) DEFAULT 0`; `_form.php`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist (semua kanal), Ranking, Dashboard, Report. Menutup ambiguity dukungan NA per kanal.
- **Laravel Implication:** satu validasi status terpusat; NA diterima di semua kanal selama `allow_na` item mengizinkan.

## Q-002 — Database Migration
- **Status:** RESOLVED
- **Decision:** Laravel memakai **DATABASE BARU** dengan schema Laravel yang clean. JANGAN memakai DB CI4 secara langsung. Semua data legacy **dimigrasikan** via proses import. Arsitektur: `EAMS CI4 DB → Legacy Data Migration/Import → EAMS Laravel DB`. Schema Laravel boleh berbeda dari CI4 selama data penting tidak hilang, business meaning tidak hilang, histori tetap dapat ditelusuri.
- **Reason:** schema CI4 membawa technical debt (signedness tak seragam, FK tak konsisten, dead columns) — tidak layak dipakai langsung.
- **Evidence:** `docs/18-production-database-reconciliation.md` (CONF-DB-006, signedness, FK CASCADE).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** seluruh lapisan data; menentukan strategi migration & import.
- **Laravel Implication:** rancang schema baru + ETL/import terpisah (lihat `docs/20` §4–§6).

## Q-003 — Legacy Columns
- **Status:** RESOLVED
- **Decision:** Tidak semua kolom legacy wajib dibawa. **Bawa:** kolom yang punya business meaning, data historis penting, atau diperlukan untuk migrasi/histori. **Jangan bawa:** dead fields, technical artifacts, kolom kosong tanpa fungsi, legacy fields tanpa business meaning. Jika field berisi data tetapi obsolete/ambigu → JANGAN hapus otomatis; dokumentasikan & tentukan migration mapping.
- **Reason:** membersihkan schema tanpa kehilangan makna/data.
- **Evidence:** `docs/18` (dead/legacy columns: `checklist_master.frequency`, `it_device_logs`, dsb.).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** desain schema Laravel; pemetaan kolom legacy→baru.
- **Laravel Implication:** setiap kolom legacy punya keputusan mapping eksplisit (lihat `docs/20` §6).

## Q-004 — Checklist Period Status
- **Status:** RESOLVED
- **Decision:** Gabungkan dua legacy period-status engine menjadi **satu engine**. Canonical status: **DONE, OPEN, LATE, FUTURE, HOLIDAY**. DONE=periode selesai; OPEN=masih dapat dikerjakan; LATE=lewat batas waktu tapi belum selesai; FUTURE=belum boleh dikerjakan; HOLIDAY=libur, checklist tidak diwajibkan. Behavior legacy dipertahankan sedekat mungkin. **Jangan** membuat dua engine status terpisah lagi.
- **Reason:** menghapus duplikasi engine (CONF-002/CONF-022) yang membuat status bergantung urutan autoload.
- **Evidence:** Legacy `period_helper` (done/future/late/pending) + `period_status_helper` (done/holiday/locked/open); `docs/17` CONF-002.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist engine, Calendar UI, Dashboard, Home, Progress, Reminder.
- **Laravel Implication:** satu Period Engine (value object/service) dengan 5 status kanonik (lihat `docs/20` §18).

## Q-005 — Saturday Effective Date
- **Status:** RESOLVED
- **Decision:** Effective date policy: **sebelum 1 April 2026** Sabtu = working day; **mulai 1 April 2026** Sabtu = holiday. Jangan terapkan rule baru secara retroaktif. Contoh: 28 Mar 2026 (Sabtu) → working day; 4 Apr 2026 (Sabtu) → holiday. Histori harus konsisten dengan policy pada saat itu.
- **Reason:** menjaga kebenaran perhitungan late/pending historis.
- **Evidence:** Legacy `checklist_helper::is_weekend_offday` (Sabtu ≥ 2026-04-01); `docs/17` CONF-003.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist daily, Report historis, migrasi data histori.
- **Laravel Implication:** offday engine memakai tanggal efektif sebagai konfigurasi (lihat `docs/20` §18).

## Q-006 — Checked By
- **Status:** RESOLVED
- **Decision:** Legacy `checked_by` = string. Laravel: gunakan **user relationship + snapshot** — kolom `checked_by_user_id` (referensi `users.id`) **dan** `checked_by_name` (nama user saat checklist dilakukan). Tujuan: histori tetap dapat ditelusuri walau user ganti nama atau akun inactive.
- **Reason:** string nama rentan duplikat & kehilangan jejak saat rename (Q-006 audit).
- **Evidence:** Production DB `checklist_logs.checked_by varchar(100)` (LEGACY DATABASE BEHAVIOR).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** schema `checklist_logs`, Ranking, Report, migrasi data.
- **Laravel Implication:** dua kolom (FK + snapshot); import memetakan nama→user_id bila bisa, sambil menyimpan nama aslinya (lihat `docs/20` §6, §18).

## Q-007 — Compliance Inventory PIC
- **Status:** RESOLVED
- **Decision:** `compliance_inventory_pics` menjadi **SOURCE OF TRUTH**. PIC berdasarkan `user_id`, **maksimal 2** PIC per inventory, **kedua PIC berkedudukan sama** — **TIDAK ADA primary/secondary PIC** (`is_primary` legacy bukan business rule Laravel). Legacy `compliance_inventory.pic`: bukan source of truth; hanya dipertahankan jika diperlukan untuk migration/backward compatibility; business logic Laravel harus memakai relation PIC.
- **Reason:** menghapus dual-mechanism (teks vs relasi) yang berisiko tidak sinkron (CONF-004).
- **Evidence:** Production DB `compliance_inventory_pics` (UNIQUE(inventory_id,user_id)); `compliance_inventory.pic varchar(100)`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Compliance Inventory, Home, Progress, Reminder, Notifications.
- **Laravel Implication:** relasi many-to-many (maks 2) tanpa flag primary; kolom teks `pic` hanya untuk import (lihat `docs/20` §6, §9).

## Q-008 — PDF Access
- **Status:** RESOLVED
- **Decision:** PDF Compliance hanya dapat diakses oleh: **(1) Admin** dan **(2) user yang mempunyai akses Compliance**. User lain tidak boleh mengakses PDF Compliance. Authorization harus memakai **permission/access control** — jangan hanya hard-code role jika architecture memungkinkan permission-based authorization.
- **Reason:** menutup celah `pdfAccess` yang didefinisikan tapi tidak pernah dipasang (CONF-005).
- **Evidence:** Legacy `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']`; route `export/pdf/*` hanya `auth`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Reports & PDF, Authorization.
- **Laravel Implication:** policy/gate permission-based untuk export PDF (lihat `docs/20` §14, §22).

## Q-009 — Risk Trend
- **Status:** RESOLVED
- **Decision:** Legacy Risk Trend endpoint/dashboard yang dead **TIDAK dibawa** ke Laravel. Jangan mereplikasi dead/unused feature hanya karena ada di legacy.
- **Reason:** route menunjuk method yang tidak ada (fitur mati) — replikasi = membawa dead code.
- **Evidence:** `docs/17` CONF-006 (route `risk-trend`/`data` → method hilang).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Dashboard (scope berkurang, sesuai fitur aktif).
- **Laravel Implication:** dashboard hanya memuat widget yang terbukti aktif.

## Q-012 — Device Online
- **Status:** RESOLVED
- **Decision:** Device dianggap **ONLINE** apabila heartbeat terakhir **≤ 10 menit (> 600 detik → offline)**. Lebih dari 10 menit = OFFLINE. Gunakan **satu centralized configuration**. Jangan membuat threshold berbeda antara dashboard, helper, dan status checker.
- **Reason:** menghapus inkonsistensi threshold 600s vs 48 jam (CONF-008).
- **Evidence:** Legacy `Commands/DeviceStatusCheck` (600s) vs `device_helper::device_is_online` (≥48 jam).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** IT Device Monitoring (UI, status command, dashboard).
- **Laravel Implication:** satu konstanta/config `device_online_threshold_seconds` dipakai semua lapisan (lihat `docs/20` §5, §18).

## Q-013 — NOT_OK Evidence
- **Status:** RESOLVED
- **Decision:** Default rule: **NOT_OK harus memiliki minimal salah satu: remark ATAU photo**. Valid: `NOT_OK+remark`, `NOT_OK+photo`. Invalid: `NOT_OK` tanpa keduanya. Untuk **STANDARD CHECKLIST**: jika keduanya kosong → tidak boleh menyelesaikan checklist. **EXCEPTION: GRID CHECKLIST boleh bypass** validasi ini untuk fast entry (penting untuk checklist harian dengan banyak pertanyaan seperti P3K). `require_photo` tetap dipertahankan sebagai konfigurasi master untuk kebutuhan khusus — **jangan** menganggap require_photo berarti semua checklist harus selalu upload foto.
- **Reason:** menegakkan akuntabilitas temuan, sambil menjaga kecepatan grid entry.
- **Evidence:** Legacy `submitChecklist` (not_ok wajib remark/foto); grid `save*Grid` (bypass); `checklist_master.require_photo`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist (standard + grid), Evidence, Report.
- **Laravel Implication:** validator evidence diterapkan berbeda per mode (standard ketat, grid bypass) — lihat `docs/20` §18–§19.

## Q-014 — Dead Compliance Checklist Tables
- **Status:** RESOLVED
- **Decision:** Jangan membawa keluarga legacy berikut ke Laravel: `compliance_checklist_master`, `compliance_checklist_log_items`, `compliance_checklist_logs`, `compliance_checklist_schedules`, `compliance_checklist_templates`. Alasan: tidak ada di production DB, bukan runtime aktif, tidak diperlukan berdasarkan keputusan stakeholder.
- **Reason:** membersihkan dead code; dikonfirmasi production schema + stakeholder.
- **Evidence:** Production DB (kelimanya TIDAK ADA — CONF-DB-002); model/migration legacy tidak ter-rute.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** scope schema Laravel (tabel ini tidak dibuat).
- **Laravel Implication:** tidak ada migration untuk famili ini.

## Q-015 — Item Type Identifier
- **Status:** RESOLVED
- **Decision:** Jangan menggunakan **hard-coded item_type ID** untuk business logic. Gunakan **stable business identifier** seperti `asset_item_types.code` (mis. APAR, CCTV, P3K, TOILET). Jangan: `if item_type_id == 1`. Gunakan business code/configuration. Untuk behavior khusus: jangan membuat architecture berlebihan — gunakan pendekatan sederhana & maintainable berdasarkan kebutuhan aktual.
- **Reason:** menghapus kerapuhan konstanta id hard-coded (CCTV=13, APAR=1, TOILET=52) yang rusak bila id berubah (technical debt, docs/14).
- **Evidence:** Legacy `ComplianceInventoryController` konstanta `*_ITEM_TYPE_ID`; `asset_item_types.code`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist channels, Report/Print, Grid khusus.
- **Laravel Implication:** lookup behavior by `code`, bukan id (lihat `docs/20` §9, §19).

## Q-016 — Grid Checklist
- **Status:** RESOLVED
- **Decision:** **GRID CHECKLIST tetap menjadi fitur resmi** EAMS Laravel. Ada **dua mode**: STANDARD CHECKLIST dan GRID CHECKLIST. GRID: fast/mass entry; **boleh bypass NOT_OK evidence validation**; tetap menghasilkan OK / NOT_OK / NA; **NA tetap tunduk pada allow_na**; period status tetap menggunakan unified period engine. Jangan menganggap Grid sebagai workaround/dead code.
- **Reason:** grid adalah kanal pengisian utama yang aktif (CCTV, P3K, Gate, dll.).
- **Evidence:** Legacy 12 `save*Grid` + `genericGrid`; `docs/17` §5.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Checklist Execution (dua mode resmi), UI.
- **Laravel Implication:** arsitektur checklist mendukung 2 mode secara eksplisit (lihat `docs/20` §18–§19).

## Q-017 — Inventory Status
- **Status:** RESOLVED
- **Decision:** Inventory condition memiliki **3 status: GOOD, NEED_REPAIR, NOT_ACTIVE**. Ini **berbeda** dengan checklist result. Jangan mencampur "inventory status" vs "checklist status".
- **Reason:** menetapkan enum resmi (menggantikan free text `varchar(50)` legacy).
- **Evidence:** Production DB `compliance_inventory.status varchar(50)` (free text); UI `_modal_edit.php` (Good/Need Repair/Not Active); `assets.status enum('aktif','rusak','mutasi')`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Compliance Inventory, Dashboard, Report.
- **Laravel Implication:** enum/lookup resmi untuk status kondisi inventory (lihat `docs/20` §6, §10).

## Q-018 — Expiry
- **Status:** RESOLVED
- **Decision:** `expired_date` terutama ditampilkan untuk **FIRE EXTINGUISHER / APAR**. Jangan menampilkan expiry field untuk semua inventory secara default. Expiry **tidak otomatis** mengubah inventory status menjadi NOT_ACTIVE — status GOOD + expiry EXPIRED tetap dapat terjadi. Jika di masa depan inventory type lain membutuhkan expiry: gunakan **configuration**, bukan hard-coded UI.
- **Reason:** expiry adalah atribut APAR-sentris, bukan universal; memisahkan kondisi vs kedaluwarsa.
- **Evidence:** Legacy print APAR (`expired_date`); `compliance_inventory.expired_date`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Compliance Inventory, Report/Print APAR, UI.
- **Laravel Implication:** visibilitas expiry dikonfigurasi per item type (lihat `docs/20` §9, §18).

## Q-019 — Specific Area
- **Status:** RESOLVED
- **Decision:** Pertahankan behavior EAMS legacy. `area` → master area; `specific_area` → detail lokasi tambahan. **Jangan** redesign menjadi master relation baru. Tetap mendukung: filtering, grouping, batch print, reporting.
- **Reason:** struktur area+specific_area sudah dipakai luas (grid, report, print).
- **Evidence:** Legacy `compliance_inventory.area_id` + `specific_area`; grid/report menggunakannya.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** Compliance Inventory, Report, Print.
- **Laravel Implication:** dua kolom dipertahankan; index untuk filter (lihat `docs/20` §6).

## Q-020 — Asset Code
- **Status:** RESOLVED
- **Decision:** Asset code adalah **BUSINESS IDENTIFIER**. Semua asset_code legacy harus dipertahankan **PERSIS** ketika migrasi. **Jangan regenerate** asset_code lama. Asset baru: gunakan generator Laravel dengan konsep/format yang sama dengan legacy (`KODEKATEGORI-KODEITEM-###`). Jika ditemukan duplicate/conflict: **JANGAN otomatis rename** — laporkan sebagai migration issue.
- **Reason:** asset_code tercetak di QR fisik & dipakai di mana-mana; mengubahnya merusak referensi.
- **Evidence:** Production DB `uniq_asset_code` UNIQUE; legacy auto-generate (BR-19).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** migrasi data, QR, Compliance Inventory, Report.
- **Laravel Implication:** import mempertahankan asset_code; generator baru memakai format sama; konflik = dilaporkan, bukan di-rename (lihat `docs/20` §6).

## Q-021 — QR URL
- **Status:** RESOLVED
- **Decision:** QR URL harus tetap **PERSIS** seperti EAMS legacy. Jangan mengubah format URL hanya karena Laravel. Laravel harus menyediakan route/endpoint yang **kompatibel dengan URL legacy**. QR lama sebisa mungkin tetap valid. Asset code tetap sama. QR image **boleh diregenerate**, tetapi payload/URL harus sama.
- **Reason:** ratusan QR fisik sudah tercetak menempel di aset — mengubah URL membuatnya mati.
- **Evidence:** Legacy QR payload = `base_url('compliance/inventory/detail/{id}')` (BR-20).
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** QR, Compliance Inventory, routing Laravel.
- **Laravel Implication:** sediakan route legacy-compatible `compliance/inventory/detail/{id}`; QR image boleh dibuat ulang via paket lokal (lihat `docs/20` §21).

## Q-022 — File Storage
- **Status:** RESOLVED
- **Decision:** Semua file/evidence legacy harus dimigrasikan (inventory photo, checklist photo, evidence, QR image, attachment relevan). Namun **storage location HARUS CONFIGURABLE**. Jangan hard-code `storage/app/...`. Design harus memungkinkan: Local Disk / Network Share / Custom Path (mis. `D:\EAMS\files` atau `\\SERVER-FILE\EAMS`). Application tidak boleh bergantung pada absolute path yang ditanam di source code. Logical categories minimal: **inventory photos, checklist evidence, QR images, attachments**.
- **Reason:** deployment Windows on-prem dengan kebutuhan path jaringan; legacy menanam `FCPATH.'uploads/...'`.
- **Evidence:** Legacy `FCPATH . 'uploads/...'` di banyak controller; BackupManager `D:\EAMS-Backups`.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** File storage, migrasi file, Backup, deployment.
- **Laravel Implication:** Laravel Filesystem disks per kategori + path via config/env (lihat `docs/20` §17).

## Q-023 — Checklist History
- **Status:** RESOLVED
- **Decision:** Legacy `checklist_logs` tidak memiliki `updated_at`. Untuk Laravel: `checklist_logs` memakai `created_at` + `updated_at`. **Tambahkan history/audit table** `checklist_log_histories` yang minimal mencatat: `checklist_log_id`, `changed_by_user_id`, `changed_by_name`, `old_status`, `new_status`, `old_remark`, `new_remark`, `old_photo`, `new_photo`, `changed_at`. Tujuan: audit trail perubahan checklist. **Ini adalah IMPROVEMENT ARSITEKTUR LARAVEL, bukan legacy behavior.** Pisahkan dengan jelas LEGACY BEHAVIOR vs LARAVEL IMPROVEMENT.
- **Reason:** koreksi grid mengubah data tanpa jejak (legacy); stakeholder menginginkan audit trail.
- **Evidence:** Production DB `checklist_logs` tanpa `updated_at` (CONF-DB-015); grid `save*Grid` menulis `updated_at` yang dibuang.
- **Date:** 2026-08-18
- **Decision maker:** Project Owner
- **Impact:** schema `checklist_logs` (+tabel history baru), Evidence, Audit.
- **Laravel Implication:** timestamps aktif + model observer menulis `checklist_log_histories` (lihat `docs/20` §20).

---

## Keputusan yang dipindahkan ke Architecture Phase (Technical Decision)

Bukan business behavior — diselesaikan sebagai bagian dari desain teknis di `docs/20-laravel-architecture.md` (bukan NEED HUMAN DECISION):

| Audit Q | Topik | Disposisi |
|---|---|---|
| Q-010 | `/unauthorized` tidak terdaftar (404) | TECHNICAL — Laravel menyediakan halaman 403 resmi (docs/20 §23) |
| Q-022 | Kebijakan FK ke tabel legacy | TECHNICAL — strategi FK/constraint pada schema baru (docs/20 §4) |
| Q-026 | Validasi upload foto tidak konsisten | TECHNICAL — standar validasi upload terpusat (docs/20 §17, §29) |

## Ambiguity yang masih membutuhkan keputusan manusia (NEED HUMAN DECISION)

Tidak terjawab oleh keputusan stakeholder di atas; tetap terbuka di `docs/15`:

| Audit Q | Topik | Kenapa masih terbuka |
|---|---|---|
| Q-011 | Weekly editable window 3 bulan vs monthly tanpa batas | business behavior (kebijakan backfill) |
| Q-015 | Jadwal produksi nyata (cron/schtasks) | butuh inventarisasi server produksi |
| Q-016 | `category_id=1` untuk asset otomatis agent | butuh DATA produksi (`asset_categories`) |
| Q-018 | Nilai `app_settings` produksi & template pesan | butuh DATA produksi (export settings) |
| Q-019 | Definisi "late" pada KPI dashboard | business behavior (definisi report) |
| Q-021 | User read-only & self-service settings | business behavior (hak akses) |
| Q-024 | Mark-all Heat Detector menimpa existing | business behavior (checklist execution) |
| Q-025 | Agent API menerima mutasi via GET | butuh verifikasi agent lapangan |

---

> **Rantai keputusan:** Legacy → Audit → Production Verification → **Human Decision (dokumen ini)** → Business Specification (`docs/17`) → Architecture Design (`docs/20`) → Architecture Review → Implementation.
> Seluruh 23 keputusan di atas adalah HUMAN-APPROVED. Jangan mengubah interpretasinya tanpa persetujuan Project Owner.
