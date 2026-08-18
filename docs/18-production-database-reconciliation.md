# 18 — Production Database Reconciliation

> **Fase 0.6 (2026-08-18).** Rekonsiliasi tiga sumber: **production schema** (`eams_database.sql`, structure-only phpMyAdmin export) × **source code CI4** (`alpianreza/eams` @ `78dd2a0`) × **dokumentasi audit** (`docs/00`–`docs/17`).
> **Prioritas evidence:** struktur = production DB; behavior = source code; dokumentasi audit = traceability. Dokumentasi audit TIDAK lebih tinggi dari evidence production DB.
> Tidak ada kode Laravel / migration / perubahan source code yang dibuat pada fase ini.

---

## 1. Database Metadata

| Atribut | Nilai (PRODUCTION VERIFIED) |
|---|---|
| Sumber | `eams_database.sql` (phpMyAdmin 5.2.1 export, structure-only, tanpa data bisnis) |
| Waktu export | 18 Agu 2026 08:22 |
| Server | **MariaDB 10.4.32** (host 127.0.0.1) |
| PHP server | 8.2.12 |
| Database | `asset_compliance_system` |
| Engine | **InnoDB — semua tabel** |
| Charset / Collation | **utf8mb4 / utf8mb4_general_ci — semua tabel** |
| Total tabel | **51** |
| SQL mode export | `NO_AUTO_VALUE_ON_ZERO` |

---

## 2. Table Inventory (FINAL — production verified)

Status: `CONFIRMED_ACTIVE` (dipakai kode aktif) / `CONFIRMED_LEGACY` (ada di DB, hanya jejak legacy) / `UNUSED` (ada di DB, tidak ditemukan penggunaan sama sekali) / `UNKNOWN`.

| # | Table | Module | PK | FK | Status | Evidence |
|---|---|---|---|---|---|---|
| 1 | app_settings | Settings | id | — | CONFIRMED_ACTIVE | SettingController, NotificationService, seeder migration |
| 2 | areas | Master Data | id | — | CONFIRMED_ACTIVE | AreaModel, ComplianceInventoryController (join+filter) |
| 3 | assets | IT Assets | id | category_id→asset_categories | CONFIRMED_ACTIVE | ITAssetController, Api\AgentController (auto-create IT-PC) |
| 4 | asset_assignments | IT Assets | id | — | CONFIRMED_ACTIVE | ITAssetController::assignSave/update (open/return) |
| 5 | asset_categories | IT Assets | id | — | CONFIRMED_ACTIVE | ITAssetController (filter `category_name='IT'`) |
| 6 | asset_item_types | Master Data | id | inventory_category_id→inventory_categories | CONFIRMED_ACTIVE | checklist engine (`checklist_frequency`, `allow_na`) |
| 7 | audit_logs | Administration | id | — | CONFIRMED_ACTIVE | AuthController, AuditLogController, filters |
| 8 | boiler_fuel_logs | Utility | id | — | CONFIRMED_ACTIVE | BoilerFuelController |
| 9 | checklist_logs | Checklist | id | 3 FK (inventory/item_type/template) | CONFIRMED_ACTIVE | seluruh kanal checklist, report, ranking, evidence |
| 10 | checklist_master | Checklist | id | item_type_id→asset_item_types | CONFIRMED_ACTIVE | ChecklistMasterController, semua form/grid |
| 11 | checklist_schedules | Checklist | id | item_type_id→asset_item_types | **CONFIRMED_LEGACY** | HANYA `ChecklistScheduleSeeder` + migration-nya; tidak ada controller/model aktif |
| 12 | compliance_calendar_events | Calendar | id | — | CONFIRMED_ACTIVE | **HolidayController** (aktif, guard `tableExists`) + ComplianceCalendarEventModel; `ComplianceCalendarController` tetap dead |
| 13 | compliance_inventory | Compliance Inventory | id | item_type_id→asset_item_types | CONFIRMED_ACTIVE | ComplianceInventoryController dsb. |
| 14 | compliance_inventory_pics | Compliance Inventory | id | — | CONFIRMED_ACTIVE | model callbacks, `assignedToUser`, email reminder |
| 15 | compliance_questionnaires | Questionnaire | id | — | CONFIRMED_ACTIVE | ComplianceQuestionnaireController |
| 16 | compliance_questionnaire_questions | Questionnaire | id | questionnaire_id→questionnaires | CONFIRMED_ACTIVE | idem |
| 17 | compliance_questionnaire_responses | Questionnaire | id | questionnaire_id→questionnaires | CONFIRMED_ACTIVE | idem (public submit) |
| 18 | compliance_questionnaire_response_answers | Questionnaire | id | response_id→responses, question_id→questions | CONFIRMED_ACTIVE | idem |
| 19 | employees | IT Assets | id | — | CONFIRMED_ACTIVE | EmployeeController |
| 20 | ems_electric_consumption_entries | EMS | id | — | CONFIRMED_ACTIVE | EmsReportController |
| 21 | ems_electric_consumption_years | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 22 | ems_mobile_combustion_entries | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 23 | ems_mobile_combustion_years | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 24 | ems_stationary_combustion_entries | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 25 | ems_stationary_combustion_years | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 26 | ems_water_consumption_entries | EMS | id | — | CONFIRMED_ACTIVE | idem (seed 2025) |
| 27 | ems_water_consumption_years | EMS | id | — | CONFIRMED_ACTIVE | idem |
| 28 | fdm_production_section_entries | FDM | id | year_id→fdm_years | CONFIRMED_ACTIVE | FdmDataCollectionController |
| 29 | fdm_production_section_years | FDM | id | — | CONFIRMED_ACTIVE | idem (ensureYears) |
| 30 | holidays | Master Data | id | — | CONFIRMED_ACTIVE | HolidayController, checklist_helper (offday) |
| 31 | inventory_categories | Master Data | id | — | CONFIRMED_ACTIVE | InventoryCategoryModel, seeder (FS/HSE/CTPAT/UTL) |
| 32 | ipal_logs | Utility | id | — | CONFIRMED_ACTIVE | IpalController |
| 33 | it_devices | IT Devices | id | asset_id→assets | CONFIRMED_ACTIVE | Api\AgentController, ITDeviceController |
| 34 | it_device_commands | IT Devices | id | — | CONFIRMED_ACTIVE | command queue (ITDeviceController/AgentController) |
| 35 | login_sessions | Authentication | id | — | CONFIRMED_ACTIVE | AuthController, AuditLogController (expire 8 jam) |
| 36 | migrations | Framework | id | — | CONFIRMED_ACTIVE | CI4 migration runner |
| 37 | notifications | Notifications | id | — | CONFIRMED_ACTIVE | NotificationService, HomeController |
| 38 | patrol_checkpoints | Patrol | id | — | CONFIRMED_ACTIVE | PatrolController |
| 39 | patrol_layouts | Patrol | id | — | CONFIRMED_ACTIVE | PatrolController (editor, admin) |
| 40 | patrol_logs | Patrol | id | — | CONFIRMED_ACTIVE | PatrolController::scanCheckpoint |
| 41 | patrol_log_photos | Patrol | id | — | CONFIRMED_ACTIVE | idem (foto wajib ≥1) |
| 42 | patrol_routes | Patrol | id | — | CONFIRMED_ACTIVE | PatrolController |
| 43 | patrol_route_checkpoints | Patrol | id | — | CONFIRMED_ACTIVE | idem (urutan checkpoint) |
| 44 | patrol_sessions | Patrol | id | — | CONFIRMED_ACTIVE | idem (1 sesi aktif/user/hari) |
| 45 | pdam_water_boiler_logs | Utility | id | — | CONFIRMED_ACTIVE | PdamWaterBoilerController |
| 46 | pdam_water_logs | Utility | id | — | CONFIRMED_ACTIVE | PdamWaterController |
| 47 | thermal_imaging_locations | Thermal | id | — | CONFIRMED_ACTIVE | ThermalImagingController |
| 48 | thermal_imaging_reports | Thermal | id | — | CONFIRMED_ACTIVE | idem |
| 49 | thermal_imaging_report_items | Thermal | id | report_id→reports, location_id→locations | CONFIRMED_ACTIVE | idem |
| 50 | users | Authentication | id | — | CONFIRMED_ACTIVE | AuthController, UserController, semua modul |
| 51 | user_roles | Administration | id | — | CONFIRMED_ACTIVE | UserController (role custom) |

