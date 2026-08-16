# 01 — System Architecture

## Arsitektur aplikasi

EAMS adalah aplikasi web **CodeIgniter 4 monolit** (server-rendered PHP views + AJAX), dengan pola:

```
Browser
  ↓ HTTP (Apache/Nginx, document root = public/)
public/index.php → CI4 bootstrap (system/ di-commit di repo)
  ↓
Config\Filters (global: csrf kecuali api/agent+kuesioner, write, secureheaders, csrfasset)
  ↓
Config\Routes → Controller → Model/Query Builder → MySQL
  ↓                    ↘ Libraries/Services (PDF, QR, WA, Email, Backup)
View (PHP template, layout AdminLTE 4) + JS (Vanilla/Alpine/jQuery, fetch/AJAX)
```

CLI: `spark` commands (backup, reminder WA/email, device status). Cron/Windows Task Scheduler mengeksekusi command tersebut.

## Peta struktur repository

```
eams/
├── app/
│   ├── Commands/          # 4 CLI command (backup, it:status, 2 reminder)
│   ├── Common.php         # kosong (bawaan CI4)
│   ├── Config/            # Routes, Filters, App, Database, Session, Email, WhatsApp,
│   │                      #   PdfPermission, CURLRequest, Autoload, Events, dst.
│   ├── Controllers/       # 36 controller + Api/AgentController
│   ├── Database/
│   │   ├── Migrations/    # 32 migration (bukan skema lengkap — tabel dasar tidak ada)
│   │   └── Seeds/         # 4 seeder checklist/item type
│   ├── Filters/           # Auth, Admin, Write (global), PdfAccess, CsrfAsset
│   ├── Helpers/           # 14 helper (access, audit, checklist, period, device, ...)
│   ├── Language/          # bawaan
│   ├── Libraries/         # BackupManager, EamsPdf, NotificationService, QuestionnaireCatalog
│   ├── Models/            # 40 model (tipis, tanpa relasi Eloquent-style)
│   ├── Services/          # QrService, WhatsAppService
│   ├── ThirdParty/        # phpqrcode (legacy, tidak dipakai QrService)
│   └── Views/             # 162 file: layouts, per-modul, pdf/, errors/
├── docs/css.md            # panduan CSS internal (satu-satunya dok existing)
├── public/
│   ├── adminlte4/         # AdminLTE 4 (template admin) — di-commit
│   ├── assets/            # css (token-based), images, reports
│   ├── js/                # 30 file JS per-fitur
│   ├── uploads/           # checklist/, employees/, inventory/, patrol/, qr/,
│   │                      #   thermal-imaging/, users/ (writable, user-generated)
│   └── index.php
├── system/                # CodeIgniter 4 framework — DI-COMMIT ke repo (tidak lazim)
├── tests/                 # hanya kerangka CI4 (README, _support, unit/, dst.) — tanpa test nyata
├── tools/                 # bump-css-versions.php, migrate-css-tokens.php
├── writable/              # cache/logs/session/uploads (runtime)
├── composer.json/.lock    # dependency PHP
├── package.json           # hanya stylelint (dev)
├── env.example            # template .env
├── preload.php, spark, phpunit.xml.dist, README.md, CHANGELOG.md, LICENSE
└── .github/PULL_REQUEST_TEMPLATE.md
```

## System map (berdasarkan bukti implementasi)

