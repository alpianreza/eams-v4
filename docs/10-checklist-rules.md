# 10 — Checklist Rules (Audit Khusus Sistem Checklist)

Ini dokumen terpenting untuk rebuild. Semua rule di bawah ditelusuri dari kode aktif (route → controller → helper → DB).

## 1. Arsitektur checklist aktif

```
inventory_categories (FS/HSE/CTPAT/UTL/…)
  ↓ 1-n [FK]
asset_item_types  ── checklist_frequency (daily|weekly|monthly), allow_na
  ↓ 1-n [FK RESTRICT]
compliance_inventory ── pic (teks) ⇄ compliance_inventory_pics (relasi user)
  ↓ 1-n [FK CASCADE]
checklist_logs (period_key, status, remark, photo, checked_by, time_slot, follow_up_*)
  ↑ referensi pertanyaan
checklist_master (item_type_id, question, require_photo, active)
```

- **Pertanyaan** diambil dari `checklist_master` per `item_type_id` (`active=1`, urut id). (CONFIRMED: semua controller grid & form)
- **Frekuensi** dibaca dari `asset_item_types.checklist_frequency` (bukan dari `checklist_schedules`/`compliance_checklist_schedules`). (CONFIRMED: HomeController, ComplianceInventoryController, ProgressController, Dashboard, Reminder commands)
- **Log** ditulis ke `checklist_logs` dengan `checklist_template_id = checklist_master.id`. (CONFIRMED: submitChecklist & semua save*Grid)
- ⚠️ Tabel `checklist_templates`/`checklist_schedules` (migration 2026-01-20) dan `compliance_checklist_*` (2026-01-19) **tidak dipakai jalur aktif** → legacy (14).

## 2. Kalender & period key

| Frekuensi | period_key | Jumlah periode/bulan | Generate |
|---|---|---|---|
| daily | `YYYY-MM-DD` | semua hari dalam bulan (offday ditandai, tidak bisa diisi) | `generate_calendar_periods('daily', y, m)` |
| weekly | `YYYY-MM-W1..W4` | 4 (W1: tgl 1–7, W2: 8–14, W3: 15–21, W4: 22–akhir) | idem `'weekly'` |
| monthly | `YYYY-MM` | 1 | idem `'monthly'` (12 per tahun) |

- Label: daily `d M Y`; weekly `M Y • Minggu ke-n` / `period_label` "Minggu n Bulan YYYY"; monthly `F Y`.
- ⚠️ Ada DUA `generate_calendar_periods` (checklist_helper: rentang tetap; calender_period_helper: kursor +7 hari, break bila keluar bulan). Autoload memuat `checklist` lebih dulu → versi rentang tetap yang menang (CONFIRMED duplikasi; perbedaan edge case kecil — AMBIGUOUS dampak, lihat 15).

## 3. Status per periode (kalender UI)

- `resolve_period_status` (yang menang): `done` bila ada log; else `future` bila periode future; else `late` bila lewat batas late; else `pending`.
- `is_period_editable`: future → false; daily → true; monthly → true (backfill); weekly → ≤3 bulan grace.
- Form dikunci (`isLocked` + `lockReason`): `slot` (toilet belum pilih slot) → `offday` (daily libur) → `done` (sudah ada log) → `future` → `expired` (tidak editable).

## 4. Aturan pengisian (submit form per item)

`POST /compliance/checklist/submit` (`submitChecklist`):
1. Role: admin/compliance/staff (else redirect /unauthorized).
2. Inventory harus ada; frequency dari item type.
3. Toilet (52): `time_slot` wajib (PG/SI/SO).
4. Anti-duplikat: existing log (inventory+period[+slot]) → error "sudah diisi".
5. Daily: tolak bila offday.
6. Per pertanyaan: map status (`ok|not_ok|ng→not_ok|na|default na`); **not_ok wajib remark atau foto**; foto → `public/uploads/checklist/{random}`.
7. Insert per pertanyaan: `check_date = date('Y-m-d')` (hari ini!), `period_key`, `time_slot`, `checked_by = session name`, `created_at = now`.
8. Redirect ke detail dgn flash success.

