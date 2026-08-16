# 16 — Laravel Migration Considerations

> Bukan desain baru — hanya catatan agar hasil audit ini bisa dipakai aman saat rebuild. **Jangan mulai implementasi sebelum item Q-001 s/d Q-018 (dokumen 15) diputuskan.**

## A. Dependency map antar modul (dari hasil audit)

```
users / user_roles / auth / session
  ↓
app_settings (branding, email, WA) ────────────────────┐
areas, inventory_categories, asset_item_types          │
holidays                                               │
  ↓                                                    │
compliance_inventory (+compliance_inventory_pics, QR)  │
  ↓                                                    │
checklist_master (pertanyaan per item type)            │
  ↓                                                    │
checklist_logs (engine periode: period_helper dsb.)    │
  ↓                                                    │
├─ Home (tugas personal)                               │
├─ Dashboard compliance                                │
├─ Progress + Ranking                                  │
├─ Evidence center                                     │
├─ Report (buildReportData)                            │
│    ↓                                                 │
│  ExportPdf (single/recap) + Print Center (batch) ◄───┤
├─ Notifications + Reminder commands (WA/email) ◄──────┤
│                                                      │
compliance_calendar_events (independen, ringan)        │
thermal_imaging_* (independen)                         │
questionnaires (independen; publik)                    │
boiler_fuel_logs / ipal_logs / pdam_* (independen)     │
ems_* / fdm_* (independen)                             │
employees → assets → asset_assignments (independen)    │
it_devices → it_device_commands → agent API            │
patrol_* (independen; butuh users)                     │
audit_logs / login_sessions (cross-cutting)            │
backups (paling akhir; operasional)                    │
```

## B. Recommended rebuild order (murni dari dependency audit)

1. **Fondasi:** users, user_roles, auth + session + page_access/permission (AuthFilter/WriteFilter → middleware), audit_logs, login_sessions.
2. **Settings & branding:** app_settings (dipakai PDF, notifikasi, WA/email) + NotificationService (dipakai model inventory & command).
3. **Master data compliance:** areas, inventory_categories, asset_item_types (termasuk `checklist_frequency`, `allow_na`), holidays (dipakai engine periode & semua laporan harian!).
4. **Compliance inventory + PIC + QR** (butuh users utk pics & notifikasi).
5. **Checklist master + engine periode + checklist_logs** (period key, late, editable, offday, slot toilet, status ok/not_ok/na, anti-duplikat, not_ok wajib bukti).
6. **Kanal pengisian:** form per item → generic grid → 11 grid khusus (prioritaskan yang daily: CCTV, First Aid Content, Gate — paling sering dipakai).
7. **Home, dashboard, progress, ranking, evidence** (semua membaca logs).
8. **Report + PDF/print + export Excel** (EamsPdf → pilih 1 engine; branding dari settings).
9. **Reminder & notifikasi lanjutan** (dedupe, template, kanal WA/email).
10. **Modul independen** (urutan bebas): calendar+holidays UI, thermal imaging, questionnaire, boiler/ipal/pdam, EMS, FDM, employees/IT assets, patrol.
11. **IT Device monitoring + Agent API** (protokol heartbeat/command/update harus identik agar agent existing tetap jalan — lihat D).
12. **Backups & tools admin** (ganti schtasks → Laravel scheduler/queue).

## C. Keputusan teknis yang disarankan audit (menunggu keputusan manusia dulu)

