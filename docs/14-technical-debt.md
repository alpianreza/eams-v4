# 14 — Technical Debt & Legacy

> **Hanya didokumentasikan — TIDAK diperbaiki pada fase ini.**
> Pembedaan: **Business Requirement** (perilaku yang memang diinginkan) vs **Legacy Implementation** (cara lama mencapainya) vs **Technical Debt** (risiko/teknis yang perlu dirapikan saat rebuild).

## A. Duplikasi & dead code (CONFIRMED dari kode/route)

| # | Temuan | Bukti | Klasifikasi |
|---|---|---|---|
| TD-01 | **Dua famili tabel checklist paralel**: `checklist_*` (aktif) vs `compliance_checklist_*` (2026-01-19) + model `ComplianceChecklist*` | migrations 2026-01-19/20; routes memakai `checklist_master`+`checklist_logs` | Legacy implementation |
| TD-02 | `ComplianceChecklistController` tidak ter-route; logika periode usang (Senin→weekly, tgl 1→monthly) | Routes.php tidak memanggilnya | Dead code |
| TD-03 | `ComplianceCalendarController` + view `compliance/calendar/index.php` menunggu route `compliance/calendar/events` yang tidak terdaftar | Routes.php | Dead code (fungsionalitas hidup di HolidayController) |
| TD-04 | `AssetItemTypeController` tidak ter-route (fungsinya ada di `ComplianceInventoryController::getItemTypesByCategory`) | Routes.php | Dead code |
| TD-05 | `Home.php` + `welcome_message.php` + `Views/dashboard.php` (0 byte) bawaan CI4 | file listing | Dead code |
| TD-06 | `ComplianceChecklistMasterController::exportPeriodePage` (view `checklist/export_periode`) dan `ComplianceInventoryController::qrBatch` tidak ter-route | Routes.php | Dead code |
| TD-07 | Helper duplikat: `generate_calendar_periods` di `checklist_helper` DAN `calender_period_helper` (edge weekly beda); `resolve_period_status` di `period_helper` DAN `period_status_helper` (semantik status beda) | kedua file; `Config\Autoload::$helpers` | Technical debt (perilaku tergantung urutan load!) |
| TD-08 | Aturan libur ganda: `checklist_helper::is_weekend_offday` (Min; Sab ≥ 2026-04-01) vs `holiday_helper::is_holiday` (Sab+Min selalu) | kedua file | Technical debt / inkonsistensi |
| TD-09 | Skor kesehatan device ganda: `device_helper::device_risk_score` (aktif) vs `it_health_helper::it_health_score` (legacy, bobot lain) | kedua file | Legacy implementation |
| TD-10 | `checklist_schedules` & `compliance_checklist_schedules` tidak dibaca jalur aktif (frekuensi dari `asset_item_types.checklist_frequency`) | controller membaca kolom item type | Legacy implementation |
| TD-11 | `ComplianceReportController` menggunakan `checklist_master` utk pertanyaan tapi `checklist_templates` untuk join lain tidak konsisten (`detailLogs` join `checklist_master`) | ComplianceInventoryController::detail | Inkonsistensi |

## B. God controller & coupling

| # | Temuan | Bukti | Klasifikasi |
|---|---|---|---|
| TD-12 | `ComplianceInventoryController` **173 KB / 5.401 baris, 60+ method** — inventory CRUD + checklist form + 12 grid + QR center jadi satu | file | Technical debt (pecah per concern di Laravel) |
| TD-13 | Konstanta item_type_id hard-coded: CCTV=13, EL=4, EEL=59, P3K=10, FAC=33, APAR=1, IA=8, HYD=2, HD=6, SD=7, GATE=40, TOILET=52 | konstanta controller + cek tersebar di report/print | Tight coupling ke data DB |
| TD-14 | Pemetaan kolom print/grid dari **teks pertanyaan** (substring `berfun`, `pecah`, `kabel`; urutan preferensial APAR/alarm) | `CompliancePrintController`, `ComplianceInventoryController` | Tight coupling ke konten master |
| TD-15 | PIC dicocokkan lewat **nama** (REGEXP nama depan di ProgressController & command WA; `LIKE` nama di model fallback) selain relasi `compliance_inventory_pics` | ProgressController, WeeklyChecklistWhatsappReminder, ComplianceInventoryModel | Technical debt (duplikasi mekanisme + rapuh nama sama) |
| TD-16 | `it_devices.cpu` = kolom JSON serbaguna (hardware, health, command queue, lock, interval, session, diagnostics) — state machine agent di satu kolom | ITDeviceModel, Api\AgentController | Technical debt |
| TD-17 | `EamsPdf::applyCompanySettings` & `replaceLegacyBranding` diminify satu baris (sulit dirawat) | EamsPdf.php | Technical debt (gaya) |

