@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')
<x-page-header
    variant="card"
    tone="soft"
    eyebrow="Akun"
    eyebrow-icon="bi-key"
    title="Ganti Password"
    lead="Self-service — berlaku juga untuk akun read-only."
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="card" style="max-width:480px"><div class="card-body">
    <form method="POST" action="{{ route('self.password.update') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Password saat ini</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Password baru</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Ulangi password baru</label><input type="password" name="password_confirmation" class="form-control" required></div>
        <button class="btn btn-primary">Simpan</button>
    </form>
</div></div>
@endsection