**Ringkasan:** 51 tabel → **49 CONFIRMED_ACTIVE · 1 CONFIRMED_LEGACY (`checklist_schedules`) · 0 UNUSED · 1 framework (`migrations`)**.

**Tabel yang DIDUGA audit tetapi TIDAK ADA di production:** `compliance_checklist_master`, `compliance_checklist_log_items`, `compliance_checklist_logs`, `compliance_checklist_schedules`, `compliance_checklist_templates`, `it_device_logs` → keenamnya **bukan tabel produksi** (sisa kode/migration saja). Lihat Q-002/Q-014 di `docs/15`.

---

## 3. Schema Differences (register CONF-DB)

Format: Table / Difference / Audit Documentation / Production DB / Source Code / Conclusion / Status.

---

**CONF-DB-001**
- **Table:** `checklist_logs`
- **Difference:** nilai ENUM `status`
- **Audit Documentation:** migration `2026-01-20-000003` = `ENUM('ok','ng','na')` → konflik dgn kode `not_ok` (Q-001)
- **Production DB:** `status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'`
- **Source Code:** `submitChecklist` + semua `save*Grid` menulis `ok|not_ok|na` (`ng` dipetakan ke `not_ok`)
- **Conclusion:** DB produksi sudah diubah dari migration awal; **kode konsisten dengan DB produksi**. Q-001 → RESOLVED.
- **Status:** CONFIRMED

**CONF-DB-002**
- **Table:** famili `compliance_checklist_*`
- **Difference:** tabel terdokumentasi/dimodelkan tetapi tidak ada di production
- **Audit Documentation:** Q-014 — model `ComplianceChecklistMaster/Log/LogItem` ada; migration ada; penggunaan UNKNOWN
- **Production DB:** `compliance_checklist_master`, `compliance_checklist_log_items`, `compliance_checklist_logs`, `compliance_checklist_schedules`, `compliance_checklist_templates` — **semua TIDAK ADA**
- **Source Code:** hanya 3 model + migration legacy; tidak ter-rute
- **Conclusion:** famili legacy tidak pernah ada / sudah di-drop di production → murni dead code. Q-014 → RESOLVED.
- **Status:** LEGACY

**CONF-DB-003**
- **Table:** `it_device_logs`
- **Difference:** didokumentasikan "tabel tanpa writer"; aktualnya tidak ada di production
- **Audit Documentation:** Appendix B (Fase 0.5) — "tabel + model ada, tidak ada yang menulis"
- **Production DB:** **TIDAK ADA**
- **Source Code:** hanya `ItDeviceLogModel` (tidak dipakai controller mana pun)
- **Conclusion:** model menunjuk tabel non-eksisten; jika pernah dipanggil akan error. Dead model.
- **Status:** CONFIRMED

**CONF-DB-004**
- **Table:** `ipal_logs`
- **Difference:** unique constraint `log_date`
- **Audit Documentation:** BR-30 — "upsert per tanggal (**tanpa** UNIQUE DB — dedupe by query)"
- **Production DB:** `UNIQUE KEY unique_log_date (log_date)`
- **Source Code:** `IpalController::save` upsert by tanggal
- **Conclusion:** dokumentasi audit **salah**; DB menegakkan 1 entri/tanggal. BR-30 dikoreksi.
- **Status:** CONFIRMED

**CONF-DB-005**
- **Table:** `compliance_inventory`
- **Difference:** unique `asset_code`
- **Audit Documentation:** BR-19 Notes / Appendix C — "tanpa UNIQUE index (app-level saja)"
- **Production DB:** `UNIQUE KEY uniq_asset_code (asset_code)`
- **Source Code:** auto-generate `LIKE prefix%` +1 (pola check-then-insert)
- **Conclusion:** DB menegakkan unique; risiko race menyebabkan **error insert** (bukan duplikat). Koreksi audit.
- **Status:** CONFIRMED

**CONF-DB-006**
- **Table:** `audit_logs`
- **Difference:** FK ke `users`
- **Audit Documentation:** migration `2026-07-07` mendeklarasikan FK `user_id → users.id`
- **Production DB:** **tidak ada FK** (hanya PRIMARY KEY)
- **Source Code:** tidak bergantung pada FK (join manual)
- **Conclusion:** FK tidak pernah terbentuk/di-drop di production — konsisten dengan pola `notifications` (komentar "legacy users table differs between installations"). Untuk Laravel: jangan asumsikan FK lama ada.
- **Status:** CONFIRMED

