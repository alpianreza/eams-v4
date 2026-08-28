# 22 — EAMS UI/UX Page Map

> **Status:** DESIGN BLUEPRINT — Phase "Design First". Dokumen ini memetakan sitemap, hubungan antar halaman, dan pola navigasi EAMS versi Laravel (Livewire 4 + Tailwind 4).
> **Sumber aturan:** `docs/17-business-specification.md`, `docs/19-decision-log.md`, `docs/20-laravel-architecture.md`, `docs/21-ui-ux-design-system.md`.
> **Batas:** blueprint tidak mengubah business rule, route kontrak (termasuk QR legacy `compliance/inventory/detail/{id}`), authorization, atau storage contract. Blueprint adalah rancangan UI; implementasi menyusul per milestone.

---

## 1. Sitemap

```
Login (tanpa shell)
└─ Application Shell (sidebar + topbar + breadcrumb)
   ├─ Utama
   │  ├─ Home — Beranda (`/home`)
   │  ├─ Notifikasi (`/notifications`)
   │  └─ Settings (`/settings`, `/settings/password`)
   ├─ Compliance
   │  ├─ Dashboard (`/compliance/dashboard`)
   │  ├─ Inventory (`/compliance/inventory`)
   │  │   ├─ Create (`/compliance/inventory/create`)
   │  │   ├─ Detail (`/compliance/inventory/detail/{id}`) ← QR legacy entry point
   │  │   │   ├─ Checklist Fill (`/compliance/checklist/{inventory}/fill`)
   │  │   │   ├─ Grid Checklist (`/compliance/checklist-grid/{itemType}`)
   │  │   │   └─ Report PDF (`/compliance/report/{inventory}/pdf`)
   │  │   └─ Edit (`/compliance/inventory/{id}/edit`)
   │  ├─ Progress (`/progress`)
   │  ├─ Evidence (`/evidence`)
   │  ├─ Ranking (`/ranking`)
   │  ├─ Kalender (`/calendar`)
   │  ├─ Kuesioner (`/compliance/questionnaires` + publik `/kuesioner/{q}`)
   │  ├─ Thermal Imaging (`/thermal`)
   │  └─ Print Center (`/compliance/print`)
   ├─ Boiler & Utility
   │  ├─ Boiler (`/utility/boiler`)
   │  ├─ PDAM Water (`/utility/pdam-water`)
   │  ├─ PDAM Boiler (`/utility/pdam-water-boiler`)
   │  └─ IPAL (`/utility/ipal`)
   ├─ EMS / GHG (`/ems/{water|electric|stationary|mobile}`)
   ├─ Security → Patrol (`/patrol`, `/patrol/sessions/{session}`)
   ├─ IT
   │  ├─ Devices (`/it/devices`)
   │  ├─ IT Assets (`/it-assets`)
   │  └─ FDM (`/fdm`)
   ├─ Master Data
   │  ├─ Areas · Kategori · Item Types · Hari Libur · Karyawan
   │  └─ Checklist Master (`/compliance/checklist-master[/category/{id}/item/{id}]`)
   └─ Admin
      ├─ Users (`/users` + create/edit + page-access matrix)
      ├─ Audit Logs · Login Sessions · Backups
```

## 2. Hubungan antar halaman (relasi fungsional)

| Dari | Ke | Maksud |
|---|---|---|
| Dashboard | Inventory, Progress, Evidence, Ranking | quick link 4 arah |
| Inventory list | Inventory Detail, Create, Edit | operasi aset |
| Inventory Detail | Checklist Fill, Grid Checklist, Report PDF | eksekusi checklist per aset |
| Checklist Fill/Grid | Evidence | temuan `not_ok` masuk evidence center |
| Calendar | Checklist Fill | klik periode → isi |
| Progress | Reminder (notifikasi) | aksi remind per PIC |
| Ranking | — (read-only leaderboard) | — |
| Print Center | PDF preview | cetak per item / batch |
| Home | Checklist pending milik user | tugas personal |
| Semua halaman | Notifikasi (badge) | unread indicator |

## 3. Pola navigasi

1. **`wire:navigate`** untuk semua link GET internal yang aman (list → detail, menu sidebar, breadcrumb, quick link).
2. **Navigasi klasik** (full reload) untuk: form submit, download/export/PDF, link `files.show`, target `_blank`, halaman publik kuesioner.
3. **Breadcrumb** selalu: grup menu → halaman → objek (mis. Compliance → Inventory → FS-APAR-001).
4. **Deep link + back/forward** wajib berfungsi (state filter & pagination di URL query).
5. Sidebar collapse persist di desktop; drawer + backdrop di <1024px; Escape menutup semua overlay.

## 4. Entry points di luar shell

| Entry point | Halaman tujuan | Catatan |
|---|---|---|
| QR scan fisik | `compliance/inventory/detail/{id}` | URL legacy Q-021, wajib kompatibel |
| Kuesioner publik | `/kuesioner/{questionnaire}` | tanpa shell, layout sendiri, halaman thanks |
| Login | `/login` → redirect `/home` | centered card |
| 403 | halaman error Laravel standar | tanpa membuat `/unauthorized` (Q-010) |
