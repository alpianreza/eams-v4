# 15 — Ambiguities / Need Human Decision

> Aturan: jangan memilih interpretasi sendiri. Setiap item di bawah butuh jawaban manusia sebelum/di saat rebuild Laravel.

---

### Q-001 — ENUM `checklist_logs.status` vs nilai `not_ok`
- **Evidence:** Migration `2026-01-20-000003_CreateChecklistLogs` membuat `status ENUM('ok','ng','na') DEFAULT 'ok'`; tetapi `submitChecklist` & semua grid menulis `'not_ok'`; report/dashboard/evidence membaca `'not_ok'`.
- **Why ambiguous:** DDL di repo tidak bisa menghasilkan data yang kode tulis → DB produksi hampir pasti sudah diubah manual (ENUM diperluas atau kolom VARCHAR). Nilai mana yang sah di produksi (`ng` masih ada? `not_ok` resmi?) tidak dapat dibuktikan dari repo.
- **Possible interpretations:** A. ENUM aktual = ok/not_ok/na (ng dibuang). B. Kolom diubah ke VARCHAR. C. Ada data lama `ng` yang masih dipakai (submit masih memetakan ng→not_ok).
- **Recommendation:** NEED HUMAN DECISION — minta `SHOW CREATE TABLE checklist_logs` dari DB produksi + cek distinct nilai status.

### Q-002 — DDL tabel dasar tidak ada di repo
- **Evidence:** tidak ada migration untuk `users, employees, areas, inventory_categories, compliance_inventory, assets, asset_categories, asset_assignments, it_devices, it_device_logs, holidays, boiler_fuel_logs, ipal_logs, checklist_master, compliance_checklist_master, compliance_checklist_log_items`.
- **Why ambiguous:** struktur hanya bisa direkonstruksi dari model/query (INFERRED). Nullable/index/unik tidak diketahui.
- **Possible interpretations:** A. DB dibuat manual/dari dump pra-repo. B. Ada migration yang dihapus.
- **Recommendation:** NEED HUMAN DECISION — minta dump skema produksi (tanpa data) sebagai referensi Laravel migration.

### Q-003 — Kolom tanpa migration
- **Evidence:** `asset_item_types.checklist_frequency` & `allow_na`, `checklist_logs.time_slot`, `checklist_logs.follow_up_status/note/date` dipakai kode tapi tidak dibuat migration mana pun.
- **Recommendation:** NEED HUMAN DECISION — verifikasi DDL aktual + nilai default di produksi.

### Q-004 — Semantik status periode mana yang benar?
- **Evidence:** `period_helper::resolve_period_status` → done/future/late/pending; `period_status_helper::resolve_period_status` → done/holiday/locked/open; keduanya di-autoload (urutan menentukan pemenang).
- **Why ambiguous:** dua bahasa status untuk UI kalender; tidak jelas mana yang disepakati bisnis (apakah "late" dan "pending" dibedakan? apakah "holiday" status tersendiri?).
- **Recommendation:** NEED HUMAN DECISION — pilih satu mesin status; dokumentasikan matrix status final untuk Laravel.

### Q-005 — Aturan Sabtu sebelum 2026-04-01
- **Evidence:** `is_weekend_offday`: Sabtu libur hanya `>= 2026-04-01`; `is_holiday` (legacy): Sabtu selalu libur.
- **Why ambiguous:** untuk histori checklist sebelum April 2026, apakah Sabtu dianggap hari kerja wajib? Memengaruhi hitung late/pending historis.
- **Recommendation:** NEED HUMAN DECISION — konfirmasi kebijakan & tanggal efektif; putuskan apakah re-kalkulasi histori diperlukan saat migrasi data.

### Q-006 — `checked_by` string nama vs FK user
- **Evidence:** `checklist_logs.checked_by` = `session name` (string); laporan/ranking group by string ini; tabel lain (patrol, compliance_checklist_logs) pakai INT user id.
- **Why ambiguous:** dua user bernama sama akan tercampur; rename user merusak histori tampilan.
- **Possible interpretations:** A. Tetap string (audit trail beku). B. Migrasi ke user_id FK + simpan snapshot nama.
- **Recommendation:** NEED HUMAN DECISION.

### Q-007 — Dua mekanisme PIC (nama vs relasi)
- **Evidence:** `compliance_inventory_pics` (relasi, max 2, is_primary) dibuat 2026-08-07 + backfill dari kolom teks `pic`; namun `ProgressController` & `WeeklyChecklistWhatsappReminder` masih mencocokkan **nama** (REGEXP/LIKE) pada kolom `pic`.
- **Why ambiguous:** mana sumber kebenaran PIC untuk progres/reminder? Kolom teks `pic` masih ditulis (model callback) dan dibaca sebagian jalur.
- **Recommendation:** NEED HUMAN DECISION — migrasi penuh ke relasi pics atau pertahankan kompatibilitas ganda.

