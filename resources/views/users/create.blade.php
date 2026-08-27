@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
<x-page-header variant="card" tone="compliance" eyebrow="Administrasi" eyebrow-icon="bi-person-plus" title="Tambah User" lead="Buat akun baru beserta role, izin, dan akses halamannya." back-url="{{ route('users.index') }}" />

<form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" required>
                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">No. WhatsApp</label>
                <input type="text" name="wa_number" value="{{ old('wa_number') }}" class="form-control" placeholder="08xx...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Foto (opsional)</label>
                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach($roles as $key => $r)
                        <option value="{{ $key }}" @selected(old('role') === $key)>{{ $r['label'] }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Izin</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="permission" id="permRead" value="read" @checked(old('permission', 'read') === 'read')>
                    <label class="form-check-label" for="permRead">Read</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="permission" id="permWrite" value="write" @checked(old('permission', 'read') === 'write')>
                    <label class="form-check-label" for="permWrite">Write</label>
                </div>
            </div>
        </div>

        <hr>
        <div class="fw-semibold mb-2">Akses Halaman</div>
        @error('page_access')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @include('users._access-form')
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const roleDefaults = @json($roleDefaults);
    function applyRoleDefaults(role) {
        document.querySelectorAll('input[name="page_access[]"]').forEach(function (cb) { cb.checked = false; });
        (roleDefaults[role] || []).forEach(function (p) {
            var cb = document.getElementById('pg-' + p);
            if (cb) cb.checked = true;
        });
    }
    document.getElementById('roleSelect').addEventListener('change', function (e) { applyRoleDefaults(e.target.value); });
    applyRoleDefaults(document.getElementById('roleSelect').value);
</script>
@endpush
