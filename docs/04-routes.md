# 04 — Routes

Semua route didefinisikan di **`app/Config/Routes.php`** (tidak ada route lain; auto-discovery tidak dipakai). Filter global: `csrf` (kecuali `api/agent*`, `kuesioner*`, `logstores*`) + `write` (semua request) + after: `secureheaders`, `csrfasset`.

> Route name: CI4 tidak pakai named route di sini → kolom "name" = pola `<group>.<action>` untuk dokumentasi.

## Root & Auth

| Method | URL | Controller@action | Filter | Catatan |
|---|---|---|---|---|
| GET | `/` | closure → redirect `/home` | – | |
| GET | `dashboard`, `dashboard/*` | closure → redirect `/home` | – | legacy alias |
| GET | `dashboard-it` | DashboardController@index | auth | |
| GET | `it` | ITController@index | auth | IT Center |
| GET/POST | `/login` | AuthController@login / doLogin | csrf | publik |
| POST | `/logout` | AuthController@logout | csrf | |
| GET | `/home` | HomeController@index | auth | |
| GET | `audit-logs` | AuditLogController@index | **admin** | |

## Users / Employees / IT Assets (auth)

```
users (auth)
├── GET    users/                     UserController@index
├── GET    users/create               @create
├── POST   users/store                @store
├── POST   users/roles/store          @storeRole
├── GET    users/edit/{id}            @edit
├── POST   users/update/{id}          @update
├── POST   users/deactivate/{id}      @deactivate
└── POST   users/activate/{id}        @activate

employees (auth)
├── GET employees/ | create | detail/{id} | edit/{id}
├── POST employees/store | update/{id} | activate/{id} | deactivate/{id} | delete/{id}
└── POST employees/unassign/{employeeId}/{assetId}   [auth + write]

it-assets (auth)
├── GET  it-assets/ | ajax | detail/{id} | assign/{id} | create | edit/{id}
└── POST it-assets/assign/{id} | store | update/{id}
```

## IT Device & Agent API

| Method | URL | Controller@action | Filter |
|---|---|---|---|
| GET | `it/devices` | ITDeviceController@index | auth |
| GET | `it/devices/ajax` | @ajax | auth |
| GET | `it/devices/stats` | @stats | auth |
| GET | `it/devices/{id}` | @detail | auth |
| GET | `it/devices/{id}/fragment` | @detailFragment | auth |
| POST | `it/device/command` | @sendCommand | auth |
| POST | `it/device/remote` | @remoteAction | auth |
| POST | `it/device/push-update` | Api\AgentController@pushUpdate | auth |
| GET/POST | `api/agent/heartbeat` | Api\AgentController@heartbeat | **publik, CSRF-exempt** (identitas: device_token/mac/hostname) |
| GET/POST | `api/agent/command` | @command | publik, CSRF-exempt |
| GET/POST | `api/agent/update` | @agentUpdate | publik, CSRF-exempt |

## Patrol (auth)

```
patrol (auth)
├── GET  patrol/                PatrolController@index      (security/compliance/admin)
├── GET  patrol/dashboard       @dashboard                  (compliance/admin)
├── GET  patrol/editor          @editor                     (admin)
├── POST patrol/sessions/start  @startSession
├── POST patrol/sessions/scan   @scanCheckpoint
├── POST patrol/sessions/cancel @cancelSession
└── POST patrol/layout/save     @saveLayout                 (admin)
```

## Compliance — Dashboard (auth)

```
GET compliance/dashboard                      ComplianceDashboardController@index
GET compliance/dashboard/trend                @getTrendAjax
GET compliance/dashboard/progress-trend       @getProgressTrendAjax
GET compliance/dashboard/status-pie           @getStatusPieAjax
GET compliance/dashboard/total-inventory      @getTotalInventoryByType
GET compliance/dashboard/risk-insight         @getRiskInsightAjax
GET compliance/dashboard/risk-trend           @getRiskTrendAjax   ⚠️ method tidak ada di controller
GET compliance/dashboard/pending-checklist    @getPendingChecklistAjax
GET compliance/dashboard/data                 @ajaxData           ⚠️ method tidak ada di controller
```

## Compliance — Inventory (auth)

