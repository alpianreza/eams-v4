# 09 — Business Rules (seluruh sistem)

Format: **Rule → Description → Source (file/method) → Evidence → Status → Impact → Laravel Consideration**.
Status: `CONFIRMED` (terbukti dari kode) / `INFERRED` (sangat mungkin) / `AMBIGUOUS` (perlu keputusan) / `UNKNOWN`.

---

## A. Periode & Kalender Checklist

### BR-01 Format period_key
- **Rule:** daily = `YYYY-MM-DD`; weekly = `YYYY-MM-Wn` (n=1..4); monthly = `YYYY-MM`.
- **Source:** `app/Helpers/period_helper.php` `generate_period_key()`.
- **Evidence:** daily return `$date`; weekly mapping tanggal 1–7→W1, 8–14→W2, 15–21→W3, 22+→W4; monthly `date('Y-m')`.
- **Status:** CONFIRMED.
- **Impact:** checklist, dashboard, report, ranking, reminder, PDF.
- **Laravel:** pertahankan format; jadikan value object/enum `ChecklistPeriod`.

### BR-02 Minggu adalah irisan bulan (bukan ISO week)
- **Rule:** W1–W4 ditentukan dari tanggal dalam bulan (1–7, 8–14, 15–21, 22–akhir bulan), reset tiap bulan.
- **Source:** `period_helper.php generate_period_key`; `calender_period_helper.php` & `checklist_helper.php` `generate_calendar_periods`; `ComplianceRankingController::weekEndDate`.
- **Evidence:** rentang hard-coded `[1,7],[8,14],[15,21],[22, daysInMonth]`; label "Minggu ke-n".
- **Status:** CONFIRMED.
- **Impact:** semua perhitungan weekly.
- **Laravel:** jangan pakai `Carbon::weekOfYear`; implementasi ulang irisan bulan.

### BR-03 Late (keterlambatan)
- **Rule:** daily → late bila `tanggal periode + 21 hari < now`; weekly → late bila `hari pertama minggu + 28 hari < now`; monthly → late bila `tanggal 1 bulan itu + 3 bulan < now`.
- **Source:** `period_helper.php is_period_late()`.
- **Evidence:** `$limit = (clone $date)->modify('+21 days')`, `'+28 days' // 4 minggu`, `'+3 months'`.
- **Status:** CONFIRMED.
- **Impact:** home, progress, reminder, notifikasi sidebar.
- **Laravel:** jadikan konfigurasi (`config/eams.php`) bukan hard-code.

### BR-04 Periode future terkunci; editable window
- **Rule:** future tidak bisa diisi. daily: editable bila tidak future. monthly: selalu editable (backfill tanpa batas). weekly: editable bila selisih bulan ≤ 3 (`graceMonths=3`).
- **Source:** `period_helper.php is_period_editable()`, `is_period_future()`.
- **Status:** CONFIRMED. (Catatan: backfill monthly tanpa batas waktu — keputusan sadar, ada komentar "tetap bisa dibuka untuk backfill".)
- **Impact:** form checklist, grid save.
- **Laravel:** pertahankan; uji batas weekly 3 bulan.

### BR-05 Status periode (kalender UI)
- **Rule (implementasi A — yang menang saat autoload):** `done` (ada log) > `future` > `late` > `pending`.
- **Source:** `period_helper.php resolve_period_status()`.
- **Evidence:** cek `ChecklistLogModel where inventory_id+period_key` dulu, lalu future, lalu late.
- **Status:** CONFIRMED untuk perilaku runtime (lihat BR-06 untuk konfliknya).