### Q-008 — Penegakan PDF access
- **Evidence:** `PdfAccessFilter` + `Config\PdfPermission::$allowedRoles=['admin']` ada; route `export/pdf/*` hanya pakai `auth`; `pdfAccess` tidak dipasang di mana pun.
- **Why ambiguous:** apakah PDF memang untuk semua user login, atau niat awal admin-only belum selesai dipasang?
- **Recommendation:** NEED HUMAN DECISION.

### Q-009 — Route dashboard yang method-nya tidak ada
- **Evidence:** Routes: `compliance/dashboard/risk-trend → ComplianceDashboardController::getRiskTrendAjax` dan `compliance/dashboard/data → ::ajaxData`; kedua method tidak ada di controller saat audit.
- **Why ambiguous:** fitur dihapus sebagian (route ketinggalan) atau method belum ditulis? `dashboard.js` mungkin memanggilnya.
- **Recommendation:** NEED HUMAN DECISION — hapus route atau kembalikan fitur di Laravel.

### Q-010 — `/unauthorized` tidak terdaftar
- **Evidence:** banyak controller `redirect()->to('/unauthorized')`; route tidak ada di Routes.php → CI4 404.
- **Recommendation:** NEED HUMAN DECISION — di Laravel sediakan halaman 403 resmi.

### Q-011 — Weekly editable window 3 bulan vs bulanan tanpa batas
- **Evidence:** `is_period_editable`: weekly grace 3 bulan (`graceMonths=3`), monthly selalu true (backfill), daily selalu true (selama tidak future).
- **Why ambiguous:** asimetri weekly vs monthly mungkin disengaja (komentar "backfill") atau belum dirapikan.
- **Recommendation:** NEED HUMAN DECISION.

### Q-012 — Threshold online device: 600 dtk vs 48 jam
- **Evidence:** `Commands/DeviceStatusCheck`: offline bila last_seen > 600 dtk; `device_helper::device_is_online`: threshold `max(172800, interval×2)` (≥48 jam).
- **Recommendation:** NEED HUMAN DECISION — satu sumber kebenaran status online.

### Q-013 — `require_photo` pada master pertanyaan tidak ditegakkan
- **Evidence:** `checklist_master.require_photo` bisa diset di UI master; submitChecklist hanya mewajibkan foto/remark untuk `not_ok`; tidak ada validasi "foto wajib bila require_photo=1".
- **Possible interpretations:** A. by design (foto hanya utk temuan). B. validasi hilang (bug).
- **Recommendation:** NEED HUMAN DECISION.

### Q-014 — Tabel & model `compliance_checklist_master` / `compliance_checklist_log_items`
- **Evidence:** model ada; migration tidak ada; route aktif tidak memakai.
- **Recommendation:** NEED HUMAN DECISION — konfirmasi apakah tabel ini masih ada/dipakai di produksi atau murni sisa eksperimen.

### Q-015 — Jadwal produksi nyata (cron)
- **Evidence:** README menyarankan cron Senin untuk reminder; `it:status` tidak terdokumentasi; auto-backup via UI (schtasks Windows).
- **Recommendation:** NEED HUMAN DECISION — inventarisasi scheduler aktual di server produksi sebelum migrasi.

### Q-016 — `category_id=1` untuk asset otomatis dari agent
- **Evidence:** `Api\AgentController::heartbeat` membuat asset baru dgn `category_id=1` + `inventory_no IT-PC-###`.
- **Why ambiguous:** mengasumsikan kategori id=1 = kategori IT — tidak diverifikasi.
- **Recommendation:** NEED HUMAN DECISION (di Laravel: lookup by kode, bukan id).

### Q-017 — Nilai `status` asset & inventory bebas teks
- **Evidence:** assets.status dipakai 'aktif' (agent), 'rusak' (UI); compliance_inventory.status: Good/Need Repair/Not Active (JS).
- **Recommendation:** NEED HUMAN DECISION — jadikan enum resmi di Laravel.

### Q-018 — `app_settings` produksi & template pesan aktual
- **Evidence:** nilai default diseed migration; nilai aktual (nama perusahaan, logo, template email/WA, token) berada di DB produksi.
- **Recommendation:** NEED HUMAN DECISION — ekspor app_settings produksi saat migrasi (tanpa secret di repo).
