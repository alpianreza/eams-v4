# 03 — Database (Production-Verified Specification)

> **Production Schema Verified:** 2026-08-18
> **Source:** `eams_database.sql` (phpMyAdmin 5.2.1, structure-only export, tanpa data bisnis)
> **Database:** MariaDB 10.4.32 — `asset_compliance_system`
>
> **Label evidence:** **[PRODUCTION VERIFIED]** = fakta dari `eams_database.sql` · **[SOURCE CODE INFERRED]** = dari kode CI4 (relasi/perilaku yang tidak tercermin di DB) · **[UNKNOWN]** = belum cukup bukti.
> Dokumen ini menggantikan versi berbasis-migration dari Fase 0. Rekonsiliasi lengkap: `docs/18-production-database-reconciliation.md`.

---

## 0. Fakta Global

- **Total tabel: 51** [PRODUCTION VERIFIED]
- **Engine:** InnoDB (semua) · **Charset:** utf8mb4 · **Collation:** utf8mb4_general_ci (semua) [PRODUCTION VERIFIED]
- **Semua PK:** `id` AUTO_INCREMENT (int atau bigint) [PRODUCTION VERIFIED]
- **Soft delete:** TIDAK ADA (`deleted_at` tidak ada di tabel mana pun) [PRODUCTION VERIFIED]
- **Foreign key di DB:** 16 (daftar di §52) — selebihnya **application-only relationship** [PRODUCTION VERIFIED + SOURCE CODE INFERRED]
- **Tabel yang diperkirakan audit lama tetapi TIDAK ADA di production:** `compliance_checklist_master`, `compliance_checklist_log_items`, `compliance_checklist_logs`, `compliance_checklist_schedules`, `compliance_checklist_templates`, `it_device_logs` [PRODUCTION VERIFIED]

---

## A. Authentication & Administration

### 1. `users`
- **Purpose / Module:** akun pengguna — Authentication & seluruh modul. **Status:** CONFIRMED_ACTIVE.
- **Columns [PRODUCTION VERIFIED]:** `id int(11) NOT NULL AI`; `name varchar(100) NULL`; `username varchar(50) NULL`; `email varchar(190) NULL`; `password varchar(255) NULL` (bcrypt); `photo varchar(255) NULL`; `role varchar(50) NOT NULL DEFAULT 'staff'`; `permission enum('read','write') DEFAULT 'read'`; `page_access text NULL` (JSON); `status enum('active','inactive') DEFAULT 'active'`; `created_at datetime DEFAULT current_timestamp()`; `wa_number varchar(20) NULL`.
- **PK/AI:** `id` AI. **FK:** —. **Indexes:** PK. **Unique:** `username`. **Enum:** `permission`, `status`.
- **Referenced by:** (application-only) `audit_logs.user_id`, `login_sessions.user_id`, `notifications.user_id/actor_user_id`, `compliance_inventory_pics.user_id`, `*.created_by`, `patrol_logs.checked_by`, `patrol_sessions.started_by` — **tanpa FK DB satu pun** [PRODUCTION VERIFIED].
- **Source Code Usage:** login username/email + throttle (AuthController); WriteFilter membaca `permission`; menu via `page_access`; PIC dropdown memfilter `status='active'`.
- **Notes:** `email` TIDAK unique (keunggulan app-level via `UserController::validateIdentity`) [PRODUCTION VERIFIED].

### 2. `user_roles`
- **Purpose / Module:** role custom — Administration. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(50) NOT NULL`; `created_at datetime NULL`; `updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `name`. **FK/Enum:** —.
- **Source Code Usage:** UserController (assign role custom); `access_helper hasRole`.