### BR-06 ⚠️ Dua implementasi resolve_period_status yang berbeda
- **Rule:** `period_status_helper.php` mendefinisikan fungsi bernama sama dgn hasil berbeda: `done` > daily(`holiday`/`locked`/`open`), weekly/monthly(`locked` bila future, else `open`). Karena keduanya di-autoload (`Config\Autoload::$helpers = ['audit','ui','period','checklist','calender_period','period_status',...]`), yang terdefinisi lebih dulu yang menang (`period` sebelum `period_status`) — namun helper bisa juga dimuat manual per controller (`helper(['period','checklist'])` dsb.).
- **Status:** CONFIRMED (duplikasi ada); **AMBIGUOUS** untuk "semantik mana yang diinginkan bisnis" → NEED HUMAN DECISION.

### BR-07 Hari non-kerja (offday)
- **Rule:** Minggu selalu libur; Sabtu libur **hanya untuk tanggal ≥ 2026-04-01**; + tanggal di tabel `holidays`.
- **Source:** `checklist_helper.php is_weekend_offday()` / `is_date_offday()` / `holiday_dates_between()`.
- **Evidence:** `if ($dayOfWeek === 0) return true; if ($dayOfWeek === 6 && $date >= '2026-04-01') return true;`.
- **Status:** CONFIRMED.
- **Impact:** daily checklist (generate & blokir), home, progress, PDAM/boiler export (warna), kalender.
- **Laravel:** jadikan aturan "Saturday off effective date" sebagai setting; **konfirmasi tanggal efektif & perlakuan Sabtu sebelumnya** (lihat 15).
- ⚠️ Konflik: `holiday_helper.php is_holiday()` menganggap **Sabtu+Minggu selalu libur** (tanpa tanggal efektif). Dipakai `period_status_helper`. → INFERRED legacy; dokumentasikan sebagai inkonsistensi (14/15).

### BR-08 Blokir pengisian pada hari libur (daily)
- **Rule:** checklist daily tidak dapat diisi pada offday.
- **Source:** `ComplianceInventoryController::submitChecklist` (`is_date_offday($periodKey, …)` → error), `saveCctvGrid`, `saveFirstAidContentGrid`, `saveGenericGrid` (daily branch); UI: `checklist()` set `lockReason='offday'`.
- **Status:** CONFIRMED.

## B. Pengisian Checklist (form & grid)

### BR-09 Anti-duplikat periode
- **Rule:** 1 log-set per (inventory_id, period_key[, time_slot]). Submit ulang ditolak ("sudah diisi").
- **Source:** `submitChecklist` (`$existsQuery->first()`), `checklist()` lockReason='done'.
- **Status:** CONFIRMED. (Grid memperbolehkan update sel existing = koreksi; form full-submit tidak.)

### BR-10 not_ok wajib bukti
- **Rule:** status `not_ok` wajib remark atau foto (min salah satu).
- **Source:** `submitChecklist` (`in_array($status, ['not_ok','ng']) && remark==='' && !photo` → error); `checklist.js validateChecklistForm` (aturan sama di client).
- **Status:** CONFIRMED.

### BR-11 Nilai status
- **Rule:** UI/kode memakai `ok`, `not_ok`, `na`; input legacy `ng` dipetakan ke `not_ok` saat submit; default tak dikenal → `na`.
- **Source:** `submitChecklist` `match ($status) { 'ok'=>'ok', 'not_ok'=>'not_ok', 'ng'=>'not_ok', 'na'=>'na', default=>'na' }`.
- **Status:** CONFIRMED di kode. ⚠️ ENUM DB (migration) = `ok|ng|na` → konflik dgn `not_ok` (AMBIGUOUS — DDL aktual perlu dicek di DB produksi; lihat 15).

### BR-12 NA hanya bila diizinkan item type
- **Rule:** opsi NA tampil hanya bila `asset_item_types.allow_na` truthy.
- **Source:** `_form.php` (`!empty($inventory['allow_na'])`); kolom di-select di `checklist()`/`genericGrid()`.
- **Status:** CONFIRMED (kolom tidak ada di migration → DDL UNKNOWN).