## 5. Aturan grid (AJAX)

Pola `save*Grid`: AJAX only; validasi format period_key per frekuensi (regex); validasi item_type cocok konstanta; pertanyaan harus active & milik item type; mode `ok|not_ok|na*|clear` (*na hanya grid monthly EL/EEL); existing → update (status, checked_by=session name, check_date); else insert; `clear` → delete log.

| Grid | item_type | Frekuensi | period_key format | Mode | Role tulis | Role baca | mark-all |
|---|---|---|---|---|---|---|---|
| CCTV | 13 | daily | Y-m-d | ok/not_ok/clear (na dilindungi) | admin/compliance/staff | +auditor | hari kerja, skip terisi |
| Emergency Light | 4 | monthly | Y-m | ok/not_ok/na/clear | admin/compliance | sama | skip terisi |
| Emergency Exit Light | 59 | monthly | Y-m | idem | admin/compliance | sama | skip terisi |
| First Aid Box | 10 | monthly | Y-m | ok/not_ok/clear | admin/compliance | sama | skip terisi |
| First Aid Content | 33 | daily | Y-m-d | ok/not_ok/clear | admin/compliance | sama | hari kerja, skip terisi |
| Fire Extinguisher | 1 | monthly | Y-m | ok/not_ok/clear | admin/compliance | sama | skip terisi |
| Intrusion Alarm | 8 | weekly | Y-m-Wn | ok/not_ok/clear | admin/compliance | sama | W yg editable saja, skip terisi |
| Hydrant | 2 | weekly | Y-m-Wn | ok/not_ok/clear | admin/compliance | sama | idem |
| Smoke Detector | 7 | monthly | Y-m | ok/not_ok/clear | admin/compliance | sama | skip terisi |
| Heat Detector | 6 | monthly | Y-m | ok/not_ok/clear | admin/compliance | sama | ⚠️ **menimpa existing jadi ok** |
| Gate | 40 | daily | Y-m-d | ok/not_ok/clear | admin/compliance | sama | hari kerja, skip terisi |
| Generic | semua | daily/weekly/monthly | sesuai frekuensi (+slot toilet) | ok/not_ok/clear | admin/compliance/staff | sama | (ada markAllGenericGrid) |

Aturan khusus grid:
- Daily grid menolak offday (`is_date_offday`).
- Weekly/monthly grid menolak future / non-editable (`is_period_future || !is_period_editable`) — generic & intrusion alarm mark-all.
- `saveCctvGrid` melarang mengubah sel `na` (409 → arahkan ke detail item).
- check_date grid: daily = periodKey; weekly = Y-m-01; monthly = Y-m-01.

## 6. Kolom grid khusus (tightly coupled ke data master)

- **Fire Extinguisher:** kolom dikelompokkan "Tabung APAR" (Kapasitas=type_description, Tanggal Kadaluarsa=expired_date + pertanyaan urut: Pressure Gauge, Pin/Segel, Selang, Klem Selang, Handle, Kondisi Fisik, Petunjuk Pemakaian) & "Kondisi APAR" (Tidak Terhalang, Mudah Dijangkau, Bersih, Siap Pakai). Pemetaan by **teks pertanyaan persis** (`resolveFireExtinguisherGridColumns`).
- **Emergency Light / Exit Light:** grup "Lampu Darurat/EXIT": kolom Berfungsi Baik/Tidak Pecah/Kabel dipetakan dari kata kunci pertanyaan (`berfun`, `pecah`, `kabel`); pertanyaan mengandung "jenis" di-skip (EEL).
- **Fire/Intrusion Alarm:** pertanyaan × W1–W4; urutan pertanyaan preferensial (Kerapihan Kabel, Lampu Indikator, Arus Listrik, Fungsi Alarm, Suara, Kebersihan, Manual Push Button) lalu sisanya.
- **CCTV:** 1 pertanyaan aktif pertama; nama tampilan = remark atau "Camera N" dari angka di asset_code; di `pdf/batch_partials/cctv_table.php` nomor inspeksi 33/34/35 dipetakan ke Monitor/Hardisk/DVR.
- **Hydrant:** label "Hydrant N" dari angka di asset_code/specific_area.
- ⚠️ Semua pemetaan ini rapuh terhadap perubahan teks master → dokumentasi sebagai debt (14); di Laravel gunakan `sort_order`/`key` eksplisit.

