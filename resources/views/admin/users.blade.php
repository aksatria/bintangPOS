<x-app-layout>
    <x-slot name="header">
        <div class="ad-head intro-y">
            <div>
                <p class="ad-kicker">Master Data</p>
                <h2 class="ad-title">Pengguna</h2>
                <p class="ad-sub">Daftar akun pengguna sistem dengan role masing-masing.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .ad-shell { max-width: 1240px; margin: 0 auto; }
        .ad-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .ad-kicker { margin: 0; font-size: .73rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .ad-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.08; font-weight: 700; color: #0f172a; }
        .ad-sub { margin: .35rem 0 0; font-size: .9rem; color: #64748b; }
        .ad-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        @media (min-width: 1040px) { .ad-grid { grid-template-columns: 1fr 360px; } }
        .ad-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15,23,42,.04); overflow: hidden; }
        .ad-headbar { padding: .92rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); }
        .ad-card-title { margin: 0; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .ad-body { padding: 1rem; }
        .ad-input,.ad-select { width: 100%; min-height: 2.6rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; padding: 0 .72rem; font-size: .9rem; }
        .ad-input:focus,.ad-select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.16); }
        .ad-search-wrap { position: relative; }
        .ad-suggest {
            position: absolute;
            top: calc(100% + .4rem);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 14px 26px rgba(15, 23, 42, .12);
            overflow: hidden;
            z-index: 30;
        }
        .ad-suggest.hidden { display: none; }
        .ad-suggest-btn {
            width: 100%;
            border: 0;
            border-top: 1px solid #eef2f7;
            background: #fff;
            color: #0f172a;
            text-align: left;
            padding: .55rem .72rem;
            display: block;
            cursor: pointer;
            font-size: .84rem;
            line-height: 1.35;
        }
        .ad-suggest-btn:first-child { border-top: 0; }
        .ad-suggest-btn:hover,
        .ad-suggest-btn.active { background: #eff6ff; color: #1d4ed8; }
        .ad-suggest-name { font-weight: 600; }
        .ad-suggest-meta { font-size: .76rem; color: #64748b; margin-top: .1rem; }
        .ad-btn { min-height: 2.45rem; padding: 0 .9rem; border-radius: 10px; border: 1px solid #1d4ed8; background: #1d4ed8; color: #fff; font-size: .8rem; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; }
        .ad-btn-soft { min-height: 2.1rem; padding: 0 .75rem; border-radius: 9px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .76rem; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; }
        .ad-btn-danger { border-color: #fecaca; background: #fff1f2; color: #b91c1c; }
        .ad-filter-wrap {
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            padding: .75rem;
            background: linear-gradient(135deg, #f8fbff 0%, #f0f7ff 45%, #ffffff 100%);
        }
        .ad-filter-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
        }
        @media (min-width: 980px) {
            .ad-filter-grid {
                grid-template-columns: 1fr 190px auto auto;
                align-items: center;
            }
        }
        .ad-btn-reset {
            min-height: 2.45rem;
            padding: 0 .9rem;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #475569;
            font-size: .8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .ad-btn-reset:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }
        .ad-form-shell { display: grid; gap: .8rem; }
        .ad-form-group {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .85rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            display: grid;
            gap: .65rem;
        }
        .ad-form-group-title {
            margin: 0;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }
        .ad-field-label {
            display: block;
            margin-bottom: .3rem;
            font-size: .73rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
        }
        .ad-file {
            border: 1px solid #dbe5f2;
            border-radius: 10px;
            padding: .58rem;
            background: #fff;
        }
        .ad-file input[type="file"] {
            width: 100%;
            font-size: .82rem;
            color: #334155;
        }
        .ad-file input[type="file"]::file-selector-button {
            margin-right: .58rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: .32rem .62rem;
            background: #f8fafc;
            color: #334155;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
        }
        .ad-file-preview {
            margin-top: .6rem;
            width: 100%;
            min-height: 88px;
            border: 1px dashed #dbe5f2;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .ad-file-preview img {
            width: 100%;
            max-height: 190px;
            object-fit: contain;
            display: block;
        }
        .ad-file-preview-empty {
            font-size: .78rem;
            color: #94a3b8;
        }
        .ad-users-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
        @media (min-width: 900px) { .ad-users-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .ad-item { border: 1px solid #e2e8f0; border-radius: 12px; padding: .9rem; display: grid; gap: .55rem; background: #fff; overflow: hidden; text-align: center; }
        .ad-row { display:flex; align-items:flex-start; justify-content:center; gap:.75rem; min-width: 0; }
        .ad-name { margin:0; font-size:1rem; font-weight:600; color:#0f172a; line-height: 1.3; overflow-wrap: anywhere; word-break: break-word; }
        .ad-meta { margin:0; color:#475569; font-size:.82rem; overflow-wrap: anywhere; word-break: break-word; text-align: center; }
        .ad-user-top { display: flex; align-items: flex-start; justify-content: center; gap: .75rem; min-width: 0; flex: 1 1 auto; width: 100%; }
        .ad-user-media { display: inline-flex; flex-direction: column; align-items: center; gap: .45rem; min-width: 0; }
        .ad-avatar {
            width: 5.2rem;
            height: 5.2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            font-weight: 700;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            flex: 0 0 5.2rem;
            overflow: hidden;
        }
        .ad-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .ad-user-content { display: flex; flex-direction: column; justify-content: center; gap: .28rem; min-width: 0; }
        .ad-name { text-align: center; }
        .ad-role-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            width: fit-content;
            margin: 0 auto;
        }
        .ad-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; padding-top: .45rem; border-top: 1px dashed #e2e8f0; }
        .ad-actions form { display: inline-flex; }
        .ad-alert { border-radius: 12px; border: 1px solid; padding: .78rem .92rem; font-size: .82rem; }
        .ad-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .ad-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .ad-pagination-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: .7rem;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            padding: .8rem .9rem;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        }
        .ad-pagination-summary {
            margin: 0;
            font-size: .82rem;
            color: #64748b;
            font-weight: 600;
        }
        .ad-pagination-pages {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            flex-wrap: wrap;
        }
        .ad-page-btn {
            min-width: 2.15rem;
            height: 2.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbe5f2;
            border-radius: 9px;
            background: #fff;
            color: #334155;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }
        .ad-page-btn:hover { background: #f8fafc; border-color: #bfdbfe; color: #1d4ed8; }
        .ad-page-btn-active {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            color: #fff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, .28);
        }
        .ad-page-dots {
            min-width: 1.8rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: .85rem;
            line-height: 1;
        }
        .ad-toast-wrap {
            position: fixed;
            right: 1rem;
            top: 1rem;
            z-index: 90;
            display: grid;
            gap: .55rem;
            width: min(360px, calc(100vw - 2rem));
        }
        .ad-toast {
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #065f46;
            border-radius: 11px;
            padding: .65rem .8rem;
            font-size: .82rem;
            font-weight: 600;
            box-shadow: 0 10px 18px rgba(15, 23, 42, .12);
        }
        .ad-toast-error {
            border-color: #fecaca;
            background: #fff1f2;
            color: #9f1239;
        }
        .ad-modal-card {
            width: 100%;
            max-width: 760px;
            border-radius: 16px;
            border: 1px solid #dbe4f0;
            background: #fff;
            box-shadow: 0 20px 48px rgba(15, 23, 42, .22);
            overflow: hidden;
        }
        .ad-modal-head {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e7eef7;
            background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
        }
        .ad-modal-title {
            margin: 0;
            font-size: .9rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #334155;
            font-weight: 700;
        }
        .ad-modal-body { padding: 1rem 1.1rem; }
        .ad-modal-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
        @media (min-width: 760px) { .ad-modal-grid { grid-template-columns: 1fr 1fr; } }
        .ad-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
            margin-top: .95rem;
            padding-top: .8rem;
            border-top: 1px dashed #dbe5f2;
        }
    </style>

    <div class="ad-shell intro-y space-y-4">
        @if (session('status'))
            <div class="ad-alert ad-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="ad-alert ad-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ad-grid">
            <div class="ad-card">
                <div class="ad-headbar"><h3 class="ad-card-title">Daftar Pengguna</h3></div>
                <div class="ad-body space-y-3">
                    <div class="ad-filter-wrap">
                        <form method="GET" action="{{ route('admin.users.index') }}" class="ad-filter-grid" id="users-filter-form" autocomplete="off">
                            <div class="ad-search-wrap">
                                <input type="text" name="q" id="user-search-input" value="{{ $filters['q'] ?? '' }}" class="ad-input" placeholder="Cari nama / email pengguna (min. 2 huruf)">
                                <div id="user-search-suggest" class="ad-suggest hidden"></div>
                            </div>
                            <select name="role" class="ad-select">
                                <option value="all" @selected(($filters['role'] ?? 'all') === 'all')>Semua Role</option>
                                <option value="owner" @selected(($filters['role'] ?? '') === 'owner')>Owner</option>
                                <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                                <option value="kasir" @selected(($filters['role'] ?? '') === 'kasir')>Kasir</option>
                            </select>
                            <select name="branch_id" class="ad-select">
                                <option value="0">Semua Cabang</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}" @selected((int)($filters['branchId'] ?? 0) === (int)$branch->id)>{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                            <button class="ad-btn" type="submit">Terapkan</button>
                            <a href="{{ route('admin.users.index') }}" class="ad-btn-reset">Reset</a>
                        </form>
                    </div>

                    <div class="ad-users-grid">
                        @forelse($users as $user)
                            <div class="ad-item">
                                <div class="ad-row">
                                    <div class="ad-user-top">
                                        <div class="ad-user-media">
                                            <span class="ad-avatar">
                                                @if(!empty($user->photo))
                                                    <img src="{{ asset('storage/' . ltrim((string) $user->photo, '/')) }}" alt="{{ $user->name }}">
                                                @else
                                                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                                @endif
                                            </span>
                                            <h4 class="ad-name">{{ $user->name }}</h4>
                                            <span class="ad-role-label">{{ strtoupper($user->role->value) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="ad-meta">{{ $user->email }}</p>
                                <p class="ad-meta">Cabang: {{ $user->branch?->name ?? '-' }}</p>
                                <div class="ad-actions">
                                    <button
                                        type="button"
                                        class="ad-btn-soft"
                                        onclick="openEditUser({{ $user->id }}, @js($user->name), @js($user->email), @js($user->role->value), @js($user->branch_id), @js(!empty($user->photo) ? asset('storage/' . ltrim((string) $user->photo, '/')) : ''))"
                                    >Edit</button>
                                    @if((int) auth()->id() !== (int) $user->id)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-delete-user="1">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ad-btn-soft ad-btn-danger" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-slate-500 py-4">Belum ada data pengguna.</div>
                        @endforelse
                    </div>
                    @php
                        $current = $users->currentPage();
                        $last = $users->lastPage();
                        $pages = [1];
                        if ($current - 1 > 1) { $pages[] = $current - 1; }
                        if ($current !== 1 && $current !== $last) { $pages[] = $current; }
                        if ($current + 1 < $last) { $pages[] = $current + 1; }
                        if ($last > 1) { $pages[] = $last; }
                        $pages = array_values(array_unique(array_filter($pages, fn ($p) => $p >= 1 && $p <= $last)));
                        sort($pages);
                    @endphp
                    <div class="ad-pagination-custom">
                        <p class="ad-pagination-summary">
                            Menampilkan {{ number_format($users->firstItem() ?? 0, 0, ',', '.') }}
                            sampai {{ number_format($users->lastItem() ?? 0, 0, ',', '.') }}
                            dari {{ number_format($users->total(), 0, ',', '.') }} pengguna
                        </p>
                        <div class="ad-pagination-pages">
                            @if($users->onFirstPage())
                                <span class="ad-page-btn" aria-disabled="true">&#8249;</span>
                            @else
                                <a href="{{ $users->previousPageUrl() }}" class="ad-page-btn" rel="prev">&#8249;</a>
                            @endif

                            @php $prevShown = null; @endphp
                            @foreach($pages as $page)
                                @if(!is_null($prevShown) && ($page - $prevShown) > 1)
                                    <span class="ad-page-dots">&hellip;</span>
                                @endif

                                @if($page === $current)
                                    <span class="ad-page-btn ad-page-btn-active">{{ $page }}</span>
                                @else
                                    <a href="{{ $users->url($page) }}" class="ad-page-btn">{{ $page }}</a>
                                @endif
                                @php $prevShown = $page; @endphp
                            @endforeach

                            @if($users->hasMorePages())
                                <a href="{{ $users->nextPageUrl() }}" class="ad-page-btn" rel="next">&#8250;</a>
                            @else
                                <span class="ad-page-btn" aria-disabled="true">&#8250;</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-headbar"><h3 class="ad-card-title">Tambah Pengguna</h3></div>
                <div class="ad-body">
                    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="ad-form-shell" id="create-user-form">
                        @csrf
                        <div class="ad-form-group">
                            <h4 class="ad-form-group-title">Identitas Akun</h4>
                            <label>
                                <span class="ad-field-label">Nama Lengkap</span>
                                <input type="text" name="name" class="ad-input" required>
                            </label>
                            <label>
                                <span class="ad-field-label">Email</span>
                                <input type="email" name="email" class="ad-input" required>
                            </label>
                            <label>
                                <span class="ad-field-label">Role</span>
                                <select name="role" class="ad-select" required>
                                    <option value="owner">Owner</option>
                                    <option value="admin">Admin</option>
                                    <option value="kasir">Kasir</option>
                                </select>
                            </label>
                            <label>
                                <span class="ad-field-label">Cabang</span>
                                <select name="branch_id" class="ad-select">
                                    <option value="">Tanpa Cabang</option>
                                    @foreach(($branches ?? collect()) as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="ad-form-group">
                            <h4 class="ad-form-group-title">Keamanan</h4>
                            <label>
                                <span class="ad-field-label">Password</span>
                                <input type="password" name="password" class="ad-input" minlength="6" required>
                            </label>
                        </div>
                        <div class="ad-form-group">
                            <h4 class="ad-form-group-title">Foto Profil</h4>
                            <label>
                                <span class="ad-field-label">Upload (Opsional)</span>
                                <div class="ad-file">
                                    <input type="file" name="photo" id="create-user-photo-input" accept="image/*">
                                    <div class="ad-file-preview" id="create-user-photo-preview">
                                        <span class="ad-file-preview-empty">Belum ada foto dipilih</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <button class="ad-btn w-full" type="submit">Simpan Pengguna</button>
                    </form>
                </div>
            </div>

            @if(auth()->user()?->hasAnyRole(['owner']))
            <div class="ad-card">
                <div class="ad-headbar"><h3 class="ad-card-title">Manajemen Cabang</h3></div>
                <div class="ad-body space-y-3">
                    <form id="create-branch-form" class="ad-form-shell">
                        @csrf
                        <div class="ad-form-group">
                            <h4 class="ad-form-group-title">Tambah Cabang</h4>
                            <label><span class="ad-field-label">Nama Cabang</span><input name="name" class="ad-input" required></label>
                            <label>
                                <span class="ad-field-label">Kode Cabang</span>
                                <input name="code" class="ad-input" required pattern="[A-Za-z0-9_-]+" title="Gunakan huruf, angka, tanda minus (-), atau underscore (_)." placeholder="Contoh: CABANG_01">
                                <span class="text-xs text-slate-500">Hanya huruf, angka, `-`, dan `_`.</span>
                            </label>
                            <label><span class="ad-field-label">Alamat (Opsional)</span><input name="address" class="ad-input"></label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                        </div>
                        <button class="ad-btn w-full" type="submit">Simpan Cabang</button>
                    </form>

                    <div class="space-y-2">
                        @foreach(($branches ?? collect()) as $branch)
                            <div class="ad-item !text-left">
                                <p class="ad-name">{{ $branch->name }} <span class="ad-role-label">{{ $branch->code }}</span></p>
                                <p class="ad-meta">{{ $branch->address ?: '-' }}</p>
                                <p class="ad-meta">Status: {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                                <div class="ad-actions !justify-start">
                                    <button type="button" class="ad-btn-soft" onclick="openBranchModal({{ $branch->id }}, @js($branch->name), @js($branch->code), @js($branch->address), {{ $branch->is_active ? 'true' : 'false' }})">Edit</button>
                                    <button type="button" class="ad-btn-soft ad-btn-danger" data-delete-branch="{{ $branch->id }}">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div id="edit-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/55 p-4">
        <div class="ad-modal-card">
            <div class="ad-modal-head">
                <h3 class="ad-modal-title">Edit Pengguna</h3>
            </div>
            <div class="ad-modal-body">
            <form id="edit-user-form" method="POST" enctype="multipart/form-data" class="ad-form-shell">
                @csrf
                @method('PUT')
                <div class="ad-modal-grid">
                    <label>
                        <span class="ad-field-label">Nama Lengkap</span>
                        <input id="edit-user-name" type="text" name="name" class="ad-input" required>
                    </label>
                    <label>
                        <span class="ad-field-label">Email</span>
                        <input id="edit-user-email" type="email" name="email" class="ad-input" required>
                    </label>
                    <label>
                        <span class="ad-field-label">Role</span>
                        <select id="edit-user-role" name="role" class="ad-select" required>
                            <option value="owner">Owner</option>
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </label>
                    <label>
                        <span class="ad-field-label">Password Baru (Opsional)</span>
                        <input type="password" name="password" class="ad-input" minlength="6">
                    </label>
                    <label>
                        <span class="ad-field-label">Cabang</span>
                        <select id="edit-user-branch" name="branch_id" class="ad-select">
                            <option value="">Tanpa Cabang</option>
                            @foreach(($branches ?? collect()) as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label>
                    <span class="ad-field-label">Foto Profil (Opsional)</span>
                    <div class="ad-file">
                        <input type="file" name="photo" id="edit-user-photo-input" accept="image/*">
                        <div class="ad-file-preview" id="edit-user-photo-preview">
                            <span class="ad-file-preview-empty">Foto saat ini akan tampil di sini</span>
                        </div>
                    </div>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remove_photo" value="1"> Hapus foto saat ini
                </label>
                <div class="ad-modal-actions">
                    <button class="ad-btn-soft" type="button" onclick="closeEditUser()">Batal</button>
                    <button class="ad-btn" type="submit">Simpan Perubahan</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    @if(auth()->user()?->hasAnyRole(['owner']))
    <div id="branch-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/55 p-4">
        <div class="ad-modal-card">
            <div class="ad-modal-head"><h3 class="ad-modal-title">Edit Cabang</h3></div>
            <div class="ad-modal-body">
                <form id="edit-branch-form" class="ad-form-shell">
                    @csrf
                    <label><span class="ad-field-label">Nama Cabang</span><input id="edit-branch-name" name="name" class="ad-input" required></label>
                    <label>
                        <span class="ad-field-label">Kode</span>
                        <input id="edit-branch-code" name="code" class="ad-input" required pattern="[A-Za-z0-9_-]+" title="Gunakan huruf, angka, tanda minus (-), atau underscore (_)." placeholder="Contoh: CABANG_01">
                        <span class="text-xs text-slate-500">Hanya huruf, angka, `-`, dan `_`.</span>
                    </label>
                    <label><span class="ad-field-label">Alamat</span><input id="edit-branch-address" name="address" class="ad-input"></label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" id="edit-branch-active" name="is_active" value="1" checked> Aktif</label>
                    <div class="ad-modal-actions">
                        <button class="ad-btn-soft" type="button" onclick="closeBranchModal()">Batal</button>
                        <button class="ad-btn" type="submit">Simpan Cabang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <div id="ad-toast-wrap" class="ad-toast-wrap" aria-live="polite"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        function showToast(message, type = 'success') {
            const wrap = document.getElementById('ad-toast-wrap');
            if (!wrap) return;
            const toast = document.createElement('div');
            toast.className = 'ad-toast';
            if (type === 'error') toast.classList.add('ad-toast-error');
            toast.textContent = message;
            wrap.appendChild(toast);
            setTimeout(() => toast.remove(), 2400);
        }

        function openEditUser(id, name, email, role, branchId, photoUrl = '') {
            const modal = document.getElementById('edit-user-modal');
            const form = document.getElementById('edit-user-form');
            if (!modal || !form) return;
            form.action = `/admin/users/${id}`;
            document.getElementById('edit-user-name').value = name || '';
            document.getElementById('edit-user-email').value = email || '';
            document.getElementById('edit-user-role').value = role || 'kasir';
            const branchInput = document.getElementById('edit-user-branch');
            if (branchInput) branchInput.value = branchId ?? '';
            const preview = document.getElementById('edit-user-photo-preview');
            if (preview) {
                preview.innerHTML = photoUrl
                    ? `<img src="${photoUrl}" alt="Foto profil pengguna">`
                    : '<span class="ad-file-preview-empty">Pengguna ini belum punya foto</span>';
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeEditUser() {
            const modal = document.getElementById('edit-user-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function wireImagePreview(inputId, previewId, emptyText = 'Belum ada foto dipilih') {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) {
                    preview.innerHTML = `<span class="ad-file-preview-empty">${emptyText}</span>`;
                    return;
                }
                if (!file.type.startsWith('image/')) {
                    preview.innerHTML = '<span class="ad-file-preview-empty">File bukan gambar valid</span>';
                    return;
                }
                const url = URL.createObjectURL(file);
                preview.innerHTML = `<img src="${url}" alt="Preview foto">`;
            });
        }

        wireImagePreview('edit-user-photo-input', 'edit-user-photo-preview', 'Foto saat ini akan tampil di sini');
        wireImagePreview('create-user-photo-input', 'create-user-photo-preview');

        (function () {
            const input = document.getElementById('user-search-input');
            const dropdown = document.getElementById('user-search-suggest');
            const form = document.getElementById('users-filter-form');
            if (!input || !dropdown || !form) return;

            let debounceTimer = null;
            let activeIndex = -1;
            let items = [];

            const closeDropdown = () => {
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                items = [];
                activeIndex = -1;
            };

            const applyActive = () => {
                items.forEach((el, idx) => {
                    el.classList.toggle('active', idx === activeIndex);
                });
            };

            const pickSuggestion = (btn) => {
                const value = btn.getAttribute('data-value') || '';
                input.value = value;
                closeDropdown();
                form.submit();
            };

            const renderSuggestions = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    closeDropdown();
                    return;
                }

                dropdown.innerHTML = rows.map((row) => {
                    const safeName = String(row.name || '');
                    const safeEmail = String(row.email || '');
                    const safeRole = String(row.role || '').toUpperCase();
                    const safeValue = String(row.label || `${safeName} ${safeEmail}`).replace(/"/g, '&quot;');

                    return `<button type="button" class="ad-suggest-btn" data-value="${safeValue}">
                        <div class="ad-suggest-name">${safeName}</div>
                        <div class="ad-suggest-meta">${safeEmail} - ${safeRole}</div>
                    </button>`;
                }).join('');

                items = Array.from(dropdown.querySelectorAll('.ad-suggest-btn'));
                activeIndex = -1;
                dropdown.classList.remove('hidden');

                items.forEach((btn) => {
                    btn.addEventListener('click', () => pickSuggestion(btn));
                });
            };

            input.addEventListener('input', () => {
                const q = input.value.trim();
                if (debounceTimer) clearTimeout(debounceTimer);
                if (q.length < 2) {
                    closeDropdown();
                    return;
                }

                debounceTimer = setTimeout(async () => {
                    try {
                        const url = `{{ route('admin.users.suggest') }}?q=${encodeURIComponent(q)}`;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) {
                            closeDropdown();
                            return;
                        }
                        const data = await res.json();
                        renderSuggestions(data.data || []);
                    } catch (_) {
                        closeDropdown();
                    }
                }, 220);
            });

            input.addEventListener('keydown', (e) => {
                if (dropdown.classList.contains('hidden') || items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = (activeIndex + 1) % items.length;
                    applyActive();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
                    applyActive();
                } else if (e.key === 'Enter' && activeIndex >= 0) {
                    e.preventDefault();
                    pickSuggestion(items[activeIndex]);
                } else if (e.key === 'Escape') {
                    closeDropdown();
                }
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && e.target !== input) {
                    closeDropdown();
                }
            });
        })();

        document.getElementById('create-user-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (data?.errors && typeof data.errors === 'object') {
                        const firstError = Object.values(data.errors)?.[0];
                        const text = Array.isArray(firstError) ? firstError[0] : 'Validasi gagal.';
                        throw new Error(text || 'Validasi gagal.');
                    }
                    throw new Error(data?.message || 'Gagal menambahkan pengguna.');
                }
                showToast(data?.message || 'Pengguna berhasil ditambahkan.');
                form.reset();
                setTimeout(() => window.location.reload(), 420);
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan saat menambah pengguna.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.getElementById('edit-user-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (data?.errors && typeof data.errors === 'object') {
                        const firstError = Object.values(data.errors)?.[0];
                        const text = Array.isArray(firstError) ? firstError[0] : 'Validasi gagal.';
                        throw new Error(text || 'Validasi gagal.');
                    }
                    throw new Error(data?.message || 'Gagal memperbarui pengguna.');
                }
                closeEditUser();
                showToast(data?.message || 'Pengguna berhasil diperbarui.');
                setTimeout(() => window.location.reload(), 380);
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan saat mengubah pengguna.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.querySelectorAll('form[data-delete-user="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!window.confirm('Hapus pengguna ini?')) return;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    payload.append('_method', 'DELETE');
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data?.message || 'Gagal menghapus pengguna.');
                    }
                    showToast(data?.message || 'Pengguna berhasil dihapus.');
                    setTimeout(() => window.location.reload(), 350);
                } catch (error) {
                    showToast(error.message || 'Terjadi kesalahan saat menghapus pengguna.', 'error');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });

        @if(auth()->user()?->hasAnyRole(['owner']))
        function normalizeBranchCode(raw) {
            return String(raw || '')
                .trim()
                .replace(/\s+/g, '_')
                .replace(/[^A-Za-z0-9_-]/g, '')
                .toUpperCase();
        }

        function openBranchModal(id, name, code, address, isActive) {
            const modal = document.getElementById('branch-modal');
            const form = document.getElementById('edit-branch-form');
            if (!modal || !form) return;
            form.dataset.branchId = String(id || '');
            document.getElementById('edit-branch-name').value = name || '';
            document.getElementById('edit-branch-code').value = code || '';
            document.getElementById('edit-branch-address').value = address || '';
            document.getElementById('edit-branch-active').checked = !!isActive;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeBranchModal() {
            const modal = document.getElementById('branch-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('create-branch-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const codeInput = form.querySelector('input[name="code"]');
            if (codeInput) codeInput.value = normalizeBranchCode(codeInput.value);
            const response = await fetch("{{ route('admin.branches.store') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                showToast(data?.message || 'Gagal menambah cabang.', 'error');
                return;
            }
            showToast(data?.message || 'Cabang berhasil ditambahkan.');
            setTimeout(() => window.location.reload(), 350);
        });

        document.getElementById('edit-branch-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const branchId = form.dataset.branchId;
            const codeInput = form.querySelector('input[name="code"]');
            if (codeInput) codeInput.value = normalizeBranchCode(codeInput.value);
            const payload = new FormData(form);
            payload.append('_method', 'PUT');
            if (!document.getElementById('edit-branch-active')?.checked) payload.set('is_active', '0');
            const response = await fetch(`/admin/branches/${branchId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                showToast(data?.message || 'Gagal memperbarui cabang.', 'error');
                return;
            }
            showToast(data?.message || 'Cabang berhasil diperbarui.');
            setTimeout(() => window.location.reload(), 350);
        });

        document.querySelectorAll('button[data-delete-branch]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!window.confirm('Hapus cabang ini?')) return;
                const id = button.getAttribute('data-delete-branch');
                const payload = new FormData();
                payload.append('_method', 'DELETE');
                payload.append('_token', csrfToken);
                const response = await fetch(`/admin/branches/${id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: payload,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showToast(data?.message || 'Gagal menghapus cabang.', 'error');
                    return;
                }
                showToast(data?.message || 'Cabang berhasil dihapus.');
                setTimeout(() => window.location.reload(), 350);
            });
        });
        @endif
    </script>
</x-app-layout>
