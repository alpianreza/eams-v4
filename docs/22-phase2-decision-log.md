# 22 — Phase-2 Human Decisions (2026-08-19)

> Kelanjutan `docs/19-decision-log.md`. Lima keputusan NEED HUMAN DECISION dijawab Project Owner pada 2026-08-19. Tiga sisanya (Q-015, Q-016, Q-018) masih menunggu data produksi.

---

### Q-011 — Jendela backfill checklist (weekly vs monthly) → **RESOLVED**
- **Decision:** **Pertahankan asimetri legacy** — weekly editable ≤ **3 bulan** (grace), monthly **tanpa batas** (backfill penuh). Daily editable selama bukan future/offday.
- **Reason:** monthly backfill memang dibutuhkan operasional; weekly 3-bulan cukup untuk koreksi.
- **Implementasi:** `ChecklistPeriod::isEditable` — weekly dibatasi `start + 3 bulan`, monthly tak dibatasi.
- **Test:** `Phase2DecisionsTest::test_weekly_backfill_has_three_month_grace_but_monthly_unlimited`.

### Q-019 — Definisi "late" di KPI dashboard → **RESOLVED**
- **Decision:** **Satukan ke time-based** (period engine `ChecklistPeriod`, BR-03 +21/+28/+3 bln). Dashboard TIDAK pakai definisi history-based terpisah.
- **Reason:** satu sumber kebenaran; konsisten dengan engine periode tunggal (Q-004).
- **Implementasi:** diterapkan saat modul Dashboard dibangun — KPI late memakai `ChecklistPeriod` (time-based).
- **Status kode:** terekam untuk pembangunan Dashboard (belum ada modul dashboard terpisah).

### Q-021 — User read-only & self-service → **RESOLVED**
- **Decision:** **Boleh** — user read-only DAPAT mengubah password/kontaknya sendiri. Self-service (`settings/*`) dikecualikan dari write-guard.
- **Reason:** keamanan akun (ganti password) adalah hak tiap user; write-guard untuk data bisnis, bukan self-service.
- **Implementasi:** `config/eams.php` write_whitelist += `settings`, `settings/*`; `SelfServiceController` (ganti password).
- **Test:** `Phase2DecisionsTest::test_read_only_user_can_change_own_password` + `..._still_blocked_from_other_writes`.

### Q-024 — Mark-all Heat Detector menimpa → **RESOLVED**
- **Decision:** **Samakan jadi skip** — mark-all TIDAK menimpa sel yang sudah terisi, untuk SEMUA grid (tanpa pengecualian Heat Detector).
- **Reason:** jangan menimpa data yang sudah ada secara diam-diam (BR-15 default).
- **Implementasi:** `SaveGridChecklist::markAll` sudah fill-empty-only untuk semua item type (patuh sejak awal).
- **Test:** `Phase2DecisionsTest::test_mark_all_skips_existing_cells`.

### Q-025 — Agent API terima mutasi via GET → **RESOLVED**
- **Decision:** **Tetap terima GET** (kompatibel agent Windows yang sudah deployed di lapangan). Kontrak agent harus identik.
- **Reason:** memutus GET akan merusak agent lapangan; kompatibilitas lebih penting di sini (keamanan dijaga via device_token).
- **Implementasi:** `routes/api.php` memakai `Route::match(['get','post'])` untuk heartbeat/command/update (patuh sejak awal).
- **Test:** `Phase2DecisionsTest::test_agent_api_accepts_get_heartbeat`.

---

## Masih terbuka (butuh data produksi)

- **Q-015** — inventarisasi scheduler/cron aktual di server produksi (siapa cek & kapan).
- **Q-016** — verifikasi `asset_categories.id=1` = kategori IT (cek isi tabel produksi).
- **Q-018** — ekspor `app_settings` produksi (token SMTP/Fonnte, template, nama perusahaan) saat cutover, tanpa secret di repo.
