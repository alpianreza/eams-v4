# 23 — Deployment Runbook (EAMS Laravel)

> Target: Windows/on-prem atau Docker, Laravel 12, PHP 8.2, MySQL. Gunakan database Laravel baru; database CI4 hanya dibaca melalui koneksi `legacy` sampai cutover selesai.

## 1. Prasyarat

- PHP **8.2** dan Composer.
- Node.js **22** dan npm untuk build frontend.
- MySQL 8.0; buat database baru, misalnya `eams_laravel`, charset `utf8mb4`.
- Web server diarahkan ke folder `public/`.
- Snapshot/backup database sebelum cutover.

## 2. Build aplikasi

```bash
composer install --no-dev --optimize-autoloader
npm install --no-audit --no-fund
npm run build
cp .env.example .env
php artisan key:generate
```

Vite menghasilkan asset production di `public/build`. Jangan menjalankan `npm run dev` di server produksi.

## 3. Konfigurasi `.env`

```env
APP_NAME="PT YHS EAMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://eams.ptyhs.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eams_laravel
DB_USERNAME=...
DB_PASSWORD=...

# Koneksi READ-ONLY ke database CI4.
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=asset_compliance_system
LEGACY_DB_USERNAME=...
LEGACY_DB_PASSWORD=...

QUEUE_CONNECTION=database
SESSION_LIFETIME=480
EAMS_COMPANY_NAME="PT YHS EAMS"
EAMS_BACKUP_RETENTION_DAYS=30
```

Nama variabel yang benar adalah **`LEGACY_DB_*`**, bukan `DB_LEGACY_*`.

## 4. Schema dan import legacy

```bash
php artisan migrate --force
php artisan eams:import --dry-run
php artisan eams:import
```

Perilaku importer:

- source CI4 tidak pernah ditulis;
- dry-run menjalankan jalur import lengkap lalu rollback;
- seluruh target import berada dalam satu transaction;
- jika ada error validasi, seluruh perubahan target di-rollback;
- checklist legacy memakai `legacy_id`, sehingga rerun memperbarui baris yang sama tanpa menghapus checklist baru atau audit history;
- tetap lakukan rekonsiliasi jumlah dan sampling data sebelum cutover.

Importer saat ini berfokus pada data inti inventory/checklist. Tabel modul legacy lain wajib dinyatakan dan dipetakan sebelum diklaim ikut bermigrasi.

## 5. Cache, storage, scheduler, dan queue

```bash
php artisan storage:link
php artisan config:cache
php artisan view:cache
php artisan queue:work database --sleep=3 --tries=3
php artisan schedule:work
```

Jadwal aktif:

- backup dan prune harian pukul 01:00;
- reminder checklist Senin pukul 08:00;
- pemeriksaan status device setiap menit.

## 6. Cutover

1. Backup database CI4 dan Laravel.
2. Jalankan migration dan dry-run.
3. Pastikan dry-run exit 0 dan tidak ada mapping error.
4. Jalankan import riil.
5. Rekonsiliasi jumlah data inti dan sampling histori checklist.
6. Uji login, inventory, checklist, QR, PDF, Settings, Users, queue, dan scheduler.
7. Arahkan webroot/DNS ke Laravel.
8. Pertahankan CI4 sebagai rollback sampai hasil produksi disetujui.

## 7. Rollback

- Kembalikan webroot/DNS ke CI4.
- Restore snapshot database Laravel jika dibutuhkan.
- Jangan menghapus database CI4 selama masa validasi.
