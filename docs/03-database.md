# 03 — Database

**Engine:** MySQL/MariaDB via MySQLi · **DB default:** `asset_compliance_system` · **Konfigurasi:** `app/Config/Database.php` + `.env` (`database.default.*`).

> ⚠️ **Batasan penting:** Migration di repo (32 file) **tidak mencakup tabel dasar** (users, employees, areas, inventory_categories, compliance_inventory, assets, asset_categories, asset_assignments, it_devices, it_device_logs, holidays, boiler_fuel_logs, ipal_logs, checklist_master, compliance_checklist_master, compliance_checklist_log_items). Tabel-tabel tersebut sudah ada sebelum repo dibuat; strukturnya di bawah ini **direkonstruksi dari model & query** → ditandai `(reconstructed)` = INFERRED, bukan CONFIRMED dari DDL.

## A. Tabel dari migration (CONFIRMED DDL)

### Master & referensi

**`asset_item_types`** — master jenis item inventory.
| Kolom | Tipe | Null/Default | Keterangan |
|---|---|---|---|
| id | INT unsigned AI | PK | |
| inventory_category_id | INT unsigned | NOT NULL | FK → `inventory_categories(id)` ON DELETE CASCADE (FK manual via ALTER) |
| name | VARCHAR(100) | | |
| code | VARCHAR(20) | | |
| active | TINYINT(1) | default 1 | |
| created_at | DATETIME | null | |
| checklist_frequency | (ENUM/VARCHAR) | — | **tidak ada di migration**; dipakai model & controller (`daily|weekly|monthly`) — ditambah di luar repo (UNKNOWN DDL) |
| allow_na | TINYINT | — | sama — dipakai di `checklist()` & grid; UNKNOWN DDL |

### Keluarga checklist — ADA DUA FAMILI PARALEL (lihat 14-Technical-Debt)

**Famili A: `checklist_*` (dipakai sistem aktif)** — migration 2026-01-20.

`checklist_templates`: id PK; item_type_id INT FK→asset_item_types CASCADE/CASCADE; question VARCHAR(255); require_photo TINYINT default 0; active TINYINT default 1; created_at.

`checklist_schedules`: id PK; item_type_id FK→asset_item_types CASCADE; frequency ENUM('daily','weekly','monthly'); active default 1. *(Tidak dipakai kode aktif — frekuensi dibaca dari `asset_item_types.checklist_frequency`; lihat 14/15.)*

`checklist_logs`: id PK; inventory_id FK→compliance_inventory CASCADE; item_type_id FK→asset_item_types CASCADE; checklist_template_id FK→checklist_templates CASCADE; check_date DATE; **period_key VARCHAR(10)**; **status ENUM('ok','ng','na') default 'ok'** ⚠️; remark TEXT null; photo VARCHAR(255) null; checked_by VARCHAR(100) null (nama, bukan FK user); created_at DATETIME.
  - Kolom tambahan dipakai kode tapi **tidak ada di migration**: `time_slot` (PG/SI/SO), `follow_up_status`, `follow_up_note`, `follow_up_date` (dari `ChecklistLogModel::$allowedFields`) → UNKNOWN DDL.
  - ⚠️ Kode menulis `status='not_ok'`, padahal ENUM migration hanya `ok|ng|na` → schema drift, lihat 15-Ambiguities.

**Famili B: `compliance_checklist_*` (legacy/paralel)** — migration 2026-01-19.

`compliance_checklist_templates`: id; item_type_id FK→asset_item_types CASCADE/RESTRICT; code VARCHAR(30); question; require_photo; active; created_at.

`compliance_checklist_schedules`: id; inventory_id FK→compliance_inventory CASCADE/RESTRICT; frequency ENUM(daily,weekly,monthly); week_day TINYINT (1=Mon..7=Sun); month_day TINYINT (1–31); start_date DATE; active; created_at.

`compliance_checklist_logs`: id; inventory_id FK; schedule_id FK→schedules CASCADE/RESTRICT; template_id FK→templates CASCADE/RESTRICT; inspection_date DATE; result ENUM('ok','ng','na'); note; photo; checked_by INT unsigned; created_at.
  - ⚠️ Model `ComplianceChecklistLogModel` memakai kolom berbeda (item_type_id, frequency, inspection_week/month/year) — tidak cocok dgn migration → drift/dead path.
  - Tabel `compliance_checklist_master` & `compliance_checklist_log_items` hanya ada modelnya; **tanpa migration** → UNKNOWN.

### Questionnaire (migration 2026-04-06/07)

