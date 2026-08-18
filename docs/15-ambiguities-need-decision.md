# 15 — Ambiguities / Need Human Decision (Final Decision List)

> **Aturan:** jangan memilih interpretasi sendiri. Setiap item di bawah butuh jawaban manusia sebelum/di saat rebuild Laravel.
> **Update Fase 0.5 (2026-08-18):** hasil cross-check menambah 8 item baru (Q-019 s/d Q-026) dan seluruh item kini **dikelompokkan per prioritas**. Total **26 keputusan**: **Critical 7 · Important 12 · Minor 7**.
> Kontradiksi teknis antar sumber dicatat terpisah di `docs/17-business-specification.md` Appendix A (CONFLICT register); dokumen ini fokus pada **keputusan** yang harus diambil manusia.

---

## Ringkasan Prioritas

| Prioritas | Kapan diputuskan | Item |
|---|---|---|
| **Critical** | **Harus** sebelum Laravel coding (memblokir schema & core engine) | Q-001, Q-002, Q-003, Q-004, Q-006, Q-007, Q-023 |
| **Important** | Sebaiknya sebelum module terkait dibuat | Q-005, Q-008, Q-009, Q-012, Q-013, Q-014, Q-016, Q-017, Q-018, Q-019, Q-020, Q-024 |
| **Minor** | Bisa diputuskan saat implementation | Q-010, Q-011, Q-015, Q-021, Q-022, Q-025, Q-026 |

---

# CRITICAL — harus diputuskan sebelum Laravel coding

### Q-001 — ENUM `checklist_logs.status` vs nilai `not_ok`
- **Evidence:** Migration `2026-01-20-000003_CreateChecklistLogs` membuat `status ENUM('ok','ng','na') DEFAULT 'ok'`; tetapi `submitChecklist` & semua grid menulis `'not_ok'`; report/dashboard/evidence membaca `'not_ok'`.
- **Why ambiguous:** DDL di repo tidak bisa menghasilkan data yang kode tulis → DB produksi hampir pasti sudah diubah manual (ENUM diperluas atau kolom VARCHAR). Nilai mana yang sah di produksi (`ng` masih ada? `not_ok` resmi?) tidak dapat dibuktikan dari repo.
- **Possible interpretations:** A. ENUM aktual = ok/not_ok/na (ng dibuang). B. Kolom diubah ke VARCHAR. C. Ada data lama `ng` yang masih dipakai (submit masih memetakan ng→not_ok).
- **Recommendation:** NEED HUMAN DECISION — minta `SHOW CREATE TABLE checklist_logs` dari DB produksi + cek distinct nilai status.
- **Kenapa Critical:** migration Laravel untuk tabel inti checklist tidak bisa ditulis tanpa DDL final.

### Q-002 — DDL tabel dasar tidak ada di repo
- **Evidence:** tidak ada migration untuk `users, employees, areas, inventory_categories, compliance_inventory, assets, asset_categories, asset_assignments, it_devices, it_device_logs, holidays, boiler_fuel_logs, ipal_logs, checklist_master, compliance_checklist_master, compliance_checklist_log_items`.
- **Why ambiguous:** struktur hanya bisa direkonstruksi dari model/query (INFERRED). Nullable/index/unik tidak diketahui.
- **Possible interpretations:** A. DB dibuat manual/dari dump pra-repo. B. Ada migration yang dihapus.
- **Recommendation:** NEED HUMAN DECISION — minta dump skema produksi (tanpa data) sebagai referensi Laravel migration.
- **Kenapa Critical:** ±16 tabel dasar adalah fondasi seluruh migration Laravel.

### Q-003 — Kolom tanpa migration
- **Evidence:** `asset_item_types.checklist_frequency` & `allow_na`, `checklist_logs.time_slot`, `checklist_logs.follow_up_status/note/date` dipakai kode tapi tidak dibuat migration mana pun. (Fase 0.5 menambah: `users.permission`, `users.wa_number`, `users.photo`, `compliance_inventory.active`, `it_devices.cpu` JSON state.)
- **Recommendation:** NEED HUMAN DECISION — verifikasi DDL aktual + nilai default di produksi.
- **Kenapa Critical:** kolom-kolom ini dipakai engine checklist & auth — schema Laravel salah bila diasumsikan.

### Q-004 — Semantik status periode mana yang benar?
- **Evidence:** `period_helper::resolve_period_status` → done/future/late/pending; `period_status_helper::resolve_period_status` → done/holiday/locked/open; keduanya di-autoload (urutan menentukan pemenang). (Terkait CONF-022: `generate_calendar_periods` juga ganda.)
- **Why ambiguous:** dua bahasa status untuk UI kalender; tidak jelas mana yang disepakati bisnis (apakah "late" dan "pending" dibedakan? apakah "holiday" status tersendiri?).
- **Recommendation:** NEED HUMAN DECISION — pilih satu mesin status; dokumentasikan matrix status final untuk Laravel.
- **Kenapa Critical:** engine periode adalah jantung checklist; harus tunggal sebelum coding.

