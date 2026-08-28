@extends('layouts.app')

@section('title', 'Dashboard Compliance')

@section('content')
    @livewire('dashboard.overview', [
        'total' => $total,
        'byStatus' => $byStatus,
        'open' => $open,
        'late' => $late,
        'expired' => $expired,
    ])
@endsection