### BR-13 checked_by = nama user (string)
- **Rule:** `checklist_logs.checked_by` diisi `session()->get('name')` (string), bukan user id.
- **Source:** `submitChecklist`, semua `save*Grid`, `markAll*`.
- **Status:** CONFIRMED. **Laravel:** kandidat migrasi ke FK user (lihat 15-16).

### BR-14 Toilet checklist 3 slot
- **Rule:** item_type_id=52 = checklist toilet harian dengan 3 slot waktu: `PG` (Pagi), `SI` (Siang), `SO` (Sore); slot wajib dipilih; lock dilakukan per (inventory, periode, slot).
- **Source:** konstanta `TOILET_CHECKLIST_ITEM_TYPE_ID=52`; `checklist()` (slot required → lockReason 'slot'), `submitChecklist` (time_slot), `genericGrid` (baris per slot), `ComplianceReportController` (grid per slot).
- **Status:** CONFIRMED.

### BR-15 Grid "mark all" tidak menimpa data
- **Rule:** mark-all hanya mengisi sel kosong (`skip` existing); pesan: "Centang semua hanya mengisi sel kosong...".
- **Source:** semua `markAll*Grid` (guard `isset($existingMap[...])`). Pengecualian: `markAllHeatDetectorGrid` **mengupdate** existing ke 'ok' (tidak skip!) — inkonsistensi.
- **Status:** CONFIRMED.

### BR-16 Grid "clear" menghapus log
- **Rule:** mode `clear` = hard delete baris log sel tsb. Khusus CCTV: clear hanya menghapus status ok/not_ok; sel `na` ditolak (409, arahkan ke halaman detail).
- **Source:** `saveCctvGrid`, `save*Grid` lain.
- **Status:** CONFIRMED.

### BR-17 check_date vs tanggal pengisian
- **Rule:** form submit: `check_date = hari ini` (bukan tanggal periode!). Grid daily (CCTV/FAC/gate): `check_date = periodKey`. Grid weekly: `check_date = bulan-01` (`preg_replace('/-W[1-4]$/','-01',…)`). Grid monthly: `check_date = periodKey.'-01'`.
- **Source:** `submitChecklist` (`'check_date' => date('Y-m-d')`), `saveGenericGrid` (cabang per frekuensi), `saveCctvGrid`, `markAllIntrusionAlarmGrid`.
- **Status:** CONFIRMED. ⚠️ Ini memengaruhi perhitungan on-time ranking (BR-18) — INFERRED sebagai penyebab deviasi ranking antara form vs grid.

### BR-18 On-time (ranking)
- **Rule:** daily on-time bila `check_date ≤ period_key`; weekly on-time bila `check_date ≤ hari ke-7 minggu`; monthly on-time bila `check_date ≤ akhir bulan`. Skor = ontime×10 + late×3.
- **Source:** `ComplianceRankingController@index` + `weekEndDate`.
- **Status:** CONFIRMED.

## C. Inventory & QR

### BR-19 Auto asset_code
- **Rule:** `KODEKATEGORI-KODEITEM-###` (nomor urut 3 digit per prefix, dari record terakhir `LIKE prefix%` ORDER BY id DESC).
- **Source:** `ComplianceInventoryController::store` & `update`.
- **Status:** CONFIRMED. (Risiko race condition & gap bila hapus — dicatat di 14.)

### BR-20 QR = URL detail, gambar dari API eksternal
- **Rule:** QR berisi `base_url('compliance/inventory/detail/{id}')`; PNG 300×300 dari `api.qrserver.com`, asset code ditulis di tengah (GD font 5, box putih); file `public/uploads/qr/qr_inv_{id}.png`; regenerate saat asset_code berubah.
- **Source:** `Services/QrService.php`, `ComplianceInventoryController::store/update/regenerateQr`.
- **Status:** CONFIRMED. **Laravel:** ganti dengan `endroid/qr-code` (sudah ada di composer tapi tidak dipakai) untuk menghapus dependency eksternal.