### Q-006 — `checked_by` string nama vs FK user
- **Evidence:** `checklist_logs.checked_by` = `session name` (string); laporan/ranking group by string ini; tabel lain (patrol, compliance_checklist_logs) pakai INT user id.
- **Why ambiguous:** dua user bernama sama akan tercampur; rename user merusak histori tampilan.
- **Possible interpretations:** A. Tetap string (audit trail beku). B. Migrasi ke user_id FK + simpan snapshot nama.
- **Recommendation:** NEED HUMAN DECISION.
- **Kenapa Critical:** menentukan kolom & relasi tabel checklist_logs di Laravel + strategi migrasi data histori.

### Q-007 — Dua mekanisme PIC (nama vs relasi)
- **Evidence:** `compliance_inventory_pics` (relasi, max 2, is_primary) dibuat 2026-08-07 + backfill dari kolom teks `pic`; namun `ProgressController` & `WeeklyChecklistWhatsappReminder` masih mencocokkan **nama** (REGEXP/LIKE) pada kolom `pic`. (Fase 0.5: `_modal_edit.php` mengonfirmasi UI menggabung 2 dropdown user aktif → satu kolom teks `pic` dgn separator `" - "`.)
- **Why ambiguous:** mana sumber kebenaran PIC untuk progres/reminder? Kolom teks `pic` masih ditulis (model callback) dan dibaca sebagian jalur.
- **Recommendation:** NEED HUMAN DECISION — migrasi penuh ke relasi pics atau pertahankan kompatibilitas ganda.
- **Kenapa Critical:** memengaruhi schema (simpan/buang kolom `pic`), relasi, dan seluruh logika penugasan/notifikasi.

### Q-023 — Kolom `updated_at` pada checklist_logs (baru, Fase 0.5)
- **Evidence:** 12+ lokasi `save*Grid` menulis `'updated_at' => date('Y-m-d H:i:s')` (terverifikasi via grep), tetapi `ChecklistLogModel::$allowedFields` **tidak** memuat `updated_at` dan migration `2026-01-20-000003` tidak membuat kolomnya → nilai **dibuang diam-diam** oleh CI4.
- **Why ambiguous:** apakah `updated_at` memang diinginkan (jejak audit koreksi grid) atau sisa kode yang tidak pernah hidup?
- **Possible interpretations:** A. Tambah kolom + allowedFields (Laravel: pakai timestamps). B. Hapus penulisan `updated_at` (koreksi grid tidak perlu jejak waktu terpisah dari created_at).
- **Recommendation:** NEED HUMAN DECISION.
- **Kenapa Critical:** menentukan apakah tabel checklist_logs Laravel memakai `timestamps()` penuh.

---

# IMPORTANT — sebaiknya diputuskan sebelum module terkait dibuat

### Q-005 — Aturan Sabtu sebelum 2026-04-01
- **Evidence:** `is_weekend_offday`: Sabtu libur hanya `>= 2026-04-01`; `is_holiday` (legacy): Sabtu selalu libur.
- **Why ambiguous:** untuk histori checklist sebelum April 2026, apakah Sabtu dianggap hari kerja wajib? Memengaruhi hitung late/pending historis.
- **Recommendation:** NEED HUMAN DECISION — konfirmasi kebijakan & tanggal efektif; putuskan apakah re-kalkulasi histori diperlukan saat migrasi data.
- **Modul terkait:** Checklist (engine periode), Report historis.

### Q-008 — Penegakan PDF access
- **Evidence:** `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']` ada; route `export/pdf/*` hanya pakai `auth`; `pdfAccess` tidak dipasang di mana pun.
- **Why ambiguous:** apakah PDF memang untuk semua user login, atau niat awal admin-only belum selesai dipasang?
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Reports & PDF.

### Q-009 — Route dashboard yang method-nya tidak ada
- **Evidence:** Routes: `compliance/dashboard/risk-trend → ComplianceDashboardController::getRiskTrendAjax` dan `compliance/dashboard/data → ::ajaxData`; kedua method tidak ada di controller saat audit; `dashboard.js` berpotensi memanggilnya.
- **Why ambiguous:** fitur dihapus sebagian (route ketinggalan) atau method belum ditulis?
- **Recommendation:** NEED HUMAN DECISION — hapus route atau kembalikan fitur di Laravel.
- **Modul terkait:** Dashboard.

