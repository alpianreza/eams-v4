@extends('layouts.app')

@php
    $pageCopy = [
        'user' => ['Akun', 'Pengaturan User', 'Perbarui kontak notifikasi dan keamanan akun Anda.', 'bi-person-gear'],
        'company' => ['Administrasi sistem', 'Pengaturan Perusahaan', 'Kelola identitas, logo, dan informasi yang digunakan pada dokumen.', 'bi-buildings'],
        'email' => ['Administrasi sistem', 'Konfigurasi Email', 'Hubungkan EAMS dengan mailbox Google Workspace dan atur template pesan.', 'bi-envelope-at'],
        'whatsapp' => ['Administrasi sistem', 'Konfigurasi WhatsApp', 'Atur koneksi provider dan template pesan WhatsApp EAMS.', 'bi-whatsapp'],
    ];
    $copy = $pageCopy[$section] ?? $pageCopy['user'];
@endphp

@section('title', $copy[1])

@section('content')
<x-page-header
    variant="card"
    tone="utility"
    eyebrow="{{ $copy[0] }}"
    eyebrow-icon="{{ $copy[3] }}"
    title="{{ $copy[1] }}"
    lead="{{ $copy[2] }}"
/>

@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
@endif

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $section === 'user' ? 'active' : '' }}" href="{{ route('settings.index', ['section' => 'user']) }}"><i class="bi bi-person-gear me-1"></i>Akun</a></li>
    @can('manage-settings')
    <li class="nav-item"><a class="nav-link {{ $section === 'company' ? 'active' : '' }}" href="{{ route('settings.index', ['section' => 'company']) }}"><i class="bi bi-buildings me-1"></i>Perusahaan</a></li>
    <li class="nav-item"><a class="nav-link {{ $section === 'email' ? 'active' : '' }}" href="{{ route('settings.index', ['section' => 'email']) }}"><i class="bi bi-envelope-at me-1"></i>Email</a></li>
    <li class="nav-item"><a class="nav-link {{ $section === 'whatsapp' ? 'active' : '' }}" href="{{ route('settings.index', ['section' => 'whatsapp']) }}"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a></li>
    @endcan
</ul>

@if($section === 'company')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="mb-1">Identitas perusahaan</h5>
        <p class="text-muted small mb-0">Dipakai pada header, footer, dan dokumen yang dicetak.</p>
    </div>
    <div class="card-body p-4">
        <form method="post" action="{{ route('settings.company') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="company_name">Nama perusahaan</label>
                    <input name="company_name" id="company_name" class="form-control" value="{{ $settings['company_name'] ?? '' }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="company_logo">Logo perusahaan</label>
                    @if(!empty($settings['company_logo']))
                        <div class="mb-2"><img src="{{ asset($settings['company_logo']) }}" alt="Logo perusahaan" style="max-height:48px"></div>
                    @endif
                    <input type="file" name="company_logo" id="company_logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                    <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="company_address">Alamat perusahaan</label>
                    <textarea name="company_address" id="company_address" class="form-control" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="document_footer">Footer dokumen</label>
                    <input name="document_footer" id="document_footer" class="form-control" value="{{ $settings['document_footer'] ?? '' }}" placeholder="Teks yang tampil pada bagian bawah dokumen">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="document_signatory_name">Nama penandatangan</label>
                    <input name="document_signatory_name" id="document_signatory_name" class="form-control" value="{{ $settings['document_signatory_name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="document_signatory_title">Jabatan penandatangan</label>
                    <input name="document_signatory_title" id="document_signatory_title" class="form-control" value="{{ $settings['document_signatory_title'] ?? '' }}">
                </div>
            </div>
            <button class="btn btn-primary mt-4"><i class="bi bi-save me-1"></i>Simpan pengaturan perusahaan</button>
        </form>
    </div>
</div>