```
GET  compliance/inventory/                    index (list+filter+sort+paginate)
GET  compliance/inventory/create              create
POST compliance/inventory/store               store (+QR generate)
GET  compliance/inventory/edit/{id}           edit
POST compliance/inventory/update/{id}         update (+QR regenerate bila kode berubah)
POST compliance/inventory/delete/{id}         delete
GET  compliance/inventory/detail/{id}         detail (grid histori + rekap)
POST compliance/inventory/update-photo/{id}   updatePhoto
POST compliance/inventory/regenerate-qr/{id}  regenerateQr
GET  compliance/inventory/item-types/{catId}  getItemTypesByCategory (JSON)
GET  compliance/inventory/get/{id}            get (JSON)
GET  compliance/inventory/qr-center           qrCenter
GET  compliance/inventory/qr-album/{item}     qrAlbumAjax
GET  compliance/inventory/qr-album-download/{item}  qrAlbumDownload (zip)
GET  compliance/inventory/qr-album-regen/{item}     qrAlbumRegen
GET  compliance/inventory/qr-album-print/{item}     qrAlbumPrint
```

## Compliance — Checklist (auth) — kanal pengisian

```
GET  compliance/checklist/{id}                         checklist (form per item; redirect CCTV→grid)
POST compliance/checklist/submit                       submitChecklist
GET  compliance/checklist/{id}/calendar                calendar (partial AJAX)

Grid khusus (GET) + save/mark-all (POST, AJAX):
  cctv-grid                    (daily,  item_type 13)
  emergency-light-grid         (monthly, item_type 4)
  emergency-exit-light-grid    (monthly, item_type 59)
  first-aid-box-grid           (monthly, item_type 10)
  first-aid-content-grid/{id}  (daily,   item_type 33)
  fire-extinguisher-grid       (monthly, item_type 1)
  intrusion-alarm-grid         (weekly,  item_type 8)
  hydrant-grid                 (weekly,  item_type 2)
  smoke-detector-grid          (monthly, item_type 7)
  heat-detector-grid           (monthly, item_type 6)
  gate-grid/{id}               (daily,   item_type 40)
  generic-grid/{id}            (semua tipe; toilet item_type 52 dgn slot PG/SI/SO)

Checklist Master:
GET  compliance/checklist/master/                  masterIndex
GET  compliance/checklist/master/category/{id}     masterByCategory
GET  compliance/checklist/master/item/{id}         masterItem
POST compliance/checklist/master/store             store
POST compliance/checklist/master/update/{id}       update
POST compliance/checklist/master/item-frequency/{id}  updateItemFrequency
POST compliance/checklist/master/delete/{id}       delete

Item type:
GET  compliance/item/create   ComplianceItemTypeController@create
POST compliance/item/store    @store
```

## Compliance — Report / Progress / Ranking / Evidence / Print / PDF (auth)

```
GET compliance/report/                      ComplianceReportController@index
GET compliance/report/load                  @loadAjax
GET compliance/report/item-by-category      @getItemTypeByCategory
GET compliance/report/inventory-by-type     @getInventoryByType

GET  compliance/progress                    ProgressController@index (admin/compliance)
GET  compliance/progress/ajax               @getProgressAjax
GET  compliance/progress/export             @export (CSV)
GET  compliance/progress/detail             @getUserDetailAjax
POST compliance/progress/remind             @sendReminderAjax (WA)
GET  compliance/ranking                     ComplianceRankingController@index

GET  compliance/evidence                    ComplianceEvidenceController@index
GET  compliance/evidence/ajax               @getEvidenceAjax
GET  compliance/evidence/detail/{id}        @detail
POST compliance/evidence/update-followup    @updateFollowUp (AJAX)

GET compliance/print/                       CompliancePrintController@index (admin/compliance/auditor)
GET compliance/print/item                   @item
GET compliance/print/item/preview           @itemPreview
GET compliance/print/inventory/{itemTypeId} @inventoryByType
GET compliance/print/batch                  @batch
GET compliance/print/batch/preview          @batchPreview → PDF

GET export/pdf/single/{inventoryId}/{periodKey}   ExportPdfController@single
GET export/pdf/recap/{inventoryId}/{year}/{month} ExportPdfController@recap
```
⚠️ `PdfAccessFilter` (alias `pdfAccess`, role admin only di `Config\PdfPermission`) **tidak dipasang ke route mana pun** → export PDF hanya butuh `auth`.