### Q-012 — Threshold online device: 600 dtk vs 48 jam
- **Evidence:** `Commands/DeviceStatusCheck`: offline bila last_seen > 600 dtk; `device_helper::device_is_online`: threshold `max(172800, interval×2)` (≥48 jam).
- **Recommendation:** NEED HUMAN DECISION — satu sumber kebenaran status online.
- **Modul terkait:** IT Device Monitoring.

### Q-013 — `require_photo` pada master pertanyaan tidak ditegakkan
- **Evidence:** `checklist_master.require_photo` bisa diset di UI master; submitChecklist hanya mewajibkan foto/remark untuk `not_ok`; tidak ada validasi "foto wajib bila require_photo=1".
- **Possible interpretations:** A. by design (foto hanya utk temuan). B. validasi hilang (bug).
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Master & Execution.

### Q-014 — Tabel & model `compliance_checklist_master` / `compliance_checklist_log_items`
- **Evidence:** model ada; migration tidak ada; route aktif tidak memakai. (Fase 0.5: `ComplianceChecklistLogModel` mendefinisikan kolom `inspection_week/month/year` yang **tidak cocok** dgn migration `compliance_checklist_logs` = `schedule_id/template_id/check_date`.)
- **Recommendation:** NEED HUMAN DECISION — konfirmasi apakah tabel ini masih ada/dipakai di produksi atau murni sisa eksperimen.
- **Modul terkait:** Checklist (pembersihan famili tabel ganda).

### Q-016 — `category_id=1` untuk asset otomatis dari agent
- **Evidence:** `Api\AgentController::heartbeat` membuat asset baru dgn `category_id=1` + `inventory_no IT-PC-###`.
- **Why ambiguous:** mengasumsikan kategori id=1 = kategori IT — tidak diverifikasi (tidak ada seeder kategori di repo).
- **Recommendation:** NEED HUMAN DECISION (di Laravel: lookup by kode, bukan id).
- **Modul terkait:** IT Device Monitoring / Agent API.

### Q-017 — Nilai `status` asset & inventory bebas teks
- **Evidence:** assets.status dipakai 'aktif' (agent), 'rusak' (UI); compliance_inventory.status: Good/Need Repair/Not Active (Fase 0.5: terkonfirmasi penuh via `_modal_edit.php`).
- **Recommendation:** NEED HUMAN DECISION — jadikan enum resmi di Laravel.
- **Modul terkait:** IT Assets, Compliance Inventory.

### Q-018 — `app_settings` produksi & template pesan aktual
- **Evidence:** nilai default diseed migration; nilai aktual (nama perusahaan, logo, template email/WA, token) berada di DB produksi.
- **Recommendation:** NEED HUMAN DECISION — ekspor app_settings produksi saat migrasi (tanpa secret di repo).
- **Modul terkait:** Settings, Notifications (cutover).

### Q-019 — Definisi "late" pada KPI dashboard (baru, Fase 0.5)
- **Evidence:** `period_helper::is_period_late` = time-based (daily +21 hari, weekly +28 hari, monthly +3 bulan); `ComplianceDashboardController` KPI late = **history-based** (inventory punya histori log tapi belum ada log pada periode aktif).
- **Why ambiguous:** dua definisi late menghasilkan angka berbeda antara KPI dashboard vs badge/reminder/progress.
- **Possible interpretations:** A. Time-based (helper) jadi standar tunggal. B. History-based khusus dashboard. C. Keduanya dipertahankan dgn label berbeda ("Terlambat" vs "Belum Diisi").
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Dashboard, Home, Progress, Reminder.

### Q-020 — Dukungan status NA tidak konsisten antar kanal (baru, Fase 0.5)
- **Evidence:** form per-item menerima `na` bila `allow_na`; grid Emergency Light & Emergency Exit Light menerima mode `na`; grid CCTV menolak mengubah sel `na` (409); grid lain (First Aid Box/Content, APAR, Intrusion Alarm, Hydrant, Heat/Smoke Detector, Gate, generic) **tidak** menerima mode `na` sama sekali.
- **Why ambiguous:** apakah pembatasan NA per kanal/item memang disengaja, atau belum dirapikan?
- **Possible interpretations:** A. NA hanya via form/detail (grid = ok/not_ok saja). B. NA tersedia di semua kanal sesuai `allow_na` item type.
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Execution (semua grid).