**CONF-DB-007**
- **Table:** `users`
- **Difference:** kolom `permission` tanpa migration → sekarang diketahui
- **Audit Documentation:** Q-003/BR-41 — UNKNOWN tipe kolom
- **Production DB:** `permission enum('read','write') DEFAULT 'read'`
- **Source Code:** `UserController` validasi `in_array(['read','write'])`; `WriteFilter` membaca kolom ini
- **Conclusion:** sepenuhnya verified. Q-003 (sebagian) → RESOLVED.
- **Status:** CONFIRMED

**CONF-DB-008**
- **Table:** `asset_item_types`
- **Difference:** kolom `checklist_frequency` tanpa migration → sekarang diketahui
- **Audit Documentation:** Q-003
- **Production DB:** `checklist_frequency enum('daily','weekly','monthly') NOT NULL DEFAULT 'monthly'`
- **Source Code:** dibaca semua jalur checklist (`detail()`, `checklist()`, grid, report)
- **Conclusion:** verified; default 'monthly' menjelaskan fallback `$frequency ?? 'monthly'` di kode.
- **Status:** CONFIRMED

**CONF-DB-009**
- **Table:** `asset_item_types`
- **Difference:** kolom `allow_na`
- **Audit Documentation:** Q-003 / BR-12 (kolom dipakai, DDL UNKNOWN)
- **Production DB:** `allow_na tinyint(1) DEFAULT 0`
- **Source Code:** `_form.php` (`!empty($inventory['allow_na'])`)
- **Conclusion:** verified.
- **Status:** CONFIRMED

**CONF-DB-010**
- **Table:** `checklist_logs`
- **Difference:** kolom `time_slot`
- **Audit Documentation:** Q-003 / BR-14
- **Production DB:** `time_slot varchar(5) DEFAULT NULL`
- **Source Code:** toilet slot `PG/SI/SO`
- **Conclusion:** verified.
- **Status:** CONFIRMED

**CONF-DB-011**
- **Table:** `checklist_logs`
- **Difference:** kolom follow-up
- **Audit Documentation:** Q-003 (`follow_up_status/note/date` tanpa migration)
- **Production DB:** `follow_up_status enum('open','monitoring','closed') DEFAULT 'open'`, `follow_up_note text NULL`, `follow_up_date date NULL`
- **Source Code:** Evidence center (`updateFollowup`)
- **Conclusion:** verified; default 'open' konsisten dgn alur evidence baru.
- **Status:** CONFIRMED

**CONF-DB-012**
- **Table:** `users`
- **Difference:** kolom `wa_number`, `photo`
- **Audit Documentation:** Q-003
- **Production DB:** `wa_number varchar(20) NULL`, `photo varchar(255) NULL`
- **Source Code:** WhatsApp service, profil user
- **Conclusion:** verified.
- **Status:** CONFIRMED

**CONF-DB-013**
- **Table:** `compliance_inventory`
- **Difference:** kolom `active`
- **Audit Documentation:** Q-003
- **Production DB:** `active tinyint(1) NOT NULL DEFAULT 1`
- **Source Code:** reminder memfilter `active=1`
- **Conclusion:** verified.
- **Status:** CONFIRMED

**CONF-DB-014**
- **Table:** `it_devices`
- **Difference:** kolom `cpu` (state JSON)
- **Audit Documentation:** Q-003 ("cpu JSON state")
- **Production DB:** `cpu longtext DEFAULT NULL` (+ kolom hasil parse: `cpu_name`, `cpu_core`, `cpu_thread`, `ram_gb`, `storage_gb`, dll.)
- **Source Code:** `Api\AgentController::heartbeat` menulis JSON besar ke `cpu`
- **Conclusion:** verified — kolom `cpu` adalah wadah state mentah; kolom turunan disimpan terpisah.
- **Status:** CONFIRMED

**CONF-DB-015**
- **Table:** `checklist_logs`
- **Difference:** kolom `updated_at` yang ditulis kode
- **Audit Documentation:** CONF-010 / Q-023 — 12+ lokasi `save*Grid` menulis `updated_at`, diduga dibuang
- **Production DB:** **kolom `updated_at` TIDAK ADA** (hanya `created_at`)
- **Source Code:** `ChecklistLogModel::$allowedFields` tidak memuatnya; `save*Grid` tetap menulisnya
- **Conclusion:** **LEGACY CODE WRITES NON-PERSISTED FIELD** — terbukti di dua lapis (model + DB). Q-023 → RESOLVED. Jangan otomatis menambah kolom ini di Laravel.
- **Status:** CONFIRMED

**CONF-DB-016**
- **Table:** `users`
- **Difference:** unique `username`
- **Audit Documentation:** Appendix C — "users.username app-level"
- **Production DB:** `UNIQUE KEY username (username)`
- **Source Code:** `UserController::validateIdentity` cek manual (double safety)
- **Conclusion:** DB-enforced. Koreksi audit. Catatan: `email` **tidak** unique (app-level untuk login-by-email).
- **Status:** CONFIRMED

**CONF-DB-017**
- **Table:** `compliance_inventory_pics`
- **Difference:** struktur & integritas PIC
- **Audit Documentation:** Q-007 — struktur dari migration; FK UNKNOWN
- **Production DB:** `id bigint(20) UNSIGNED AI`, `inventory_id int(11) NOT NULL` (**SIGNED**), `user_id int(11) NOT NULL`, `is_primary tinyint(1) NOT NULL DEFAULT 0`, `created_at datetime NOT NULL DEFAULT current_timestamp()`; **UNIQUE(inventory_id,user_id)** + KEY(user_id,inventory_id); **TANPA FK**
- **Source Code:** callback `ComplianceInventoryModel`, `assignedToUser`, email reminder
- **Conclusion:** verified. FK mustahil tanpa normalisasi: `inventory_id` SIGNED vs `compliance_inventory.id int(10) UNSIGNED`. Fakta legacy untuk Q-007 lengkap.
- **Status:** CONFIRMED

**CONF-DB-018**
- **Table:** `holidays`
- **Difference:** unique `holiday_date`
- **Audit Documentation:** Appendix C — "unique constraint tak jelas"
- **Production DB:** hanya `KEY idx_holiday_date` (**bukan** UNIQUE)
- **Source Code:** `HolidayController` validasi unik manual
- **Conclusion:** **APPLICATION-LEVEL CONSTRAINT** (terverifikasi).
- **Status:** CONFIRMED

