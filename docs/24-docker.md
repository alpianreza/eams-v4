# 24 — Menjalankan EAMS dengan Docker

Stack: **Node 22 build stage + Vite + PHP 8.2-FPM + Nginx + MySQL 8.0 + queue + scheduler**.

## Quick start

```bash
docker compose up --build -d
```

Buka **http://localhost:8080**. Build Docker otomatis menjalankan Vite dan menyalin `public/build` ke image aplikasi.

## Layanan

| Service | Peran | Port host |
|---|---|---|
| `app` | PHP 8.2-FPM | internal 9000 |
| `nginx` | web server | 8080 |
| `db` | MySQL 8.0 | internal 3306 |
| `queue` | queue worker | — |
| `scheduler` | Laravel scheduler | — |

Port database tidak dipublikasikan sehingga tidak bentrok dengan MySQL XAMPP pada port 3306.

## Konfigurasi

```env
DB_DATABASE=eams
DB_USERNAME=eams
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root
APP_URL=http://localhost:8080
```

Jika database legacy berjalan di XAMPP host Windows, gunakan:

```env
LEGACY_DB_HOST=host.docker.internal
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=asset_compliance_system
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=
```

## Import legacy

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan eams:import --dry-run
docker compose exec app php artisan eams:import
```

Dry-run dan import yang memiliki error di-rollback penuh. Import checklist tidak lagi menghapus seluruh tabel target.

## Perintah berguna

```bash
docker compose ps
docker compose logs app
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test
docker compose down
docker compose down -v   # menghapus volume database; gunakan dengan sangat hati-hati
```

Setelah perubahan kode atau asset frontend:

```bash
docker compose up --build -d
```
