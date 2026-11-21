<div class="form-group">
    <label for="name">Full name</label>
    <input id="name" name="name" value="{{ old('name', $managedUser?->name) }}" required maxlength="255">
</div>
<div class="form-group">
    <label for="email">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email', $managedUser?->email) }}" required maxlength="255">
</div>
<div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role" required>
        @foreach ($roles as $role)
            <option value="{{ $role }}" @selected(old('role', $managedUser?->roles->first()?->name) === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label for="password">{{ $managedUser ? 'New password (optional)' : 'Password' }}</label>
    <input id="password" type="password" name="password" minlength="12" {{ $managedUser ? '' : 'required' }} autocomplete="new-password">
</div>
<div class="form-group">
    <label for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" minlength="12" {{ $managedUser ? '' : 'required' }} autocomplete="new-password">
</div>
<button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