### 3. `login_sessions`
- **Purpose / Module:** pelacakan sesi login (idle 8 jam) — Authentication. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id bigint(20) UNSIGNED AI`; `session_key varchar(64) NOT NULL`; `user_id int(11) NULL`; `username varchar(120) NULL`; `login_method varchar(30) NOT NULL DEFAULT 'password'`; `channel varchar(30) NOT NULL DEFAULT 'web'`; `ip_address varchar(45) NULL`; `user_agent text NULL`; `browser varchar(100) NULL`; `platform varchar(100) NULL`; `device_type varchar(30) NULL`; `started_at datetime NOT NULL DEFAULT current_timestamp()`; `last_seen_at datetime NULL`; `ended_at datetime NULL`; `last_route varchar(255) NULL`; `logout_reason varchar(50) NULL`; `is_active tinyint(1) NOT NULL DEFAULT 1`.
- **PK/AI:** id. **Unique:** `session_key`. **Indexes:** `user_id`, `started_at`, `is_active`. **FK/Enum:** —.
- **Source Code Usage:** dibuat saat login; AuditLogController menandai expired bila idle > 8 jam.

### 4. `audit_logs`
- **Purpose / Module:** audit trail auth & aksi — Administration. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `user_id int(11) NULL`; `action varchar(100) NULL`; `description text NULL`; `ip_address varchar(45) NULL`; `user_agent text NULL`; `created_at datetime NULL`; `session_id varchar(64) NULL`; `status varchar(20) DEFAULT 'success'`; `login_method varchar(30) NULL`; `channel varchar(30) NULL`; `route varchar(255) NULL`; `request_method varchar(10) NULL`; `device_type varchar(30) NULL`; `browser varchar(100) NULL`; `platform varchar(100) NULL`; `metadata text NULL`.
- **PK/AI:** id. **FK:** **tidak ada** (migration mendeklarasikan FK ke users, tetapi production tidak memilikinya — CONF-DB-006). **Indexes:** PK saja. **Enum:** — (`status` varchar).
- **Source Code Usage:** AuthController (login/logout/failed/blocked), AuditLogController (viewer + expire sessions).

### 5. `app_settings`
- **Purpose / Module:** key-value settings (SMTP, WA, branding, template) — Settings. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(10) UNSIGNED AI`; `setting_key varchar(120) NOT NULL`; `setting_value text NULL`; `is_secret tinyint(1) NOT NULL DEFAULT 0`; `updated_by int(10) UNSIGNED NULL`; `updated_at datetime NOT NULL DEFAULT current_timestamp()`.
- **PK/AI:** id. **Unique:** `setting_key`. **FK/Enum:** —.
- **Source Code Usage:** SettingController; NotificationService (flag email/WA + template placeholder).

### 6. `notifications`
- **Purpose / Module:** notifikasi in-app idempoten multi-kanal — Notifications. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id bigint(20) UNSIGNED AI`; `user_id int(11) NOT NULL`; `actor_user_id int(11) NULL`; `type varchar(40) NOT NULL DEFAULT 'info'`; `title varchar(180) NOT NULL`; `message text NOT NULL`; `url varchar(500) NULL`; `entity_type varchar(80) NULL`; `entity_id bigint(20) UNSIGNED NULL`; `dedupe_key varchar(190) NULL`; `read_at datetime NULL`; `email_status varchar(20) NOT NULL DEFAULT 'skipped'`; `whatsapp_status varchar(20) NOT NULL DEFAULT 'skipped'`; `created_at datetime NOT NULL DEFAULT current_timestamp()`.
- **PK/AI:** id. **Unique:** `dedupe_key` (idempotensi — BR-24). **Indexes:** `(user_id, read_at)`, `actor_user_id`, `created_at`. **FK:** tidak ada (desain sengaja — komentar migration re: legacy users). **Enum:** —.
- **Source Code Usage:** NotificationService; HomeController; badge sidebar (BaseController).

### 7. `migrations`
- **Purpose / Module:** jejak migration CI4 — framework. **Status:** CONFIRMED_ACTIVE (framework).
- **Columns:** `id bigint(20) UNSIGNED AI`; `version varchar(255) NOT NULL`; `class varchar(255) NOT NULL`; `group varchar(255) NOT NULL`; `namespace varchar(255) NOT NULL`; `time int(11) NOT NULL`; `batch int(11) UNSIGNED NOT NULL`.
- **Notes:** isi baris tidak termuat (structure-only) → migration mana yang pernah jalan = UNKNOWN (docs/18 §11).

---

## B. Master Data

### 8. `areas`
- **Purpose / Module:** master area — Master Data. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(100) NOT NULL`; `active tinyint(1) NOT NULL DEFAULT 1`.
- **PK/AI:** id. **FK/Unique/Enum:** — (nama tidak unique).
- **Referenced by:** `compliance_inventory.area_id` (application-only, tanpa FK/index — CONF-DB-025).

### 9. `inventory_categories`
- **Purpose / Module:** kategori inventory compliance (FS/HSE/CTPAT/UTL, dsb.) — Master Data. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(100) NOT NULL`; `code varchar(50) NULL`; `active tinyint(1) NOT NULL DEFAULT 1`; `created_at datetime NULL`.
- **PK/AI:** id. **FK/Unique/Enum:** — (`code` tidak unique; dipakai prefix asset_code — app-level).
- **Referenced by:** `asset_item_types.inventory_category_id` (FK CASCADE), `compliance_inventory.category_id` (application-only).

### 10. `asset_item_types`
- **Purpose / Module:** jenis item checklist (CCTV, APAR, dst.) + **frekuensi checklist** — Master Data / Checklist. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) UNSIGNED AI`; `inventory_category_id int(11) UNSIGNED NOT NULL`; `name varchar(100) NOT NULL`; `code varchar(20) NOT NULL`; **`checklist_frequency enum('daily','weekly','monthly') NOT NULL DEFAULT 'monthly'`** [PRODUCTION VERIFIED — Q-003]; `active tinyint(1) NOT NULL DEFAULT 1`; `created_at datetime NULL`; **`allow_na tinyint(1) DEFAULT 0`** [PRODUCTION VERIFIED — Q-003].
- **PK/AI:** id. **FK:** `inventory_category_id → inventory_categories.id` ON DELETE CASCADE ON UPDATE CASCADE. **Indexes:** PK + KEY fk. **Unique:** — (`code` tidak unique). **Enum:** `checklist_frequency`.
- **Source Code Usage:** engine checklist membaca `checklist_frequency` & `allow_na`; grid khusus memakai konstanta id hard-coded (CCTV=13, APAR=1, TOILET=52, dst. — legacy, docs/17 §17).