### BR-21 PIC maksimal 2 & notifikasi assignment
- **Rule:** maks 2 PIC per inventory; PIC pertama = primary; perubahan PIC memicu notifikasi "Penugasan inventory baru" (dedupe per inventory+user).
- **Source:** `ComplianceInventoryModel` callbacks; migration 2026-08-07-000002 (backfill dari kolom teks).
- **Status:** CONFIRMED.

### BR-22 Status inventory
- **Rule:** nilai yang dipahami UI: `Good`, `Need Repair`, `Not Active` (badge: Baik/Perlu Perbaikan/Tidak Aktif; baris kuning utk Need Repair, abu utk Not Active).
- **Source:** `public/js/inventory.js getStatusMeta` + `updateInventoryRowFromEditForm`.
- **Status:** CONFIRMED (level UI; kolom DB bebas teks — INFERRED daftar nilai lengkap).

## D. Reminder & Notifikasi

### BR-23 Reminder mingguan (WA & email)
- **Rule:** command CLI `notify:weekly-checklist` (WA Fonnte) & `notify:weekly-checklist-email` (notifikasi in-app + email) mengirim ke user aktif yang punya pending checklist pada periode berjalan (per tanggal acuan). Maks 15 item/pesan (default). Email command membuat notifikasi `reminder` dgn dedupe `weekly_email_reminder:{date}:{userId}` (tidak kirim WA ulang).
- **Source:** `app/Commands/WeeklyChecklistWhatsappReminder.php`, `WeeklyChecklistEmailReminder.php`, `Libraries/NotificationService.php`.
- **Status:** CONFIRMED. Jadwal cron disarankan Senin 08:00/08:05 (README).
- ⚠️ WA command mencocokkan PIC lewat **parsing nama** (min 1 kata cocok); email command memakai relasi pics (`assignedToUser`) → dua mekanisme berbeda (14/15).

### BR-24 Notifikasi in-app + kanal
- **Rule:** `notifications` dgn `dedupe_key` unik (idempoten); setiap notifikasi mencoba email (bila `notification_email_enabled=1` & SMTP valid) & WA webhook (bila `notification_whatsapp_enabled=1` & nomor ada); status kanal dicatat (`sent|failed|disabled|missing_target|skipped`).
- **Source:** `Libraries/NotificationService.php`.
- **Status:** CONFIRMED.

### BR-25 Sidebar notif = unread + pending checklist
- **Rule:** badge = unreadCount + jumlah periode pending milik user (cache 300 dtk).
- **Source:** `BaseController::loadNotifications/calculateChecklistReminders`.
- **Status:** CONFIRMED.

## E. EMS / FDM / Utility

### BR-26 Faktor emisi GHG (CONFIRMED, `EmsReportController` const)
- Listrik 0.87 kgCO2e/kWh; Solar 2.69 kg/L; LPG 2.984 kg/kg; Scrap 1.8 kg/kg; Petrol 2.28 kg/L. Emission ton = konsumsi × faktor ÷ 1000. Scope 1 = stationary+mobile; Scope 2 = listrik (market-based); grand total = S1+S2.

### BR-27 Rentang laporan EMS/FDM
- Tahun 2026–2030 (diperluas hingga tahun berjalan); baseline 2026; target label "-2% s/d -5%" (hard-coded). Water punya seed 2025 (data nyata) sebagai pembanding.
- **Status:** CONFIRMED.

### BR-28 PDAM 1 entri per tanggal
- `pdam_water_logs.log_date` & `pdam_water_boiler_logs.log_date` UNIQUE; save = upsert by tanggal. Role: admin/compliance/office.
- **Status:** CONFIRMED.

### BR-29 Boiler multi-entri per hari; index menampilkan SUM(polybag), SUM(kg) per tanggal; export bulanan menandai hari libur merah + total.
- **Status:** CONFIRMED (`BoilerFuelController`).