@elseif($section === 'email')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1">Google Workspace SMTP</h5>
                    <p class="text-muted small mb-0">Gunakan mailbox perusahaan seperti <code>eams@ptyhs.com</code>.</p>
                </div>
                @if(!empty($emailConfig['ready']))
                    <span class="badge text-bg-success">Siap digunakan</span>
                @else
                    <span class="badge text-bg-warning">Belum lengkap</span>
                @endif
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('settings.email') }}">
                    @csrf
                    <div class="form-check form-switch p-3 ps-5 rounded border mb-4">
                        <input class="form-check-input" type="checkbox" name="notification_email_enabled" id="emailEnabled" value="1" {{ ($settings['notification_email_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="emailEnabled">Aktifkan pengiriman email</label>
                        <div class="text-muted small">Notifikasi yang mendukung email akan dikirim ke alamat pengguna.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="email_smtp_user">Akun Google Workspace</label>
                            <input type="email" name="email_smtp_user" id="email_smtp_user" class="form-control" value="{{ $emailConfig['user'] ?? '' }}" placeholder="eams@ptyhs.com" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email_smtp_password">App Password</label>
                            <input type="password" name="email_smtp_password" id="email_smtp_password" class="form-control" placeholder="{{ !empty($emailConfig['password_saved']) ? 'Tersimpan — kosongkan jika tidak diubah' : 'Masukkan App Password 16 karakter' }}" autocomplete="new-password">
                            <div class="form-text">Gunakan App Password Google, bukan password login biasa.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="email_smtp_host">SMTP host</label>
                            <input name="email_smtp_host" id="email_smtp_host" class="form-control" value="{{ $emailConfig['host'] ?? 'smtp.gmail.com' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="email_smtp_port">Port</label>
                            <input type="number" name="email_smtp_port" id="email_smtp_port" class="form-control" min="1" max="65535" value="{{ $emailConfig['port'] ?? 587 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="email_smtp_crypto">Keamanan</label>
                            <select name="email_smtp_crypto" id="email_smtp_crypto" class="form-select">
                                <option value="tls" {{ ($emailConfig['crypto'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ ($emailConfig['crypto'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="" {{ ($emailConfig['crypto'] ?? '') === '' ? 'selected' : '' }}>Tanpa enkripsi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email_from_address">Alamat pengirim</label>
                            <input type="email" name="email_from_address" id="email_from_address" class="form-control" value="{{ $emailConfig['from_address'] ?? '' }}" placeholder="eams@ptyhs.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email_from_name">Nama pengirim</label>
                            <input name="email_from_name" id="email_from_name" class="form-control" value="{{ $emailConfig['from_name'] ?? 'EAMS' }}" placeholder="EAMS PT Younghyun Star">
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Template email</h6>
                            <p class="small text-muted mb-0">Template ini digunakan untuk notifikasi dan reminder checklist.</p>
                        </div>
                        <span class="badge bg-body-secondary text-body border">Plain text</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email_subject_template">Subjek email</label>
                        <input name="email_subject_template" id="email_subject_template" class="form-control font-monospace" maxlength="255" value="{{ $emailConfig['subject_template'] ?? '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="email_body_template">Isi email</label>
                        <textarea name="email_body_template" id="email_body_template" class="form-control font-monospace" rows="11" maxlength="10000">{{ $emailConfig['body_template'] ?? '' }}</textarea>
                    </div>
                    <div class="form-text">Variabel: <code>@{{company}}</code>, <code>@{{name}}</code>, <code>@{{title}}</code>, <code>@{{message}}</code>, <code>@{{url}}</code>, dan <code>@{{date}}</code>. Jangan hapus <code>@{{message}}</code> jika detail notifikasi tetap ingin ditampilkan.</div>
                    <button class="btn btn-primary mt-4"><i class="bi bi-save me-1"></i>Simpan konfigurasi &amp; template email</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary-subtle text-primary mb-3" style="width:46px;height:46px"><i class="bi bi-google fs-5"></i></span>
                <h6>Sebelum mengaktifkan</h6>
                <ol class="small text-muted ps-3 mb-3">
                    <li class="mb-2">Aktifkan Verifikasi 2 Langkah.</li>
                    <li class="mb-2">Buat App Password khusus EAMS.</li>
                    <li>Pastikan SMTP diizinkan oleh admin Workspace.</li>
                </ol>
                <hr>
                <h6 class="small">Contoh variabel</h6>
                <p class="small text-muted mb-0"><code>@{{name}}</code> otomatis menjadi nama penerima, sedangkan <code>@{{message}}</code> berisi detail penugasan atau checklist.</p>
            </div>
        </div>
    </div>
</div>

@elseif($section === 'whatsapp')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1">Webhook &amp; template WhatsApp</h5>
                    <p class="text-muted small mb-0">Hubungkan provider dan atur pembungkus pesan reminder yang sudah berjalan.</p>
                </div>
                @if(!empty($whatsappConfig['ready']))
                    <span class="badge text-bg-success">Webhook siap</span>
                @else
                    <span class="badge text-bg-warning">Belum lengkap</span>
                @endif
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('settings.whatsapp') }}">
                    @csrf
                    <div class="form-check form-switch p-3 ps-5 rounded border mb-4">
                        <input class="form-check-input" type="checkbox" name="notification_whatsapp_enabled" id="waEnabled" value="1" {{ !empty($whatsappConfig['enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="waEnabled">Aktifkan pengiriman WhatsApp</label>
                        <div class="text-muted small">Notifikasi akan menggunakan webhook dan template pesan di bawah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notification_whatsapp_webhook">URL webhook provider</label>
                        <input type="url" name="notification_whatsapp_webhook" id="notification_whatsapp_webhook" class="form-control" value="{{ $whatsappConfig['webhook'] ?? '' }}" placeholder="https://provider.example.com/send">
                        <div class="form-text">Masukkan endpoint lengkap yang menerima request pengiriman pesan.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="notification_whatsapp_token">Bearer token</label>
                        <input type="password" name="notification_whatsapp_token" id="notification_whatsapp_token" class="form-control" placeholder="{{ !empty($whatsappConfig['token_saved']) ? 'Tersimpan — kosongkan jika tidak diubah' : 'Masukkan token provider' }}" autocomplete="new-password">
                        <div class="form-text">Token tidak akan ditampilkan kembali setelah disimpan.</div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Template WhatsApp</h6>
                            <p class="small text-muted mb-0">Berlaku juga untuk reminder mingguan yang dikirim melalui perintah existing.</p>
                        </div>
                        <span class="badge text-bg-success-subtle text-success">Mendukung format WhatsApp</span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="whatsapp_message_template">Isi template</label>
                        <textarea name="whatsapp_message_template" id="whatsapp_message_template" class="form-control font-monospace" rows="10" maxlength="10000">{{ $whatsappConfig['message_template'] ?? '' }}</textarea>
                    </div>
                    <div class="form-text">Variabel universal: <code>@{{message}}</code>, <code>@{{company}}</code>, dan <code>@{{date}}</code>. Untuk notifikasi dari aplikasi juga tersedia <code>@{{name}}</code>, <code>@{{title}}</code>, serta <code>@{{url}}</code>. Pertahankan <code>@{{message}}</code> agar detail checklist tidak hilang.</div>
                    <button class="btn btn-success mt-4"><i class="bi bi-save me-1"></i>Simpan konfigurasi &amp; template WhatsApp</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-success-subtle text-success mb-3" style="width:46px;height:46px"><i class="bi bi-whatsapp fs-5"></i></span>
                <h6>Format pesan</h6>
                <p class="small text-muted">Gunakan tanda bintang untuk <strong>*tebal*</strong> dan garis bawah untuk <em>_miring_</em>.</p>
                <div class="rounded border bg-body-tertiary p-3 small font-monospace">@{{message}}<br><br>_@{{company}}_</div>
            </div>
        </div>
    </div>
</div>

@else
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="mb-1">Kontak notifikasi saya</h5>
        <p class="text-muted small mb-0">Email kerja dapat digunakan sebagai alamat notifikasi sekaligus identitas saat login.</p>
    </div>
    <div class="card-body p-4">
        <form method="post" action="{{ route('settings.contact') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="email">Email kerja</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ $currentUser->email ?? '' }}" placeholder="nama@ptyhs.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="wa_number">Nomor WhatsApp</label>
                    <input name="wa_number" id="wa_number" class="form-control" value="{{ $currentUser->wa_number ?? '' }}" placeholder="62812...">
                </div>
            </div>
            <button class="btn btn-outline-primary mt-3"><i class="bi bi-save me-1"></i>Simpan kontak</button>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="mb-1">Perbarui kata sandi</h5>
        <p class="text-muted small mb-0">Gunakan minimal 8 karakter dan hindari kata sandi yang sama dengan akun lain.</p>
    </div>
    <div class="card-body p-4">
        <a href="{{ route('self.password.edit') }}" class="btn btn-outline-primary"><i class="bi bi-key me-1"></i>Ganti kata sandi</a>
    </div>
</div>
@endif
@endsection
