# 02 — Modules

Setiap modul didokumentasikan berdasarkan bukti source code (bukan asumsi nama folder).

---

## M-01 Authentication & Session

- **Tujuan:** login/logout aman + pelacakan sesi.
- **User/role:** semua user.
- **Route:** `GET/POST /login`, `POST /logout`.
- **Controller:** `AuthController` (`login`, `doLogin`, `logout`).
- **Model/Tabel:** `UserModel` → `users`; `login_sessions`; `audit_logs`.
- **View:** `auth/login.php`, layout `layouts/auth.php`.
- **Validation/rule:**
  - Login menerima **username ATAU email**; hanya `status='active'` (`AuthController::doLogin`).
  - Throttle 5 percobaan/menit per IP (`service('throttler')`, bucket `login_{ip}`).
  - `password_verify` + dummy hash saat user tidak ada (anti user-enumeration timing).
  - Session regenerate saat login; `auth_session_id` acak 48 hex → dicatat ke `login_sessions`.
  - Sesi idle > 8 jam ditandai `expired` (lihat `AuditLogController::index` update + `Config\Session::$expiration = 28800`).
- **Audit:** `login_success`, `login_failed`, `login_blocked`, `logout` (dengan IP, browser, platform, device, channel).
- **Status bukti:** CONFIRMED (kode + migration 2026-07-07 & 2026-08-10).

## M-02 Authorization, Users & Roles

- **Route:** grup `/users` (auth) — index/create/store/roles-store/edit/update/deactivate/activate.
- **Controller:** `UserController`; **Model:** `UserModel`, `UserRoleModel` → `users`, `user_roles`.
- **View:** `users/index|create|edit|_access_form.php`, JS `users-management.js`, `user-access-form.js`.
- **Rule kunci (CONFIRMED):**
  - Role tersimpan sebagai string di `users.role` (dulu ENUM → diubah ke VARCHAR(50) oleh migration 2026-04-22; daftar role dikelola di tabel `user_roles`, default: `admin, staff, compliance, auditor, office`).
  - `users.permission`: `'write'` atau selain itu dianggap read (`isReadOnlyAccess()` — role `read/readonly/read_only` juga dipaksa read-only).
  - `users.page_access` = JSON array menu key; kosong → default per role (`access_default_pages_for_role`).
  - Hanya admin yang bisa memberi/mengubah/menonaktifkan akun admin (`validateRoleAssignment`, `edit`, `deactivate`).
  - Password minimal 8 karakter; user aktif tidak bisa menonaktifkan dirinya sendiri.
  - `WriteFilter` GLOBAL: semua request non-GET dari user read-only → 403/redirect (kecuali /login, /logout, /api/agent, /kuesioner, /logstores).
- **Halaman admin-only:** `audit_logs`, `backups` (flag `admin_only` di `access_menu_catalog` + filter `admin` di route).

## M-03 Home & Notification Center

- **Route:** `GET /home` (auth); notifikasi via `GET /home?view=notifications`; mark-read via `POST settings/change-password` (action `mark_notifications_read`).
- **Controller:** `HomeController`; **Model:** `ComplianceInventoryModel`, `ChecklistLogModel`, `NotificationModel`.
- **View:** `home/index.php`, `home/notifications.php`; JS `home-dashboard.js`.
- **Rule kunci (CONFIRMED):**
  - Home menghitung tugas checklist **milik user** untuk bulan terpilih: inventory dengan PIC=user (`assignedToUser`) × periode (daily: hari kerja s/d hari ini; weekly: W1..W_ceil(hari/7); monthly: Y-m) yang belum ada log → `pending`/`late`/`not_ok`/`done` + progress %.
  - Notifikasi sidebar = unread notifications + pending/late checklist (cache `sidebar_notif_{userId}` 300 dtk, di `BaseController::loadNotifications`).
  - Klik notifikasi → tandai `read_at` via query `notification_id` (`markOpenedNotification`).

## M-04 Compliance Inventory (Inventory & QR)

