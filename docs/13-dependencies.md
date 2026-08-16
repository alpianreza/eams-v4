# 13 — Dependencies

## A. Runtime & server

| Kebutuhan | Versi/Syarat | Evidence |
|---|---|---|
| PHP | ^8.1 | composer.json, README |
| PHP extensions | intl, mbstring, json, mysqlnd, curl (wajib README); GD dipakai QrService (imagecreatefrompng); ZipArchive dipakai BackupManager | composer.json suggest + kode |
| Web server | Apache/Nginx, docroot → `public/` (`.htaccess` ada) | README, public/.htaccess |
| Database | MySQL/MariaDB, driver MySQLi, DB default `asset_compliance_system` | env.example, README |
| OS server | **Windows untuk fitur auto-backup** (`schtasks` exec) + opsi drive `D:\EAMS-Backups`; lintas OS untuk sisanya | BackupController/BackupManager |
| CI4 framework | **folder `system/` di-commit** (bukan vendor) — autoload psr-4 `CodeIgniter\ → system/` | composer.json autoload, repo root |

## B. Composer packages (require)

| Package | Versi | Pemakaian | Evidence |
|---|---|---|---|
| dompdf/dompdf | ^3.1 | PDF (fallback EamsPdf; langsung di PDAM & Thermal Imaging) | EamsPdf, PdamWater*, ThermalImagingController |
| mpdf/mpdf | ^8.2 | PDF primary EamsPdf | EamsPdf |
| setasign/fpdi | ^2.6 | di-require; ⚠️ tidak ditemukan pemakaian di kode yang dibaca | composer.json (UNKNOWN pemakaian) |
| endroid/qr-code | ^6.0 | di-require; ⚠️ QR aktual memakai api.qrserver.com + GD | composer.json vs QrService (14) |
| phpoffice/phpspreadsheet | ^5.5 | semua export XLSX | BoilerFuel/Ipal/PdamWater*/Questionnaire controllers |
| laminas/laminas-escaper | ^2.17 | dependency CI4 (esc()) | composer.json |
| psr/log | ^3.0 | logger interface | composer.json |

require-dev: codeigniter/coding-standard ^1.7, fakerphp/faker ^1.24, friendsofphp/php-cs-fixer ^3.47.1, kint-php/kint ^6.1, mikey179/vfsstream ^1.6.12, nexusphp/cs-config ^3.6, phpunit/phpunit ^10.5.16||^11.2, predis/predis ^3.0.

## C. NPM / frontend

- `package.json`: hanya devDependency **stylelint ^16.13.0** (`npm run lint:css`) + script `bump:css` (`tools/bump-css-versions.php`). **Tidak ada build JS/CSS.**
- Frontend CDN (lihat 08): Bootstrap 5.3.2, Bootstrap Icons, SweetAlert2 11, cropperjs 1.6.1, Alpine.js 3.x, jQuery 3.7.1, jquery-sparklines 2.1.2, Google Fonts (Plus Jakarta Sans).
- Admin template: **AdminLTE 4** lokal (`public/adminlte4/`, versi tidak tertera di file yang dibaca — UNKNOWN versi pasti).
- CSS internal: `public/assets/css/*` token-based (`tokens.css` wajib sumber warna; aturan di `docs/css.md`); laporan PDF: `public/assets/reports/`.

## D. Third-party di repo

- `app/ThirdParty/phpqrcode/` — library QR PHP lama; **tidak dipakai** jalur aktif (QrService memakai API eksternal) → legacy (14).

## E. Integrasi eksternal & credential (env/app_settings)

| Integrasi | Konfigurasi | Kode |
|---|---|---|
| Fonnte (WA) | `.env`: whatsapp.enabled/provider/fonnteEndpoint/fonnteToken/timeout/namePhoneMap | `Config\WhatsApp`, `WhatsAppService`, command reminder, `ProgressController::sendReminderAjax` |
| Google Workspace SMTP | `.env` email.* ATAU `app_settings` (email_smtp_*, email_from_*, template) — App Password 16 char (README) | `Config\Email`, `NotificationService`, `SettingsController` |
| WA webhook kustom (notifikasi in-app) | `app_settings`: notification_whatsapp_enabled/webhook/token(secret) + whatsapp_message_template | `NotificationService::sendWhatsApp` |
| api.qrserver.com | hard-coded URL di QrService/controller | QR generate |
| EAMS Agent (Windows) | `.env` agent.*: defaultHeartbeatInterval=86400, remoteHeartbeatInterval=86400, commandPollInterval=5, commandPollThrottleSeconds=5, pushPort=8765, pushTimeout=2, (+remoteBoostSeconds=180, remoteLockSeconds=25, latestVersion — dibaca dgn default di kode) | `Api\AgentController`, `ITDeviceController` |
| File agent installer | `public/downloads/agent/` atau `public/download/agent/` — pola nama `EAMSAgent(Setup)(-win7|-xp)-X.Y.Z.exe` | `Api\AgentController::resolveLatestAgentRelease` |

## F. Environment variables (env.example)

`CI_ENVIRONMENT, app.baseURL, app.publicURL, app.forceGlobalSecureRequests, app.indexPage, app.uriProtocol, app.debugToolbar, app.proxyIPs, database.default.*, encryption.key, whatsapp.*, agent.*`.

## G. Cron / scheduler yang didokumentasikan (README)

```cron
0 8 * * 1  php spark notify:weekly-checklist          # WA reminder (Senin 08:00)
5 8 * * 1  php spark notify:weekly-checklist-email    # email reminder (Senin 08:05)
```
+ `backup:daily` via Windows Task Scheduler 01:00 (dibuat dari UI Backups).
+ `it:status` (device online/offline) — perlu dijadwalkan manual (tidak terdokumentasi di README → UNKNOWN apakah aktif di cron produksi).

## H. Catatan untuk Laravel

- Ganti QR eksternal → `endroid/qr-code` (sudah di-require) atau paket Laravel QR.
- mPDF+Dompdf ganda → pilih satu (Dompdf paling terbukti di server menurut komentar; mPDF dipakai EamsPdf bila ada).
- PhpSpreadsheet → tetap (atau `maatwebsite/excel`).
- Agent protocol (heartbeat/command/update + device_token) harus dipertahankan apa adanya agar agent Windows existing tidak perlu diubah.
- Windows schtasks → ganti dengan Laravel Scheduler/queue + cron lintas OS.
