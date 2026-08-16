# 05 — Controllers

Pola umum: semua controller web extend `BaseController` (memuat role, isWritable, notifikasi, appSettings ke renderer). Validasi dilakukan **manual di controller** (bukan Form Request). Response: redirect + flash untuk form, JSON untuk AJAX (umumnya menyertakan `csrfHash` baru).

## BaseController (induk)

- `initController`: set `role` (default `viewer`), `isWritable = hasWriteAccess() && role ∈ {admin, compliance}`; load `appSettings`; bila login: `touchLoginSession` (update `login_sessions.last_seen_at/last_route` maks 1×/menit), `markOpenedNotification` (query `notification_id`), `loadNotifications` (unread + reminder checklist cache 300 dtk).
- `render($view, $data)`: menyuntik `defaultTitle, isWritable, role, notifCount, notifications, appSettings`.
- `resolveDefaultTitle`: judul dari nama controller/method.

## Peta Route → Controller → Model → DB → Response (per controller)

### AuthController
- `login` GET /login → view `auth/login` (redirect /home bila sudah login; header no-cache).
- `doLogin` POST /login → throttle 5/mnt/IP → cari user aktif by username/email → `password_verify` → regenerate session + set (`logged_in, user_id, name, role, photo, permission, page_access, auth_session_id, login_*`) → insert `login_sessions` → `audit_log('login_success')` → redirect `redirect_after_login` atau `resolve_default_landing_url()`.
- `logout` POST → audit + tutup `login_sessions` (logout_reason=manual) → destroy session → /login.

### HomeController
- `index` GET /home → (?view=notifications → notificationCenter) → inventory milik user (`assignedToUser`) × periode bulan ini → summary total/pending/late/not_ok/done + progress% → view `home/index`.
- `notificationCenter` → 100 notifikasi user (filter type) → `home/notifications`.

### ComplianceInventoryController (5401 baris — lihat 14 Technical Debt)
Method publik: `index, update, delete, store, detail, updatePhoto, getItemTypesByCategory, checklist, submitChecklist, calendar, get, regenerateQr, qrCenter, qrBatch*, qrAlbumAjax, qrAlbumDownload, qrAlbumRegen, qrAlbumPrint` + 12 pasang grid (`xxxGrid/saveXxxGrid/markAllXxxGrid`: cctv, emergencyLight, emergencyExitLight, firstAidBox, firstAidContent, fireExtinguisher, intrusionAlarm, hydrant, smokeDetector, heatDetector, gate, generic).
- Input utama: POST form (inventory CRUD), multipart foto, grid AJAX (`inventory_id, period_key, template_id, mode=ok|not_ok|na|clear, time_slot`).
- Validasi: role per method; format `period_key` per frekuensi (regex); blokir hari libur (daily); anti-duplikat periode; not_ok wajib remark/foto (form).
- DB: `compliance_inventory`, `checklist_logs`, `checklist_master`, `asset_item_types`, `inventory_categories`, `areas`.
- Response: JSON `{ok|status, state, message, csrfHash, inserted}` / redirect dgn flash.
- Catatan: `qrBatch` tidak ter-route (dead method); grid memakai konstanta item_type_id hard-coded.

### ComplianceChecklistMasterController
`masterIndex → masterByCategory($id) → masterItem($id)` (3 level drill-down), `store/update/delete` (AJAX JSON), `updateItemFrequency` (ubah `asset_item_types.checklist_frequency`). `exportPeriodePage` tidak ter-route.

### ComplianceChecklistController — **DEAD (tidak ter-route)**
Berisi logika lama: `index($inventoryId)` memilih periode berdasarkan hari (Senin→weekly, tgl 1→monthly), `store()` menulis `checklist_logs` dgn `checked_by=username`, `checklist()` stub "DEFAULT AMAN". Jangan dijadikan acuan behaviour aktif.

### ComplianceItemTypeController — `create`/`store` item type baru (redirect ke checklist master).
### AssetItemTypeController — **DEAD** (tidak ter-route): `byCategory` JSON.
### ComplianceCalendarController — **DEAD** (route `compliance/calendar/events` tidak terdaftar). Duplikat logika HolidayController.

