# Legacy → Laravel: Field Mapping (CARRY / TRANSFORM / DROP / REVIEW)

Referensi import `php artisan eams:import` (2L). Sumber = koneksi `legacy` (READ-ONLY).

| Legacy | Laravel | Aksi |
|---|---|---|
| users.username | users.username | CARRY (kunci idempoten) |
| users.name / email / password | sama | CARRY (password bcrypt kompatibel) |
| users.role | users.role | TRANSFORM → kanonik (admin/compliance/security/staff/auditor/office), else staff |
| users.permission | users.permission | TRANSFORM → read/write, else read |
| areas.name | areas.name | CARRY (kunci) |
| asset_item_types.code | asset_item_types.code | CARRY (kunci bisnis, Q-015) |
| asset_item_types.checklist_frequency | sama | TRANSFORM → daily/weekly/monthly, else monthly |
| holidays.holiday_date | holidays.holiday_date | TRANSFORM → substr Y-m-d (kunci) |
| employees.employee_id | employees.employee_id | CARRY (kunci) |
| compliance_inventory.asset_code | compliance_inventories.asset_code | CARRY **persis** (Q-020, kunci) |
| compliance_inventory.status | status | TRANSFORM → good/need_repair/not_active (Q-017) |
| compliance_inventory.pic (teks) | compliance_inventory_pics | TRANSFORM → resolve nama→user, maks 2 setara (Q-007) |
| checklist_logs.checked_by (teks) | checked_by_user_id + checked_by_name | TRANSFORM (Q-006): resolve user + snapshot nama |
| checklist_logs.status | status | TRANSFORM → ok/not_ok/na (Q-001) |
| compliance_inventory.created_at dsb. | — | DROP (timestamp lama tak dibawa) |
| kolom tak dikenal | — | REVIEW → tercatat di error report, tidak dibawa |