**CONF-DB-019**
- **Table:** `assets`
- **Difference:** nilai ENUM `status`
- **Audit Documentation:** Q-017 — kode memakai 'aktif' & 'rusak'
- **Production DB:** `status enum('aktif','rusak','mutasi') DEFAULT 'aktif'`
- **Source Code:** 'aktif' (agent auto-create), 'rusak' (UI → auto-return assignment); **`mutasi` tidak ditemukan di kode aktif**
- **Conclusion:** DB superset — nilai `mutasi` ada di DB tapi tak dipakai kode (legacy value). Fakta untuk Q-017.
- **Status:** CONFIRMED

**CONF-DB-020**
- **Table:** (global)
- **Difference:** jumlah tabel
- **Audit Documentation:** estimasi audit ~56 (40 migration + ~16 inferred)
- **Production DB:** **51 tabel aktual**
- **Source Code:** —
- **Conclusion:** angka final = **51** (6 tabel yang diperkirakan ternyata tidak ada; beberapa tabel inferred terbukti ada).
- **Status:** CONFIRMED

**CONF-DB-021**
- **Table:** `checklist_schedules`
- **Difference:** tabel ada di production tapi tak dipakai kode aktif
- **Audit Documentation:** terdokumentasi dari migration (2026-01-20-000002)
- **Production DB:** ADA — `item_type_id`, `frequency enum('daily','weekly','monthly') NOT NULL`, `active`, FK CASCADE
- **Source Code:** HANYA `ChecklistScheduleSeeder`; frekuensi efektif dibaca dari `asset_item_types.checklist_frequency`
- **Conclusion:** **CONFIRMED_LEGACY** — jangan dibawa ke Laravel apa adanya.
- **Status:** LEGACY

**CONF-DB-022**
- **Table:** `compliance_calendar_events`
- **Difference:** status penggunaan (dikira mati total di Fase 0.5)
- **Audit Documentation:** CONF-019 — "kalender standalone mati"
- **Production DB:** ADA (dengan kolom `sticker` dari migration 2026-08-10-000003)
- **Source Code:** dipakai **HolidayController** (aktif; guard `tableExists`) + `ComplianceCalendarEventModel`; `ComplianceCalendarController` tetap dead
- **Conclusion:** tabel **AKTIF** lewat modul Holidays (kalender menggabungkan holidays + events); koreksi parsial atas CONF-019 (yang mati = controller standalone & feed URL, bukan tabelnya).
- **Status:** CONFIRMED

**CONF-DB-023**
- **Table:** `checklist_master`
- **Difference:** kolom `frequency`
- **Audit Documentation:** CONF-021 — "ditulis seeder, diabaikan query aktif"
- **Production DB:** `frequency enum('daily','weekly','monthly') NOT NULL` (kolom ADA)
- **Source Code:** query aktif hanya memfilter `item_type_id + active`
- **Conclusion:** **legacy column** — terbukti ada, terbukti tak dipakai. CONF-021 tetap berlaku.
- **Status:** LEGACY

**CONF-DB-024**
- **Table:** `it_device_commands`
- **Difference:** relasi device tanpa FK
- **Audit Documentation:** Appendix C — `command_id` indexed non-unique
- **Production DB:** `device_id int(11) UNSIGNED` vs `it_devices.id int(11)` (**SIGNED**) → mismatch signedness; KEY saja, tanpa FK; `command_id varchar(64)` KEY (bukan unique)
- **Source Code:** queue/dispatch/ack memakai `command_id` sebagai identitas
- **Conclusion:** application-only relationship; `command_id` unik hanya di level aplikasi (UUID dari kode).
- **Status:** CONFIRMED

**CONF-DB-025**
- **Table:** `compliance_inventory`
- **Difference:** relasi `area_id`
- **Audit Documentation:** relasi didokumentasikan dari kode
- **Production DB:** `area_id int(11) UNSIGNED NULL` — tipe **cocok** dgn `areas.id`, tetapi **tanpa FK & tanpa index**
- **Source Code:** join `areas` di index/detail
- **Conclusion:** application-only relationship (meskipun tipe cocok).
- **Status:** CONFIRMED

**CONF-DB-026**
- **Table:** `compliance_inventory` & `employees`
- **Difference:** siapa yang mengelola `updated_at`
- **Audit Documentation:** model CI4 `useTimestamps=false` (audit Fase 0)
- **Production DB:** `updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()` — **DB yang menyentuh otomatis**
- **Source Code:** kode tidak menulis `updated_at` pada dua tabel ini
- **Conclusion:** `updated_at` dikelola **database**, bukan framework. Jangan disamakan dgn `checklist_logs` (yang tidak punya kolom sama sekali).
- **Status:** CONFIRMED

---

## 4. Source Code vs Production DB (rekonsiliasi penggunaan)

Detail untuk tabel inti (sisanya diringkas dalam tabel di bawah).

### Table: checklist_logs
- **Used by:** `ComplianceInventoryController` (submitChecklist, save*Grid, markAll*, detail, calendar), `ComplianceReportController`, `ComplianceRankingController`, `ComplianceDashboardController`, `ComplianceProgressController`, `ComplianceEvidenceController`, `HomeController`, `BaseController` (badge), commands reminder.
- **Columns written:** `inventory_id, item_type_id, checklist_template_id, check_date, period_key, time_slot, status, remark, photo, checked_by, created_at` (+ `follow_up_status/note/date` oleh evidence update). **Ditulis tapi tidak tersimpan: `updated_at`** (CONF-DB-015).
- **Columns read:** semua di atas (grid, rekap, ranking, report, evidence).
- **Expected by code but absent in DB:** `updated_at` (dead write — terbukti).
- **Present in DB but apparently unused:** tidak ada (semua kolom terpakai).
- **Catatan:** tidak ada UNIQUE(inventory_id, period_key, time_slot) → dedup **application-level**; FK CASCADE ke inventory berarti **menghapus inventory menghapus seluruh histori checklist-nya** (fakta bisnis!).

### Table: compliance_inventory
- **Used by:** `ComplianceInventoryController`, `ComplianceReportController`, `HomeController`, `ProgressController`, commands, QR center.
- **Columns written:** `category_id, area_id, item_type_id, asset_code, type_description, specific_area, pic, status, remark, expired_date, qty, photo, qr_image, active` (+`created_at`).
- **Columns read:** semua + join kategori/item/area.
- **Expected by code but absent:** tidak ada.
- **Present but unused:** tidak ada; `updated_at` **dikelola DB** (ON UPDATE) — kode tidak menulisnya (CONF-DB-026).

### Table: users
- **Used by:** Auth, UserController, access_helper, PIC dropdowns, notifikasi, audit.
- **Columns written:** `name, username, email, password, photo, role, permission, page_access, status, wa_number` (+`created_at`).
- **Columns read:** semua (login by username **atau** email; permission; page_access JSON).
- **Expected but absent:** tidak ada.
- **Present but unused:** tidak ada. `email` tidak UNIQUE → keunggulan email dijaga aplikasi.