- **Route:** grup `compliance/inventory` — index/create/store/edit/update/delete/detail/update-photo/regenerate-qr/item-types/get + qr-center/qr-album*; AJAX: `get/(:num)`, `item-types/(:num)`.
- **Controller:** `ComplianceInventoryController` (5401 baris — god controller); **Model:** `ComplianceInventoryModel`, `InventoryCategoryModel`, `AreaModel`, `AssetItemTypeModel`.
- **View:** `compliance/inventory/*` (index, _table, detail, _detail_month, create, _modal_add/edit/qr/crop/zoom, qr_center, qr_print_album); JS `inventory.js`, `inventory-detail.js`, `qr-center.js`.
- **Tabel:** `compliance_inventory`, `inventory_categories`, `areas`, `asset_item_types`, `compliance_inventory_pics`.
- **Rule kunci:**
  - `asset_code` auto-generate `KODEKATEGORI-KODEITEM-###` bila kosong (CONFIRMED, `store`/`update`).
  - QR dibuat via **API eksternal** `api.qrserver.com` berisi URL detail `/compliance/inventory/detail/{id}`, lalu asset code ditulis di tengah gambar (GD) (CONFIRMED, `QrService` + inline di store/update).
  - PIC: kolom teks `pic` (maks 2 nama, pemisah newline/koma/` - `) disinkronkan ke `compliance_inventory_pics` (is_primary utk nama pertama) + notifikasi `assignment` ke user aktif yang namanya cocok (CONFIRMED, callbacks model).
  - Filter/sort/pagination server-side; upload foto max 2MB JPG/PNG/WEBP (`updatePhoto`).
  - Hapus inventory = hard delete (`delete($id)`); tidak ada soft delete.
  - Status inventory: nilai yang dipakai UI: `Good` (Baik), `Need Repair` (Perlu Perbaikan), `Not Active` (Tidak Aktif) — dari `inventory.js getStatusMeta` (CONFIRMED di JS; kolom bebas teks di DB).

## M-05 Checklist Master

- **Route:** `compliance/checklist/master` — masterIndex, masterByCategory, masterItem, store, update, item-frequency, delete.
- **Controller:** `ComplianceChecklistMasterController`; **Model:** `ChecklistMasterModel` → `checklist_master`.
- **View:** `compliance/checklist_master/*`; JS `checklist-master.js`.
- **Rule kunci (CONFIRMED):**
  - Pertanyaan per `item_type_id`, flag `require_photo`, `active`.
  - **Frekuensi checklist disimpan per item type** di `asset_item_types.checklist_frequency` (`daily|weekly|monthly`), diubah via `updateItemFrequency`.
  - Catatan: tabel `checklist_master` **tidak punya migration** di repo (lihat 15-Ambiguities).

## M-06 Checklist Execution (inti bisnis)

Detail penuh di `10-checklist-rules.md`. Ringkasan kanal pengisian:

| Kanal | Route | Target item | Frekuensi | Role |
|---|---|---|---|---|
| Form per item | `GET /compliance/checklist/{id}` + `POST /compliance/checklist/submit` | semua (fallback) | sesuai item type | admin, compliance, staff |
| Generic grid | `…/generic-grid/{id}` + save/mark-all | semua (fallback grid) | daily/weekly/monthly | admin, compliance, staff |
| CCTV grid | `…/cctv-grid` | item_type 13 | daily | baca: +auditor; tulis: admin/compliance/staff; mark-all: admin/compliance |
| Emergency Light grid | `…/emergency-light-grid` | item_type 4 | monthly | admin, compliance |
| Emergency Exit Light grid | `…/emergency-exit-light-grid` | item_type 59 | monthly | admin, compliance |
| First Aid Box grid | `…/first-aid-box-grid` | item_type 10 | monthly | admin, compliance |
| First Aid Content grid | `…/first-aid-content-grid/{id}` | item_type 33 | daily | admin, compliance |
| Fire Extinguisher grid | `…/fire-extinguisher-grid` | item_type 1 | monthly | admin, compliance |
| Intrusion Alarm grid | `…/intrusion-alarm-grid` | item_type 8 | weekly | admin, compliance |
| Hydrant grid | `…/hydrant-grid` | item_type 2 | weekly | admin, compliance |
| Smoke Detector grid | `…/smoke-detector-grid` | item_type 7 | monthly | admin, compliance |
| Heat Detector grid | `…/heat-detector-grid` | item_type 6 | monthly | admin, compliance |
| Gate grid | `…/gate-grid/{id}` | item_type 40 | daily | admin, compliance |
| Toilet (slot) | form per-item / generic grid | item_type 52 | daily × 3 slot (PG/SI/SO) | admin, compliance, staff |

