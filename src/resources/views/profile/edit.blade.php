@extends('layouts.app')

@section('title', 'My profile | HelpDesk')
@section('page-title', 'My profile')

@section('content')
    <div class="page-header">
        <div>
            <h1>My profile</h1>
            <p>Manage your personal information and profile photo.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="content-card form-card">
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;">
            @if ($user->profile_photo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo_path) }}"
                     alt="Profile photo"
                     style="width:88px;height:88px;object-fit:cover;border-radius:50%;">
            @else
                <div class="avatar" style="width:88px;height:88px;font-size:26px;">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            @endif
            <div>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                @if ($user->profile_photo_path)
                    <form method="POST" action="{{ route('profile.photo.destroy') }}" style="margin-top:8px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary">Remove photo</button>
                    </form>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
                <small>Changing your email address requires verifying the new address.</small>
            </div>

            <div class="form-group">
                <label for="profile_photo">Profile photo</label>
                <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp">
                <small>JPG, PNG or WEBP. Maximum file size: 2 MB.</small>
            </div>

            <button type="submit" class="btn btn-primary">Save profile</button>
        </form>
    </section>

    <section class="content-card" style="margin-top:24px;">
        <h2>Account information</h2>
        <p><strong>Member since:</strong> {{ $user->created_at->format('d.m.Y') }}</p>
        <p><strong>Account roles:</strong> {{ $user->getRoleNames()->map(fn ($role) => ucfirst($role))->join(', ') }}</p>
        <p><strong>Email status:</strong> {{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</p>
    </section>
@endsection
