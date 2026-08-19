# 24 — Menjalankan EAMS dengan Docker

Setup Docker lengkap: **PHP 8.5-FPM + Nginx + MySQL 8.0 + Queue worker + Scheduler**. Satu perintah untuk jalan.

## Quick start
```bash
docker compose up --build -d
```
Lalu buka **http://localhost:8080**. Container `app` otomatis: salin `.env` dari `.env.example`, generate `APP_KEY`, menunggu DB, lalu `migrate --force`. Container `queue` & `scheduler` menunggu app selesai init.

> **Catatan DB:** Docker memakai **MySQL 8.0** untuk kemudahan lokal. Produksi on-prem boleh tetap MariaDB/MySQL — aplikasi (Eloquent/Query Builder) DB-agnostic; driver `mysql` dipakai keduanya. DB **legacy CI4** untuk import tetap dibaca via koneksi `DB_LEGACY_*` terpisah.

## Layanan
| Service | Image | Peran | Port |
|---|---|---|---|
| `app` | build (PHP 8.5-FPM) | menjalankan aplikasi (php-fpm) | internal :9000 |
| `nginx` | nginx:alpine | web server → `public/` | **8080** |
| `db` | mysql:8.0 | database | internal :3306 |
| `queue` | eams-app | `queue:work database` (email/WA/PDF) | — |
| `scheduler` | eams-app | `schedule:work` (backup harian, reminder Senin, device check) | — |

Volume: `appcode` (kode + storage, persisten) dan `dbdata` (data MySQL). File upload tersimpan di `appcode` → `storage/`.

## Konfigurasi
Edit variabel lewat environment di `docker-compose.yml` atau file **`.env`** di root (otomatis terbaca compose untuk `${...}`):
```
DB_DATABASE=eams
DB_USERNAME=eams
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root
APP_URL=http://localhost:8080
```
Config bisnis (threshold, path, Sabtu efektif, retensi backup, branding) = `EAMS_*` (lihat `config/eams.php` & `docs/23-deployment.md`). **Secret (SMTP/Fonnte)** jangan di-commit — isi di `.env` container `app`.

Setelah ubah env, refresh config cache:
```bash
docker compose exec app php artisan config:cache
```

## Import data legacy (opsional, saat cutover)
Isi koneksi `DB_LEGACY_*` (read-only ke DB CI4) di `.env` container `app`, lalu:
```bash
docker compose exec app php artisan eams:import --dry-run   # uji dulu
docker compose exec app php artisan eams:import             # import riil (idempotent)
```

## Perintah berguna
```bash
docker compose logs -f app            # log app
docker compose exec app php artisan migrate:status
docker compose exec app php artisan eams:about
docker compose down                   # stop (data tetap di volume)
docker compose down -v                # stop + HAPUS data (hati-hati)
```

## Catatan produksi
- `php/opcache` aktif (`validate_timestamps=0`). Setelah deploy kode baru: `docker compose up --build -d`.
- Untuk HTTPS, taruh reverse proxy (nginx/Traefik/IIS) di depan `nginx:8080`.
- Backup berjalan otomatis via `scheduler` (BR-39); file backup di `storage` (disk `EAMS_BACKUP_DISK`).
- Belum punya PHP 8.5 image di registry-mu? `Dockerfile` pakai `php:8.5-fpm` resmi — pastikan Docker bisa pull dari Docker Hub.