### 11. `holidays`
- **Purpose / Module:** hari libur nasional (memengaruhi offday checklist daily) — Master Data. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `holiday_date date NOT NULL`; `description varchar(255) NULL`.
- **PK/AI:** id. **Indexes:** `idx_holiday_date` (**BUKAN unique** — CONF-DB-018). **FK/Enum:** —.
- **Source Code Usage:** `checklist_helper holiday_dates_between/is_date_offday`; HolidayController (validasi unik manual = APPLICATION-LEVEL CONSTRAINT).

---

## C. Compliance (inti)

### 12. `compliance_inventory`
- **Purpose / Module:** inventory fasilitas compliance — Compliance Inventory. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(10) UNSIGNED AI`; `category_id int(11) UNSIGNED NULL`; `item_type_id int(10) UNSIGNED NULL`; `type_description varchar(100) NULL`; `asset_code varchar(50) NOT NULL`; `photo varchar(255) NULL`; `active tinyint(1) NOT NULL DEFAULT 1` [Q-003 VERIFIED]; `created_at datetime DEFAULT current_timestamp()`; `updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()` (**DB-managed** — CONF-DB-026); `qr_image varchar(255) NULL`; `area_id int(11) UNSIGNED NULL`; `expired_date date NULL`; `pic varchar(100) NULL` (kolom teks PIC — Q-007); `status varchar(50) NULL` (free text; UI: Good/Need Repair/Not Active — Q-017); `remark text NULL`; `qty int(11) DEFAULT 1`; `specific_area varchar(150) NULL`.
- **PK/AI:** id. **Unique:** `uniq_asset_code (asset_code)` [PRODUCTION VERIFIED — koreksi CONF-DB-005]. **FK:** `item_type_id → asset_item_types.id` (tanpa aksi). **Indexes:** PK + KEY fk. **Enum:** —.
- **Referenced by:** `checklist_logs.inventory_id` (FK **CASCADE** — hapus inventory = hapus histori checklist!), `compliance_inventory_pics.inventory_id` (application-only, signedness mismatch).
- **Source Code Usage:** ComplianceInventoryController (CRUD via modal, auto asset_code, QR), report, ranking, progress, reminder (`active=1`).

### 13. `compliance_inventory_pics`
- **Purpose / Module:** relasi PIC (maks 2, primary) — Compliance Inventory. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id bigint(20) UNSIGNED AI`; `inventory_id int(11) NOT NULL` (**SIGNED**); `user_id int(11) NOT NULL`; `is_primary tinyint(1) NOT NULL DEFAULT 0`; `created_at datetime NOT NULL DEFAULT current_timestamp()`.
- **PK/AI:** id. **Unique:** `(inventory_id, user_id)`. **Indexes:** UNIQUE + `(user_id, inventory_id)`. **FK:** **tidak ada** (signedness mismatch dgn `compliance_inventory.id int(10) UNSIGNED` — CONF-DB-017). **Enum:** —.
- **Source Code Usage:** callback ComplianceInventoryModel (sync dari kolom teks), `assignedToUser`, email reminder, notifikasi assignment (dedupe `inventory_assignment:{inv}:{user}`). Q-007: fakta lengkap; keputusan sumber-kebenaran tetap terbuka.

