<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HelpDesk | Support Workspace')</title>
    <meta name="description" content="@yield('meta-description', 'Access your HelpDesk account to manage support requests and stay connected with your team.')">
    <meta name="application-name" content="HelpDesk">
    <meta name="theme-color" content="#101a32">
    <link rel="icon" type="image/png" href="{{ asset('helpdesk.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('helpdesk.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:'DM Sans',sans-serif}
        .auth-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(420px,.92fr);background:#f7f9fc}
        .auth-form-side{min-height:100vh;display:flex;flex-direction:column;justify-content:center;padding:42px clamp(25px,7vw,108px)}
        .auth-brand{display:inline-flex;align-items:center;gap:12px;color:#101a2d;text-decoration:none;font:800 23px 'Manrope',sans-serif;letter-spacing:-.8px}.auth-brand img{width:42px;height:42px;object-fit:contain}
        .auth-form-wrap{width:100%;max-width:440px;margin:48px auto 32px}
        .auth-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#52627b;font-size:11px;font-weight:700;letter-spacing:1.7px;text-transform:uppercase}.auth-eyebrow:before{content:'';width:22px;height:2px;border-radius:3px;background:#6578f5}
        .auth-title{margin:16px 0 9px;color:#111c31;font:800 clamp(30px,3vw,39px)/1.15 'Manrope',sans-serif;letter-spacing:-1.6px}
        .auth-subtitle{margin:0 0 30px;color:#718096;font-size:14px;line-height:1.7}
        .auth-field{display:block;margin-bottom:18px}.auth-label{display:block;margin-bottom:8px;color:#29364c;font-size:13px;font-weight:700}
        .auth-input{width:100%;height:50px;padding:0 15px;border:1px solid #dfe5ef;border-radius:12px;background:#fff;color:#18243a;font:500 14px 'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s}
        .auth-input::placeholder{color:#a5afbf}.auth-input:focus{border-color:#7182f7;box-shadow:0 0 0 4px rgba(101,120,245,.12)}
        .auth-primary{display:flex;width:100%;min-height:52px;align-items:center;justify-content:center;gap:10px;border:0;border-radius:12px;background:linear-gradient(110deg,#5368e8,#7886fa);color:#fff;font:700 14px 'DM Sans',sans-serif;cursor:pointer;box-shadow:0 10px 22px rgba(83,104,232,.22);transition:transform .2s,box-shadow .2s}
        .auth-primary:hover{transform:translateY(-1px);box-shadow:0 13px 25px rgba(83,104,232,.3)}
        .auth-primary:focus-visible,.auth-link:focus-visible{outline:3px solid rgba(101,120,245,.35);outline-offset:3px}
        .auth-link{color:#586de8;font-weight:700;text-decoration:none}.auth-link:hover{color:#394fc9;text-decoration:underline}
        .auth-alert{margin:0 0 19px;padding:13px 15px;border-radius:12px;font-size:13px;line-height:1.55}.auth-alert-success{background:#ecfdf5;color:#087a55;border:1px solid #c9f2df}.auth-alert-error{background:#fff1f2;color:#be3347;border:1px solid #ffe0e5}
        .auth-check{width:16px;height:16px;accent-color:#6276ee}.auth-footer{color:#9aa5b5;font-size:12px;text-align:center}
        .auth-visual{position:relative;display:flex;min-height:100vh;flex-direction:column;justify-content:space-between;overflow:hidden;padding:52px clamp(32px,6vw,82px) 36px;color:#fff;background:radial-gradient(ellipse at 80% 15%,rgba(107,126,255,.34),transparent 35%),radial-gradient(ellipse at 12% 80%,rgba(38,177,190,.16),transparent 35%),linear-gradient(145deg,#111c36,#101a31 48%,#17264a)}
        .auth-visual:before,.auth-visual:after{content:'';position:absolute;border:1px solid rgba(255,255,255,.08);border-radius:50%;pointer-events:none}.auth-visual:before{width:620px;height:620px;right:-220px;top:8%}.auth-visual:after{width:450px;height:450px;right:-135px;top:19%}
        .visual-topline,.visual-content,.visual-bottom{position:relative;z-index:1}.visual-topline{color:rgba(232,238,255,.58);font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase}
        .visual-content{max-width:570px;margin:55px auto}.visual-pill{display:inline-flex;align-items:center;gap:9px;padding:9px 13px;border:1px solid rgba(255,255,255,.16);border-radius:30px;background:rgba(255,255,255,.07);color:#d9e1ff;font-size:11px;font-weight:600;backdrop-filter:blur(10px)}
        .visual-dot{width:7px;height:7px;border-radius:50%;background:#66e3bd;box-shadow:0 0 12px #66e3bd}.visual-heading{margin:27px 0 16px;font:800 clamp(34px,4vw,53px)/1.12 'Manrope',sans-serif;letter-spacing:-2px}.visual-heading span{color:#91a0ff}
        .visual-copy{max-width:440px;color:rgba(226,233,250,.68);font-size:15px;line-height:1.8}
        .visual-card{margin-top:42px;padding:22px;border:1px solid rgba(255,255,255,.16);border-radius:19px;background:linear-gradient(135deg,rgba(255,255,255,.13),rgba(255,255,255,.055));box-shadow:0 24px 70px rgba(0,0,0,.2);backdrop-filter:blur(18px)}
        .visual-card-head{display:flex;align-items:center;justify-content:space-between;color:#e9edff;font-size:12px;font-weight:700}.visual-status{padding:5px 9px;border-radius:20px;background:rgba(102,227,189,.13);color:#83eacb;font-size:10px}
        .visual-ticket{display:flex;align-items:center;gap:12px;margin-top:17px;padding:13px;border:1px solid rgba(255,255,255,.09);border-radius:13px;background:rgba(7,14,31,.2)}
        .visual-ticket-icon{display:grid;width:38px;height:38px;place-items:center;border-radius:11px;background:rgba(128,145,255,.2);color:#aab5ff;font-size:17px}.visual-ticket-title{color:#f4f6ff;font-size:12px;font-weight:700}.visual-ticket-meta{margin-top:4px;color:rgba(221,229,250,.52);font-size:10px}
        .visual-ticket-tag{margin-left:auto;padding:5px 8px;border-radius:7px;background:rgba(255,193,112,.12);color:#ffd18d;font-size:9px;white-space:nowrap}
        .visual-bottom{display:flex;justify-content:space-between;gap:15px;color:rgba(226,233,250,.43);font-size:11px}
        @media(max-width:900px){.auth-shell{grid-template-columns:1fr}.auth-form-side{padding:28px 24px}.auth-form-wrap{margin:45px auto 30px}.auth-visual{display:none}}
        @media(max-width:480px){.auth-form-side{padding:22px 19px}.auth-form-wrap{margin:38px auto 25px}.auth-title{font-size:32px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
    </style>
</head>
<body class="antialiased">
    <main class="auth-shell">
        <section class="auth-form-side">
            <a href="{{ url('/') }}" class="auth-brand" aria-label="HelpDesk home"><img src="{{ asset('logo.png') }}" alt=""><span>HelpDesk</span></a>
            <div class="auth-form-wrap">@yield('content')</div>
            <p class="auth-footer">© {{ date('Y') }} HelpDesk. Built for better support.</p>
        </section>
        <aside class="auth-visual" aria-label="HelpDesk workspace introduction">
            <div class="visual-topline">ONE WORKSPACE. BETTER SUPPORT.</div>
            <div class="visual-content">
                <div class="visual-pill"><span class="visual-dot"></span> YOUR TEAM, IN SYNC</div>
                <h2 class="visual-heading">Make every<br>conversation <span>count.</span></h2>
                <p class="visual-copy">Bring requests, people and progress together. Give your team the clarity to deliver exceptional support, every single day.</p>
                <div class="visual-card">
                    <div class="visual-card-head"><span>Team activity</span><span class="visual-status">● All systems active</span></div>
                    <div class="visual-ticket"><div class="visual-ticket-icon">✳</div><div><div class="visual-ticket-title">Customer request received</div><div class="visual-ticket-meta">Ticket #HD-2048 · Just now</div></div><span class="visual-ticket-tag">In progress</span></div>
                    <div class="visual-ticket"><div class="visual-ticket-icon">✓</div><div><div class="visual-ticket-title">Team collaboration, simplified</div><div class="visual-ticket-meta">One place for every update</div></div><span class="visual-ticket-tag" style="background:rgba(102,227,189,.12);color:#83eacb">Resolved</span></div>
                </div>
            </div>
            <div class="visual-bottom"><span>Thoughtful support starts here.</span><span>Secure · Simple · Connected</span></div>
        </aside>
    </main>
    @livewireScripts
</body>
</html>