### Q-024 — Mark-all Heat Detector menimpa status existing (baru, Fase 0.5)
- **Evidence:** semua `markAll*Grid` skip sel terisi (pesan: "Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa."); `markAllHeatDetectorGrid` justru **meng-update** existing menjadi `ok`.
- **Why ambiguous:** inkonsistensi perilaku bulk; berisiko menimpa koreksi manual.
- **Possible interpretations:** A. Heat Detector disamakan dgn yang lain (skip). B. Perilaku menimpa memang disengaja (dokumentasikan alasannya).
- **Recommendation:** NEED HUMAN DECISION.
- **Modul terkait:** Checklist Execution (grid Heat Detector).

---

# MINOR — bisa diputuskan saat implementation

### Q-010 — `/unauthorized` tidak terdaftar
- **Evidence:** banyak controller `redirect()->to('/unauthorized')`; route tidak ada di Routes.php → CI4 404.
- **Recommendation:** NEED HUMAN DECISION — di Laravel sediakan halaman 403 resmi.

### Q-011 — Weekly editable window 3 bulan vs bulanan tanpa batas
- **Evidence:** `is_period_editable`: weekly grace 3 bulan (`graceMonths=3`), monthly selalu true (backfill), daily selalu true (selama tidak future).
- **Why ambiguous:** asimetri weekly vs monthly mungkin disengaja (komentar "backfill") atau belum dirapikan.
- **Recommendation:** NEED HUMAN DECISION.

### Q-015 — Jadwal produksi nyata (cron)
- **Evidence:** README menyarankan cron Senin untuk reminder; `it:status` tidak terdokumentasi; auto-backup via UI (schtasks Windows).
- **Recommendation:** NEED HUMAN DECISION — inventarisasi scheduler aktual di server produksi sebelum migrasi.

### Q-021 — User read-only tidak dapat self-service settings (baru, Fase 0.5)
- **Evidence:** `WriteFilter` memblokir semua POST non-whitelist untuk `permission=read`; halaman Settings (ganti password/kontak, tandai notifikasi terbaca) memakai POST ke `settings/change-password` dan `/settings` tidak masuk whitelist → ikut terblokir.
- **Why ambiguous:** apakah read-only memang dilarang total (termasuk mengubah passwordnya sendiri) atau ini oversight?
- **Possible interpretations:** A. By design (read-only benar-benar pasif). B. Kecualikan self-service settings dari WriteFilter.
- **Recommendation:** NEED HUMAN DECISION.

### Q-022 — Kebijakan foreign key ke tabel users (baru, Fase 0.5)
- **Evidence:** migration `audit_logs` membuat FK ke `users(id)`; migration `notifications` sengaja tanpa FK dgn komentar "legacy users table differs between installations (signedness, engine, id type)".
- **Why ambiguous:** dua pendekatan berlawanan terhadap tabel legacy yang sama; implikasi integritas data di Laravel.
- **Possible interpretations:** A. FK penuh setelah normalisasi tabel users. B. Tanpa FK (konvensi aplikasi) demi kompatibilitas.
- **Recommendation:** NEED HUMAN DECISION.

### Q-025 — Agent API menerima mutasi via GET (baru, Fase 0.5)
- **Evidence:** `Api\AgentController::resolvePayload` membaca payload dari **query GET** bila `device_token/hostname/mac` ada; heartbeat/command mengubah state (last_seen, JSON cpu, ack command) → mutasi dapat dipicu GET.
- **Why ambiguous:** keamanan & semantik HTTP; namun agent Windows yang sudah terpasang mungkin bergantung pada perilaku ini.
- **Possible interpretations:** A. Batasi ke POST di Laravel (verifikasi dulu agent lama tidak pakai GET). B. Pertahankan kompatibilitas GET.
- **Recommendation:** NEED HUMAN DECISION — cek implementasi agent aktual di lapangan.

### Q-026 — Validasi upload foto tidak konsisten (baru, Fase 0.5)
- **Evidence:** foto checklist (`submitChecklist`) dipindah **tanpa** validasi mime/size; `updatePhoto` inventory validasi mime saja (tanpa size); IT asset & employee foto validasi mime + size 2MB.
- **Why ambiguous:** celah keamanan & standar yang tidak seragam; belum jelas batas yang diinginkan bisnis (ukuran maks, tipe file).
- **Possible interpretations:** A. Standar seragam (mime gambar + batas size) untuk semua upload. B. Biarkan per modul.
- **Recommendation:** NEED HUMAN DECISION (rekomendasi teknis: seragamkan).

---

> **Total: 26 keputusan (Critical 7 · Important 12 · Minor 7).** Tidak ada satu pun yang diputuskan oleh auditor. Setiap keputusan yang sudah dijawab manusia harus dicatat kembali ke dokumen ini (tambahkan `Decision:` + `Decided by/date:` per item) sebelum Laravel coding dimulai.
