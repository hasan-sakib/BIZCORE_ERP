<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Login') ?> — BizCore ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body { height: 100%; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── Two-column full-page layout ─────────────────────────── */
        .auth-split {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left — form side */
        .auth-left {
            flex: 0 0 50%;
            max-width: 50%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            overflow-y: auto;
        }
        .auth-form-inner {
            width: 100%;
            max-width: 400px;
        }

        /* Right — animated panel */
        .auth-right {
            flex: 0 0 50%;
            max-width: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: #ffffff;
            text-align: center;
            padding: 3rem 3.5rem;
        }

        /* Mobile: stack vertically, right panel hidden */
        @media (max-width: 767.98px) {
            .auth-split { flex-direction: column; }
            .auth-left  { flex: none; max-width: 100%; min-height: 100vh; padding: 2.5rem 1.5rem; }
            .auth-right { display: none !important; }
        }

        /* ── Input group icon pattern ─────────────────────────────── */
        .input-group .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #6c757d;
        }
        .input-group .form-control,
        .input-group .form-select {
            border-left: none;
            padding-left: 0;
        }
        .input-group .form-control:focus,
        .input-group .form-select:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .2);
            border-radius: 0.375rem;
        }
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control,
        .input-group:focus-within .form-select,
        .input-group:focus-within .password-toggle {
            border-color: #86b7fe;
        }
        .password-toggle {
            cursor: pointer;
            border-left: none;
            background: transparent;
            color: #6c757d;
        }
        .password-toggle:hover { color: #0d6efd; background: transparent; }

        /* ── Divider ─────────────────────────────────────────────── */
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 1.25rem 0;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .auth-divider span {
            padding: 0 0.75rem;
            color: #6c757d;
            font-size: 0.8125rem;
            white-space: nowrap;
        }

        /* ── Google button ───────────────────────────────────────── */
        .btn-google {
            border: 1px solid #dee2e6;
            background: #ffffff;
            color: #212529;
            font-weight: 500;
            transition: background-color 0.15s ease;
        }
        .btn-google:hover {
            background: #f8f9fa;
            color: #212529;
            border-color: #ced4da;
        }

        /* ── Side panel animations ───────────────────────────────── */

        /* Drifting background orbs */
        .sp-orb {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            pointer-events: none;
        }
        .sp-orb-1 { width: 420px; height: 420px; top: -140px; right: -120px;
                    animation: orb-drift  9s ease-in-out infinite; }
        .sp-orb-2 { width: 280px; height: 280px; bottom: -80px; left: -80px;
                    animation: orb-drift 12s ease-in-out infinite reverse; animation-delay: -3s; }
        .sp-orb-3 { width: 160px; height: 160px; top: 55%; left: 5%;
                    animation: orb-drift  7s ease-in-out infinite; animation-delay: -5s; }
        .sp-orb-4 { width: 90px;  height: 90px;  top: 20%; left: 60%;
                    animation: orb-drift  5s ease-in-out infinite; animation-delay: -2s; }

        @keyframes orb-drift {
            0%, 100% { transform: translate(0,0) scale(1); }
            33%       { transform: translate(20px,-24px) scale(1.08); }
            66%       { transform: translate(-14px,16px) scale(0.92); }
        }

        /* Animated icon */
        .sp-content { position: relative; z-index: 1; }

        .sp-icon-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: icon-entrance 0.7s cubic-bezier(.34,1.56,.64,1) both,
                       icon-float    3.4s ease-in-out 0.7s infinite;
        }
        .sp-icon {
            width: 96px; height: 96px;
            background: rgba(255,255,255,.18);
            border-radius: 28px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(8px);
            position: relative; z-index: 1;
            box-shadow: 0 12px 40px rgba(0,0,0,.2), inset 0 1px 0 rgba(255,255,255,.25);
        }

        /* Pulse rings */
        .sp-ring {
            position: absolute;
            width: 118px; height: 118px;
            border-radius: 32px;
            border: 2px solid rgba(255,255,255,.3);
            animation: ring-pulse 2.8s ease-out infinite;
            pointer-events: none;
        }
        .sp-ring-2 { animation-delay: 1.4s; }

        @keyframes ring-pulse {
            0%   { transform: scale(1);    opacity: .5; }
            100% { transform: scale(1.85); opacity: 0; }
        }
        @keyframes icon-entrance {
            from { opacity: 0; transform: scale(.7) translateY(16px); }
            to   { opacity: 1; transform: scale(1)  translateY(0); }
        }
        @keyframes icon-float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

        /* Staggered text entrance */
        .sp-anim {
            opacity: 0;
            animation: fade-up .55s ease both;
            animation-delay: calc(var(--i) * 0.15s + 0.4s);
        }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Feature chips */
        .sp-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: .45rem; margin-bottom: 2rem; }
        .sp-chip {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.22);
            padding: .3rem .8rem;
            border-radius: 999px;
            font-size: .75rem;
            backdrop-filter: blur(4px);
            transition: transform .2s, background .2s;
            cursor: default;
        }
        .sp-chip:hover { background: rgba(255,255,255,.28); transform: translateY(-2px); }

        /* Floating decorative dots */
        .sp-dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            pointer-events: none;
        }
        .sp-dot-1 { width: 10px; height: 10px; top: 22%;    right: 12%;  animation: dot-drift 4.5s ease-in-out infinite; }
        .sp-dot-2 { width:  6px; height:  6px; top: 65%;    right: 20%;  animation: dot-drift 5.5s ease-in-out infinite; animation-delay: -2s; }
        .sp-dot-3 { width: 13px; height: 13px; bottom: 25%; left:  16%;  animation: dot-drift 6.5s ease-in-out infinite; animation-delay: -1s; }
        .sp-dot-4 { width:  8px; height:  8px; bottom: 40%; right:  8%;  animation: dot-drift 4s   ease-in-out infinite; animation-delay: -3s; }

        @keyframes dot-drift {
            0%, 100% { transform: translate(0,0);      opacity: .55; }
            50%       { transform: translate(10px,-14px); opacity: 1; }
        }

        /* Divider line in right panel */
        .sp-divider {
            width: 48px; height: 2px;
            background: rgba(255,255,255,.35);
            border-radius: 2px;
            margin: 1.5rem auto;
        }

        /* ── Dark mode ───────────────────────────────────────────── */
        body.dark-mode .auth-left { background: #0f172a; color: #e2e8f0; }
        body.dark-mode .auth-left .form-label { color: #cbd5e1; }
        body.dark-mode .auth-left .text-muted { color: #94a3b8 !important; }
        body.dark-mode .auth-left h2 { color: #f1f5f9; }
        body.dark-mode .form-control,
        body.dark-mode .form-select { background: #1e293b; color: #e2e8f0; border-color: #334155; }
        body.dark-mode .form-control::placeholder { color: #64748b; }
        body.dark-mode .input-group .input-group-text { border-color: #334155; color: #94a3b8; }
        body.dark-mode .input-group:focus-within .input-group-text,
        body.dark-mode .input-group:focus-within .form-control { border-color: #3b82f6; }
        body.dark-mode .btn-google { background: #1e293b; color: #e2e8f0; border-color: #334155; }
        body.dark-mode .btn-google:hover { background: #0f172a; }
        body.dark-mode .auth-divider::before,
        body.dark-mode .auth-divider::after { border-color: #334155; }
        body.dark-mode .form-check-input { background-color: #1e293b; border-color: #475569; }
    </style>
</head>
<body class="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1' ? 'dark-mode' : '' ?>">

<div class="auth-split">

    <!-- ── Left: form ─────────────────────────────────────────── -->
    <div class="auth-left">
        <div class="auth-form-inner">
            <?= $content ?? '' ?>
        </div>
        <p class="text-muted mt-4 mb-0" style="font-size:.75rem">
            © <?= date('Y') ?> BizCore ERP. All rights reserved.
        </p>
    </div>

    <!-- ── Right: animated branding panel ────────────────────── -->
    <div class="auth-right">
        <?php if (isset($authSideContent)): ?>
            <?= $authSideContent ?>
        <?php else: ?>
            <!-- Drifting background orbs -->
            <div class="sp-orb sp-orb-1"></div>
            <div class="sp-orb sp-orb-2"></div>
            <div class="sp-orb sp-orb-3"></div>
            <div class="sp-orb sp-orb-4"></div>

            <!-- Floating decorative dots -->
            <div class="sp-dot sp-dot-1"></div>
            <div class="sp-dot sp-dot-2"></div>
            <div class="sp-dot sp-dot-3"></div>
            <div class="sp-dot sp-dot-4"></div>

            <!-- Main content -->
            <div class="sp-content">
                <!-- Animated icon with pulse rings -->
                <div class="sp-icon-wrap mb-4">
                    <div class="sp-ring"></div>
                    <div class="sp-ring sp-ring-2"></div>
                    <div class="sp-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M10 24c0-7.732 6.268-14 14-14s14 6.268 14 14-6.268 14-14 14S10 31.732 10 24z" fill="rgba(255,255,255,.25)"/>
                            <path d="M16 23l6 6 10-10" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>

                <h2 class="fw-bold mb-1 sp-anim" style="--i:1; font-size:1.75rem">BizCore ERP</h2>
                <p class="mb-0 sp-anim" style="--i:2; opacity:.75; font-size:.9rem">Enterprise Resource Planning Platform</p>

                <div class="sp-divider sp-anim" style="--i:3"></div>

                <!-- Feature chips -->
                <div class="sp-chips sp-anim" style="--i:4">
                    <span class="sp-chip"><i class="fas fa-chart-line me-1"></i>Analytics</span>
                    <span class="sp-chip"><i class="fas fa-users me-1"></i>HR &amp; Payroll</span>
                    <span class="sp-chip"><i class="fas fa-boxes-stacked me-1"></i>Inventory</span>
                    <span class="sp-chip"><i class="fas fa-file-invoice me-1"></i>Sales</span>
                    <span class="sp-chip"><i class="fas fa-shopping-cart me-1"></i>Purchasing</span>
                </div>

                <blockquote class="sp-anim" style="--i:5; max-width:320px; margin:0 auto;">
                    <p class="fst-italic mb-2 lh-base" style="font-size:.9rem">"Streamline your business operations with powerful ERP tools designed for modern enterprises."</p>
                    <footer style="font-size:.75rem; opacity:.7">— BizCore Team</footer>
                </blockquote>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
