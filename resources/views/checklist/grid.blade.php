@extends('layouts.app')

@section('title', 'Grid Checklist — ' . $itemType->name)

@section('content')
    @livewire('checklist.grid-checklist', ['itemType' => $itemType])
@endsection