### BR-30 IPAL upsert per tanggal (tanpa unique DB — dedupe by query); field: start/stop meter, pemakaian, ket.
- **Status:** CONFIRMED (`IpalController::save`).

## F. IT Asset & Device

### BR-31 Assignment asset
- Assign: tutup assignment aktif (returned_at=now) → insert baru; employee harus aktif; butuh write. Status asset `rusak` saat update → semua assignment aktif di-return otomatis.
- **Source:** `ITAssetController::assignSave/update`.
- **Status:** CONFIRMED.

### BR-32 Employee tidak bisa dihapus bila punya assignment aktif; bila ada riwayat → disarankan nonaktif.
- **Source:** `EmployeeController::delete`. **Status:** CONFIRMED.

### BR-33 Device health score (UI Device Control)
- Skor 100 − penalti: pending_updates >20→−30, ≥5→−15, ≥1→−5; OS not activated →−25; storage free <10%→−25, <20%→−10; cpu_usage>90→−10; last_seen >72 jam→−25, >24 jam→−10. Label: ≥80 Sehat, ≥50 Waspada, <50 Kritis.
- **Source:** `app/Helpers/device_helper.php device_risk_score/device_risk_label`.
- **Status:** CONFIRMED. (Legacy alternatif: `it_health_helper` — Win7 −40, not activated −30, pending>5 −20; lihat 14.)

### BR-34 Online/offline device
- Helper UI: online bila `now − last_seen ≤ max(172800 dtk, 2×interval)` (default ≥48 jam). Command `it:status`: offline bila `> 600 dtk`.
- **Status:** CONFIRMED keduanya → **inkonsisten** (15).

### BR-35 Agent identity & auto-register
- Device dikenali berurutan: device_token → mac_address → hostname. Heartbeat pertama membuat device + asset `IT-PC-###` (category_id=1, status 'aktif').
- **Source:** `Api\AgentController::heartbeat/findDeviceByIdentity/generateInventoryNo`.
- **Status:** CONFIRMED.

### BR-36 Remote command queue
- 1 command antrian per device; `remote_lock_until = now + 25 dtk`; push ke `http://{ip}:8765/command` dgn device_token; gagal → polling. Whitelist aksi: restart, shutdown, update, sync, restart_agent, lock, logoff, popup_message.
- **Source:** `ITDeviceController::queueRemoteCommand/remoteAction`, `Api\AgentController::command/popQueuedCommand`.
- **Status:** CONFIRMED.

## G. Patrol

### BR-37 Sesi patrol
- 1 sesi aktif per user per hari; rute baru ditolak (409) selama ada sesi aktif; sesi `completed` otomatis saat semua checkpoint dicek; `canceled` manual.
- **Source:** `PatrolController::startSession/scanCheckpoint/cancelSession`.
- **Status:** CONFIRMED.

### BR-38 Scan checkpoint
- Wajib urut sesuai rute (`nextCheckpoint`); barcode cocok (normalize uppercase tanpa spasi); **foto wajib ≥1**; **GPS wajib dalam radius_m** (default 10 m, haversine); status ok/not_ok + note opsional.
- **Status:** CONFIRMED.

## H. Backup & Sistem

### BR-39 Backup
- 3 jenis (database/files/full); penamaan `backup-{database|file|penuh|harian}-Ymd-His`; retensi 30 hari; full zip = `database.sql` + `manifest.json` + uploads; restore DB via mysqli multi_query; auto harian 01:00 via Windows schtasks "EAMS Daily Backup".
- **Source:** `Libraries/BackupManager.php`, `BackupController`, `Commands/DailyBackup`.
- **Status:** CONFIRMED.

### BR-40 Login security
- Throttle 5/menit/IP; dummy hash utk user tak dikenal; session regenerate; session idle 8 jam → expired; audit penuh (login/logout/failed/blocked + konteks device).
- **Status:** CONFIRMED.
