# 27 — Rekonsiliasi Data Legacy (`eams:reconcile`)

`eams:import` memindahkan data. `eams:reconcile` **membuktikan** bahwa data itu benar-benar pindah dan memberi tahu bagian mana yang diam-diam diubah importer.

Perintah ini **read-only**: tidak pernah menulis ke database legacy maupun ke database Laravel.

```bash
php artisan eams:reconcile
php artisan eams:reconcile --samples=10
php artisan eams:reconcile --json
php artisan eams:reconcile --save=reconcile-2026-08-27.json
```

| Opsi | Arti |
| --- | --- |
| `--samples=N` | Jumlah contoh baris per temuan (default 5) |
| `--json` | Cetak laporan mentah sebagai JSON, bukan tabel |
| `--save=FILE` | Simpan laporan JSON ke `storage/app/FILE` |

**Exit code:** `0` kalau bersih, `1` kalau masih ada temuan. Jadi bisa dipakai di skrip atau CI.

## Urutan pakai

```bash
# 1. lihat masalah data SEBELUM import
php artisan eams:reconcile

# 2. simulasi import (tidak menulis apa pun)
php artisan eams:import --dry-run

# 3. import beneran
php artisan eams:import

# 4. buktikan hasilnya
php artisan eams:reconcile
```

Langkah 1 berguna walaupun belum pernah import: bagian "nilai legacy yang akan dinormalisasi" dan "relasi legacy yang menggantung" sudah bisa dibaca dari database lama saja.

## Isi laporan

### 1. Jumlah baris

Membandingkan tiap pasangan tabel legacy → target.

Kolom **Kunci unik legacy** adalah jumlah *business key* yang berbeda, bukan jumlah baris mentah. Ini penting karena `eams:import` memakai `updateOrCreate` pada business key, jadi dua baris legacy dengan kunci sama memang seharusnya jadi satu baris target.

| Tabel legacy | Tabel target | Business key |
| --- | --- | --- |
| `users` | `users` | `username` |
| `areas` | `areas` | `name` |
| `inventory_categories` | `inventory_categories` | `name` |
| `asset_item_types` | `asset_item_types` | `code` |
| `holidays` | `holidays` | `holiday_date` |
| `employees` | `employees` | `employee_id` |
| `compliance_inventory` | `compliance_inventories` | `asset_code` |
| `checklist_master` | `checklist_master` | `asset_item_type_id` + `question` |
| `checklist_logs` | `checklist_logs` (baris ber-`legacy_id`) | `legacy_id` |

**Delta positif bukan temuan.** Delta positif berarti target punya baris lebih banyak — itu data yang dibuat langsung di aplikasi Laravel setelah migrasi, dan memang tidak boleh dianggap error. Yang dilaporkan sebagai temuan hanya **kekurangan** baris.

Untuk `checklist_logs`, hanya baris yang punya `legacy_id` yang dihitung, sehingga checklist yang diisi user lewat aplikasi tidak ikut mengacaukan angka.

### 2. Nilai legacy yang akan dinormalisasi importer

Inilah bagian yang paling sering jadi sumber kejutan. Importer tidak menolak nilai aneh — ia memaksanya ke nilai default. Bagian ini menunjukkan baris mana saja yang kena.

| Kolom legacy | Nilai yang dikenali | Sisanya dipaksa jadi |
| --- | --- | --- |
| `users.role` | `admin`, `compliance`, `security`, `staff`, `auditor`, `office` | `staff` |
| `compliance_inventory.status` | `good`, `need repair`, `need_repair`, `not active`, `not_active`, `inactive` | `good` |
| `checklist_logs.status` | `ok`, `not_ok`, `not ok`, `not-ok`, `ng`, `na`, `n/a` | `ok` |

Perbandingan dilakukan setelah `lower()` + `trim()`, jadi `"Need Repair"` tetap terbaca benar.

Contoh nyata: baris legacy `checklist_logs` id `350` punya `status = ''`. Nilai itu tidak dikenali, jadi importer menuliskannya sebagai `ok`. Laporan ini memunculkan id `350` supaya bisa diputuskan: perbaiki di database lama, atau terima nilai `ok`.