## C. Query & struktur data

| # | Temuan | Bukti | Klasifikasi |
|---|---|---|---|
| TD-18 | `ComplianceInventoryModel::getBaseQuery` join item type via `compliance_inventory.item_name` (bukan `item_type_id`) | model | Kemungkinan bug legacy (INFERRED; `getDetail` tampak tak terpakai) |
| TD-19 | `checklist_logs.checked_by` string nama; `compliance_checklist_logs.checked_by` INT; `patrol_logs.checked_by` INT | migrations + model | Inkonsistensi (keputusan diperlukan, 15) |
| TD-20 | Tabel dasar tanpa migration (users, employees, compliance_inventory, assets, it_devices, holidays, boiler_fuel_logs, ipal_logs, checklist_master, ...) | tidak ada DDL di repo | Technical debt dokumentasi (15) |
| TD-21 | Kolom tanpa migration: `asset_item_types.checklist_frequency`, `asset_item_types.allow_na`, `checklist_logs.time_slot`, `checklist_logs.follow_up_*` | model/view memakai, migration tidak ada | Schema drift (15) |
| TD-22 | `checklist_logs.status` ENUM migration `ok|ng|na` vs kode menulis `not_ok` | migration 2026-01-20-000003 vs submitChecklist | Schema drift (15) |
| TD-23 | N+1/loop query di beberapa tempat (mis. HomeController per-inventory per-periode cek log; ComplianceReportController per item) | HomeController::index loop `$this->logModel->where(...)` per periode | Technical debt performa |
| TD-24 | Gaya kode campur (indent 2 vs 4 spasi); file migration `2026-01-19-000005_CreateComplianceChecklistLogs.php.php` (ekstensi ganda) | nama file | Kosmetik |

## D. Dependency & infra

| # | Temuan | Bukti | Klasifikasi |
|---|---|---|---|
| TD-25 | `system/` (framework CI4) di-commit ke repo | composer.json autoload | Technical debt (upgrade sulit, repo berat) |
| TD-26 | QR via API eksternal `api.qrserver.com` padahal `endroid/qr-code` + `phpqrcode` tersedia | QrService, composer, ThirdParty | Technical debt + risiko availability |
| TD-27 | `setasign/fpdi` di-require tanpa pemakaian terlihat | composer.json | Dependency tidak terpakai (UNKNOWN pemakaian) |
| TD-28 | Auto-backup bergantung `exec('schtasks …')` (Windows only) + path `D:\` | BackupController/Manager | Technical debt portabilitas |
| TD-29 | `app.proxyIPs = ['*' => 'X-Forwarded-For']` | Config\App | Security hardening |
| TD-30 | Uploads publik langsung di `public/uploads/` (tanpa proteksi) | struktur public | by design CI4; catat untuk Laravel (storage + symlink) |
| TD-31 | `evidence.js` dimuat global di layout walau hanya utk 1 halaman | layouts/main.php | Kosmetik/perf |
| TD-32 | `Config\App::$indexPage = 'index.php'` di kode vs `app.indexPage=''` di env.example → URL bisa beda antar environment | App.php vs env.example | Inkonsistensi konfigurasi |

## E. JS/CSS debt

| # | Temuan | Klasifikasi |
|---|---|---|
| TD-33 | ±11 file grid JS hampir identik (save/mark-all) | Duplikasi JS |
| TD-34 | Pola AJAX fetch+DOMParser+innerHTML memakai HTML mentah dari server (view umumnya escape dgn `esc()`) | Technical debt |
| TD-35 | Tidak ada bundler/minify; cache busting manual filemtime | Technical debt (bisa diterima) |

## F. Yang BUKAN debt (business requirement yang terbukti)

- Sesi 8 jam + audit login lengkap → requirement keamanan.
- Backfill monthly tanpa batas → keputusan sadar (komentar kode).
- Sabtu libur mulai 2026-04-01 → keputusan bisnis bertanggal.
- Read-only guard dua lapis (server filter + UI) → requirement.
- Notifikasi dedupe + multi-kanal (in-app/email/WA) → requirement.
