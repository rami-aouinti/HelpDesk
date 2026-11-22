@extends('layouts.app')

@section('title', 'Appearance | HelpDesk')
@section('meta-description', 'Personalize your HelpDesk workspace with your preferred theme, accent color and display settings.')
@section('page-title', 'Appearance')

@section('content')
    <div class="page-header">
        <div>
            <span class="eyebrow">PERSONALIZATION</span>
            <h1>Appearance</h1>
            <p>Make HelpDesk feel like your workspace. Your preferences are saved on this device.</p>
        </div>
    </div>

    <div class="theme-page">
        <section class="content-card theme-section">
            <div class="theme-section-heading">
                <div>
                    <span class="eyebrow">COLOR PALETTE</span>
                    <h2>Choose your accent color</h2>
                    <p>Pick a color for primary buttons, active navigation, links and highlights.</p>
                </div>
            </div>

            <div class="theme-grid" id="theme-palette">
                @php
                    $palettes = [
                        ['id' => 'indigo', 'name' => 'Indigo', 'description' => 'The classic workspace', 'color' => '#4f46e5'],
                        ['id' => 'blue', 'name' => 'Ocean blue', 'description' => 'Clear and confident', 'color' => '#2563eb'],
                        ['id' => 'emerald', 'name' => 'Emerald', 'description' => 'Fresh and balanced', 'color' => '#059669'],
                        ['id' => 'rose', 'name' => 'Rose', 'description' => 'Warm and expressive', 'color' => '#e11d48'],
                        ['id' => 'amber', 'name' => 'Amber', 'description' => 'Bright and energetic', 'color' => '#d97706'],
                        ['id' => 'violet', 'name' => 'Violet', 'description' => 'Creative and modern', 'color' => '#7c3aed'],
                        ['id' => 'cyan', 'name' => 'Cyan', 'description' => 'Calm and focused', 'color' => '#0891b2'],
                    ];
                @endphp

                @foreach ($palettes as $palette)
                    <button type="button" class="theme-option" data-accent-option="{{ $palette['id'] }}" aria-pressed="false">
                        <span class="theme-preview" style="--swatch: {{ $palette['color'] }}">
                            <span class="theme-preview-sidebar"></span>
                            <span class="theme-preview-content">
                                <span class="theme-preview-line"></span>
                                <span class="theme-preview-line short"></span>
                            </span>
                            <span class="theme-preview-accent"></span>
                        </span>
                        <span class="theme-option-footer">
                            <span>
                                <strong>{{ $palette['name'] }}</strong>
                                <small>{{ $palette['description'] }}</small>
                            </span>
                            <span class="theme-option-check" aria-hidden="true">✓</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="content-card theme-section">
            <div class="theme-section-heading">
                <div>
                    <span class="eyebrow">DISPLAY</span>
                    <h2>Light and dark mode</h2>
                    <p>Switch the interface between a bright workspace and a darker environment.</p>
                </div>
            </div>
            <div class="theme-settings-note">
                <span class="theme-settings-note-icon" aria-hidden="true">☼</span>
                <span>
                    <strong>Quick access in the top bar</strong>
                    Use the sun/moon button in the top bar to switch between Light and Dark mode at any time. Your selected accent color stays active.
                </span>
            </div>
        </section>
    </div>
@endsection