> Daftar di atas harus tetap sinkron dengan `LegacyImporter::mapRole()`, `mapStatus()`, dan `mapChecklistStatus()`. Kalau mapping importer diubah, ubah juga konstanta `LEGACY_VOCABULARY` di `LegacyReconciler`.

### 3. Relasi legacy yang menggantung

Baris legacy yang menunjuk ke id yang sudah tidak ada. Baris seperti ini **gagal saat import** (masuk ke daftar error importer dan me-rollback transaksi), jadi sebaiknya dibereskan lebih dulu.

Yang diperiksa: `asset_item_types.inventory_category_id` (atau `category_id`), `compliance_inventory.category_id` / `item_type_id` / `area_id`, `checklist_master.item_type_id`, `checklist_logs.inventory_id` / `checklist_template_id`.

Nilai `0` dan `NULL` dianggap "tidak ada relasi" dan tidak dilaporkan, karena database CI4 lama memakai keduanya secara bergantian.

### 4. Kunci bisnis ganda di legacy

Dua baris legacy dengan `username`, `name`, `code`, `asset_code`, `employee_id`, atau `holiday_date` yang sama. Ini penjelasan resmi kalau jumlah baris target lebih sedikit dari jumlah baris legacy — bukan data hilang, tapi digabung.

### 5. Relasi target yang menggantung

Sama seperti nomor 3 tapi di sisi Laravel: `compliance_inventories`, `compliance_inventory_pics`, `checklist_master`, `checklist_logs`, `checklist_log_histories`. Kalau bagian ini ada isinya, ada foreign key yang bocor dan harus diselidiki.

### 6. Duplikat & enum tidak valid di target

- Duplikat pada `users.username`, `asset_item_types.code`, `compliance_inventories.asset_code`, `checklist_logs.legacy_id`.
- Nilai di luar enum pada kolom `permission`, `status`, `checklist_frequency`, `frequency`, `mode`, `follow_up_status`.

Di MySQL mode `STRICT` nilai di luar enum biasanya sudah ditolak saat insert, jadi bagian ini terutama berguna kalau database pernah diubah manual lewat phpMyAdmin.

### 7. Parity `checklist_logs`

Pencocokan satu per satu memakai `checklist_logs.legacy_id`:

| Metrik | Arti |
| --- | --- |
| Baris legacy | Jumlah baris di `checklist_logs` legacy |
| Baris target hasil import | Baris Laravel yang punya `legacy_id` |
| Baris target dibuat di aplikasi | Baris Laravel tanpa `legacy_id` (normal) |
| Belum ada di target | Baris legacy yang belum ter-import — **temuan** |
| Ada di target tapi hilang di legacy | Baris legacy sudah dihapus setelah import — **temuan** |
| Tanggal tidak bisa diturunkan | `check_date` tidak valid **dan** `period_key` tidak bisa dipakai — importer melewati baris ini — **temuan** |

Pembacaan dilakukan per 1.000 id sehingga aman untuk tabel besar.

## Kalau koneksi legacy belum diisi

Perintah tetap jalan dan tetap memeriksa integritas database Laravel (bagian 5 dan 6), tapi melaporkan koneksi legacy gagal dan keluar dengan exit code 1. Isi dulu `.env`:

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=asset_compliance_system
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=
```

Kalau Laravel jalan di Docker sedangkan MySQL lama ada di XAMPP host, pakai `LEGACY_DB_HOST=host.docker.internal`.

## Kalau `checklist_logs.legacy_id` belum ada

Laporan akan bilang kolomnya belum tersedia. Jalankan:

```bash
php artisan migrate
```

Migration `2026_08_27_000001_add_legacy_id_to_checklist_logs` menambahkan kolom nullable + unique yang dipakai importer maupun reconciler.

## File terkait

- `app/Services/Import/LegacyReconciler.php`
- `app/Console/Commands/ReconcileLegacyData.php`
- `tests/Feature/Import/LegacyReconcileTest.php`
- `docs/import-field-mapping.md` — pemetaan kolom legacy → target