### Table: checklist_master
- **Used by:** ChecklistMasterController, seluruh kanal checklist, report print (pemetaan kolom dari teks).
- **Columns written:** `item_type_id, question, frequency (seeder), require_photo, active, created_at`.
- **Columns read:** `item_type_id, question, active` (+`require_photo` di UI master).
- **Expected but absent:** tidak ada.
- **Present but unused:** **`frequency`** — ditulis seeder, tidak dibaca query aktif (CONF-DB-023, legacy column).

### Table: asset_item_types
- **Used by:** checklist engine (frekuensi, allow_na), inventory, report.
- **Written/read:** `inventory_category_id, name, code, checklist_frequency, active, allow_na, created_at` — semua terpakai. `code` tidak UNIQUE (dipakai prefix asset_code — app-level).

### Table: compliance_inventory_pics
- **Used by:** model callbacks (sync dari kolom teks), `assignedToUser`, email reminder, home tasks.
- **Written:** `inventory_id, user_id, is_primary, created_at`. **Read:** relasi PIC.
- **Catatan:** UNIQUE(inventory_id,user_id); tanpa FK (signedness mismatch, CONF-DB-017).

### Table: it_devices
- **Used by:** AgentController (heartbeat/command/update), ITDeviceController, device_helper.
- **Written:** hampir semua kolom (heartbeat menulis `cpu` JSON + kolom turunan, `last_seen`, `status`, `agent_version`, dll.).
- **Read:** dashboard device, health score, command push (ip).
- **Present but unused:** tidak ada yang terbukti unused. `device_token` = identitas utama (varchar(100), tidak UNIQUE di DB — keunggulan dijaga alur enroll app-level).

### Table: it_device_commands
- **Used by:** ITDeviceController (queue/remote), AgentController (poll/ack).
- **Written:** `device_id, command_id, command, payload_json, status, result, requested_by, requested_at, executed_at, created_at, updated_at`.
- **Catatan:** `command_id` KEY non-unique → identitas command dijaga aplikasi (UUID).

### Tabel lain (ringkas)
| Table | Used by (kode aktif) | Expected-but-absent | Present-but-unused |
|---|---|---|---|
| areas | inventory (join/filter), master data | — | — |
| assets / asset_categories / asset_assignments | ITAssetController, AgentController | — | nilai ENUM `mutasi` (CONF-DB-019) |
| employees | EmployeeController, assignment | — | — |
| audit_logs / login_sessions | Auth, AuditLogController | — | — |
| notifications | NotificationService, Home, BaseController | — | — |
| holidays | HolidayController, checklist_helper | — | — |
| checklist_schedules | **hanya seeder** | — | **seluruh tabel (legacy)** |
| compliance_calendar_events | HolidayController (aktif) | — | — |
| questionnaire (4 tabel) | QuestionnaireController (+bootstrap ctor) | — | — |
| ems_* (8) | EmsReportController | — | — |
| fdm_* (2) | FdmDataCollectionController | — | — |
| boiler/ipal/pdam (4) | utility controllers | — | — |
| patrol_* (7) | PatrolController | — | — |
| thermal_* (3) | ThermalImagingController | — | — |
| app_settings | SettingController, NotificationService | — | — |
| user_roles | UserController | — | — |
| migrations | CI4 runner | — | — |

---

## 5. Schema Drift

```
Source Code expects X  →  Production DB tidak punya X   (dead write / dead reference)
Production DB punya X  →  Source Code tidak memakai X    (legacy / dead schema)
```

**A. Code expects → DB absent (terbukti):**
1. `checklist_logs.updated_at` — ditulis 12+ lokasi `save*Grid`; tidak ada di DB → **LEGACY CODE WRITES NON-PERSISTED FIELD** (CONF-DB-015).
2. Tabel `it_device_logs` — direferensikan `ItDeviceLogModel`; tidak ada di DB (CONF-DB-003).
3. Tabel `compliance_checklist_master/log_items/logs/schedules/templates` — direferensikan 3 model + migration; tidak ada di DB (CONF-DB-002).
4. Kolom `inspection_week/month/year` milik `ComplianceChecklistLogModel` — tidak cocok dgn migration-nya sendiri (CONF-016 Fase 0.5); tabelnya pun tidak ada di production.

**B. DB has → Code tidak memakai (Legacy Schema):**
1. Tabel `checklist_schedules` (CONF-DB-021) — CONFIRMED_LEGACY.
2. Kolom `checklist_master.frequency` (CONF-DB-023) — legacy column.
3. Nilai ENUM `assets.status='mutasi'` (CONF-DB-019) — legacy value.

**C. Dead Schema:** tidak ada tabel production yang 100% tanpa referensi kode — yang mendekati: `checklist_schedules` (hanya seeder). Tidak ada yang dikategorikan `UNUSED` murni.

**D. Unknown:** tidak ada kolom/tabel yang tersisa tanpa bukti memadai pada level struktur. (Isi DATA — mis. apakah ada baris historis `status='ng'` — tidak dapat ditentukan dari export structure-only; dicatat di §11.)

---

## 6. Foreign Keys

### 6.1 Database FK exists (16) — PRODUCTION VERIFIED

| # | Source | Target | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| 1 | assets.category_id | asset_categories.id | (default RESTRICT) | (default) |
| 2 | asset_item_types.inventory_category_id | inventory_categories.id | CASCADE | CASCADE |
| 3 | checklist_logs.checklist_template_id | checklist_master.id | CASCADE | (default) |
| 4 | checklist_logs.inventory_id | compliance_inventory.id | CASCADE | CASCADE |
| 5 | checklist_logs.item_type_id | asset_item_types.id | CASCADE | CASCADE |
| 6 | checklist_master.item_type_id | asset_item_types.id | CASCADE | (default) |
| 7 | checklist_schedules.item_type_id | asset_item_types.id | CASCADE | CASCADE |
| 8 | compliance_inventory.item_type_id | asset_item_types.id | (default) | (default) |
| 9 | compliance_questionnaire_questions.questionnaire_id | compliance_questionnaires.id | CASCADE | CASCADE |
| 10 | compliance_questionnaire_responses.questionnaire_id | compliance_questionnaires.id | CASCADE | CASCADE |
| 11 | compliance_questionnaire_response_answers.question_id | compliance_questionnaire_questions.id | CASCADE | CASCADE |
| 12 | compliance_questionnaire_response_answers.response_id | compliance_questionnaire_responses.id | CASCADE | CASCADE |
| 13 | fdm_production_section_entries.year_id | fdm_production_section_years.id | CASCADE | CASCADE |
| 14 | it_devices.asset_id | assets.id | (default) | (default) |
| 15 | thermal_imaging_report_items.location_id | thermal_imaging_locations.id | CASCADE | **SET NULL** |
| 16 | thermal_imaging_report_items.report_id | thermal_imaging_reports.id | CASCADE | CASCADE |

