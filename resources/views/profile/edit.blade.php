@extends('layouts.app')

@section('title', 'My profile | HelpDesk')
@section('page-title', 'My profile')

@section('content')
    <div class="page-header">
        <div>
            <span class="eyebrow">ACCOUNT SETTINGS</span>
            <h1>My profile</h1>
            <p>Keep your personal details and profile photo up to date.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="profile-layout">
        <section class="content-card profile-card">
            <div class="profile-card-heading">
                <div>
                    <span class="eyebrow">PERSONAL INFORMATION</span>
                    <h2>Profile details</h2>
                    <p>Update the name and email address associated with your account.</p>
                </div>
            </div>

            <div class="profile-identity">
                <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="profile-avatar-upload-form">
                    @csrf
                    <input id="profile-avatar-upload" class="profile-avatar-file" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" required>
                    <label for="profile-avatar-upload" class="profile-avatar-trigger" title="Change profile photo" aria-label="Change profile photo">
                        @if ($user->profile_photo_path)
                            <img class="profile-photo-large" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo_path) }}" alt="Profile photo">
                        @else
                            <span class="profile-photo-placeholder">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                        <span class="profile-avatar-overlay" aria-hidden="true">✎</span>
                    </label>
                    <small class="profile-avatar-hint">Click your avatar to change it</small>
                </form>
                <div class="profile-identity-copy">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                    <span class="profile-role">{{ $user->getRoleNames()->map(fn ($role) => ucfirst($role))->join(' · ') }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="profile-form">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" required>
                    @error('name')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
                    <small>Changing your email address requires verifying the new address.</small>
                    @error('email')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    @if ($user->profile_photo_path)
                        <button type="submit" form="remove-profile-photo" class="btn btn-secondary">Remove photo</button>
                    @endif
                </div>
            </form>
            @if ($user->profile_photo_path)
                <form id="remove-profile-photo" method="POST" action="{{ route('profile.photo.destroy') }}">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </section>

        <aside class="content-card account-info-card">
            <span class="eyebrow">ACCOUNT</span>
            <h2>Account information</h2>
            <div class="account-info-row">
                <span>Member since</span>
                <strong>{{ $user->created_at->format('d M Y') }}</strong>
            </div>
            <div class="account-info-row">
                <span>Email verification</span>
                <strong class="{{ $user->hasVerifiedEmail() ? 'text-success' : 'text-warning' }}">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</strong>
            </div>
            <div class="account-info-row">
                <span>Role</span>
                <strong>{{ $user->getRoleNames()->map(fn ($role) => ucfirst($role))->join(', ') }}</strong>
            </div>
            <div class="account-info-note">
                <strong>Need help?</strong>
                <p>Visit the Help center to learn how to use tickets, teams and conversations.</p>
                <a href="{{ route('help') }}">Open Help center →</a>
            </div>
        </aside>
    </div>

    <script>
        (function () {
            var avatarInput = document.getElementById('profile-avatar-upload');
            if (!avatarInput) return;
            avatarInput.addEventListener('change', function () {
                if (avatarInput.files && avatarInput.files.length) avatarInput.form.submit();
            });
        })();
    </script>
@endsection
