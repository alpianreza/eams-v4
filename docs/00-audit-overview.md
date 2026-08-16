# 00 — Audit Overview

**Project:** EAMS — Enterprise Asset & Compliance Management System
**Perusahaan:** PT Younghyun Star (pabrik garmen, Cibadak — Sukabumi, Jawa Barat)
**Repository sumber:** `alpianreza/eams` (private, branch `main`, commit audit `78dd2a0`)
**Framework:** CodeIgniter 4 (folder `system/` di-commit ke repo, BUKAN via `composer require`)
**PHP:** ^8.1 · **DB:** MySQL/MariaDB (MySQLi, default DB `asset_compliance_system`) · **Timezone:** Asia/Jakarta
**Tanggal audit:** 2026-08-16 · **Sifat:** READ-ONLY (Fase 0 — tanpa perubahan kode)

## Tujuan dokumen

Dokumentasi ini adalah *source of truth* hasil audit menyeluruh EAMS CI4 sebelum rebuild ke Laravel (repo target: `alpianreza/eams-v4`). Semua business rule dilabeli:

- **CONFIRMED** — dapat dibuktikan langsung dari kode/database.
- **INFERRED** — sangat mungkin berdasarkan implementasi, belum 100% pasti.
- **AMBIGUOUS** — informasi tidak cukup → **NEED HUMAN DECISION**.
- **UNKNOWN** — belum ditemukan.

## Statistik repository (hasil audit)

| Komponen | Jumlah | Catatan |
|---|---|---|
| Controllers | 37 file | 33 terpakai via route; 4 tidak ter-route (dead) |
| Models | 40 file | Mayoritas tipis (table + allowedFields) |
| Migrations | 32 | 2026-01-19 s/d 2026-08-10 |
| Seeders | 4 | AssetItemType, ChecklistMaster, ChecklistSchedule, ChecklistTemplate |
| Views | 162 file PHP | termasuk PDF template & error pages |
| JS (public/js) | 30 file | Vanilla JS + Alpine.js + jQuery |
| Routes | ±240 definisi | semua di `app/Config/Routes.php` |
| Custom Filters | 5 | auth, admin, write, pdfAccess, csrfasset |
| Custom Helpers | 14 | termasuk engine periode checklist |
| Libraries/Services | 4 + 2 | BackupManager, EamsPdf, NotificationService, ComplianceQuestionnaireCatalog, QrService, WhatsAppService |
| CLI Commands | 4 | backup:daily, it:status, notify:weekly-checklist, notify:weekly-checklist-email |
| Tabel DB (migrations) | 40 | terdokumentasi penuh di repo |
| Tabel DB (tanpa migration) | ±16 | schema direkonstruksi dari model/query — lihat 03 & 15 |

## Modul yang ditemukan (berdasarkan bukti kode, bukan nama folder)

1. **Authentication & Security** — login/logout, throttle, session 8 jam, audit log, login sessions.
2. **Authorization & User Management** — role + permission read/write + page_access per user, menu catalog 28 halaman.
3. **Home / Notification Center** — tugas checklist personal + notifikasi in-app.
4. **Compliance Inventory** — master inventory fasilitas + QR code + PIC.
5. **Checklist Master** — pertanyaan checklist per item type + frekuensi.
6. **Checklist Execution** — form per-item + 12 grid khusus (CCTV, APAR, Hydrant, dll) + generic grid + slot toilet.
7. **Compliance Dashboard** — KPI, tren, status pie, risk insight, pending checklist.
8. **Monitoring Progress & Ranking** — progres per user, skor ketepatan, reminder WA.
9. **Evidence Center** — temuan not_ok + foto + follow-up (open/monitoring/closed).
10. **Report & Print Center** — laporan per item, PDF single/recap/batch (mPDF→Dompdf), export Excel.
11. **Compliance Calendar & Holidays** — event kalender + hari libur nasional.
12. **Thermal Imaging** — laporan inspeksi termal + PDF.
13. **Questionnaire (Kuesioner)** — publik via slug, analitik, export Excel/PDF.
14. **EMS Report** — konsumsi air/listrik/solar/LPG/scrap/petrol + GHG summary (CO2e).
15. **FDM Data Collection** — data produksi per retailer + tenaga kerja (bulanan).
16. **Boiler & Utility** — Boiler Fuel, IPAL, PDAM Water, PDAM Water Boiler (log harian).
17. **IT Asset & Employees** — asset IT, assignment ke karyawan.
18. **IT Device Monitoring (Device Control)** — agent heartbeat, remote command, push update.
19. **Patrol Security** — rute/checkpoint patroli, scan barcode + GPS + foto.
20. **Backup System** — backup DB/file/penuh, restore, retensi 30 hari, Windows Task Scheduler.
21. **Settings** — identitas perusahaan, SMTP email, WhatsApp, template pesan, ganti password.

## Cara membaca dokumentasi ini

- `01` Arsitektur & peta sistem · `02` Detail per modul · `03` Database · `04` Routes · `05` Controllers · `06` Models · `07` Views/UI · `08` JS/AJAX · `09` Business rules umum · `10` Aturan checklist (inti) · `11` Report/PDF/Export · `12` Auth & authorization · `13` Dependencies · `14` Technical debt · `15` Ambiguitas / need human decision · `16` Pertimbangan migrasi Laravel.

## Batasan audit (penting)

- Database produksi tidak tersedia → tabel dasar (users, employees, compliance_inventory, assets, it_devices, holidays, boiler_fuel_logs, ipal_logs, checklist_master, dll) **tidak punya migration di repo**; strukturnya direkonstruksi dari model & query → ditandai INFERRED/UNKNOWN.
- File `vendor/` dan `.env` tidak ada di repo (sesuai .gitignore) — versi library dari `composer.json`/`composer.lock`.
- Behaviour runtime (cron aktual, isi `app_settings` produksi, data seed aktual di DB) tidak dapat diverifikasi dari repo.