## M-07 Compliance Dashboard

- **Route:** `compliance/dashboard` + 8 endpoint AJAX (`trend`, `progress-trend`, `status-pie`, `total-inventory`, `risk-insight`, `risk-trend`*, `pending-checklist`, `data`).
  - *Catatan: `risk-trend` di-route ke `getRiskTrendAjax` — method tsb tidak ditemukan di controller (lihat 14-Technical-Debt / 15).*
- **Controller:** `ComplianceDashboardController`; **View:** `compliance/dashboard/index.php` (+`overdue.php`); JS `dashboard.js`.
- **Rule:** KPI periode aktif (Y-m): total distinct inventory dengan log, ok/not_ok/na; `late` = inventory yang pernah ada log tapi tidak di periode aktif (logika sederhana — komentar di kode "sementara sederhana dulu") (CONFIRMED). Pending checklist menghitung hari kerja (skip weekend+holiday) untuk daily, W1..Wn utk weekly, Y-m utk monthly.

## M-08 Monitoring Progress & Ranking

- **Route:** `compliance/progress` (+ajax, export CSV, detail, remind), `compliance/ranking`.
- **Controller:** `ProgressController`, `ComplianceRankingController`; JS `progress-monitoring.js`.
- **Rule kunci (CONFIRMED):**
  - Progress per user: inventory dicocokkan ke user lewat **nama depan** pada kolom teks `pic` (REGEXP) — bukan relasi pics. Exclude role `auditor` & username `admin`.
  - Export = CSV (User, Total, Done, Pending, Late, Progress%).
  - Reminder WA per user via Fonnte (butuh `wa_number`/`namePhoneMap`).
  - Ranking per bulan: grup per `checked_by` (nama string), ontime bila `check_date <= akhir periode` (daily: ≤ tanggal; weekly: ≤ hari ke-7 minggu; monthly: ≤ akhir bulan); skor = `ontime*10 + late*3`; urut skor → ontime% → total.

## M-09 Evidence Center

- **Route:** `compliance/evidence` (+ajax grid, detail, update-followup).
- **Controller:** `ComplianceEvidenceController`; **View:** `compliance/evidence/*`; JS `evidence.js`.
- **Rule (CONFIRMED):** sumber = `checklist_logs` dengan `status='not_ok'` DAN ada foto; follow-up status ∈ `open|monitoring|closed` + `follow_up_note` (maks 1000 char) + `follow_up_date` = hari ini; pagination 12.

## M-10 Report (Laporan Compliance)

- **Route:** `compliance/report` (+`load` AJAX, `item-by-category`, `inventory-by-type`).
- **Controller:** `ComplianceReportController::buildReportData` (juga dipakai ExportPdfController).
- **View:** `compliance/report/{index,_table,_daily,_weekly,_monthly}.php`; JS `compliance-report.js`.
- **Rule:** grid per item: monthly → 12 bulan × pertanyaan; daily → hari × pertanyaan (toilet: per slot); weekly → W1-4 × pertanyaan; findings = log not_ok; checker per periode dari `checked_by`; prev/next item by `asset_code`. `isToiletChecklist` = `item_type_id === 52` (hard-coded).

## M-11 Print Center & Export PDF

- **Route:** `compliance/print` (index/item/itemPreview/inventoryByType/batch/batchPreview), `export/pdf/single/{inventoryId}/{periodKey}`, `export/pdf/recap/{inventoryId}/{year}/{month}`.
- **Controller:** `CompliancePrintController`, `ExportPdfController`; **Library:** `EamsPdf`.
- **Rule:** batch PDF per item type+bulan dengan layout khusus per item (APAR, Fire Alarm, Hydrant, EL, EEL, CCTV, Smoke/Heat detector — kolom & judul hard-coded); agregasi status worst-case `not_ok > ok > na`; foto finding hanya bila file ada di `uploads/checklist/`. Template: `app/Views/pdf/*` + `pdf/batch_partials/*`.