### HolidayController (Kalender + Hari Libur)
- `index`: GET `?month=Y-m` → `eventFeed` JSON (events + holidays + offdays) else view `holidays/index`.
- `store/update/delete` event kalender (admin/compliance+write); validasi judul ≤180, tanggal & jam valid, end ≥ start, warna ∈ 6 warna, sticker ∈ 18 emoji.
- `listHolidays` (JSON per tahun), `storeHoliday/updateHoliday` (validasi tanggal unik, deskripsi ≤150) + audit_log; `deleteHoliday` **admin only**.

### ComplianceDashboardController
`index` (KPI periode aktif Y-m; tahun tersedia dari `LEFT(period_key,4)`) + AJAX: `getTrendAjax`, `getProgressTrendAjax`, `getStatusPieAjax`, `getTotalInventoryByType`, `getRiskInsightAjax` (top 5 item & area not_ok + trend 12 bulan), `getPendingChecklistAjax` (hari kerja, missing per periode). ⚠️ route `risk-trend`→`getRiskTrendAjax` dan `dashboard/data`→`ajaxData`: method tidak ada → error bila dipanggil.

### ComplianceReportController
`index` (kategori), `getItemTypeByCategory`, `getInventoryByType` (JSON), `loadAjax` → `buildReportData($inventoryId, $year, $month)` → partial `_table`. buildReportData juga dipakai `ExportPdfController@recap`.

### ProgressController (admin/compliance)
`index`, `getProgressAjax` (progres per user per bulan; cocokkan PIC via REGEXP nama depan), `export` (CSV via re-use getProgressAjax), `getUserDetailAjax`, `sendReminderAjax` (WA Fonnte; butuh pending>0, WA siap, nomor ada).

### ComplianceRankingController
`index`: ranking per `ym` dari `checklist_logs` (join frequency item type): per user (checked_by string): total unik (inventory|period|slot), ontime/late (aturan per frekuensi), ok/not_ok/na, skor = ontime×10 + late×3.

### ComplianceEvidenceController
`index` (filter item type), `getEvidenceAjax` (status=not_ok & punya foto; filter tahun/item/follow-up; paginate 12 → `_grid`), `detail` (`_detail`), `updateFollowUp` (AJAX; status open|monitoring|closed; note ≤1000; date=hari ini).

### CompliancePrintController
`index` (admin/compliance/auditor), `item`/`itemPreview` (pilih inventory+tahun+bulan → preview), `inventoryByType` (partial), `batch`/`batchPreview` (PDF kolektif per item type+bulan via EamsPdf type `batch_form`; layout per item dgn kolom & judul hard-coded).

### ExportPdfController
`single($inventoryId, $periodKey)` → EamsPdf `single`; `recap($inventoryId, $year, $month)` → delegate `ComplianceReportController::buildReportData` → type by frequency (daily / daily_toilet bila item 52 / weekly / recap_year_item utk monthly).

### ComplianceQuestionnaireController (58KB)
- CRUD kuesioner (guardWrite admin/compliance) + `bootstrapDefaultsIfNeeded` di constructor (auto-seed 2 template dari `ComplianceQuestionnaireCatalog` bila tabel kosong — side effect di constructor!).
- Questions: store/update/delete/reorder/move-up/down (`sort_order` kelipatan 10; resequence setelah mutasi); hapus pertanyaan ditolak bila sudah ada jawaban (409).
- Publik: `publicFill($slug)`/`publicSubmit`/`publicThanks` (tanpa login); kuesioner nonaktif → 404.
- `submit/handleSubmit`: validasi required per pertanyaan (kecuali auto-timestamp "Tanggal pengisian formulir"), respondent name wajib bila collect_name; transaksi: responses + insertBatch answers.
- `analytics` (HTML atau JSON+html partial), `exportExcel` (XLSX semua respon), `responsePdf` (EamsPdf `questionnaire_response`), `updateSubmittedAt` (audit), `deleteResponse`.

### ThermalImagingController
`index/create/store/show/pdf` + `storeLocation` (AJAX, admin/compliance; nama unik — reaktivasi bila sudah ada nonaktif). store: validasi tanggal + inspector + facility; minimal 1 baris; tiap baris: lokasi aktif valid, celsius numerik, foto image ≤5MB → `uploads/thermal-imaging/Y/m`; transaksi report+items.

### EmsReportController
Index + 4 report (water/electric/stationary/mobile) + `ghgSummary`. Save = AJAX autosave (read-only → 403; JSON berisi dataset + summaryHtml + csrfHash). Tahun dinormalisasi 2026..max(2030, tahun berjalan). Semua kalkulasi (intensity, emission, perubahan YoY, baseline) di controller.

