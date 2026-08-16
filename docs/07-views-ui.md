# 07 — Views & UI

**Template engine:** CI4 PHP view + layout `layouts/main.php` (extend/section). **UI kit:** AdminLTE 4 (lokal `public/adminlte4/`) + Bootstrap 5.3.2 + Bootstrap Icons (CDN). Font: Plus Jakarta Sans. Tema: `tokens.css` (wajib token, lihat `docs/css.md`), dark/light/system + palette + density via cookie.

## Layout & struktur UI

- `layouts/main.php`: head (CSS bertingkat dgn cache-busting `filemtime`), body `eams-v2` (+`is-read-only`), shell: header/sidebar/footer/bottom-nav (hanya bila login). Inline script: tema system, `FLASH_MESSAGE` → `safeToast`, **read-only guard global** (disable semua form mutating + intercept fetch POST/PUT/PATCH/DELETE untuk user read-only), view-transition shell navigation.
- `layouts/partials/sidebar.php`: menu dibangun dari `access_menu_catalog` + `canAccessPage` + `canShowMenuPage` (permission-based UI per menu key). Grup: Home / Security / IT Asset / Compliance (Checklist submenu: Inventory, Checklist Master, QR Gallery) / Boiler & Utility / Print Center / Admin (Users, Audit Log, Backup). Badge jumlah notifikasi di menu Home.
- `layouts/partials/header.php`: judul halaman, user (nama, role, foto dari `uploads/users/`), notifikasi.
- `layouts/partials/bottom-nav.php`: navigasi mobile <768px, dibangun dari helper akses yang sama.
- `layouts/auth.php`: layout khusus halaman login.

## Permission-based UI (hidden logic di view)

- `isWritable` (role admin/compliance + write) mengontrol tombol aksi di banyak tabel (mis. `it_assets/_table.php`, `compliance/inventory/_table.php`).
- `canAccessPage('<key>')` mengontrol visibilitas menu; `hasRole([...])` mengontrol tombol tertentu (mis. manage kalender `canManageCalendar`).
- Body `is-read-only` + JS guard = dua lapis proteksi read-only (UI + server WriteFilter).

## Halaman & komponen penting per modul

