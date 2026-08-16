# 12 — Authentication & Authorization

## A. Login & session (CONFIRMED)

- **Identitas:** username ATAU email + password (bcrypt `password_hash`/`password_verify`, `PASSWORD_DEFAULT`).
- **User aktif saja** yang bisa login (`status='active'`).
- **Throttle:** 5 percobaan gagal per menit per IP (CI4 Throttler, bucket `login_{ip}`); kelebihan → `login_blocked` audit + pesan generik.
- **Anti enumerasi:** `password_verify` tetap dijalankan ke dummy hash bila user tidak ada; pesan error generik sama.
- **Session:** file handler; cookie `eams_session`; **expiration 28800 dtk (8 jam)**; regenerate ID tiap 3600 dtk; `regenerateDestroy=false`; `matchIP=false`.
- **Session data:** `logged_in, user_id, name, role, photo, permission, page_access, auth_session_id, login_method, login_channel, login_at, login_session_touched_at`.
- **Login sessions (audit):** tiap login → baris `login_sessions` (session_key unik, IP, UA, browser, platform, device_type); tiap request → `last_seen_at/last_route` (throttle 60 dtk); logout → `ended_at, logout_reason=manual, is_active=0`; saat admin buka halaman audit → sesi idle >8 jam ditandai `expired`.
- **Audit log:** `login_success / login_failed / login_blocked / logout` + konteks klien (IP, UA, browser, platform, device, route, method, channel).

## B. Model otorisasi (3 lapis)

### 1) Role (`users.role` — string, dinormalisasi `normalize_role_name`)
Katalog default (`access_role_catalog`):

| Role | Label | Deskripsi | Default pages |
|---|---|---|---|
| admin | Administrator | akses penuh | semua 28 menu |
| compliance | Compliance | kelola checklist, laporan, utility, pengguna | home, patrol_daily, patrol_dashboard, compliance_* (dashboard, progress, inventory, checklist_master, qr_gallery, holidays, report, thermal_imaging, ems_reports, fdm, questionnaires, evidence_center, print), boiler_fuel, ipal, pdam_water, pdam_water_boiler, users_management |
| security | Security | patrol harian | patrol_daily |
| staff | Staff / PIC | isi checklist miliknya | home, compliance_inventory, thermal_imaging |
| auditor | Auditor | baca: dashboard, report, evidence, print | 4 halaman tsb |
| office | Office | home, ems_reports, fdm_data_collection | 3 halaman tsb |

Role kustom bisa dibuat (tabel `user_roles`); halamannya dipilih manual per user.

### 2) Permission (`users.permission` — 'write' atau lainnya=read)
- `isReadOnlyAccess()`: admin → false; `permission !== 'write'` → true; role `read/readonly/read_only` → true.
- **`WriteFilter` GLOBAL (before, semua route):** request non-GET/HEAD/OPTIONS dari user read-only yang login → 403 JSON (AJAX) atau redirect back + flash. Publik yang dikecualikan: `/login, /logout, /api/agent*, /kuesioner*, /logstores*`.
- UI: body `is-read-only` + JS menonaktifkan form mutating & fetch mutating.

### 3) Page access (`users.page_access` JSON array menu key)
- `access_menu_catalog` = 28 menu key (path + group + label + admin_only flag untuk audit_logs & backups).
- `canAccessPage($key)`: admin → true; menu admin_only → false utk non-admin; else key ∈ daftar efektif user.
- Daftar efektif: bila `page_access` terisi → difilter (menu admin_only dibuang utk non-admin); bila kosong → default role.
- **`AuthFilter` menegakkan ini per request:** resolve path → page key (`resolve_page_key_from_path`, prefix terpanjang menang) → 403 JSON (AJAX) / redirect ke landing default + flash error.

## C. Filter (middleware) — `app/Config/Filters.php`

