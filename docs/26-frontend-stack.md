# 26 — Frontend Stack

Frontend EAMS menggunakan:

- Laravel Blade untuk server-side rendering;
- Bootstrap 5.3.3 untuk layout dan komponen;
- Alpine.js 3.14 untuk state/interaksi ringan;
- Vite 6 melalui Laravel Vite Plugin;
- Bootstrap Icons yang dibundel lokal.

Bootstrap dan Alpine tidak lagi dimuat melalui CDN pada layout utama dan login.

## Development di VS Code

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

Buka dua terminal:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Aplikasi tersedia di `http://127.0.0.1:8000`.

## Production build

```powershell
npm install --no-audit --no-fund
npm run build
php artisan config:cache
php artisan view:cache
```

Hasil build berada di `public/build` dan tidak perlu menjalankan Vite dev server di produksi.

## Struktur

```text
resources/
├── css/app.css
├── js/app.js
└── views/**/*.blade.php
```

Design-token dan beberapa stylesheet modul lama masih berada di `public/assets/css` dan dimuat secara lokal. Pemindahannya ke pipeline Vite dapat dilakukan bertahap tanpa menulis ulang Blade.

## Aturan penggunaan

- Gunakan Bootstrap untuk modal, dropdown, form, grid, dan tabel.
- Gunakan Alpine untuk state aplikasi ringan seperti theme switcher dan sidebar mobile.
- Jangan membuat komponen Bootstrap dan Alpine mengontrol state DOM yang sama.
- Jangan menambahkan CDN baru; dependency frontend dikelola melalui `package.json`.