## 7. Laporan & histori

- **Detail inventory** (`detail`): rekap bulan (total periode, ok, not_ok, late-by-helper) + grid harian/mingguan + histori per periode (group by period_key, MAX(check_date), MAX(checked_by), paginate 10) + detail monthly (join pertanyaan).
- **Report** (`ComplianceReportController::buildReportData`): monthly → grid pertanyaan×12 bulan (period_key prefix tahun); daily → pertanyaan×hari (toilet: ×slot PG/SI/SO); weekly → pertanyaan×W1-4; findings = not_ok (+foto); checker per periode = `checked_by`+`check_date` terakhir; prev/next inventory by `asset_code`.
- **Evidence:** not_ok + foto wajib; follow-up open/monitoring/closed.
- **Ranking:** lihat BR-18.

## 8. Print checklist

- Single item per periode: `export/pdf/single/{id}/{periodKey}` (EamsPdf `single`, view `pdf/single_item`, status→✓/✗/-, portrait).
- Recap: `export/pdf/recap/{id}/{year}/{month}` → daily (landscape, `pdf/recap_daily`), daily toilet (`recap_daily_toilet`), weekly (`recap_weekly`), monthly → tahunan per item (`recap_item_yearly`).
- Batch per item type+bulan: `compliance/print/batch/preview` (EamsPdf `batch_form`, A4 landscape, margin 7/6mm) — "form kolektif" + findings not_ok dgn foto.

## 9. Traceability rule checklist → sumber

| Rule | Source utama | Status |
|---|---|---|
| Format period_key | `period_helper.php generate_period_key` | CONFIRMED |
| Late 21d/28d/3mo | `period_helper.php is_period_late` | CONFIRMED |
| Editable/future | `period_helper.php is_period_editable/is_period_future` | CONFIRMED |
| Offday (Min + Sab≥2026-04-01 + holidays) | `checklist_helper.php` | CONFIRMED |
| Status periode (done/future/late/pending) | `period_helper.php resolve_period_status` | CONFIRMED (menang autoload) |
| Status periode alternatif (done/holiday/open/locked) | `period_status_helper.php` | CONFIRMED ada, tidak menang → 15 |
| Lock form (slot/offday/done/future/expired) | `ComplianceInventoryController::checklist` | CONFIRMED |
| Anti-duplikat & not_ok wajib bukti | `::submitChecklist` | CONFIRMED |
| Slot toilet PG/SI/SO | konstanta 52 + submitChecklist + genericGrid | CONFIRMED |
| Grid mode ok/not_ok/na/clear | `::save*Grid` | CONFIRMED |
| Mark-all hanya isi kosong | `::markAll*Grid` | CONFIRMED (kecuali Heat Detector menimpa) |
| check_date=hari ini di form | `::submitChecklist` | CONFIRMED |
| checked_by=nama string | semua write path | CONFIRMED |
| Frekuensi dari item type | `asset_item_types.checklist_frequency` | CONFIRMED (DDL UNKNOWN) |
| Pertanyaan dari checklist_master | `ChecklistMasterModel` | CONFIRMED (DDL UNKNOWN) |
| require_photo flag master | checklist_master.require_photo | CONFIRMED dipakai di master; ⚠️ tidak ditegakkan saat submit (foto wajib hanya utk not_ok) → 15 |
