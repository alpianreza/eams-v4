# Legacy → Laravel: Field Mapping

Sumber dibaca dari koneksi `legacy` yang bersifat read-only. Target import menggunakan transaction dan tidak menyimpan perubahan jika ditemukan error.

| Legacy | Laravel | Aksi |
|---|---|---|
| users.username | users.username | CARRY, kunci upsert |
| users.name/email/password/photo/wa_number | kolom yang sama | CARRY; hash password dipertahankan |
| users.page_access | users.page_access | validasi JSON array |
| users.role | users.role | TRANSFORM ke role kanonikal |
| users.permission | users.permission | TRANSFORM ke read/write |
| areas.name | areas.name | CARRY, kunci upsert |
| inventory_categories.name | inventory_categories.name | CARRY, kunci upsert |
| asset_item_types.inventory_category_id | asset_item_types.inventory_category_id | TRANSFORM melalui peta ID |
| asset_item_types.code | asset_item_types.code | CARRY, kunci bisnis |
| employees.employee_id/name/division/position/photo/status | kolom yang sama | CARRY/normalisasi status |
| compliance_inventory.asset_code | compliance_inventories.asset_code | CARRY persis, kunci upsert |
| compliance_inventory.pic | compliance_inventories.pic | CARRY snapshot teks |
| compliance_inventory.pic | compliance_inventory_pics | TRANSFORM nama ke maksimal dua user |
| checklist_master.item_type_id | checklist_master.asset_item_type_id | TRANSFORM melalui peta ID |
| checklist_logs.id | checklist_logs.legacy_id | CARRY, kunci idempoten |
| checklist_logs.checklist_template_id | checklist_logs.checklist_master_id | TRANSFORM melalui peta ID |
| checklist_logs.checked_by | checked_by_user_id + checked_by_name | resolve user dan snapshot nama |
| checklist_logs.status | checklist_logs.status | TRANSFORM ke ok/not_ok/na |
| checklist_logs.created_at | checklist_logs.created_at | CARRY jika valid |

## Menjalankan

```bash
php artisan migrate
php artisan eams:import --dry-run
php artisan eams:import
```

## Jaminan keselamatan

- Dry-run menjalankan semua query target di dalam transaction lalu rollback.
- Import riil juga transactional; satu error menyebabkan rollback penuh.
- Checklist diproses per chunk 1.000 baris.
- Tidak ada `delete()` massal terhadap `checklist_logs`.
- Baris hasil importer lama tanpa `legacy_id` diadopsi menggunakan business key, sehingga ID target dan history tetap dipertahankan.
- Checklist yang dibuat langsung di Laravel dan tidak cocok dengan baris legacy tidak disentuh.

## Batas cakupan

Importer inti belum berarti seluruh 49 tabel legacy otomatis dipindahkan. Tabel modul lain harus memiliki mapping eksplisit dan rekonsiliasi sebelum cutover final.