### 14. `checklist_master`
- **Purpose / Module:** master pertanyaan checklist per item type — Checklist. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(10) UNSIGNED AI`; `item_type_id int(10) UNSIGNED NOT NULL`; `question varchar(255) NOT NULL`; `frequency enum('daily','weekly','monthly') NOT NULL` (**legacy column** — ditulis seeder, diabaikan query aktif; CONF-DB-023); `require_photo tinyint(1) DEFAULT 0` (tidak ditegakkan saat submit — Q-013); `active tinyint(1) DEFAULT 1`; `created_at datetime DEFAULT current_timestamp()`.
- **PK/AI:** id. **FK:** `item_type_id → asset_item_types.id` ON DELETE CASCADE. **Indexes:** PK + `idx_item_type`. **Enum:** `frequency`.
- **Source Code Usage:** semua kanal checklist memfilter `item_type_id + active`, urut id; kolom grid/print dipetakan dari **teks pertanyaan** (rapuh — legacy).

### 15. `checklist_logs`  ⭐ tabel inti
- **Purpose / Module:** log pengisian checklist per periode — Checklist/Evidence/Report/Ranking. **Status:** CONFIRMED_ACTIVE.
- **Columns [PRODUCTION VERIFIED penuh]:** `id int(10) UNSIGNED AI`; `inventory_id int(10) UNSIGNED NOT NULL`; `item_type_id int(10) UNSIGNED NOT NULL`; `checklist_template_id int(10) UNSIGNED NOT NULL`; `check_date date NOT NULL`; `period_key varchar(10) NOT NULL`; **`time_slot varchar(5) NULL`** [Q-003]; **`status enum('ok','not_ok','na') NOT NULL DEFAULT 'ok'`** [Q-001 RESOLVED]; `remark text NULL`; `photo varchar(255) NULL`; **`checked_by varchar(100) NULL`** (string nama — LEGACY DATABASE BEHAVIOR, Q-006); `created_at datetime NULL`; **`follow_up_status enum('open','monitoring','closed') DEFAULT 'open'`** [Q-003]; **`follow_up_note text NULL`**; **`follow_up_date date NULL`**. **Tidak ada `updated_at`** (Q-023: LEGACY CODE WRITES NON-PERSISTED FIELD). **Tidak ada `deleted_at`.**
- **PK/AI:** id. **FK:** `inventory_id → compliance_inventory.id` CASCADE/CASCADE; `item_type_id → asset_item_types.id` CASCADE/CASCADE; `checklist_template_id → checklist_master.id` CASCADE.
- **Indexes:** PK, KEY ×3 (kolom FK), `idx_notok_photo (status, photo)` (mendukung evidence center).
- **Unique:** **TIDAK ADA** unique `(inventory_id, period_key[, time_slot])` → dedup **APPLICATION-LEVEL** (BR-09).
- **Source Code Usage:** ditulis oleh submitChecklist + 12 save*Grid + markAll*; dibaca detail/grid/report/ranking/evidence/dashboard/home/badge.
- **Notes:** `period_key varchar(10)` pas untuk `YYYY-MM-DD` / `YYYY-MM-Wn`.

### 16. `checklist_schedules`
- **Purpose / Module:** jadwal frekuensi per item type — **LEGACY**. **Status:** **CONFIRMED_LEGACY** (CONF-DB-021).
- **Columns:** `id int(10) UNSIGNED AI`; `item_type_id int(10) UNSIGNED NOT NULL`; `frequency enum('daily','weekly','monthly') NOT NULL`; `active tinyint(4) NOT NULL DEFAULT 1`.
- **PK/AI:** id. **FK:** `item_type_id → asset_item_types.id` CASCADE/CASCADE.
- **Source Code Usage:** **hanya `ChecklistScheduleSeeder`** — tidak ada controller/model aktif. Frekuensi efektif = `asset_item_types.checklist_frequency`.
- **Notes:** jangan dibawa ke Laravel apa adanya.

### 17. `compliance_calendar_events`
- **Purpose / Module:** event kalender compliance (digabung dgn holidays di UI kalender) — Calendar. **Status:** CONFIRMED_ACTIVE (via HolidayController; koreksi CONF-DB-022).
- **Columns:** `id bigint(20) UNSIGNED AI`; `title varchar(180) NOT NULL`; `start_at datetime NOT NULL`; `end_at datetime NULL`; `all_day tinyint(1) NOT NULL DEFAULT 1`; `color varchar(20) NOT NULL DEFAULT 'blue'`; `sticker varchar(16) NULL`; `notes text NULL`; `created_by int(11) NULL`; `created_at datetime NOT NULL DEFAULT current_timestamp()`; `updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `start_at`, `end_at`, `created_by`. **FK/Unique/Enum:** —.
- **Source Code Usage:** HolidayController (aktif, guard `tableExists`) + ComplianceCalendarEventModel. `ComplianceCalendarController` (standalone) tetap dead; feed `compliance/calendar/events` tetap tanpa route (CONF-019).

---

## D. IT Assets & Devices

### 18. `assets`
- **Purpose / Module:** aset IT (PC/laptop/dll.) — IT Assets. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `inventory_no varchar(50) NULL`; `category_id int(11) NULL`; `asset_name varchar(100) NULL`; `brand varchar(50) NULL`; `serial_number varchar(100) NULL`; `photo varchar(255) NULL`; `purchase_date date NULL`; `status enum('aktif','rusak','mutasi') DEFAULT 'aktif'` (`mutasi` tidak dipakai kode — CONF-DB-019); `location varchar(100) NULL`; `created_at datetime DEFAULT current_timestamp()`.
- **PK/AI:** id. **Unique:** `inventory_no`. **FK:** `category_id → asset_categories.id` (default RESTRICT). **Indexes:** PK + KEY category_id.
- **Source Code Usage:** ITAssetController; AgentController auto-create `IT-PC-###` (category_id=1 — Q-016); status 'rusak' memicu auto-return assignment (BR-31).

### 19. `asset_categories`
- **Purpose / Module:** kategori aset IT — IT Assets. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `category_name varchar(50) NULL`; `sub_category varchar(50) NULL`.
- **PK/AI:** id. **FK/Unique/Enum/Indexes:** PK saja.
- **Source Code Usage:** ITAssetController (filter `category_name='IT'`).