## Thermal Imaging / Questionnaires / Calendar / Holidays (auth)

```
GET  compliance/thermal-imaging/            index (admin/compliance/staff)
GET  compliance/thermal-imaging/create      create
POST compliance/thermal-imaging/store       store
POST compliance/thermal-imaging/locations/store  storeLocation (admin/compliance, AJAX)
GET  compliance/thermal-imaging/{id}        show
GET  compliance/thermal-imaging/{id}/pdf    pdf (Dompdf)

Questionnaires (auth; read: admin/compliance/auditor/staff; write: admin/compliance):
GET  compliance/questionnaires/ | create | edit/{id} | fill/{id} | analytics
POST compliance/questionnaires/store | update/{id} | delete/{id}
POST compliance/questionnaires/submit/{id}
GET  compliance/questionnaires/response/{id} | response/{id}/pdf | {id}/excel
POST compliance/questionnaires/response/update-submitted-at/{id} | response/delete/{id}
POST compliance/questionnaires/{id}/respondent-settings
POST compliance/questionnaires/{id}/questions/store | {id}/questions/reorder
POST compliance/questionnaires/questions/update/{id} | questions/delete/{id}
POST compliance/questionnaires/questions/move-up/{id} | move-down/{id}
GET  compliance/questionnaires/{id}         detail (di bawah pola lain)

PUBLIK (tanpa auth, CSRF-exempt):
GET  kuesioner/{slug}          publicFill
GET  kuesioner/{slug}/selesai  publicThanks
POST kuesioner/{slug}/kirim    publicSubmit

Holidays (auth; manage: admin/compliance+write; delete holiday: admin):
GET  holidays/ | holidays/list
POST holidays/store | update/{id} | delete/{id}
POST holidays/national/store | national/update/{id} | national/delete/{id}
```

## EMS / FDM (auth)

```
GET  ems-reports/                          EmsReportController@index
GET  ems-reports/water-consumption         @waterConsumption
POST ems-reports/water-consumption/save    @saveWaterConsumption (AJAX autosave)
GET  ems-reports/electric-consumption      @electricConsumption
POST ems-reports/electric-consumption/save @saveElectricConsumption
GET  ems-reports/stationary-combustion     @stationaryCombustion
POST ems-reports/stationary-combustion/save @saveStationaryCombustion
GET  ems-reports/mobile-combustion         @mobileCombustion
POST ems-reports/mobile-combustion/save    @saveMobileCombustion
GET  ems-reports/ghg-summary               @ghgSummary

GET  fdm-data-collection/                       FdmDataCollectionController@index
GET  fdm-data-collection/production-section     @productionSection
POST fdm-data-collection/production-section/save @saveProductionSection (AJAX)
```

## Boiler & Utility (auth; role admin/compliance/office)

```
GET  boiler/ | boiler/detail/{date} | boiler/export
POST boiler/save | boiler/delete
GET  ipal/ | ipal/export
POST ipal/save
GET  pdam-water/ | detail/{date} | export-excel | export-pdf
POST pdam-water/save | delete
GET  pdam-water-boiler/ | detail/{date} | export-excel | export-pdf
POST pdam-water-boiler/save | delete
```

## Admin: Backups (filter admin) & Settings (auth)

```
GET  backups/                          BackupController@index
POST backups/database | files | full   createDatabase / createFiles / createFull
POST backups/upload                    upload
POST backups/auto-enable | auto-disable  enableAutoBackup / disableAutoBackup (schtasks)
GET  backups/download/{file}           download
POST backups/restore-full/{file} | restore-database/{file} | restore-files/{file}
POST backups/delete/{file}             delete

GET  settings/                  SettingsController@index
POST settings/change-password   changePassword (multi-action: password/company/email/whatsapp/contact/mark_notifications_read)
```

## Lain-lain

- `logstores/*` → selalu 404 (diblokir sengaja; CSRF-exempt).
- Route `/unauthorized` **tidak terdaftar** di Routes.php, tapi banyak controller redirect ke sana → CI4 akan 404 (lihat 14/15).
- Total definisi route: **±240**.