**Fakta bisnis penting dari FK:** menghapus `compliance_inventory` akan **CASCADE menghapus seluruh `checklist_logs`**-nya (FK #4); menghapus item type menghapus master & logs (FK #5/#6); menghapus kategori master menghapus item types (FK #2). UI saat ini membatasi delete (role admin/compliance), tetapi DB mengizinkan cascade penuh.

### 6.2 Application-only relationships (tanpa FK di production)

| Relationship | Alasan tidak ber-FK (evidence) |
|---|---|
| compliance_inventory_pics → compliance_inventory / users | signedness mismatch (`inventory_id int(11)` SIGNED vs `id int(10) UNSIGNED`) — CONF-DB-017 |
| it_device_commands → it_devices | signedness mismatch (`device_id UNSIGNED` vs `id` SIGNED) — CONF-DB-024 |
| notifications → users | desain sengaja (komentar migration "legacy users table differs…") |
| audit_logs → users | FK dideklarasikan di migration tapi **tidak ada** di production (CONF-DB-006) |
| compliance_inventory.area_id → areas | tipe cocok, tapi FK/index tidak dibuat (CONF-DB-025) |
| compliance_inventory.category_id → inventory_categories | tidak ada FK (murni konvensi) |
| asset_assignments → assets / employees | tanpa FK & tanpa index pada kolom relasi |
| patrol_sessions → patrol_routes; patrol_route_checkpoints → routes/checkpoints; patrol_logs → sessions/routes/checkpoints/users; patrol_log_photos → patrol_logs | tanpa FK (integritas dijaga controller) |
| *.created_by → users (boiler, ipal, pdam, thermal, calendar_events) | konvensi saja |
| ems_*_entries → ems_*_years | via `report_year` INT (bukan FK) |
| login_sessions → users | konvensi (`user_id` KEY saja) |
| checklist_logs (inventory, period, slot) dedup | bukan relasi — tapi dedup juga app-level (lihat §7) |

---

## 7. Indexes & Unique Constraints

### 7.1 UNIQUE di production (bisnis-relevan) — PRODUCTION VERIFIED
| Table | Constraint | Business rule terkait |
|---|---|---|
| app_settings | `setting_key` | key-value settings unik |
| assets | `inventory_no` | nomor inventaris IT unik |
| checklist_logs | — (tidak ada unique kombinasi) | **APPLICATION-LEVEL**: 1 log per (inventory, period[, slot]) — BR-09 |
| compliance_inventory | `uniq_asset_code` | asset_code unik (BR-19; koreksi CONF-DB-005) |
| compliance_inventory_pics | (inventory_id, user_id) | 1 baris per pasangan PIC-inventory (BR-21) |
| compliance_questionnaires | `slug` | URL publik unik |
| compliance_questionnaire_responses | `response_code` | kode respon unik |
| employees | `employee_id` | NIK unik |
| ems_*_entries | (report_year, report_month) / (year, section_key, month) | 1 entri per periode/section |
| ems_*_years | `report_year` | 1 baris per tahun |
| fdm_production_section_entries | (year_id, section_key) | 1 section per tahun |
| fdm_production_section_years | `report_year` | 1 baris per tahun |
| ipal_logs | `unique_log_date` | 1 entri/tanggal (BR-30; koreksi CONF-DB-004) |
| it_devices | `asset_id` | 1 device per asset |
| login_sessions | `session_key` | sesi unik |
| notifications | `dedupe_key` | notifikasi idempoten (BR-24) |
| patrol_checkpoints | `code` | kode checkpoint unik |
| patrol_routes | `slug` | slug rute unik |
| pdam_water_boiler_logs | `uniq_pdam_water_boiler_log_date` | 1 entri/tanggal (BR-28) |
| pdam_water_logs | `uniq_pdam_water_log_date` | 1 entri/tanggal (BR-28) |
| users | `username` | username unik (koreksi CONF-DB-016) |
| user_roles | `name` | nama role unik |

### 7.2 APPLICATION-LEVEL CONSTRAINT (business rule tanpa dukungan UNIQUE DB)
| Rule | Evidence kode | Catatan |
|---|---|---|
| 1 log per (inventory_id, period_key[, time_slot]) | `submitChecklist` exists-check; grid upsert | risiko race tetap ada (BR-09) |
| holidays.holiday_date unik | `HolidayController` validasi manual | CONF-DB-018 |
| users.email unik (untuk login-by-email) | `UserController::validateIdentity` | DB tidak menegakkan |
| asset_item_types.code unik (prefix asset_code) | konvensi | tidak ada index sama sekali |
| inventory_categories.code unik (FS/HSE/…) | konvensi seeder | tidak ada index |
| it_device_commands.command_id unik | UUID dari kode | KEY saja (CONF-DB-024) |
| 1 sesi patrol aktif per user per hari | `PatrolController::startSession` 409 | tidak ada partial unique (MariaDB tak mendukung) |
| 1 command antrian per device | `queueRemoteCommand` + `remote_lock_until` | dijaga logika |
| asset_assignments: 1 assignment aktif per asset | `assignSave` menutup yg lama dulu | tanpa constraint DB |

### 7.3 Index non-unique penting
`checklist_logs`: 3 FK key + `idx_notok_photo(status, photo)` (mendukung evidence center). `boiler_fuel_logs.idx_date`. `holidays.idx_holiday_date`. `login_sessions`: user_id, started_at, is_active. `notifications`: (user_id, read_at), actor_user_id, created_at. `patrol_logs(session_id,checkpoint_id)`, `patrol_sessions(route_id,patrol_date)`, `patrol_route_checkpoints(route_id,checkpoint_id)`. `thermal_*`: inspection_date, report_id, location_id, active. `compliance_calendar_events`: start_at, end_at, created_by. `pdam_*`: log_date (selain unique). `it_device_commands`: device_id, command_id. `fdm_entries.year_id`.

---

## 8. ENUM Inventory

| # | table.column | ENUM values (PRODUCTION) | Default | Nilai di source code | Conflict? |
|---|---|---|---|---|---|
| 1 | assets.status | `aktif, rusak, mutasi` | `aktif` | `aktif` (agent), `rusak` (UI) | **Ya — `mutasi` tidak dipakai kode** (CONF-DB-019) |
| 2 | asset_item_types.checklist_frequency | `daily, weekly, monthly` | `monthly` | daily/weekly/monthly (+fallback monthly) | Tidak |
| 3 | checklist_logs.status | `ok, not_ok, na` | `ok` | ok/not_ok/na (`ng`→`not_ok` saat input) | **Terputus: konsisten** (CONF-DB-001; Q-001 RESOLVED) |
| 4 | checklist_logs.follow_up_status | `open, monitoring, closed` | `open` | open/monitoring/closed (evidence.js) | Tidak |
| 5 | checklist_master.frequency | `daily, weekly, monthly` | (tidak ada) | ditulis seeder; diabaikan query aktif | Legacy column (CONF-DB-023) |
| 6 | checklist_schedules.frequency | `daily, weekly, monthly` | (tidak ada) | tabel legacy (seeder saja) | Legacy table (CONF-DB-021) |
| 7 | compliance_questionnaire_questions.answer_type | `radio, text, textarea, date, email, phone, number, select, scale_5, scale_10` | `radio` | dipakai katalog kuesioner | Tidak |
| 8 | employees.status | `active, inactive` | `active` | active/inactive (guard assignment) | Tidak |
| 9 | it_devices.status | `online, offline` | `offline` | online/offline (command it:status, UI) | Tidak (definisi threshold = Q-012, bukan ENUM) |
| 10 | users.permission | `read, write` | `read` | read/write (WriteFilter, UserController) | Tidak |
| 11 | users.status | `active, inactive` | `active` | active/inactive (login guard, PIC dropdown) | Tidak |

**Kolom status yang BUKAN ENUM (free text) — penting untuk keputusan Laravel:** `compliance_inventory.status varchar(50)` (UI: Good/Need Repair/Not Active — Q-017), `users.role varchar(50)` (role custom), `patrol_logs.status varchar(20)`, `patrol_sessions.status varchar(20)`, `it_device_commands.status varchar(30)`, `notifications.type varchar(40)`, `notifications.email_status/whatsapp_status varchar(20)`, `audit_logs.status varchar(20)`, `fdm_production_section_entries.entry_type/frequency_label varchar(50)`.

---

## 9. Timestamp Inventory

| Table | created_at | updated_at | deleted_at | Dikelola oleh |
|---|---|---|---|---|
| checklist_logs | ✅ datetime NULL (diisi kode manual) | ❌ **TIDAK ADA** (dead write — CONF-DB-015) | ❌ | kode (created_at) |
| checklist_master | ✅ DEFAULT current_timestamp() | ❌ | ❌ | DB default |
| checklist_schedules | ❌ | ❌ | ❌ | — (legacy) |
| compliance_inventory | ✅ DEFAULT current_timestamp() | ✅ **ON UPDATE current_timestamp()** | ❌ | **DB otomatis** (CONF-DB-026) |
| compliance_inventory_pics | ✅ DEFAULT current_timestamp() | ❌ | ❌ | DB default |
| users | ✅ DEFAULT current_timestamp() | ❌ | ❌ | DB default |
| employees | ✅ DEFAULT ct | ✅ ON UPDATE ct | ❌ | DB otomatis |
| assets | ✅ DEFAULT ct | ❌ | ❌ | DB default |
| asset_assignments | `assigned_at` / `returned_at` (custom) | — | ❌ | kode |
| audit_logs | ✅ NULL (diisi kode) | ❌ | ❌ | kode |
| login_sessions | `started_at` / `last_seen_at` / `ended_at` (custom) | — | ❌ | kode/filter |
| notifications | ✅ DEFAULT ct + `read_at` | ❌ | ❌ | DB + kode (read_at) |
| app_settings | ❌ | ✅ NOT NULL DEFAULT ct | ❌ | kode update (SettingController) |
| boiler/ipal/pdam (4) | ✅ NULL | ✅ NULL | ❌ | kode/model |
| ems_* (8), fdm_* (2) | ✅ NULL | ✅ NULL | ❌ | model CI4 (timestamps) |
| patrol_* (7) | ✅ NULL | ✅ NULL (log_photos: created_at saja) | ❌ | model |
| thermal_* (3) | ✅ NULL | ✅ NULL | ❌ | model |
| questionnaire (4) | ✅ NULL | ✅ NULL (responses/answers: created_at saja) | ❌ | model/kode |
| compliance_calendar_events | ✅ NOT NULL DEFAULT ct | ✅ NULL | ❌ | model |
| holidays | ❌ | ❌ | ❌ | — |
| inventory_categories | ✅ NULL | ❌ | ❌ | kode |
| areas | ❌ | ❌ | ❌ | — |
| asset_categories | ❌ | ❌ | ❌ | — |
| it_devices | ❌ (pakai `last_seen`, `last_update_check`) | — | ❌ | kode/agent |
| it_device_commands | ✅ NULL | ✅ NULL | ❌ | kode |
| user_roles | ✅ NULL | ✅ NULL | ❌ | model |
| migrations | `time` int (custom CI4) | — | ❌ | framework |

**Fakta:** **tidak ada `deleted_at` di tabel mana pun → tidak ada soft delete** (semua delete = hard delete; grid clear = hard delete baris log). `ON UPDATE current_timestamp()` hanya di `compliance_inventory` & `employees`. Ini fakta untuk keputusan `timestamps()` di Laravel — bukan keputusan itu sendiri.

---

## 10. Critical Decision Resolution

| Q | Hasil | Evidence production |
|---|---|---|
| **Q-001** | ✅ **RESOLVED BY PRODUCTION SCHEMA** | `checklist_logs.status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'` — konsisten dgn kode (`not_ok`), `ng` hanya mapping input legacy (CONF-DB-001) |
| **Q-002** | ✅ **RESOLVED BY PRODUCTION SCHEMA** | Seluruh tabel dasar TERBUKTI ADA dgn DDL lengkap; **kecuali** `it_device_logs`, `compliance_checklist_master`, `compliance_checklist_log_items` (+`compliance_checklist_logs/schedules/templates`) yang **TIDAK ADA** di production (CONF-DB-002/003) |
| **Q-003** | ✅ **RESOLVED BY PRODUCTION SCHEMA** | 11 kolom verified: `checklist_frequency` ENUM NOT NULL DEFAULT 'monthly'; `allow_na` tinyint(1) DEFAULT 0; `time_slot` varchar(5) NULL; `follow_up_status` ENUM DEFAULT 'open'; `follow_up_note` text; `follow_up_date` date; `permission` ENUM('read','write') DEFAULT 'read'; `wa_number` varchar(20); `photo` varchar(255); `active` tinyint(1) NOT NULL DEFAULT 1; `cpu` longtext (CONF-DB-007 s/d 014) |
| **Q-006** | ⚠️ **Fakta DB RESOLVED — keputusan bisnis TETAP TERBUKA** | `checked_by varchar(100) DEFAULT NULL` = **LEGACY DATABASE BEHAVIOR** (string nama, terbukti). **LARAVEL MIGRATION DECISION** (tetap string vs FK user_id + snapshot) = tetap NEED HUMAN DECISION |
| **Q-007** | ⚠️ **Fakta DB RESOLVED — keputusan bisnis TETAP TERBUKA** | `compliance_inventory.pic varchar(100) NULL` MASIH ADA & masih ditulis/dibaca sebagian jalur; `compliance_inventory_pics`: UNIQUE(inventory_id,user_id), `is_primary` tinyint, TANPA FK (signedness mismatch). Sumber kebenaran PIC = tetap NEED HUMAN DECISION |
| **Q-014** | ✅ **RESOLVED BY PRODUCTION SCHEMA** | famili `compliance_checklist_*` TIDAK ADA di production → murni dead code (model+migration); aman dihapus saat rebuild (housekeeping, bukan keputusan bisnis) |
| **Q-023** | ✅ **RESOLVED BY PRODUCTION SCHEMA** | `checklist_logs.updated_at` TIDAK ADA di production → **LEGACY CODE WRITES NON-PERSISTED FIELD**; jangan otomatis menambah kolom di Laravel (CONF-DB-015) |

**Tambahan fakta yang membantu (bukan penutup keputusan):** Q-017 — `assets.status` ENUM('aktif','rusak','mutasi') & `compliance_inventory.status varchar(50)` kini diketahui pasti; keputusan enum resmi Laravel tetap terbuka. Q-016, Q-018 — tetap membutuhkan data/artefak produksi (structure-only tidak memuatnya).

---

## 11. Remaining Unknowns

Hal yang **tidak** dapat dibuktikan dari export structure-only (tetap terbuka):
1. **Isi data**: apakah ada baris historis `checklist_logs.status='ng'`; distinct nilai `compliance_inventory.status` aktual; jumlah baris per tabel (volume → sizing).
2. **Isi `migrations`**: daftar migration yang benar-benar pernah dijalankan (baris tidak termuat di export structure-only).
3. **Mengapa FK `audit_logs` hilang** di production (di-drop manual vs migration dimodifikasi) — CONF-DB-006.
4. **`app_settings` aktual** (token, template pesan, nama perusahaan) → tetap Q-018.
5. **Kategori id=1** (asumsi agent IT-PC) → tetap Q-016 (butuh data).
6. **Scheduler aktual** di server (cron/schtasks) → tetap Q-015.
7. Collation data existing (utf8mb4_general_ci) vs rencana — fakta struktur sudah diketahui; dampak migrasi data baru akan terlihat saat ETL.

---

## 12. Migration Implications

> **Hanya fakta yang perlu diperhatikan. BUKAN desain migration Laravel.**

1. **Signedness tidak seragam:** `users.id int(11)` SIGNED, `compliance_inventory.id int(10) UNSIGNED`, `assets.id int(11)` SIGNED, `it_devices.id int(11)` SIGNED, `patrol_*.id UNSIGNED`, `notifications.id bigint(20) UNSIGNED`, kolom FK kadang beda signedness dari targetnya (`pics.inventory_id`, `it_device_commands.device_id`). Ini alasan beberapa FK tidak ada — normalisasi tipe id adalah prasyarat bila Laravel ingin FK penuh.
2. **FK CASCADE berbahaya yang sekarang hidup:** hapus inventory → hapus seluruh histori checklist (FK #4); hapus item type → hapus master + logs + schedules (FK #2/#5/#6/#7); hapus kategori → hapus item types. Keputusan retain/ubah perilaku cascade = keputusan arsitektur nanti (dokumentasikan, jangan diasumsikan).
3. **Application-level constraints yang harus diputuskan implementasinya:** dedup checklist (inventory+period+slot), email unik, holiday_date unik, 1 sesi patrol aktif, 1 command antrian — saat ini tanpa dukungan DB.
4. **`period_key varchar(10)`** pas untuk `YYYY-MM-DD` dan `YYYY-MM-Wn` (10 char) — tidak ada ruang lebih; pertahankan panjang atau putuskan sadar.
5. **ENUM vs free text:** 11 ENUM verified (§8) vs 10 kolom status free-text — pemetaan mana yang jadi enum resmi Laravel mengacu Q-017 & keputusan terkait.
6. **Timestamp heterogen:** 2 tabel DB-managed `ON UPDATE`, sebagian kode-managed, `checklist_logs` tanpa `updated_at`, tanpa `deleted_at` di mana pun. Jangan menyapu rata `timestamps()`/`softDeletes()` — ikuti fakta per tabel + keputusan Q-023.
7. **Tabel yang tidak perlu dibuat di Laravel (legacy/dead, terbukti):** `checklist_schedules`, famili `compliance_checklist_*` (5 tabel), `it_device_logs` — kecuali ada keputusan bisnis sebaliknya.
8. **Kolom legacy yang ada di production tapi tak dipakai kode aktif:** `checklist_master.frequency` — putuskan drop/pertahankan saat desain (CONF-DB-023).
9. **Semua tabel InnoDB utf8mb4_general_ci, semua PK AUTO_INCREMENT int/bigint** — baseline konsisten untuk migrasi struktur.
10. **Production DB = MariaDB 10.4.32** (bukan MySQL) — perhatikan kompatibilitas fitur (mis. no partial index, CHECK constraints di-enforce sejak 10.2 tapi tak dipakai di schema ini).

---

> **Prinsip fase ini:** Production database = evidence untuk schema. Source code = evidence untuk behavior. Keduanya direkonsiliasi di sini; tidak ada business rule yang diubah oleh dokumen ini.
> Rantai: CI4 Source Code + Production Database Schema + Audit Documentation → **Schema Reconciliation (dokumen ini)** → Verified Legacy Specification (`docs/03`) → Human Decisions (`docs/15`) → Laravel Architecture → Laravel Implementation.