## M-12 Compliance Calendar & Holidays

- **Route:** `holidays` grup (index/list/store/update/delete + national/*). **Controller:** `HolidayController` (juga melayani event kalender).
- **Tabel:** `holidays` (libur nasional), `compliance_calendar_events` (event manual, warna+sticker emoji).
- **View:** `holidays/index.php`; JS `compliance-calendar.js`.
- **Rule (CONFIRMED):** manage = role admin/compliance + write; delete holiday = **admin only**; event feed menggabungkan events + holidays + `offdays` (weekend rule).
- **Dead code terkait:** `ComplianceCalendarController` + view `compliance/calendar/index.php` menunggu route `compliance/calendar/events` yang **tidak terdaftar** di Routes.php → tidak dapat diakses (CONFIRMED dari Routes.php).

## M-13 Thermal Imaging

- **Route:** `compliance/thermal-imaging` (index/create/store/locations-store/show/pdf).
- **Controller:** `ThermalImagingController`; **Tabel:** `thermal_imaging_locations/reports/report_items`.
- **Rule (CONFIRMED):** akses baca admin/compliance/staff; kelola lokasi hanya admin/compliance; report = tanggal + inspector + facility (default 'PT.Younghyun Star') + N baris (lokasi aktif, °C wajib numerik, foto max 5MB `uploads/thermal-imaging/Y/m`); PDF Dompdf A4 portrait.

## M-14 Questionnaire (Kuesioner)

- **Route internal:** `compliance/questionnaires` (CRUD, questions CRUD/reorder, responses, analytics, excel, pdf, respondent-settings). **Route publik (tanpa login, CSRF-exempt):** `GET /kuesioner/{slug}`, `GET /kuesioner/{slug}/selesai`, `POST /kuesioner/{slug}/kirim`.
- **Controller:** `ComplianceQuestionnaireController`; **Library:** `ComplianceQuestionnaireCatalog` (2 template default: supervisor-behaviour-survey, worker-behaviour-survey — diseed otomatis saat migration & saat controller construct bila belum ada).
- **Tabel:** `compliance_questionnaires`, `_questions`, `_responses`, `_response_answers`.
- **Rule (CONFIRMED):** answer_type: radio/select (min 2 opsi), text, textarea, date, email, phone, number, scale_5, scale_10; `is_required` divalidasi server-side; pertanyaan "Tanggal pengisian formulir/form" otomatis terisi timestamp; response_code `QNR-YmdHis-{qid}(-n)`; kuesioner berisi respon tidak bisa dihapus; pertanyaan berisi jawaban tidak bisa dihapus; pengumpulan identitas responden (nama/phone/email) bisa dimatikan per kuesioner; analytics = trend 7 hari + distribusi jawaban + statistik angka.

## M-15 EMS Report (Energy/GHG)

- **Route:** `ems-reports` — water-consumption, electric-consumption, stationary-combustion, mobile-combustion, ghg-summary (+ save per jenis, AJAX autosave).
- **Controller:** `EmsReportController`; JS `ems-report.js` (Alpine).
- **Tabel:** `ems_water_consumption_years/entries`, `ems_electric_consumption_years/entries`, `ems_stationary_combustion_years/entries`, `ems_mobile_combustion_years/entries`.
- **Rule (CONFIRMED):**
  - Rentang tahun 2026–2030 (EMS_START_YEAR=2026, RANGE=5; diperluas sampai tahun berjalan).
  - Intensity = total konsumsi tahunan ÷ `production_output`; Emission (ton CO2e) = total × faktor ÷ 1000.
  - Faktor emisi: listrik 0.87 kg/kWh; solar 2.69 kg/L; LPG 2.984 kg/kg; scrap 1.8 kg/kg; petrol 2.28 kg/L.
  - GHG Summary: Scope 1 = stationary + mobile; Scope 2 market-based = listrik; baris lain fixed 0/kosong; grand total = scope1+scope2.
  - Water years seed 2025–2029 (2025 berisi data aktual, production_output 4.350.778); yang lain 2026–2030.
  - Label baseline: "Baseline Year: 2026, Target: -2% s/d -5%" (hard-coded di dataset label).

## M-16 FDM Data Collection

- **Route:** `fdm-data-collection` (index, production-section, save).
- **Controller:** `FdmDataCollectionController`; **Tabel:** `fdm_production_section_years/entries`.
- **Rule (CONFIRMED):** per tahun: N retailer (default GAP Inc., Target, Walmart, Macy's) × nilai bulanan (JSON `monthly_values`) + baris workforce `full_time_employee`; baris agregat "a) Finished Product Assembler" = Σ retailer; 2 menu lain (Source Document, Validation Queue) masih placeholder `soon`.

## M-17 Boiler & Utility (4 log harian)

| Modul | Route | Tabel | Field inti | Export |
|---|---|---|---|---|
| Boiler Fuel | `boiler` (index/detail/save/delete) + `boiler/export` | `boiler_fuel_logs` | log_date, log_time, polybag, kg, note | XLSX bulanan (SUM per hari, hari libur merah) |
| IPAL | `ipal` (index/save) + `ipal/export` | `ipal_logs` | log_date, start_meter, stop_meter, volume, pemakaian, ket | XLSX bulanan |
| PDAM Water | `pdam-water` (+detail/export-excel/export-pdf/save/delete) | `pdam_water_logs` | log_date (UNIQUE), log_time, meter_reading, note | XLSX + PDF (Dompdf landscape) |
| PDAM Water Boiler | `pdam-water-boiler` (idem) | `pdam_water_boiler_logs` | idem | XLSX + PDF |

- **Rule (CONFIRMED):** role admin/compliance/office; 1 baris per tanggal (upsert by log_date; pdam pakai unique key); holiday + weekend ditandai merah di export; hari Indonesia (Minggu=Sunday, dst.).
- Boiler detail memakai `is_weekend_offday` (Minggu + Sabtu ≥ 2026-04-01).

## M-18 IT Asset & Employees

- **Route:** `it-assets` grup + `employees` grup + `employees/unassign` (auth+write).
- **Controller:** `ITAssetController`, `EmployeeController`; **Tabel:** `assets`, `asset_categories`, `asset_assignments`, `employees`.
- **Rule (CONFIRMED):**
  - Asset IT = `assets` dengan kategori `category_name='IT'`; filter type Peripheral = sub_category Mouse/Keyboard/Monitor.
  - Assign: tutup assignment aktif (`returned_at=now`) lalu insert baru; hanya karyawan `status='active'`; butuh write access.
  - Status asset `rusak` saat update → semua assignment aktif otomatis di-return.
  - Employee tidak bisa dihapus bila masih punya assignment aktif; bila punya riwayat → disarankan nonaktif (warning).
  - Dashboard IT (`DashboardController`): total IT, dipakai (returned_at null), rusak, kategori 'Compliance', pemakai komputer (sub_category 'Komputer'), status summary.

## M-19 IT Device Monitoring (Device Control + Agent API)

- **Route UI:** `/it/devices` (index/ajax/stats/detail/fragment), `/it/device/command`, `/it/device/remote`, `/it/device/push-update`.
- **Route API publik (CSRF-exempt, tanpa auth filter):** `GET|POST /api/agent/heartbeat`, `/api/agent/command`, `/api/agent/update`.
- **Controller:** `ITDeviceController`, `Api\AgentController`; **Tabel:** `it_devices` (kolom `cpu` = JSON "extra" serbaguna), `it_device_commands`, `it_device_logs`.
- **Rule (CONFIRMED):**
  - Identitas device: `device_token` → `mac_address` → `hostname` (fallback berurutan). Device baru otomatis dibuatkan `assets` dgn `inventory_no` `IT-PC-###` (category_id=1) dan status `online`.
  - Heartbeat default interval 86400 dtk (env `agent.*`); command poll min 5 dtk; throttle file-based 5 dtk untuk `/command`.
  - Remote action: restart, shutdown, update, sync, restart_agent, lock, logoff, popup_message (popup: pesan wajib, timeout 15–300 dtk, default 90).
  - Remote lock: `remote_lock_until = now + 25 dtk` (env `agent.remoteLockSeconds`); hanya 1 command queued per device.
  - Push langsung ke agent via `http://{lan_ip|last_ip|request_ip}:{agent.pushPort=8765}/command` dgn `device_token`; gagal → fallback polling.
  - Auto-update agent: track `stable|win7|xp` (dari update_channel atau deteksi OS/build), file `EAMSAgent(Setup)(-win7|-xp)-X.Y.Z.exe` di `public/downloads/agent/` (atau `download/agent/`), bandingkan `version_compare`.
  - Risk score device (0–100) & online = `device_helper` (lihat 09); command `it:status` menandai offline bila last_seen > 600 dtk (INKONSISTEN dgn helper 48 jam — lihat 15).

## M-20 Patrol Security

- **Route:** `patrol` (index, dashboard, editor, sessions/start, sessions/scan, sessions/cancel, layout/save).
- **Controller:** `PatrolController`; **Tabel:** `patrol_routes/checkpoints/route_checkpoints/sessions/logs/layouts/log_photos`.
- **View:** `patrol/index|dashboard|editor.php`; JS `patrol.js` (32KB).
- **Rule (CONFIRMED):**
  - Role: patrol harian = security/compliance/admin; dashboard = compliance/admin; editor layout = admin saja.
  - 1 sesi aktif per user per hari; rute menentukan urutan checkpoint; scan wajib barcode sesuai urutan (`nextCheckpoint`), foto wajib (≥1), GPS wajib dalam `radius_m` checkpoint (default 10 m, haversine).
  - Sesi selesai otomatis bila semua checkpoint dicek (`status=completed`, `ended_at`); bisa `canceled`.
  - Seed: rute `forward` CP1→CP4 & `reverse` CP4→CP1; checkpoint CP1 Pos Bambu, CP2 Area B3, CP3 Gudang D, CP4 Mushola (koordinat Sukabumi).
  - Layout peta: 1 layout aktif (gambar + scale 1–3 + offset ±80), posisi checkpoint map_x/map_y.

## M-21 Backup System

- **Route:** `backups` grup (**filter admin**) — index, database, files, full, upload, auto-enable/disable, download, restore-database/files/full, delete.
- **Controller:** `BackupController`; **Library:** `BackupManager`; **Command:** `backup:daily`.
- **Rule (CONFIRMED):**
  - Jenis: database (`.sql` dump via `SHOW CREATE TABLE` + escape manual), files (zip `public/uploads` + `writable/uploads`), full (zip berisi `database.sql` + manifest.json + uploads).
  - Retensi 30 hari (`RETENTION_DAYS`), dibersihkan tiap pembuatan backup.
  - Direktori: `D:\EAMS-Backups` bila Windows & drive D: ada; selain itu `writable/backups`.
  - Auto backup harian 01:00 via **Windows `schtasks`** (create/delete/query task "EAMS Daily Backup") — exec dari PHP.
  - Restore DB via `mysqli::multi_query`; restore files via extract+mirror.

## M-22 Settings & Branding

- **Route:** `settings` (index, POST change-password dengan `settings_action`: company/email/whatsapp/contact/mark_notifications_read).
- **Controller:** `SettingsController`; **Model:** `AppSettingModel` → `app_settings` (key-value, `is_secret`).
- **Rule (CONFIRMED):**
  - Section `company/email/whatsapp` hanya role admin/compliance (`ensureAdmin`); `user` untuk semua.
  - Ganti password: verifikasi password lama, konfirmasi sama, min 8 char.
  - Branding (company_name, address, logo, document_footer, signatory) dipakai EamsPdf untuk semua PDF; logo default `assets/images/company/logo.png`.
  - Email disimpan di app_settings (host/user/pass secret/port/crypto/from) dengan fallback ke `Config\Email`/.env; WA memakai webhook + token dari app_settings (berbeda dari Fonnte di .env — dua kanal WA berbeda!).

## Matriks singkat: modul → dependency modul lain

Lihat `16-laravel-migration-considerations.md` untuk urutan rebuild berdasarkan dependency ini.