### FdmDataCollectionController
`index` (2 dari 3 koleksi placeholder "soon"), `productionSection` (boot dataset), `saveProductionSection` (AJAX; retailers_json + workforce_json; hapus retailer yang tidak dikirim lagi; workforce key tetap `full_time_employee`).

### BoilerFuelController / IpalController / PdamWaterController / PdamWaterBoilerController
Pola sama: `index` (grid bulanan, holiday merah), `save` (upsert; PDAM: per tanggal unik; Boiler: multi baris per tanggal via id), `delete`, `export` (XLSX PhpSpreadsheet, hari libur fill merah, nama hari Indonesia) / PDAM juga `exportPdf` (Dompdf landscape, view `pdam_water*/export_pdf`). Role PDAM: admin/compliance/office (403 JSON bila bukan). Boiler/IPAL tidak membatasi role di method (hanya filter global auth+write).

### ITAssetController
`index/ajax` (filter kategori IT, type, keyword, perPage 20/50/100), `detail` (current + history assignment), `assignForm/assignSave` (tutup assignment lama, insert baru; employee harus aktif; audit), `create/store`, `edit/update` (status `rusak` → auto-return semua assignment aktif). Upload foto ≤2MB ke `uploads/assets`.

### EmployeeController
CRUD employees (query builder langsung), validasi manual (employee_id wajib & unik, name/division/position wajib, foto ≤2MB), activate/deactivate, delete (ditolak bila ada assignment aktif; warning bila ada riwayat), `unassign` (set returned_at).

### ITDeviceController
`index` (KPI), `ajax` (list+search+paginate), `stats` (JSON KPI), `detail`/`detailFragment`, `sendCommand`, `remoteAction` (8 aksi whitelist) → `queueRemoteCommand` (lock 25 dtk, 1 command antrian, push→fallback polling), insights (patch pending, lisensi, storage <15%, offline, remote lock, hasil aksi terakhir).

### Api\AgentController (extends CodeIgniter\Controller — BUKAN BaseController; tanpa session)
`heartbeat` (upsert device by token/mac/hostname; device baru → auto buat `assets` IT-PC-###; simpan state JSON di `it_devices.cpu`; balas interval+command+client_profile), `command` (throttle 5 dtk; pop queued command → mark dispatched), `agentUpdate` (track stable/win7/xp; cari installer terbaru di public/downloads/agent; force_update flag), `pushUpdate` (auth — dipanggil dari UI).

### PatrolController
Semua query via `Database::connect()` (tanpa model). Rule: role check per halaman (`canAccess/canViewDashboard/canEditLayout`); sesi 1 aktif/user/hari; scan berurutan barcode + foto wajib + GPS ≤ radius_m; auto-complete; cancel; saveLayout (admin; gambar + transform + posisi checkpoint).

### SettingsController
`index` (section user|company|email|whatsapp; company/email/whatsapp → admin/compliance); `changePassword` multi-aksi (password / saveCompanySettings / saveEmailSettings / saveWhatsAppSettings / saveNotificationContact / markNotificationsRead). Template email/WA dengan placeholder `{{name}}, {{title}}, {{message}}, {{url}}, {{company}}, {{date}}`.

### UserController
Manajemen user + role (`storeRole`), page access per user (JSON), validasi identitas unik (username/email), proteksi akun admin, sinkron session bila user mengedit dirinya.

### AuditLogController (admin)
`index`: filter q/action/status, 300 log terbaru, daftar action distinct, 150 sesi (auto-expire >8 jam), summary (total/today/failed/active_sessions).

### BackupController (admin)
Thin wrapper ke `BackupManager` + `schtasks` exec untuk auto backup harian 01:00 (Windows only).

### DashboardController / ITController / Home.php
- `DashboardController@index`: agregat assets (kategori IT/Compliance, rusak, pemakai komputer) → `dashboard/index`.
- `ITController@index`: view `it/index` (kartu workspace).
- `Home.php`: `welcome_message` — **DEAD** (tidak ter-route).

## Error handling

- AJAX kebanyakan mengembalikan `ok:false|status:error` + HTTP 4xx/5xx; beberapa endpoint membungkus try/catch dgn `log_message('error', ...)`.
- `PageNotFoundException` untuk inventory/kuesioner tidak ditemukan.
- Redirect `/unauthorized` dipakai luas **tapi route tsb tidak terdaftar** → menghasilkan 404 (lihat 15).