### 20. `asset_assignments`
- **Purpose / Module:** riwayat penugasan aset → karyawan — IT Assets. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `asset_id int(11) NOT NULL`; `employee_id int(11) NOT NULL`; `assigned_at datetime NOT NULL`; `returned_at datetime NULL`.
- **PK/AI:** id. **FK/Indexes:** PK saja (**tanpa FK & tanpa index pada kolom relasi**).
- **Source Code Usage:** assignSave menutup assignment aktif (`returned_at=now`) lalu insert baru; auto-return saat asset 'rusak' (BR-31). Constraint "1 aktif per asset" = application-level.

### 21. `employees`
- **Purpose / Module:** data karyawan — IT Assets. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `employee_id varchar(50) NOT NULL`; `name varchar(100) NOT NULL`; `division varchar(100) NOT NULL`; `position varchar(100) NOT NULL`; `photo varchar(255) NULL`; `status enum('active','inactive') DEFAULT 'active'`; `created_at datetime DEFAULT current_timestamp()`; `updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()` (DB-managed).
- **PK/AI:** id. **Unique:** `employee_id`. **Enum:** `status`.
- **Source Code Usage:** EmployeeController; guard delete bila ada assignment aktif (BR-32).

### 22. `it_devices`
- **Purpose / Module:** device Windows terdaftar agent — IT Device Monitoring. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) AI`; `asset_id int(11) NULL`; `hostname varchar(100) NULL`; `manufacturer varchar(100) NULL`; `model varchar(100) NULL`; `bios varchar(100) NULL`; `device_user varchar(100) NULL`; `os varchar(100) NULL`; `os_version varchar(100) NULL`; **`cpu longtext NULL`** (wadah state JSON dari agent — Q-003 VERIFIED); `cpu_name varchar(150) NULL`; `cpu_core int(11) NULL`; `cpu_thread int(11) NULL`; `gpu varchar(150) NULL`; `disk_model varchar(255) NULL`; `architecture varchar(50) NULL`; `ram varchar(50) NULL`; `ram_gb int(11) NULL`; `storage varchar(50) NULL`; `storage_gb int(11) NULL`; `last_ip varchar(50) NULL`; `mac_address varchar(50) NULL`; `agent_version varchar(20) NULL`; `last_update_check datetime NULL`; `last_seen datetime NULL`; `status enum('online','offline') DEFAULT 'offline'`; `device_token varchar(100) NULL`.
- **PK/AI:** id. **Unique:** `asset_id`. **FK:** `asset_id → assets.id` (default). **Enum:** `status`.
- **Source Code Usage:** heartbeat (identitas token→mac→hostname), command poll/ack, update; health score membaca kolom turunan; threshold online = Q-012.
- **Notes:** `device_token` tidak UNIQUE di DB (unik app-level saat enroll).

### 23. `it_device_commands`
- **Purpose / Module:** antrian command remote ke agent — IT Device Monitoring. **Status:** CONFIRMED_ACTIVE.
- **Columns:** `id int(11) UNSIGNED AI`; `device_id int(11) UNSIGNED NOT NULL`; `command_id varchar(64) NULL`; `command varchar(100) NOT NULL`; `payload_json longtext NULL`; `status varchar(30) NOT NULL DEFAULT 'queued'`; `result text NULL`; `requested_by varchar(120) NULL`; `requested_at datetime NULL`; `executed_at datetime NULL`; `created_at datetime NULL`; `updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `device_id`, `command_id` (**KEY, bukan unique** — identitas command app-level; CONF-DB-024). **FK:** tidak ada (signedness mismatch dgn `it_devices.id` SIGNED). **Enum:** —.
- **Source Code Usage:** queueRemoteCommand (1 antrian/device, remote_lock_until +25 dtk), popQueuedCommand, ack.

---

## E. Patrol Security