| Area legacy | Saran Laravel (berdasar evidence) | Terkait |
|---|---|---|
| 2 famili tabel checklist | Satu skema: `checklist_masters`, `checklist_logs` (+`time_slot`, `follow_up_*` resmi) | TD-01, Q-001/003/014 |
| Frekuensi di item type | Tetap di `asset_item_types.checklist_frequency` (enum) | BR-01/02 |
| Engine periode | Service `ChecklistPeriodService` (period_key, late, editable, offday, status) — 1 sumber kebenaran | TD-07/08, Q-004/005 |
| checked_by string | `checked_by_user_id` FK + snapshot nama | Q-006 |
| PIC ganda | Hanya relasi `compliance_inventory_pics`; hapus parsing nama | Q-007, TD-15 |
| God controller | Pecah: InventoryController, ChecklistFormController, ChecklistGridController (generic + strategi per tipe), QrController | TD-12/13/14 |
| Grid khusus per item | Strategi/konfigurasi per item type (bukan ID hard-coded): definisi kolom grid/print di DB atau config per type | TD-13/14 |
| QR eksternal | endroid/qr-code lokal | TD-26 |
| PDF ganda (mPDF+Dompdf) | 1 engine (Dompdf terbukti di server) + template Blade | 11-A |
| page_access JSON | tabel pivot + policy/gate | 12-B |
| it_devices.cpu JSON | pecah state agent ke kolom/tabel; pertahankan endpoint & payload | TD-16, D |
| schtasks Windows | Laravel Scheduler (`schedule:run` via cron/Task Scheduler generik) | TD-28 |
| /unauthorized 404 | halaman 403 resmi | Q-010 |
| pdfAccess tak terpasang | middleware `can:export-pdf` eksplisit | Q-008 |

## D. Kompatibilitas yang WAJIB dijaga

1. **Protokol Agent API** (`POST /api/agent/heartbeat|command|update`): nama field, struktur respons (`status, device_token, heartbeat_interval, command_poll_interval, command, client_profile, remote_lock_until, server_time`), identitas device (token→mac→hostname), file installer naming — agent Windows yang sudah terpasang bergantung penuh pada ini.
2. **Format `period_key`** (`Y-m-d`, `Y-m-Wn`, `Y-m`) — seluruh histori checklist memakainya.
3. **URL publik kuesioner** `/kuesioner/{slug}` — sudah dibagikan ke responden.
4. **URL detail inventory** `/compliance/inventory/detail/{id}` — **tercetak di semua QR code fisik**.
5. **Nilai status** `ok/not_ok/na` (+ mapping `ng`) dan label UI (Sesuai/Tidak Sesuai/NA).
6. **Uploads**: path `uploads/{checklist,inventory,qr,employees,users,patrol,thermal-imaging}/…` direferensikan dari DB — migrasi file harus memetakan path.
7. **app_settings keys** yang dipakai kode: company_name, company_address, company_logo, document_footer, document_signatory_name/title, notification_email_enabled, notification_whatsapp_enabled, notification_whatsapp_webhook, notification_whatsapp_token, email_smtp_*, email_from_*, email_*_template, whatsapp_message_template.

## E. Migrasi data (checklist untuk fase berikutnya — bukan fase ini)

- [ ] Dump skema + data produksi (jawaban Q-001/002/003).
- [ ] Peta `users` lama → baru (email nullable, wa_number, page_access JSON → pivot).
- [ ] `checklist_logs`: normalisasi status (`ng`→`not_ok`?), checked_by nama → user id (perlu tabel korek nama).
- [ ] `compliance_inventory.pic` → pics (sudah ada pola backfill di migration 2026-08-07-000002).
- [ ] Uploads: salin `public/uploads/**` + update path bila pindah ke `storage/app/public`.
- [ ] `it_devices.cpu` JSON → struktur baru (dengan fallback baca format lama selama transisi agent).

## F. Risiko terbesar saat rebuild (dari audit)

1. **Skema DB tidak lengkap di repo** (Q-002) — rebuild tanpa dump produksi berisiko kehilangan kolom/constraint.
2. **Engine periode** adalah jantung bisnis (late/offday/editable) — harus di-port persis + test unit dari contoh tanggal nyata.
3. **Grid per item type** dengan kolom dari teks pertanyaan — perlu sesi mapping bersama user bisnis.
4. **Agent API** — salah kontrak = semua device offline.
5. **Kontrak AJAX** (`ok/status, message, csrfHash`, rotasi token) — bila UI lama dipakai ulang sementara, harus kompatibel.
