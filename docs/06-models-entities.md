# 06 — Models & Entities

CI4 Model (bukan Eloquent). Mayoritas model **tipis**: hanya `table`, `primaryKey`, `allowedFields`, `useTimestamps`. Tidak ada definisi relasi formal ($hasMany/dst.) — relasi dilakukan via join manual di controller/model. **Tidak ada soft delete** (`useSoftDeletes` tidak dipakai di mana pun).

## Model dengan business logic penting

### ComplianceInventoryModel → `compliance_inventory`
- allowedFields: `category_id, area_id, item_type_id, asset_code, type_description, specific_area, pic, status, qty, remark, expired_date, photo, qr_image`. `useTimestamps=true`.
- Callbacks:
  - `beforeInsert/beforeUpdate → limitPicUsers`: PIC teks diparse (pemisah newline/koma/` - `), unik, **dipotong maks 2 nama**.
  - `afterInsert/afterUpdate → syncPicRelations`: tulis ulang `compliance_inventory_pics` (delete+insert; `is_primary=1` utk nama pertama; hanya user `status='active'` yang namanya cocok persis).
  - `afterInsert/afterUpdate → notifyPicAssignment`: kirim notifikasi `assignment` ke tiap PIC (dedupe `inventory_assignment:{invId}:{userId}`), hapus cache `sidebar_notif_{userId}`.
  - `afterFind → hydratePicRelations`: menimpa kolom `pic` dengan gabungan nama dari tabel pics + menambah `pic_users`, `pic_user_ids`.
- Method:
  - `assignedToUser($userId)`: join `compliance_inventory_pics` (bila tabel ada); **fallback**: `LIKE pic` nama dari session; bila tidak ada nama → `where id=0`.
  - `getBaseQuery()/getDetail($id)`: join kategori + area + item type. ⚠️ join item type memakai `compliance_inventory.item_name` (bukan `item_type_id`) — kolom `item_name` tidak terdokumentasi → kemungkinan bug legacy (INFERRED; `getDetail` tampak tidak dipakai route aktif).

### ChecklistLogModel → `checklist_logs`
- allowedFields: `inventory_id, item_type_id, checklist_template_id, check_date, period_key, time_slot, status, remark, photo, checked_by, created_at, follow_up_status, follow_up_note, follow_up_date`. `useTimestamps=false`.
- Tanpa custom method — semua logic periode/status ada di helper & controller.

### ChecklistMasterModel → `checklist_master`
- allowedFields: `item_type_id, question, frequency, require_photo, active`; `getByItemType($id)` (active=1).

### ComplianceChecklistLogModel → `compliance_checklist_logs` (LEGACY)
- allowedFields: `inventory_id, item_type_id, frequency, inspection_date, inspection_week, inspection_month, inspection_year, checked_by`. `useTimestamps=true`.
- `alreadyChecked($inventoryId, $frequency, $date)`: daily → by inspection_date; weekly → `date('W')` (ISO week!) + year; monthly → month+year.
- ⚠️ Kolom model tidak cocok dengan migration 2026-01-19 (schedule_id/template_id/result) → drift; tidak dipakai route aktif.

### ComplianceChecklistMasterModel → `compliance_checklist_master` (LEGACY, tanpa migration)
### ComplianceChecklistLogItemModel → `compliance_checklist_log_items` (LEGACY, tanpa migration)

### UserModel → `users`
- allowedFields: `username, email, name, password, role, permission, status, wa_number, photo, page_access`.

### UserRoleModel → `user_roles` — allowedFields `name`; timestamps.

### AppSettingModel → `app_settings`
- `allAsMap($includeSecrets=false)`; `value($key, $default, $includeSecrets)`; `put($key, $value, $secret, $userId)` (upsert by key). Secret tidak pernah dikembalikan tanpa flag.

### NotificationModel → `notifications`
- `unreadForUser($userId, $limit=8)`; `unreadCount($userId)`. (Pengiriman ada di `Libraries/NotificationService`.)

### ComplianceCalendarEventModel → `compliance_calendar_events` — allowedFields termasuk `sticker`.

### HolidayModel → `holidays` — `holiday_date, description`.

### AssetItemTypeModel → `asset_item_types` — termasuk `checklist_frequency` (kolom kunci engine checklist; tidak ada di migration).

### InventoryCategoryModel → `inventory_categories` (`name, area_id`) · **AreaModel → `areas` (`name`)** · **AssetModel → `assets`** (inventory_no, category_id, asset_name, brand, serial_number, purchase_date, photo, status, location).

### ITDeviceModel → `it_devices`
- allowedFields sangat luas; yang penting: **`cpu` menyimpan JSON state serbaguna** (hardware, health, command queue, remote lock, interval heartbeat, diagnostics, session). Seluruh protokol agent bergantung pada kolom ini.

### ItDeviceCommandModel → `it_device_commands` (returnType array, timestamps) · **ItDeviceLogModel → `it_device_logs`** (tanpa primaryKey didefinisikan!).

### Thermal Imaging (3 model): locations (name, section, active, created_by), reports (inspection_date, inspector_name, facility, area_name, created_by), report_items (report_id, location_id, location_name, celsius, thermal_image, findings, recommendation, sort_order).

### Questionnaire (4 model): questionnaires (slug unik, collect_*, active, sort_order; timestamps), questions (semua field pertanyaan; timestamps), responses (response_code unik, respondent_*, submitted_at, created_by; `updatedField=''`), answers (response_id, question_id, answer_value).

### EMS (8 model): 4 pasang years(entries) — water (`consumption_m3`), electric (`consumption_kwh`), stationary & mobile (`section_key`, `consumption_amount`). Semua timestamps.

### FDM (2 model): years (`report_year`), entries (`year_id, section_key, section_label, entry_type, frequency_label, logo_path, display_order, monthly_values` JSON).

### Utility: BoilerFuelModel (`boiler_fuel_logs`: log_date, log_time, polybag, kg, note, created_by), IpalModel (`ipal_logs`: log_date, start_meter, stop_meter, volume, pemakaian, ket, created_by), PdamWaterLogModel & PdamWaterBoilerLogModel (`*_logs`: log_date, log_time, meter_reading, note, created_by).

## Callback / event / method bernuansa business rule (inventaris)

| Model | Method/callback | Rule | Status |
|---|---|---|---|
| ComplianceInventoryModel | limitPicUsers | PIC maks 2 | CONFIRMED |
| ComplianceInventoryModel | syncPicRelations | pics diturunkan dari teks `pic`; hanya user aktif | CONFIRMED |
| ComplianceInventoryModel | notifyPicAssignment | notifikasi assignment + dedupe | CONFIRMED |
| ComplianceInventoryModel | assignedToUser | PIC by user id (pics) fallback nama | CONFIRMED |
| ComplianceChecklistLogModel | alreadyChecked | dedupe per periode (ISO week utk weekly) | CONFIRMED (legacy) |
| AppSettingModel | put/value/allAsMap | key-value settings, secret masking | CONFIRMED |
| NotificationModel | unread* | inbox user | CONFIRMED |

## Catatan untuk Laravel

- Semua relasi perlu didefinisikan ulang sebagai Eloquent relations (sekarang join manual tersebar di controller).
- `checklist_logs.checked_by` (string nama) → kandidat `checked_by_user_id` FK (perlu migrasi data; lihat 15).
- `it_devices.cpu` JSON → pecah ke kolom/tabel (`it_device_state`, dst.) atau tetap JSON column dgn casts.
- `page_access` JSON → tabel pivot `role_page`/`user_page` + permission enum.