### 24. `patrol_routes`
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(100) NOT NULL`; `slug varchar(100) NOT NULL`; `description text NULL`; `sort_order int(11) NOT NULL DEFAULT 0`; `active tinyint(1) NOT NULL DEFAULT 1`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `slug`. **Status:** CONFIRMED_ACTIVE. **Usage:** PatrolController (rute CP1–CP4, forward/reverse).

### 25. `patrol_checkpoints`
- **Columns:** `id int(11) UNSIGNED AI`; `code varchar(50) NOT NULL`; `name varchar(120) NOT NULL`; `area varchar(120) NULL`; `barcode_value varchar(120) NULL`; `lat decimal(10,7) NULL`; `lng decimal(10,7) NULL`; `radius_m int(11) NOT NULL DEFAULT 10`; `map_x decimal(5,2) NULL`; `map_y decimal(5,2) NULL`; `active tinyint(1) NOT NULL DEFAULT 1`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `code`. **Status:** CONFIRMED_ACTIVE. **Usage:** scan checkpoint (barcode + GPS radius — BR-38).

### 26. `patrol_route_checkpoints`
- **Columns:** `id int(11) UNSIGNED AI`; `route_id int(11) UNSIGNED NOT NULL`; `checkpoint_id int(11) UNSIGNED NOT NULL`; `route_order int(11) NOT NULL DEFAULT 1`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `(route_id, checkpoint_id)`. **FK:** — (application-only). **Status:** CONFIRMED_ACTIVE. **Usage:** urutan checkpoint wajib (nextCheckpoint).

### 27. `patrol_sessions`
- **Columns:** `id int(11) UNSIGNED AI`; `route_id int(11) UNSIGNED NOT NULL`; `patrol_date date NOT NULL`; `started_by int(11) UNSIGNED NOT NULL`; `started_at datetime NULL`; `ended_at datetime NULL`; `status varchar(20) NOT NULL DEFAULT 'active'`; `total_checkpoints int(11) NOT NULL DEFAULT 0`; `checked_count int(11) NOT NULL DEFAULT 0`; `issue_count int(11) NOT NULL DEFAULT 0`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `(route_id, patrol_date)`. **FK/Enum:** — (`status` varchar). **Status:** CONFIRMED_ACTIVE. **Usage:** 1 sesi aktif/user/hari (BR-37; app-level).

### 28. `patrol_logs`
- **Columns:** `id int(11) UNSIGNED AI`; `session_id int(11) UNSIGNED NOT NULL`; `route_id int(11) UNSIGNED NOT NULL`; `checkpoint_id int(11) UNSIGNED NOT NULL`; `checked_by int(11) UNSIGNED NOT NULL` (INT user id — kontras dgn checklist_logs.checked_by string, Q-006); `barcode_value varchar(120) NULL`; `status varchar(20) NOT NULL DEFAULT 'ok'`; `note text NULL`; `latitude decimal(10,7) NULL`; `longitude decimal(10,7) NULL`; `distance_m decimal(6,2) NULL`; `photo_path varchar(255) NULL`; `checked_at datetime NULL`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `(session_id, checkpoint_id)`. **FK:** — (application-only). **Status:** CONFIRMED_ACTIVE.

### 29. `patrol_log_photos`
- **Columns:** `id int(11) UNSIGNED AI`; `log_id int(11) UNSIGNED NOT NULL`; `photo_path varchar(255) NOT NULL`; `sort_order int(11) NOT NULL DEFAULT 1`; `created_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `log_id`. **Status:** CONFIRMED_ACTIVE. **Usage:** foto wajib ≥1 per scan (BR-38).

### 30. `patrol_layouts`
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(100) NOT NULL`; `image_path varchar(255) NULL`; `image_scale decimal(8,4) DEFAULT 1.0000`; `image_offset_x decimal(8,2) DEFAULT 0.00`; `image_offset_y decimal(8,2) DEFAULT 0.00`; `active tinyint(1) NOT NULL DEFAULT 1`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Status:** CONFIRMED_ACTIVE. **Usage:** editor layout peta (admin).

---

## F. Utility (Boiler / IPAL / PDAM)

### 31. `boiler_fuel_logs`
- **Columns:** `id int(11) AI`; `log_date date NOT NULL`; `log_time time NOT NULL`; `polybag int(11) DEFAULT 0`; `kg decimal(12,2) DEFAULT 0.00`; `note varchar(255) NULL`; `created_by int(11) NULL`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Indexes:** `idx_date (log_date)` (BUKAN unique — multi entri per hari, BR-29). **Status:** CONFIRMED_ACTIVE. **Usage:** BoilerFuelController (SUM per tanggal, export bulanan dgn warna libur).

### 32. `ipal_logs`
- **Columns:** `id int(11) AI`; `log_date date NOT NULL`; `start_meter decimal(10,2) NULL`; `stop_meter decimal(10,2) NULL`; `pemakaian decimal(10,2) NULL`; `ket varchar(255) NULL`; `created_by int(11) NULL`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `unique_log_date (log_date)` [PRODUCTION VERIFIED — koreksi CONF-DB-004; BR-30 dikoreksi]. **Status:** CONFIRMED_ACTIVE.

### 33. `pdam_water_logs`
- **Columns:** `id int(11) UNSIGNED AI`; `log_date date NOT NULL`; `log_time time NULL`; `meter_reading decimal(12,2) NOT NULL DEFAULT 0.00`; `note text NULL`; `created_by int(11) NULL`; `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `uniq_pdam_water_log_date (log_date)` + KEY log_date. **Status:** CONFIRMED_ACTIVE. (BR-28; role admin/compliance/office.)

### 34. `pdam_water_boiler_logs`
- **Columns:** sama dgn #33. **Unique:** `uniq_pdam_water_boiler_log_date (log_date)` + KEY log_date. **Status:** CONFIRMED_ACTIVE.

---

## G. EMS / GHG (8 tabel)