| Modul | View utama | Form/Modal | Tabel/Grid | Logic tersembunyi di view |
|---|---|---|---|---|
| Auth | `auth/login.php` | form login | – | styling inline (di luar tema) |
| Home | `home/index.php`, `home/notifications.php` | – | pending list periode | peta bulan Indonesia; progress clamp 0–100 |
| Inventory | `compliance/inventory/index.php` + `_table` | `_modal_add/_modal_edit` | tabel + kartu mobile | sort kolom via Alpine `complianceInventoryPage`; status badge Good/Need Repair/Not Active; crop foto (cropperjs CDN) |
| Inventory detail | `detail.php` + `_detail_month` + `_detail_*_grid` | – | grid histori per periode | frekuensi menentukan partial (daily/weekly/monthly/toilet) |
| Checklist form | `checklist/index.php` + `_calendar` + `_form` | form radio ok/not_ok/(na) + remark + foto | kalender periode | `allow_na` item type menampilkan opsi NA; `not-ok-row` muncul hanya saat not_ok; progress bar isian; tombol "Tandai Semua Sesuai" |
| Grid khusus | `checklist/{cctv,emergency_*,first_aid_*,fire_extinguisher,intrusion_alarm,hydrant,smoke_detector,heat_detector,gate,generic}_grid.php` | sel grid (klik = toggle) | matriks inventory×periode | boot data via PHP → JS grid per tipe |
| Checklist master | `checklist_master/{index,items,detail}.php` | modal add/edit pertanyaan + select frekuensi | daftar pertanyaan | AJAX penuh (checklist-master.js) |
| Dashboard | `compliance/dashboard/index.php` (+`overdue.php`) | – | grafik (dashboard.js) | meta `data-year`, `data-base-url` |
| Report | `report/index.php` + `_table/_daily/_weekly/_monthly` | filter kategori→item→inventory (dependent dropdown) | grid laporan | |
| Progress | `progress/index.php` | modal detail + tombol remind | tabel user | `data-can-remind` = hasWriteAccess |
| Ranking | `ranking/index.php` | – | tabel ranking + prev/next bulan | `canNext` false utk bulan depan |
| Evidence | `evidence/index.php` + `_grid` + `_detail` | modal detail + follow-up | kartu foto | empty state SVG inline; follow-up badge open/monitoring/closed |
| Print | `print/{index,item,batch,preview}.php` + `_inventory_list` | pilih item/inventory/periode | preview | batch = "form kolektif" |
| PDF templates | `pdf/{single_item,recap_daily,recap_daily_toilet,recap_weekly,recap_periodic,recap_item_yearly,batch_form,questionnaire_response,attachment_ng}.php` + `pdf/batch_partials/*` | – | dokumen cetak | CSS inline sengaja di luar tema (docs/css.md) |
| Calendar/Holiday | `holidays/index.php` | modal event + modal hari libur | kalender bulan + mini kalender + agenda | data-* URL endpoints + csrf; event holiday readonly, warna merah |
| Thermal imaging | `compliance/thermal_imaging/{index,form,show,print}.php` | form baris dinamis + multi foto | daftar laporan | default inspector = nama user login; facility default PT.Younghyun Star |
| Questionnaire | `questionnaire/{index,form,detail,fill,response_detail,analytics,thanks,_question_list,_analytics_content}.php` | builder pertanyaan (section, tipe jawaban, opsi, wajib) + form publik | daftar respon | auto-timestamp question; halaman publik tanpa layout sidebar |
| EMS | `ems/{index,water_consumption,electric_consumption,combustion_report,ghg_summary,_*_summary_panels}.php` | editor matriks bulan×tahun (Alpine) | ringkasan | boot JSON `window.EMS_*_BOOT`; autosave |
| FDM | `fdm/{index,production_section}.php` | editor retailer + FTE (Alpine) | tabel bulanan | boot `window.FDM_*` |
| Boiler/IPAL/PDAM | `{boiler,ipal,pdam_water,pdam_water_boiler}/{index,detail,export_pdf}` | sel input per tanggal | grid bulanan | monthMap Indonesia; holiday fill |
| IT assets | `it_assets/{index,_table,detail,create,edit,assign}.php` | form asset + assign | tabel+paginasi | isWritable fallback `permission==='write' || role==='admin'` |
| IT devices | `it/devices/{index,_table,detail,_detail_content}.php` | modal remote command | tabel live | Alpine `itDeviceDetail` auto-refresh fragment |
| Employees | `employees/{index,create,edit,detail,_form}.php` | form + foto | kartu/tabel | canDelete = tanpa assignment aktif & riwayat |
| Users | `users/{index,create,edit,_access_form}.php` | form + role + page access matrix | tabel user | default pages per role dari `access_default_pages_for_role` |
| Audit logs | `audit_logs/index.php` | filter q/action/status | tabel log + sesi | badge per aksi |
| Backups | `backups/index.php` | upload + tombol aksi | tabel backup | tipe backup badge; tombol restore sesuai isi zip |
| Settings | `settings/index.php` | 4 section form | – | secret tidak ditampilkan kembali (password_saved flag) |
| Patrol | `patrol/{index,dashboard,editor}.php` | editor layout (drag checkpoint) | peta + daftar sesi | boot JSON; kamera + geolokasi via browser |

## Pola validation UI

- Server-side flash (`with('error'|'success'|'warning'|'info')`) → toast via `FLASH_MESSAGE`.
- AJAX JSON: `{ok|status, message, csrfHash}`; client menampilkan `safeToast`/`Swal.fire` konfirmasi hapus.
- Form checklist & grid memakai validasi client (semua pertanyaan wajib; not_ok wajib catatan/foto) + validasi server yang sama.
- Foto checklist dikompresi client-side (canvas max 1280px, JPEG 0.7) + **dibakar timestamp + nama user ke gambar** (checklist.js `bindPhotoCompression`).

## Pagination

- CI4 Pager dengan custom view `pagers/eams.php`; AJAX list memakai partial `_table` + re-bind link pagination (inventory.js, it-devices.js).
