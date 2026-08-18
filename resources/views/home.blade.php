@extends('layouts.app')

@section('title', 'Beranda — ' . config('app.name', 'EAMS'))

@section('content')
<h1 class="h4 mb-3">Beranda</h1>
<div class="card">
    <div class="card-body">
        <p class="mb-0">Selamat datang, <strong>{{ auth()->user()->name }}</strong>. EAMS Laravel rebuild — Milestone 1 (foundation + auth + master data).</p>
    </div>
</div>
@endsection
