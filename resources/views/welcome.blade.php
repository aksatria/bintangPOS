<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'BINTANG') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f7fb;
            --ink: #101827;
            --muted: #52607a;
            --card: rgba(255, 255, 255, 0.86);
            --primary: #175cd3;
            --primary-deep: #0f4fbf;
            --ring: rgba(23, 92, 211, 0.24);
            --line: rgba(16, 24, 39, 0.1);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Manrope", sans-serif;
            background:
                radial-gradient(circle at 12% 18%, #d5f3ea 0%, rgba(213, 243, 234, 0) 46%),
                radial-gradient(circle at 89% 10%, #dbeafe 0%, rgba(219, 234, 254, 0) 52%),
                linear-gradient(150deg, #f7fafc 0%, #eef2ff 42%, #f8fafc 100%);
        }

        .shell {
            width: min(1120px, calc(100% - 2.4rem));
            margin: 1.8rem auto;
            border-radius: 28px;
            border: 1px solid var(--line);
            background: var(--card);
            box-shadow: 0 30px 70px rgba(16, 24, 39, 0.14);
            overflow: hidden;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.4rem;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.72rem;
            font-family: "Sora", sans-serif;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .dot {
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 0.66rem;
            background: linear-gradient(145deg, #175cd3, #22c55e);
            box-shadow: 0 8px 20px rgba(23, 92, 211, 0.35);
        }

        .nav-actions {
            display: flex;
            gap: 0.64rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 0.78rem;
            font-weight: 700;
            font-size: 0.92rem;
            border: 1px solid transparent;
            padding: 0.63rem 1rem;
            transition: 0.18s ease;
        }

        .btn-main {
            color: #fff;
            background: linear-gradient(140deg, var(--primary), #2f80ed);
            box-shadow: 0 10px 26px rgba(23, 92, 211, 0.3);
        }

        .btn-main:hover { background: linear-gradient(140deg, var(--primary-deep), #216fd6); }

        .btn-soft {
            color: var(--ink);
            border-color: var(--line);
            background: rgba(255, 255, 255, 0.8);
        }

        .btn-soft:hover {
            border-color: var(--ring);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .hero {
            padding: 2.6rem 1.4rem 2.8rem;
            display: grid;
            gap: 2rem;
            grid-template-columns: 1.15fr 0.85fr;
        }

        .badge {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid rgba(22, 163, 74, 0.22);
            background: rgba(22, 163, 74, 0.1);
            color: #166534;
            font-size: 0.77rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.45rem 0.8rem;
        }

        h1 {
            margin: 0.9rem 0 0;
            font-family: "Sora", sans-serif;
            font-size: clamp(1.8rem, 4.2vw, 3.35rem);
            line-height: 1.12;
            letter-spacing: -0.02em;
            max-width: 16ch;
        }

        .lead {
            margin: 1rem 0 0;
            color: var(--muted);
            max-width: 54ch;
            line-height: 1.74;
            font-size: 1.02rem;
        }

        .stats {
            margin-top: 1.4rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.62rem;
        }

        .stat {
            border-radius: 0.8rem;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.62);
            padding: 0.7rem 0.78rem;
        }

        .stat strong {
            display: block;
            font-size: 1.2rem;
            font-family: "Sora", sans-serif;
        }

        .stat span {
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.35;
        }

        .panel {
            border-radius: 1rem;
            border: 1px solid rgba(23, 92, 211, 0.2);
            background: linear-gradient(160deg, #f8fbff 0%, #ecf4ff 100%);
            padding: 1rem;
            align-self: center;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.85);
        }

        .panel h2 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #1f4f9f;
        }

        .feature-list {
            margin: 0.95rem 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.55rem;
        }

        .feature-list li {
            border-radius: 0.7rem;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(23, 92, 211, 0.14);
            padding: 0.72rem 0.78rem;
            font-size: 0.88rem;
            color: #1e3a5f;
        }

        .feature-list li strong {
            display: block;
            color: #0f2542;
            margin-bottom: 0.2rem;
        }

        .footer {
            padding: 0.95rem 1.4rem 1.2rem;
            border-top: 1px solid var(--line);
            color: #6b7280;
            font-size: 0.82rem;
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; padding-top: 1.9rem; }
            .stats { grid-template-columns: 1fr; }
            .panel { order: -1; }
        }

        @media (max-width: 520px) {
            .shell { width: min(1120px, calc(100% - 1rem)); margin: 0.6rem auto; border-radius: 18px; }
            .nav { padding: 0.85rem; }
            .hero { padding: 1.3rem 0.85rem 1.55rem; }
            .footer { padding: 0.86rem; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="nav">
            <div class="brand">
                <span class="dot"></span>
                <span>{{ config('app.name', 'BINTANG') }}</span>
            </div>
            <div class="nav-actions">
                <a href="{{ route('login') }}" class="btn btn-soft">Login</a>
                <a href="{{ route('dashboard') }}" class="btn btn-main">Masuk Dashboard</a>
            </div>
        </div>

        <section class="hero">
            <div>
                <span class="badge">POS untuk UMKM</span>
                <h1>Kelola toko lebih rapi, cepat, dan enak dilihat dari hari pertama.</h1>
                <p class="lead">Satu sistem untuk kasir, stok, pengeluaran, pelanggan, dan laporan penjualan. Tim kasir tetap sederhana dipakai, owner tetap pegang kontrol penuh.</p>
                <div class="stats">
                    <article class="stat">
                        <strong>Kasir Cepat</strong>
                        <span>Alur checkout singkat, minim klik, anti ribet.</span>
                    </article>
                    <article class="stat">
                        <strong>Stok Aman</strong>
                        <span>Monitoring stok menipis dan opname lebih mudah.</span>
                    </article>
                    <article class="stat">
                        <strong>Laporan Jelas</strong>
                        <span>Omzet, modal, laba tersaji rapi untuk keputusan cepat.</span>
                    </article>
                </div>
            </div>

            <aside class="panel">
                <h2>Fitur Inti</h2>
                <ul class="feature-list">
                    <li>
                        <strong>Point of Sale</strong>
                        Transaksi harian dan cetak struk berjalan lancar.
                    </li>
                    <li>
                        <strong>Master Data</strong>
                        Produk, kategori, dan pengeluaran tersusun rapi.
                    </li>
                    <li>
                        <strong>Persetujuan & Audit</strong>
                        Approval perubahan sensitif dan jejak aktivitas kasir.
                    </li>
                    <li>
                        <strong>Customer Follow-up</strong>
                        Riwayat pelanggan dan tindak lanjut tetap terpantau.
                    </li>
                </ul>
            </aside>
        </section>

        <div class="footer">
            Dibangun untuk operasional toko harian: cepat, jelas, dan siap dipakai tim.
        </div>
    </main>
</body>
</html>