### 35–38. `ems_electric_consumption_entries` / `ems_stationary_combustion_entries` / `ems_mobile_combustion_entries` / `ems_water_consumption_entries`
- **Pola entries:** `id int(11) UNSIGNED AI`; `report_year int(4) NOT NULL`; (`section_key varchar(32) NOT NULL` untuk stationary/mobile); `report_month int(2) NOT NULL`; kolom konsumsi (`consumption_kwh decimal(14,2)` / `consumption_amount decimal(14,2)` / `consumption_m3 decimal(12,2)`) NOT NULL DEFAULT 0; `created_at`/`updated_at datetime NULL`.
- **Unique:** electric/water: `(report_year, report_month)`; stationary/mobile: `(report_year, section_key, report_month)`. **Status:** CONFIRMED_ACTIVE (EmsReportController).

### 39–42. `ems_electric_consumption_years` / `ems_stationary_combustion_years` / `ems_mobile_combustion_years` / `ems_water_consumption_years`
- **Pola years:** `id int(11) UNSIGNED AI`; `report_year int(4) NOT NULL`; `production_output decimal(18,2) NULL`; `notes text NULL`; `created_at`/`updated_at datetime NULL`.
- **Unique:** `report_year`. **Status:** CONFIRMED_ACTIVE. (Baseline 2026; water punya seed 2025.)
- **Relasi entries→years:** via `report_year` INT (application-only, bukan FK).

---

## H. FDM (2 tabel)

### 43. `fdm_production_section_years`
- **Columns:** `id int(11) UNSIGNED AI`; `report_year int(4) NOT NULL`; `created_at`/`updated_at datetime NULL`.
- **Unique:** `report_year`. **Status:** CONFIRMED_ACTIVE. **Notes:** `ensureYears()` INSERT tahun berjalan s.d. +4 **saat GET** (write-on-read — CONF-023).

### 44. `fdm_production_section_entries`
- **Columns:** `id int(11) UNSIGNED AI`; `year_id int(11) UNSIGNED NOT NULL`; `section_key varchar(100) NOT NULL`; `section_label varchar(255) NOT NULL`; `entry_type varchar(50) NOT NULL DEFAULT 'retail'`; `frequency_label varchar(50) NOT NULL DEFAULT 'Monthly'`; `logo_path varchar(255) NULL`; `display_order int(11) NOT NULL DEFAULT 0`; `monthly_values longtext NULL` (JSON); `created_at`/`updated_at datetime NULL`.
- **PK/AI:** id. **Unique:** `(year_id, section_key)`. **FK:** `year_id → fdm_production_section_years.id` CASCADE/CASCADE. **Status:** CONFIRMED_ACTIVE.

---

## I. Questionnaire (4 tabel)

### 45. `compliance_questionnaires`
- **Columns:** `id int(10) UNSIGNED AI`; `slug varchar(160) NOT NULL`; `title varchar(255) NOT NULL`; `subtitle varchar(255) NULL`; `description text NULL`; `footer_note text NULL`; `collect_name tinyint(4) DEFAULT 1`; `collect_phone tinyint(4) DEFAULT 1`; `collect_email tinyint(4) DEFAULT 1`; `active tinyint(4) NOT NULL DEFAULT 1`; `sort_order int(11) NOT NULL DEFAULT 0`; `created_at`/`updated_at datetime NULL`.
- **Unique:** `slug`. **Status:** CONFIRMED_ACTIVE. **Usage:** publik `/kuesioner/{slug}` tanpa auth/CSRF; bootstrap template default di constructor (CONF-024).

### 46. `compliance_questionnaire_questions`
- **Columns:** `id int(10) UNSIGNED AI`; `questionnaire_id int(10) UNSIGNED NOT NULL`; `section_label varchar(255) NULL`; `question_code varchar(30) NULL`; `sort_order int(11) NOT NULL DEFAULT 0`; `question_text text NOT NULL`; `answer_type enum('radio','text','textarea','date','email','phone','number','select','scale_5','scale_10') NOT NULL DEFAULT 'radio'`; `options_json longtext NULL`; `scale_low_label varchar(255) NULL`; `scale_high_label varchar(255) NULL`; `placeholder varchar(255) NULL`; `help_text text NULL`; `is_required tinyint(4) NOT NULL DEFAULT 1`; `created_at`/`updated_at datetime NULL`.
- **FK:** `questionnaire_id → compliance_questionnaires.id` CASCADE/CASCADE. **Enum:** `answer_type`. **Status:** CONFIRMED_ACTIVE.

### 47. `compliance_questionnaire_responses`
- **Columns:** `id int(10) UNSIGNED AI`; `questionnaire_id int(10) UNSIGNED NOT NULL`; `response_code varchar(60) NOT NULL`; `respondent_name varchar(255) NOT NULL`; `birth_date date NULL`; `phone varchar(50) NULL`; `email varchar(255) NULL`; `submitted_at datetime NULL`; `created_by varchar(255) NULL`; `created_at datetime NULL`.
- **Unique:** `response_code`. **FK:** `questionnaire_id → questionnaires.id` CASCADE/CASCADE. **Status:** CONFIRMED_ACTIVE.

