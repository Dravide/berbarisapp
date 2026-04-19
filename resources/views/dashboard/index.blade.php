@extends('layouts.admin')

@section('title', 'Dashboard - BARIS APP')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-body">
                <h4 class="card-title fw-semibold">Dashboard</h4>
                <p class="mb-0">Welcome, {{ auth()->user()->name }}! You are logged in as <strong>{{ auth()->user()->role }}</strong>.</p>
                
                @if(auth()->user()->role === 'Admin')
                    <div class="alert alert-primary mt-3">This section is only visible to the Admin.</div>
                @elseif(auth()->user()->role === 'Eventner')
                    <div class="alert alert-success mt-3">This section is only visible to the Eventner.</div>
                @elseif(auth()->user()->role === 'Peserta')
                    <div class="alert alert-info mt-3">This section is only visible to the Peserta.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