`compliance_questionnaires`: id PK; **slug VARCHAR(160) UNIQUE**; title; subtitle null; description TEXT; footer_note; collect_name/collect_phone/collect_email TINYINT default 1; active default 1; sort_order INT default 0; created_at/updated_at.

`compliance_questionnaire_questions`: id PK; questionnaire_id FK CASCADE; section_label null; question_code null; sort_order; question_text TEXT; **answer_type ENUM('radio','text','textarea','date','email','phone','number','select','scale_5','scale_10')** default radio; options_json LONGTEXT; scale_low_label/scale_high_label null; placeholder; help_text; is_required default 1; timestamps.

`compliance_questionnaire_responses`: id PK; questionnaire_id FK CASCADE; **response_code VARCHAR(60) UNIQUE**; respondent_name; birth_date null; phone null; email null; submitted_at null; created_by VARCHAR null; created_at.

`compliance_questionnaire_response_answers`: id PK; response_id FK CASCADE; question_id FK CASCADE; answer_value TEXT null; created_at.

### IT Device (migration 2026-04-08)

`it_device_commands`: id PK; device_id INT (index); command_id VARCHAR(64) (index); command VARCHAR(100); payload_json LONGTEXT; status VARCHAR(30) default 'queued'; result TEXT; requested_by VARCHAR(120); requested_at; executed_at; created_at/updated_at. **Tanpa FK DB** ke it_devices (relasi di level kode).

### EMS (migration 2026-04-09/13/14)

Pola seragam 4 pasangan tabel (water/electric/stationary/mobile):
- `ems_*_years`: id PK; **report_year INT(4) UNIQUE**; production_output DECIMAL(18,2) null; notes TEXT; timestamps.
- `ems_water_consumption_entries`: id PK; UNIQUE(report_year, report_month); consumption_m3 DECIMAL(12,2) default 0; timestamps.
- `ems_electric_consumption_entries`: sama dgn `consumption_kwh` DECIMAL(14,2).
- `ems_stationary_combustion_entries` / `ems_mobile_combustion_entries`: UNIQUE(report_year, section_key, report_month); section_key VARCHAR(32); consumption_amount DECIMAL(14,2).

### FDM (migration 2026-04-15)

`fdm_production_section_years`: id PK; report_year UNIQUE (nama index kustom); timestamps.
`fdm_production_section_entries`: id PK; year_id FK→years CASCADE; section_key VARCHAR(100); section_label; entry_type VARCHAR(50) default 'retail'; frequency_label VARCHAR(50) default 'Monthly'; logo_path null; display_order INT default 0; monthly_values LONGTEXT (JSON); UNIQUE(year_id, section_key); timestamps.

### Users & access (migration 2026-04-22, 2026-05-08, 2026-08-07)

- `users.role` diubah: `ENUM('admin','staff','compliance','auditor','office')` → **VARCHAR(50) default 'staff'**.
- `user_roles`: id PK; **name VARCHAR(50) UNIQUE**; timestamps. Seed: admin, staff, compliance, auditor, office + role existing.
- `users.page_access` TEXT null (JSON array menu key) — ditambah 2026-05-08 setelah kolom `permission`.
- `users.email` VARCHAR(190) null — ditambah 2026-08-07 setelah `username`.

### Patrol (migration 2026-04-22/23)

`patrol_routes`: id PK; name; **slug UNIQUE**; description; sort_order; active; timestamps. Seed: forward (CP1→CP4), reverse (CP4→CP1).
`patrol_checkpoints`: id PK; **code UNIQUE**; name; area null; barcode_value null; lat/lng DECIMAL(10,7) null; radius_m INT default 10; map_x/map_y DECIMAL(5,2); active; timestamps. Seed CP1–CP4 dgn koordinat pabrik.
`patrol_route_checkpoints`: id PK; route_id; checkpoint_id; route_order default 1; KEY(route_id, checkpoint_id); timestamps. **Tanpa FK DB.**
`patrol_sessions`: id PK; route_id; patrol_date DATE; started_by INT; started_at; ended_at null; status VARCHAR(20) default 'active'; total_checkpoints; checked_count; issue_count; KEY(route_id, patrol_date); timestamps.
`patrol_logs`: id PK; session_id; route_id; checkpoint_id; checked_by INT; barcode_value; status VARCHAR(20) default 'ok'; note; latitude/longitude DECIMAL(10,7); distance_m DECIMAL(6,2); photo_path; checked_at; KEY(session_id, checkpoint_id); timestamps.
`patrol_layouts`: id PK; name; image_path null; image_scale DECIMAL(8,4) default 1; image_offset_x/y DECIMAL(8,2) default 0; active; timestamps. Seed: 'Layout Utama'.
`patrol_log_photos`: id PK; log_id (index); photo_path; sort_order; created_at.

