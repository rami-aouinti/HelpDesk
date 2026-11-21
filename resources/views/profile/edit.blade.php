@extends('layouts.app')

@section('title', 'My profile | HelpDesk')
@section('page-title', 'My profile')

@section('content')
    <div class="page-header">
        <div>
            <span class="eyebrow">ACCOUNT SETTINGS</span>
            <h1>My profile</h1>
            <p>Manage your personal information, account security and notifications.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="profile-settings-grid">
        <section class="content-card profile-settings-card profile-personal-card">
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
                            <img class="profile-photo-large" src="{{ \\Illuminate\\Support\\Facades\\Storage::disk('public')->url($user->profile_photo_path) }}" alt="Profile photo">
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

            <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>
                    @error('name')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>
                    <small>Changing your email address requires verifying the new address.</small>
                    @error('email')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary">Save profile</button>
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
            <span class="eyebrow">ACCOUNT OVERVIEW</span>
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
                <span>Account status</span>
                <strong class="{{ $user->is_active ? 'text-success' : 'text-warning' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</strong>
            </div>
            <div class="account-info-row">
                <span>Role</span>
                <strong>{{ $user->getRoleNames()->map(fn ($role) => ucfirst($role))->join(', ') ?: 'No role assigned' }}</strong>
            </div>
            <div class="account-info-note">
                <strong>Need help?</strong>
                <p>Visit the Help center to learn how to use tickets, teams and conversations.</p>
                <a href="{{ route('help') }}">Open Help center →</a>
            </div>
        </aside>

        <section class="content-card profile-settings-card">
            <div class="profile-card-heading">
                <div>
                    <span class="eyebrow">SECURITY</span>
                    <h2>Change password</h2>
                    <p>Use a strong password that you do not use on other websites.</p>
                </div>
                <span class="profile-section-icon" aria-hidden="true">⌑</span>
            </div>
            <form method="POST" action="{{ route('profile.password.update') }}" class="profile-form">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="current_password">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    @error('password')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
        </section>

        <section class="content-card profile-settings-card">
            <div class="profile-card-heading">
                <div>
                    <span class="eyebrow">PREFERENCES</span>
                    <h2>Notifications</h2>
                    <p>Choose how you receive updates about ticket activity.</p>
                </div>
                <span class="profile-section-icon" aria-hidden="true">♧</span>
            </div>
            <form method="POST" action="{{ route('profile.preferences.update') }}" class="profile-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="email_notifications_enabled" value="0">
                <label class="preference-option" for="email_notifications_enabled">
                    <span class="preference-copy">
                        <strong>Email notifications</strong>
                        <small>Receive an email when a ticket assigned to or involving you is updated.</small>
                    </span>
                    <input id="email_notifications_enabled" type="checkbox" name="email_notifications_enabled" value="1" @checked(old('email_notifications_enabled', $user->email_notifications_enabled ?? true))>
                </label>
                <p class="profile-preference-note">In-app notifications remain available in the top bar. This setting only controls notification emails.</p>
                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary">Save preferences</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        (function () {
            var avatarInput = document.getElementById('profile-avatar-upload');
            if (avatarInput) {
                avatarInput.addEventListener('change', function () {
                    if (avatarInput.files && avatarInput.files.length) avatarInput.form.submit();
                });
            }
        })();
    </script>
@endsection
