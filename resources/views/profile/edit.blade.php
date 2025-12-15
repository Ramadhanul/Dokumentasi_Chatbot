@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('content')
<div class="container py-5">
    <h2 class="mb-4">Profile</h2>

    <div class="card mb-4">
        <div class="card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