### PDAM & audit & notifikasi & kalender (2026-05 s/d 2026-08)

`pdam_water_logs`: id PK; log_date DATE (index → lalu **UNIQUE** `uniq_pdam_water_log_date`); log_time TIME null; meter_reading DECIMAL(12,2) default 0; note TEXT; created_by INT null; timestamps.
`pdam_water_boiler_logs`: sama + UNIQUE `uniq_pdam_water_boiler_log_date` sejak awal.
`audit_logs`: id PK; user_id INT unsigned null FK→users CASCADE; action VARCHAR(100) (index); description TEXT; ip_address VARCHAR(45); user_agent TEXT; created_at default CURRENT_TIMESTAMP (index); **+ kolom 2026-08-10:** session_id VARCHAR(64), status VARCHAR(20) default 'success', login_method, channel, route, request_method, device_type, browser, platform, metadata TEXT. `down()` sengaja tidak menghapus (data penting).
`login_sessions`: id BIGINT PK; session_key VARCHAR(64) UNIQUE; user_id; username; login_method default 'password'; channel default 'web'; ip_address; user_agent; browser; platform; device_type; started_at default now; last_seen_at; ended_at; last_route; logout_reason; is_active TINYINT default 1.
`app_settings`: id PK; **setting_key VARCHAR(120) UNIQUE**; setting_value TEXT; is_secret TINYINT default 0; updated_by; updated_at default CURRENT_TIMESTAMP. Default keys: company_* , document_*, notification_* (email_enabled, whatsapp_enabled, whatsapp_webhook, whatsapp_token secret).
`notifications`: id BIGINT PK; user_id INT (index dgn read_at); actor_user_id null; type VARCHAR(40) default 'info'; title VARCHAR(180); message TEXT; url VARCHAR(500); entity_type/entity_id; **dedupe_key VARCHAR(190) UNIQUE**; read_at null; email_status/whatsapp_status VARCHAR(20) default 'skipped'; created_at. **Tanpa FK DB** (komentar migration: kompatibilitas tabel users lama).
`compliance_inventory_pics`: id BIGINT PK; inventory_id INT; user_id INT; **UNIQUE(inventory_id, user_id)**; is_primary TINYINT default 0; created_at. + migrasi data dari kolom teks `compliance_inventory.pic` (maks 2 nama, cocokkan `users.name`).
`compliance_calendar_events`: id BIGINT PK; title VARCHAR(180); start_at DATETIME (index); end_at null (index); all_day default 1; color VARCHAR(20) default 'blue'; sticker VARCHAR(16) null; notes TEXT; created_by; created_at default now; updated_at.

## B. Tabel tanpa migration (reconstructed dari model/query — INFERRED)

**`users`** (inti): id PK; username; email; name; password (bcrypt); role VARCHAR(50); permission ('read'|'write'); status ('active'|'inactive'); wa_number; photo; page_access TEXT(JSON); created_at. Dipakai: AuthController, UserController, model callbacks.

**`employees`**: id PK; employee_id (unik, divalidasi manual); name; division; position; status ('active'|'inactive'); photo. (EmployeeController)

**`assets`**: id; inventory_no; category_id FK→asset_categories; asset_name; brand; serial_number; purchase_date; photo; status ('aktif','rusak', dst.); location. (AssetModel, ITAssetController)

**`asset_categories`**: id; category_name ('IT','Compliance',...); sub_category ('Komputer','Mouse','Keyboard','Monitor',...). (DashboardController, ITAssetController)

**`asset_assignments`**: id; asset_id; employee_id; assigned_at; returned_at null=aktif. (ITAssetController, EmployeeController)

**`inventory_categories`**: id; name; code (FS/HSE/CTPAT/UTL); area_id?; active. (AssetItemTypeSeeder memakai code; ChecklistMasterController memfilter `active=1`)

**`areas`**: id; name. (AreaModel)

**`compliance_inventory`**: id PK; category_id; area_id; item_type_id (FK→asset_item_types, RESTRICT, migration 2026-01-19-000002); asset_code; type_description; specific_area; pic (teks legacy); status; qty; remark; expired_date; photo; qr_image; **active** (dipakai reminder: `where('active',1)`); timestamps (model `useTimestamps=true`).

**`holidays`**: id; holiday_date DATE; description. (HolidayModel)

**`boiler_fuel_logs`**: id; log_date; log_time; polybag; kg; note; created_by; timestamps. (BoilerFuelModel)

