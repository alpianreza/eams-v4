# 25 — Fase 1 Modules & Legacy Data Import (2026-08-27)

> Kelanjutan `docs/21-reconciliation.md` dan `docs/22-phase2-decision-log.md`. Fase 1 menutup gap modul yang hilang di rebuild Laravel (`eams-v4`) dibanding CI4 (`eams`), di atas fondasi design system Fase 0. Semua terverifikasi: full test **163 passed** + import data legacy **107.210 log checklist** sukses tanpa error.

---

## Ringkasan

| Fase | Isi |
|---|---|
| Fase 0 | Design system: `public/assets/css/{tokens,app,page-header}.css`, komponen `<x-page-header>`, dark mode, Plus Jakarta Sans. |
| Fase 1 | Modul baru: **Users**, **Checklist Master**, **Settings**, **Print Center**. |
| Data | Import legacy CI4 → Laravel via `php artisan eams:import` (koneksi read-only `legacy`). |

---

## 1. Users Management

- **Gate:** `manage-users` (admin only) — `AppServiceProvider`.
- **Route:** `/users` → index, create, store, roles.store, edit, update, activate, deactivate.
- **Controller:** `app/Http/Controllers/Admin/UserController.php`.
- **Views:** `resources/views/users/{index,create,edit,_access-form}.blade.php`.
- **Config:** `config/eams.php` → `roles` (6 role kanonikal: admin, compliance, security, staff, auditor, office) + `role_default_pages` (halaman default per role).
- **Fitur:** katalog role (bawaan + custom `user_roles`), editor `page_access` per grup menu (grup Admin dikecualikan karena digate admin), normalisasi WA (0/8 → 62), upload foto ke `public/uploads/users`, guard anti self-deactivate.
- **Menu:** grup Admin → Users (page key `users_management`).

## 2. Checklist Master

- **Gate tulis:** `manage-master-data`.
- **Route:** `compliance/checklist-master` → index, category, item, question.store/update/destroy, frequency.
- **Controller:** `app/Http/Controllers/Compliance/ChecklistMasterController.php`.
- **Views:** `resources/views/checklist-master/*`.
- **Alur 3-level:** kategori → item type → pertanyaan (modal add/edit + frekuensi daily/weekly/monthly).
- **Catatan kolom:** v4 memakai `asset_item_type_id` (BUKAN `item_type_id` seperti CI4).

## 3. Settings

- **Gate:** `manage-settings` (admin atau compliance).
- **Tabel baru:** `app_settings` (migration `2026_08_27_000000`). Model `app/Models/Setting.php` (`value/put/allAsMap`, nilai rahasia dienkripsi via `Crypt`).
- **Route:** `settings` → index, company, email, whatsapp, contact.
- **4 seksi:** user (kontak + tautan ganti password via self-service), company (identitas + logo), email (SMTP + template), whatsapp (webhook + template).
- **Password change TIDAK diduplikasi** (sudah ada `SelfServiceController`, Q-021).

## 4. Print Center

- **Gate:** `access-print-center` (admin / compliance / auditor).
- **Route:** `compliance/print` → index, item, inventory-by-type, batch, batch-preview.
- **Controller:** `app/Http/Controllers/Compliance/PrintController.php`.
- **2 mode:** Print Per Inventory (reuse `compliance.report.pdf`) + Print Batch (form kolektif bulanan + temuan "tidak sesuai" via Dompdf, template `print/batch-form.blade.php`, landscape A4).
- **Penyederhanaan:** layout per-item-type CI4 (APAR/hydrant/dll) dipadatkan jadi tabel generik (improvement, bukan copy).

## 5. Menu & role_default_pages (tambahan)

| Item menu baru | Grup | Page key | Role default (selain admin) |
|---|---|---|---|
| Users | Admin | users_management | (admin only) |
| Checklist Master | Master Data | checklist_master | compliance, auditor |
| Pengaturan | Utama | settings | compliance, security, staff, auditor, office |
| Print Center | Compliance | print_center | compliance, auditor |

## 6. Data legacy → Laravel

- **DB v4:** `eams` (bersih). **DB CI4:** `asset_compliance_system` dibaca read-only via koneksi `legacy` (`config/database.php`).
- **Perintah:** `php artisan eams:import [--dry-run]` (idempoten, detail pemetaan di `docs/import-field-mapping.md`).
- **Optimasi:** `importChecklistLogs` diubah jadi streaming — preload lookup map (users + inventory→asset_item_type_id), delete-existing sekali, lalu bulk insert per chunk 1000. Memperbaiki OOM (512MB) saat menampung 107k baris sekaligus, dan memangkas import dari ~1,5 jam menjadi hitungan detik.
- **Hasil import:** users 40, areas 19, categories 8, item types 38, holidays 40, employees 20, inventories 627, pics 627, checklist master 209, checklist logs **107.210** — 0 error.
- `app_settings` dibuat via `php artisan migrate` (sudah dijalankan).

## 7. Verifikasi

- **Full test:** `php artisan test` → **163 passed (406 assertions)**.
- **Per modul:** `view:cache` exit 0, `route:list` lengkap, `php -l` bersih.
- **Import:** dry-run 0 error + run sungguhan 0 error, jumlah persis sesuai legacy.

## 8. Sisa / risiko

- **Fase 2:** migrasi ~35 view tersisa ke `<x-page-header>` (polish UI, belum).
- **Browser QA:** belum (login admin → Users / Checklist Master / Settings / Print Center + dark mode).
- **Audit log:** Settings belum mencatat `audit_log` seperti CI4.
- **Auditor vs `access-compliance-pdf`:** auditor bisa buka Print Center, tapi tombol Print Per Inventory mengarah ke `compliance.report.pdf` (gate admin/compliance) → 403 untuk auditor tanpa page `compliance`.
