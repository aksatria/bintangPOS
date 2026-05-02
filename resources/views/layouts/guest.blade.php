<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BINTANG') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: #f5f7fb;
                --ink: #101827;
                --muted: #5a657a;
                --card: rgba(255, 255, 255, 0.9);
                --line: rgba(16, 24, 39, 0.1);
                --primary: #175cd3;
                --primary-deep: #0f4fbf;
                --focus: rgba(23, 92, 211, 0.2);
                --danger: #be123c;
                --danger-soft: #fff1f2;
                --ok: #166534;
                --ok-soft: #ecfdf3;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                font-family: "Manrope", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at 8% 12%, #d5f3ea 0%, rgba(213, 243, 234, 0) 34%),
                    radial-gradient(circle at 92% 10%, #dbeafe 0%, rgba(219, 234, 254, 0) 36%),
                    linear-gradient(145deg, #f8fafc, #eef2ff);
            }

            .auth-shell {
                width: min(1060px, calc(100% - 2.2rem));
                margin: 1.8rem auto;
                border-radius: 28px;
                border: 1px solid var(--line);
                background: var(--card);
                box-shadow: 0 28px 70px rgba(16, 24, 39, 0.14);
                overflow: hidden;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .auth-showcase {
                padding: 2rem;
                background: linear-gradient(160deg, #f8fbff, #eaf2ff);
                border-right: 1px solid var(--line);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 1.2rem;
            }

            .brand { font-family: "Sora", sans-serif; font-weight: 800; letter-spacing: 0.02em; font-size: 0.98rem; color: #1f4f9f; }
            .showcase-title { margin: 0.6rem 0 0; font-family: "Sora", sans-serif; font-size: clamp(1.6rem, 2.5vw, 2.2rem); line-height: 1.2; }
            .showcase-text { margin: 0.9rem 0 0; color: var(--muted); line-height: 1.7; max-width: 45ch; }

            .mini-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.7rem;
            }
            .mini-card {
                border-radius: 0.82rem;
                padding: 0.78rem;
                border: 1px solid rgba(23, 92, 211, 0.15);
                background: rgba(255, 255, 255, 0.82);
                color: #1f3f68;
                font-size: 0.84rem;
            }
            .mini-card strong { display: block; color: #0f2542; margin-bottom: 0.15rem; }

            .auth-form {
                padding: 1.8rem;
            }

            .brand-link {
                display: inline-flex;
                align-items: center;
                gap: 0.65rem;
                text-decoration: none;
                color: inherit;
                margin-bottom: 0.8rem;
            }

            .brand-link span { font-family: "Sora", sans-serif; font-size: 1.1rem; font-weight: 800; }
            .form-area { max-width: 440px; }

            .label-ui {
                display: inline-block;
                margin-bottom: 0.38rem;
                font-size: 0.85rem;
                font-weight: 700;
                color: #1f2937;
            }

            .input-ui {
                width: 100%;
                border: 1px solid #cdd5e1;
                border-radius: 0.75rem;
                background: #fff;
                font-size: 0.93rem;
                color: #111827;
                padding: 0.7rem 0.8rem;
                outline: none;
                transition: 0.16s ease;
            }
            .input-ui:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 4px var(--focus);
            }

            .btn-primary {
                width: 100%;
                display: inline-flex;
                justify-content: center;
                align-items: center;
                border: 0;
                border-radius: 0.76rem;
                padding: 0.75rem 0.95rem;
                font-size: 0.93rem;
                font-weight: 800;
                color: #fff;
                background: linear-gradient(140deg, var(--primary), #2f80ed);
                box-shadow: 0 11px 26px rgba(23, 92, 211, 0.32);
                cursor: pointer;
            }
            .btn-primary:hover { background: linear-gradient(140deg, var(--primary-deep), #216fd6); }

            .status-ui {
                border: 1px solid rgba(34, 197, 94, 0.25);
                background: var(--ok-soft);
                color: var(--ok);
                border-radius: 0.75rem;
                padding: 0.6rem 0.72rem;
                font-size: 0.84rem;
                font-weight: 700;
                margin-bottom: 0.8rem;
            }

            .error-ui {
                margin: 0.38rem 0 0;
                padding: 0.5rem 0.62rem;
                border-radius: 0.62rem;
                border: 1px solid rgba(190, 18, 60, 0.18);
                background: var(--danger-soft);
                color: var(--danger);
                font-size: 0.79rem;
                list-style: none;
            }

            @media (max-width: 920px) {
                .auth-shell { grid-template-columns: 1fr; margin: 0.85rem auto; border-radius: 20px; }
                .auth-showcase { border-right: 0; border-bottom: 1px solid var(--line); }
            }
        </style>
    </head>
    <body>
        <div class="auth-shell">
            <section class="auth-showcase">
                <div>
                    <p class="brand">BINTANG POS</p>
                    <h1 class="showcase-title">Login yang simple, tetap terlihat profesional.</h1>
                    <p class="showcase-text">Satu halaman masuk untuk owner, admin, dan kasir. Fokus pada kecepatan operasional tanpa bikin tim bingung.</p>
                </div>
                <div class="mini-grid">
                    <div class="mini-card">
                        <strong>Kasir</strong>
                        Transaksi lebih cepat dan minim error.
                    </div>
                    <div class="mini-card">
                        <strong>Owner</strong>
                        Laporan dan kontrol akses tetap rapi.
                    </div>
                </div>
            </section>

            <section class="auth-form">
                <div class="form-area">
                    <a href="/" class="brand-link">
                        <x-application-logo class="h-10 w-10" />
                        <span>BINTANG</span>
                    </a>
                    {{ $slot }}
                </div>
            </section>
        </div>
    </body>
</html>