**`ipal_logs`**: id; log_date; start_meter; stop_meter; volume; pemakaian; ket; created_by; timestamps. (IpalModel)

**`it_devices`**: id; asset_id; hostname; manufacturer; model; bios; device_user; os; os_version; **cpu LONGTEXT (JSON serbaguna: hardware, health, command queue, lock, interval)**; cpu_name; cpu_core; cpu_thread; gpu; disk_model; architecture; ram; ram_gb; storage; storage_gb; last_ip; mac_address; agent_version; last_update_check; last_seen; status ('online'/'offline'); device_token. (ITDeviceModel)

**`it_device_logs`**: id; device_id; ip_address; cpu_usage; ram_usage; storage_free; logged_at. (ItDeviceLogModel)

**`checklist_master`**: id; item_type_id; question; frequency; require_photo; active. (ChecklistMasterModel + seeder) — kolom `frequency` ada di seeder tapi query aktif memfilter hanya item_type_id+active.

**`compliance_checklist_master`**: id; category; code; name; period; require_photo; active. (ComplianceChecklistMasterModel — tidak dipakai route aktif)

**`compliance_checklist_log_items`**: id; checklist_log_id; checklist_item_id; status; remark. (ComplianceChecklistLogItemModel — tidak dipakai route aktif)

## C. Peta relasi (gabungan FK database + FK tersirat di kode)

```
users ──< user_roles (name, lookup)          [tersirat]
users ──< audit_logs (user_id)               [FK]
users ──< login_sessions (user_id)           [tersirat]
users ──< notifications (user_id/actor)      [tersirat]
users ──< compliance_inventory_pics (user_id)      [tersirat]
users ──< patrol_sessions/log (started_by/checked_by) [tersirat]

inventory_categories ──< asset_item_types (inventory_category_id)  [FK CASCADE]
asset_item_types ──< compliance_inventory (item_type_id)           [FK RESTRICT]
asset_item_types ──< checklist_templates/checklist_schedules       [FK CASCADE]
asset_item_types ──< compliance_checklist_templates                [FK CASCADE/RESTRICT]
asset_item_types ──< checklist_master (item_type_id)               [tersirat]
areas ──< compliance_inventory (area_id)                           [tersirat]
asset_item_types.checklist_frequency ── engine periode checklist   [tersirat, KRITIS]

compliance_inventory ──< checklist_logs (inventory_id)             [FK CASCADE]
compliance_inventory ──< compliance_checklist_schedules/logs       [FK CASCADE/RESTRICT]
compliance_inventory ──< compliance_inventory_pics (inventory_id)  [tersirat]
checklist_templates ──< checklist_logs (checklist_template_id)     [FK CASCADE]
checklist_master ──< checklist_logs (checklist_template_id)        [tersirat — query aktif pakai ini]
holidays ── dipakai engine periode & semua laporan harian          [tersirat]

compliance_questionnaires ──< questions ──< responses ──< answers  [FK CASCADE]

patrol_routes ──< patrol_route_checkpoints >── patrol_checkpoints  [tersirat]
patrol_sessions ──< patrol_logs ──< patrol_log_photos              [tersirat]
patrol_layouts (1 aktif)                                           [tersirat]

assets ──< asset_assignments >── employees                         [tersirat]
asset_categories ──< assets (category_id)                          [tersirat]
assets ──< it_devices (asset_id)                                   [tersirat]
it_devices ──< it_device_commands (device_id)                      [tersirat]
it_devices ──< it_device_logs (device_id)                          [tersirat]

thermal_imaging_reports ──< thermal_imaging_report_items           [FK CASCADE]
thermal_imaging_locations ──< report_items (location_id SET NULL)  [FK]

ems_*_years ──< ems_*_entries (report_year, bukan id)              [tersirat]
fdm_production_section_years ──< entries (year_id)                 [FK CASCADE]

app_settings (key-value global)                                    [dipakai lintas modul]
```

## D. Constraint & aturan DB penting (untuk Laravel)

- Tidak ada **soft delete** di mana pun (Semua delete = hard delete).
- Timestamp tidak seragam: sebagian tabel hanya `created_at`, sebagian `created_at+updated_at`; model CI4 sebagian `useTimestamps=false` dan mengisi manual.
- `checked_by` di `checklist_logs` = **nama string**, bukan FK user. Di `compliance_checklist_logs` = INT user id. Di patrol_logs = INT user id. → inkonsisten, perlu keputusan (lihat 15).
- Beberapa tabel baru sengaja tanpa FK ke `users` karena perbedaan signedness/engine tabel users lama (komentar eksplisit di migration 2026-08-07).