### 48. `compliance_questionnaire_response_answers`
- **Columns:** `id int(10) UNSIGNED AI`; `response_id int(10) UNSIGNED NOT NULL`; `question_id int(10) UNSIGNED NOT NULL`; `answer_value text NULL`; `created_at datetime NULL`.
- **FK:** `response_id → responses.id` CASCADE/CASCADE; `question_id → questions.id` CASCADE/CASCADE. **Status:** CONFIRMED_ACTIVE.

---

## J. Thermal Imaging (3 tabel)

### 49. `thermal_imaging_locations`
- **Columns:** `id int(11) UNSIGNED AI`; `name varchar(180) NOT NULL`; `section varchar(180) NULL`; `active tinyint(1) NOT NULL DEFAULT 1`; `created_by int(11) NULL`; `created_at`/`updated_at datetime NULL`.
- **Indexes:** KEY `active`. **Status:** CONFIRMED_ACTIVE.

### 50. `thermal_imaging_reports`
- **Columns:** `id int(11) UNSIGNED AI`; `inspection_date date NOT NULL`; `inspector_name varchar(120) NOT NULL`; `facility varchar(180) NOT NULL`; `area_name varchar(180) NOT NULL DEFAULT 'Main Building (Sewing Area)'`; `created_by int(11) NULL`; `created_at`/`updated_at datetime NULL`.
- **Indexes:** KEY `inspection_date`. **Status:** CONFIRMED_ACTIVE.

### 51. `thermal_imaging_report_items`
- **Columns:** `id int(11) UNSIGNED AI`; `report_id int(11) UNSIGNED NOT NULL`; `location_id int(11) UNSIGNED NULL`; `location_name varchar(180) NOT NULL`; `celsius decimal(6,2) NOT NULL`; `thermal_image varchar(255) NULL`; `findings text NULL`; `recommendation text NULL`; `sort_order int(11) NOT NULL DEFAULT 0`; `created_at`/`updated_at datetime NULL`.
- **FK:** `report_id → thermal_imaging_reports.id` CASCADE/CASCADE; `location_id → thermal_imaging_locations.id` ON DELETE CASCADE / **ON UPDATE SET NULL** (kombinasi jarang). **Indexes:** report_id, location_id. **Status:** CONFIRMED_ACTIVE.

---

## 52. Peta Relasi

### 52.1 Database FK (16) [PRODUCTION VERIFIED]

```
inventory_categories ──CASCADE──▶ asset_item_types ──(default)──▶ compliance_inventory
                                        ▲    ▲
                     CASCADE (master)───┘    └───CASCADE (logs)─── checklist_logs ──CASCADE──▶ checklist_master
asset_categories ──(default)──▶ assets ──(default)──▶ it_devices
compliance_questionnaires ──CASCADE──▶ questions ──CASCADE──▶ response_answers ◀──CASCADE── responses ◀──CASCADE── questionnaires
fdm_production_section_years ──CASCADE──▶ fdm_production_section_entries
thermal_imaging_reports ──CASCADE──▶ thermal_imaging_report_items ◀──CASCADE/SET NULL── thermal_imaging_locations
asset_item_types ──CASCADE──▶ checklist_schedules (LEGACY)
```

**Konsekuensi CASCADE (fakta bisnis):** hapus `compliance_inventory` → seluruh `checklist_logs`-nya ikut terhapus; hapus item type → master + logs ikut terhapus; hapus kategori → item types ikut terhapus.

### 52.2 Application-only relationships [SOURCE CODE INFERRED + PRODUCTION VERIFIED tanpa FK]

`users` ← audit_logs, login_sessions, notifications, pics, patrol_logs.checked_by, patrol_sessions.started_by, *.created_by · `compliance_inventory` ← pics (signedness mismatch), area_id→areas (tanpa FK/index), category_id→inventory_categories · `it_devices` ← it_device_commands (signedness mismatch) · patrol chain (sessions→routes, route_checkpoints→routes/checkpoints, logs→sessions/checkpoints, log_photos→logs) · asset_assignments→assets/employees (tanpa FK & index) · ems entries→years via report_year.

---

## 53. Application-Level Constraints (business rule tanpa UNIQUE DB)

1 log per (inventory_id, period_key[, time_slot]) · holidays.holiday_date unik · users.email unik · asset_item_types.code unik (prefix) · inventory_categories.code unik · it_device_commands.command_id unik (UUID kode) · 1 sesi patrol aktif/user/hari · 1 command antrian/device · 1 assignment aktif/asset.

---

## 54. Catatan Ketidakpastian yang Tersisa [UNKNOWN]

Isi data (volume, nilai historis `ng`, distinct status), isi tabel `migrations`, alasan FK audit_logs hilang (CONF-DB-006), nilai `app_settings` produksi (Q-018), kategori id=1 (Q-016), scheduler aktual (Q-015). Semua membutuhkan data/artefak produksi tambahan — bukan structure-only export.
