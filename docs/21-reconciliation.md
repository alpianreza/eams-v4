# PHASE 2M — Reconciliation: CI4 → Laravel Parity Report

Rebuild EAMS (Laravel 13 + PHP 8.5, `eams-v4`) diverifikasi terhadap business rules &
keputusan HUMAN-APPROVED (docs/19 decision log). Status CI terkini: **129 passed · 294 assertions · exit 0**.

## A. Checklist engine (inti)

| Rule | Implementasi | Test | Status |
|---|---|---|---|
| BR-01 period key (daily=Y-m-d, weekly=Y-W#bulan, monthly=Y-m) | `ChecklistPeriod::periodKey` (month-slice Minggu 1-4) | `ChecklistPeriodTest` | ✅ |
| BR-02 batas periode | `ChecklistPeriod::bounds` (Mon–Sun / month) | `ChecklistPeriodTest` | ✅ |
| BR-03 late threshold (21/28/90 hr) | `ChecklistPeriod::lateAfterDays` + `isLate` | `ChecklistPeriodTest` | ✅ |
| BR-04 weekly tidak ganti hari | `periodKey` weekly pakai Senin pekan | `ChecklistPeriodTest` | ✅ |
| BR-05 time slot fixed | `availableTimeSlots` (pagi/siang 2-shift, else single) | `ShiftPatternTest` | ✅ |
| BR-07 fingerprint non-redirect (403) | `EnsureFingerprint` middleware | `FingerprintTest` | ✅ |
| BR-08 hari libur diblok | `isOffday` + block di `SubmitChecklist` | `ChecklistSubmissionTest` | ✅ |
| BR-09 dedup per periode | unique key + `updateOrCreate` | `ChecklistSubmissionTest` | ✅ |
| BR-10 NA follow-up | `follow_up_*` di `checklist_logs` | `ChecklistSubmissionTest` | ✅ |
| BR-36 grid rows=questions, cols=time-slot | `checklist_master` per type + `checklist_logs.time_slot` | `GridChecklistTest` | ✅ |
| Result OK/NOT_OK/NA (bukan "baik"/"tersedia") | enum `ok/not_ok/na` | `ChecklistSubmissionTest` | ✅ |
| NOT_OK wajib remark/photo | `SubmitChecklist` evidence validation | `ChecklistSubmissionTest` | ✅ |
| Re-submit = UPDATE + history | `updating` observer → `checklist_log_histories` | `ChecklistSubmissionTest` | ✅ |
| Edit diblok pada offday | `isOffday` guard di submit | `ChecklistSubmissionTest` | ✅ |
| Saturday holiday ≥2026-04-01, tidak retroaktif, configurable | `config('eams.saturday_holiday_effective')` | `SaturdayHolidayTest` | ✅ |

## B. Auth & authorization

| Rule | Implementasi | Test | Status |
|---|---|---|---|
| BR-22 login username ATAU email | `AuthenticatedSessionController` (dual-field) | `AuthenticationTest` | ✅ |
| Session 8 jam, throttle, regen, logout, audit | config + `AuthAudit` + `login_sessions` | `AuthenticationTest` | ✅ |
| BR-25 role user_roles + canonical 6 + custom | `user_roles` pivot + `User::hasRole` | `AuthenticationTest` | ✅ |
| BR-26 write guard (read-only 403) + whitelist | `EnsureWriteAccess` (global) | `WriteAccessTest` | ✅ |
| Middleware/Gate/Policy (no hard-code) | gates di `AppServiceProvider` | `WriteAccessTest` | ✅ |

## C. Compliance Inventory + QR

| Rule | Implementasi | Test | Status |
|---|---|---|---|
| BR-11 asset_code human-readable preserved | `asset_code` unique + carry | `ComplianceInventoryTest` | ✅ |
| BR-19 generator `{CAT}-{ITEM}-###` | `GenerateAssetCode` | `AssetCodeTest` | ✅ |
| Duplicate asset_code → FAIL (no auto-rename) | duplicate validation | `AssetCodeTest` | ✅ |
| BR-13 1 item type → many inventory | `AssetItemType::inventories` | `ComplianceInventoryTest` | ✅ |
| BR-14 kategori standalone | `InventoryCategory` | `ComplianceInventoryTest` | ✅ |
| BR-21 PIC maks 2 setara, NO primary, SoT pics | `compliance_inventory_pics` | `PicAssignmentTest` | ✅ |
| Edit-lock kategori/area/item type | `ComplianceInventoryController@update` lock | `ComplianceInventoryTest` | ✅ |
| Status GOOD/NEED_REPAIR/NOT_ACTIVE | enum canonical (Q-017) | `ComplianceInventoryTest` | ✅ |
| specific_area bebas konsisten | `specific_area` | `ComplianceInventoryTest` | ✅ |
| Expired ≠ NOT_ACTIVE (APAR) | `isExpired()` terpisah | `ComplianceInventoryTest` | ✅ |
| BR-20 QR → `compliance/inventory/detail/{id}` | `QrService` + compat route | `QrCompatTest` | ✅ |

## D. File, PDF, IT Device, Notifikasi

| Rule | Implementasi | Test | Status |
|---|---|---|---|
| File storage per-kategori configurable | `FileStorage` + `config/eams.files` | `FileStorageTest` | ✅ |
| Upload terpusat (size+mimes) | `ImageUpload` | `FileStorageTest` | ✅ |
| File serving auth + traversal guard | `FileController` | `FileStorageTest` | ✅ |
| BR-32 PDF admin+Compliance via Gate | `can:access-compliance-pdf` (Q-008) | `ComplianceReportPdfTest` | ✅ |
| PDF per item + fallback generik (Q-015) | `ComplianceReportController::resolveView` | `ComplianceReportPdfTest` | ✅ |
| BR-37/38 IT device online ≤10 mnt (config) | `ItDevice::isOnline` + config threshold | `DeviceThresholdTest` | ✅ |
| Agent API kompatibel legacy | `AgentApiController` (heartbeat/command/update) | `AgentApiTest` | ✅ |
| BR-23/24 reminder mingguan, hormati offday, hanya due | `WeeklyChecklistReminder` + `eams:remind-checklists` | `ChecklistReminderTest` | ✅ |

## E. Modul sekunder (2K) — semua hijau

Utility (Boiler/IPAL/PDAM daily log + total bulanan, `UtilityLogTest`), Patrol (session/scan/GPS, `PatrolTest`), Kuesioner (public fill, `QuestionnaireTest`), Kalender (`CalendarTest`), EMS/GHG (year×month matrix, `EmsReportTest`), FDM (`FdmTest`), Thermal (`ThermalImagingTest`), Notifikasi (`ChecklistReminderTest`).

## F. Legacy import (2L)

| Aspek | Status |
|---|---|
| `php artisan eams:import` repeatable + idempotent | ✅ `LegacyImportTest` |
| dry-run tidak menulis | ✅ |
| tabel legacy hilang → skip (bukan fatal) | ✅ |
| password bcrypt legacy di-carry (tidak re-hash) | ✅ |
| asset_code preserved, status ter-map, checked_by ter-resolve | ✅ |
| FK via peta id legacy→baru; REVIEW bila tak ter-resolve | ✅ |

## Catatan
- `compliance_checklist_*` (dead) TIDAK dibawa (Q-009) — checklist pakai `checklist_master` + `checklist_logs`.
- Username unik; email opsional. Kolom `date` disimpan string `Y-m-d` murni (portabel SQLite/MySQL/MariaDB).
- Deferred (technical-scope, terdokumentasi): channel agent update win7/xp, remote lock, normalisasi hardware mendalam; `it_devices.asset_id` FK; CO₂ EMS dari faktor emisi (perlu faktor); export Excel/PDF per modul; layout editor patrol (visual); analytics/PDF kuesioner.
