@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<x-page-header
    variant="card"
    tone="compliance"
    eyebrow="Administrasi"
    eyebrow-icon="bi-people"
    title="Manajemen User"
    lead="Kelola akun, role, izin baca/tulis, dan akses halaman tiap user."
>
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Tambah User</a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleModal"><i class="bi bi-shield-plus"></i> Tambah Role</button>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 mb-3">
            <div class="col-sm-6 col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / username / email">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Izin</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($user->photo)
                                        <img src="{{ asset('uploads/users/'.$user->photo) }}" class="rounded-circle" width="34" height="34" style="object-fit:cover" alt="">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:34px;height:34px;background:var(--c-primary-soft);color:var(--c-primary)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="small text-body-secondary">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><code>{{ $user->username }}</code></td>
                            <td><span class="badge text-bg-primary">{{ $roles[$user->role]['label'] ?? ucwords(str_replace('_', ' ', $user->role)) }}</span></td>
                            <td><span class="badge {{ $user->permission === 'write' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $user->permission }}</span></td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="status-pill is-ok"><i class="bi bi-check-circle"></i> Aktif</span>
                                @else
                                    <span class="status-pill is-offday"><i class="bi bi-slash-circle"></i> Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                @if($user->id !== auth()->id())
                                    @if($user->status === 'active')
                                        <form method="POST" action="{{ route('users.deactivate', $user) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger" title="Nonaktifkan"><i class="bi bi-person-x"></i></button></form>
                                    @else
                                        <form method="POST" action="{{ route('users.activate', $user) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="Aktifkan"><i class="bi bi-person-check"></i></button></form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Belum ada user.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('users.roles.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tambah Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Nama Role</label>
                <input type="text" name="name" class="form-control" placeholder="mis. supervisor" required>
                <div class="form-text">Nama akan dinormalisasi menjadi slug (huruf kecil, spasi menjadi _).</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
