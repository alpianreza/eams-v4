# 23 — Deployment Runbook (EAMS Laravel)

> Target: Windows on-prem (`eams.ptyhs.com`), PHP 8.5, MariaDB/MySQL. **DB baru yang clean** (Q-002) — bukan menunjuk DB CI4. Legacy CI4 **tetap berjalan** sampai cutover divalidasi (rollback = kembali ke CI4).

## 1. Prasyarat
- PHP **8.5** + Composer.
- MariaDB/MySQL (production 10.4.32) — buat **database BARU** (mis. `eams_laravel`), charset `utf8mb4`.
- Web server (IIS/Apache/Nginx) mengarah ke `public/`.
- Folder untuk **file storage** (bisa network share, mis. `\\SERVER-FILE\EAMS` atau `D:\EAMS\files`).

## 2. Konfigurasi `.env`
Salin `.env.example` → `.env`, isi:
```
APP_NAME="PT YHS EAMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://eams.ptyhs.com
APP_KEY=            # php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=eams_laravel
DB_USERNAME=...
DB_PASSWORD=...

# Koneksi READ-ONLY ke DB CI4 (untuk import) — jangan dipakai menulis.
DB_LEGACY_HOST=...
DB_LEGACY_DATABASE=eams_ci4
DB_LEGACY_USERNAME=...
DB_LEGACY_PASSWORD=...

QUEUE_CONNECTION=database
SESSION_LIFETIME=480          # 8 jam (menit)

# Branding & business config (config/eams.php):
EAMS_COMPANY_NAME="PT YHS EAMS"
EAMS_SATURDAY_HOLIDAY_EFFECTIVE=2026-04-01   # Q-005 (TIDAK retroaktif)
EAMS_DEVICE_ONLINE_THRESHOLD_SECONDS=600     # Q-012
EAMS_AGENT_HEARTBEAT_INTERVAL=300
EAMS_AGENT_COMMAND_POLL_INTERVAL=30
EAMS_FILES_BASE_PATH=          # root file storage (Q-022)
EAMS_UPLOAD_MAX_KB=5120
EAMS_BACKUP_RETENTION_DAYS=30  # BR-39
EAMS_BACKUP_PATH=backups
EAMS_BACKUP_DISK=local

# Notifikasi (SECRET — jangan commit):
MAIL_MAILER=smtp ...           # SMTP Google Workspace (BR-24)
FONNTE_TOKEN=...               # WA gateway (BR-24)
```

## 3. Storage
- `php artisan storage:link` (bila pakai disk `public`).
- Pastikan root tiap disk kategori (inventory/checklist/qr/attachments) menunjuk `EAMS_FILES_BASE_PATH` (config/filesystems.php membaca `config/eams.php`).

## 4. Migrasi schema + import data legacy
```
php artisan migrate --force                 # schema baru
php artisan eams:import --dry-run           # UJI dulu: laporkan mapping + issue, TANPA menulis
php artisan eams:import                     # import riil (idempotent — bisa diulang)
```
- Tinjau **migration issues log** (asset_code duplikat, checked_by tak cocok, PIC > 2) — jangan diam-diam diubah.
- Pindahkan file legacy (foto inventory/checklist/evidence/QR) ke storage baru.
- **Validasi:** rekonsiliasi count/sum per tabel vs produksi; sampling histori checklist; pastikan QR lama tetap resolve (`compliance/inventory/detail/{id}`).

## 5. Scheduler (menggantikan schtasks Windows) — §15
Satu entri **Windows Task Scheduler** tiap menit:
```
Program: C:\path\to\php.exe
Args:    C:\path\to\eams-v4\artisan schedule:run
Trigger: setiap 1 menit
```
(Alternatif service: `php artisan schedule:work`.) Jadwal yang berjalan:
- `eams:backup full --prune` — **harian 01:00** (BR-39).
- `eams:remind-checklists` — **Senin 08:00** (BR-23, hormati hari libur).
- `eams:device-status-check` — **tiap menit** (Q-012).

## 6. Queue worker
```
php artisan queue:work database --sleep=3 --tries=3
```
Jalankan sebagai Windows service / Task Scheduler (auto-restart). Pantau `failed_jobs`.

## 7. DATA YANG HARUS DIKUMPULKAN SAAT CUTOVER (bukan coding)
- **Q-015** — inventarisasi **scheduler/cron aktual** di server produksi (siapa cek & kapan) → pastikan semua dipetakan ke Laravel Scheduler.
- **Q-016** — verifikasi **`asset_categories.id=1`** = kategori IT (cek tabel produksi) → memastikan perilaku kategori IT benar.
- **Q-018** — ekspor **`app_settings`** produksi (token SMTP/Fonnte, template pesan, nama perusahaan) → isi ke `.env`/settings; **secret jangan masuk repo**.

## 8. Cutover checklist
1. Schema baru + import divalidasi (langkah 4) di environment staging/test.
2. Scheduler + queue worker berjalan (langkah 5–6).
3. Smoke test: login (username/email), checklist fill, QR scan, PDF, agent heartbeat.
4. Switch DNS/webroot ke Laravel. Legacy CI4 tetap ada (rollback instan).
5. Pantau audit logs + login sessions + backup berjalan.

## 9. Rollback
- Import idempotent → bisa bersihkan DB target & ulangi tanpa menyentuh CI4.
- Cutover gagal → arahkan kembali ke CI4 (data sumber tak diubah import).
- Snapshot/backup DB target sebelum cutover.