```
EAMS
│
├── Identity & Access
│   ├── Login (username/email + password, bcrypt, throttle 5/menit)
│   ├── Session file-based 8 jam + login_sessions (audit sesi)
│   ├── Role: admin, compliance, security, staff, auditor, office (+custom)
│   ├── Permission: read / write (WriteFilter global)
│   └── Page access per user (users.page_access JSON → 28 menu key)
│
├── Home & Notification
│   ├── Home = daftar tugas checklist milik user (periode bulan berjalan)
│   └── Notification Center (in-app + email + WhatsApp, dedupe_key)
│
├── Compliance (inti bisnis)
│   ├── Master data: inventory_categories → asset_item_types → areas
│   ├── Compliance Inventory (asset_code auto: KATEGORI-ITEM-###, QR eksternal)
│   ├── Checklist Master (pertanyaan per item type, require_photo, active)
│   ├── Checklist Execution
│   │   ├── Form per-item (daily/weekly/monthly, period_key)
│   │   ├── Grid khusus: CCTV(daily), Emergency Light(monthly), Exit Light(monthly),
│   │   │   First Aid Box(monthly), First Aid Content(daily), APAR(monthly),
│   │   │   Intrusion Alarm(weekly), Hydrant(weekly), Smoke Detector(monthly),
│   │   │   Heat Detector(monthly), Gate(daily), Generic (semua tipe)
│   │   └── Toilet checklist: item_type_id=52, 3 slot/hari (PG/SI/SO)
│   ├── Dashboard, Progress monitoring, Ranking (skor ontime*10+late*3)
│   ├── Evidence Center (not_ok + foto + follow-up open/monitoring/closed)
│   ├── Report + Print Center (PDF mPDF→Dompdf, Excel PhpSpreadsheet)
│   ├── Calendar events + Holidays
│   ├── Thermal Imaging (report + foto + PDF)
│   ├── Questionnaire (publik tanpa login via /kuesioner/{slug})
│   ├── EMS Report (air, listrik, solar/LPG/scrap, petrol → GHG CO2e)
│   └── FDM Data Collection (produksi per retailer + FTE, bulanan)
│
├── Boiler & Utility
│   ├── Boiler Fuel (polybag+kg per hari, multi-entry)
│   ├── IPAL (start/stop meter harian)
│   ├── PDAM Water & PDAM Water Boiler (1 meter reading/hari, unik per tanggal)
│
├── IT Asset & Device
│   ├── Assets + Employees (assignment, unassign, status rusak → auto-return)
│   ├── Dashboard IT (agregat asset)
│   └── Device Control: EAMS Agent (Windows) ⇄ /api/agent/* (heartbeat,
│       command queue via kolom it_devices.cpu JSON, remote lock, push update)
│
├── Security Patrol
│   └── Rute + checkpoint (barcode + GPS radius + foto wajib), sesi harian
│
└── Administration
    ├── Users & Roles & page access
    ├── Audit Logs + Login Sessions
    ├── Backups (DB dump / files / full zip, retensi 30 hari, schtasks Windows)
    └── Settings (perusahaan, SMTP Google Workspace, WhatsApp, template)
```

## Alur request khas (checklist)

```
User (PIC) buka /home
  → HomeController: inventory di-assign ke user (compliance_inventory_pics / fallback nama)
  → hitung periode bulan ini (skip hari libur & weekend) → pending/late
User klik item → /compliance/checklist/{id}
  → ComplianceInventoryController::checklist → tentukan frequency dari asset_item_types
  → redirect ke grid khusus bila item_type_id cocok (mis. CCTV→cctvGrid)
  → kalender periode (generate_calendar_periods) + status (resolve_period_status)
  → form (_form.php) dengan lockReason (done/future/expired/offday/slot)
Submit → POST /compliance/checklist/submit
  → submitChecklist: validasi role, anti-duplikat, not_ok wajib catatan/foto, blokir hari libur
  → insert checklist_logs (per pertanyaan)
Grid AJAX → POST …-grid/save (mode ok/not_ok/na/clear) → upsert checklist_logs per sel
```

## Integrasi eksternal

| Integrasi | Pemakaian | Evidence |
|---|---|---|
| api.qrserver.com | generate QR inventory (PNG 300px + overlay asset code via GD) | `QrService`, `ComplianceInventoryController::store/update` |
| Fonnte API | reminder checklist WhatsApp mingguan | `WhatsAppService`, `notify:weekly-checklist` |
| Google Workspace SMTP | email notifikasi + reminder | `NotificationService`, `SettingsController` (app_settings) |
| Webhook WA kustom (app_settings) | notifikasi in-app→WA per user | `NotificationService::sendWhatsApp` |
| EAMS Agent (Windows service) | heartbeat/command/update perangkat IT | `Api\AgentController` (public, token device) |

## Lingkungan & deployment

- `app.baseURL` default `https://eams.ptyhs.com/`; `forceGlobalSecureRequests=true`; proxy `'*' => 'X-Forwarded-For'`.
- Session: file handler, cookie `eams_session`, exp 28800 dtk, regenerate tiap 3600 dtk.
- Backup otomatis via **Windows Task Scheduler** (`schtasks`) — server produksi Windows (folder `D:\EAMS-Backups` bila drive D: ada).
- Uploads di `public/uploads/**` (langsung bisa diakses publik).