| Alias | Class | Dipasang | Fungsi |
|---|---|---|---|
| auth | `App\Filters\AuthFilter` | hampir semua grup route | wajib login (AJAX→401 JSON, else redirect /login + simpan `redirect_after_login`) + cek page access |
| admin | `App\Filters\AdminFilter` | `audit-logs`, `backups/*` | `hasRole(['admin'])` else redirect '/' |
| write | `App\Filters\WriteFilter` | **GLOBAL before** | blokir tulis utk read-only |
| pdfAccess | `App\Filters\PdfAccessFilter` | **tidak dipakai di route mana pun** | role ∈ `Config\PdfPermission::$allowedRoles` = `['admin']` |
| csrfasset | `App\Filters\CsrfAssetFilter` | GLOBAL after | suntik meta CSRF + bootstrap JS auto-token |
| csrf | CI4 CSRF | GLOBAL before, **except** `api/agent*`, `kuesioner*`, `logstores*` | proteksi CSRF standar |
| secureheaders | CI4 | GLOBAL after | header keamanan |
| forcehttps | CI4 | required before | `forceGlobalSecureRequests=true` |

## D. Siapa boleh apa (matriks efektif dari kode)

| Fitur | admin | compliance | security | staff | auditor | office |
|---|---|---|---|---|---|---|
| Login, home*, settings user | ✔ | ✔ | (home tdk default) | ✔ | (home tdk default) | ✔ |
| Patrol harian | ✔ | ✔ | ✔ | – | – | – |
| Patrol dashboard | ✔ | ✔ | – | – | – | – |
| Patrol editor layout | ✔ | – | – | – | – | – |
| IT Center/Dashboard/Assets/Devices/Employees | ✔ | – | – | – | – | – |
| Compliance dashboard | ✔ | ✔ | – | – | ✔ | – |
| Inventory & checklist form | ✔ | ✔ | – | ✔ | – | – |
| Grid khusus (baca) | ✔ | ✔ | – | CCTV saja | CCTV saja | – |
| Grid khusus (tulis) | ✔ | ✔ | – | CCTV+generic saja | – | – |
| Mark-all grid | ✔ | ✔ | – | – | – | – |
| Checklist master | ✔ | ✔ | – | – | – | – |
| Report / Print center | ✔ | ✔ | – | – | ✔ | – |
| Progress monitoring + remind | ✔ | ✔ | – | – | – | – |
| Evidence center | ✔ | ✔ | – | – | ✔ | – |
| Holidays manage | ✔ | ✔(+write) | – | – | – | – |
| Holiday delete | ✔ | – | – | – | – | – |
| Thermal imaging (baca/buat) | ✔ | ✔ | – | ✔ | – | – |
| Thermal location manage | ✔ | ✔ | – | – | – | – |
| Questionnaire read/fill | ✔ | ✔ | – | ✔ | ✔ | – |
| Questionnaire write | ✔ | ✔ | – | – | – | – |
| EMS report / FDM | ✔ | ✔ | – | – | – | ✔ |
| Boiler / IPAL | ✔ | ✔ | – | – | – | – |
| PDAM water / boiler | ✔ | ✔ | – | – | – | ✔ |
| Users management | ✔ | ✔(default pages) | – | – | – | – |
| Audit logs | ✔ | – | – | – | – | – |
| Backups | ✔ | – | – | – | – | – |
| Export PDF (route aktual) | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ (cukup login; filter pdfAccess tidak terpasang) |

\* home = halaman default bila role punya; security/auditor default-nya bukan home → landing = menu pertama mereka (`resolve_default_landing_url`).

## E. Celah & catatan keamanan (untuk 14/15)

1. `/unauthorized` tidak terdaftar → redirect gagal menjadi 404 (CONFIRMED dari Routes.php).
2. `pdfAccess` tidak terpasang → PDF/export bisa diakses semua user login (CONFIRMED).
3. `app.proxyIPs = ['*' => 'X-Forwarded-For']` — mempercayai semua proxy header (risiko spoofing IP pada audit log & throttle) (CONFIRMED config).
4. Endpoint `api/agent/*` publik tanpa auth — identitas device_token/mac/hostname; penulisan device state & eksekusi command bergantung pada kerahasiaan token (CONFIRMED; by design, tapi token disimpan plaintext di DB).
5. Kuesioner publik (`/kuesioner/{slug}`) tanpa rate limit khusus (hanya CSRF-exempt + tanpa honeypot aktif) (CONFIRMED).
6. `users.password` nullable di beberapa alur? — UserController selalu hash; tidak ada fitur reset password (UNKNOWN by design).
