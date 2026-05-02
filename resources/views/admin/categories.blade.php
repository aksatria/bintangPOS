<x-app-layout>
    <x-slot name="header">
        <div class="cg-head intro-y">
            <div>
                <p class="cg-kicker">Master Data</p>
                <h2 class="cg-title">Manajemen Kategori</h2>
                <p class="cg-sub">Kelola kategori produk secara rapi untuk menjaga struktur inventori.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .cg-shell { max-width: 1240px; margin: 0 auto; }
        .cg-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .cg-kicker { margin: 0; font-size: .73rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .cg-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.08; font-weight: 700; color: #0f172a; }
        .cg-sub { margin: .35rem 0 0; font-size: .9rem; color: #64748b; }
        .cg-alert { border-radius: 12px; border: 1px solid; padding: .78rem .92rem; font-size: .82rem; }
        .cg-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .cg-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .cg-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        @media (min-width: 1040px) { .cg-grid { grid-template-columns: 1fr 360px; } }
        .cg-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15, 23, 42, .04); overflow: hidden; }
        .cg-headbar { padding: .92rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%); }
        .cg-card-title { margin: 0; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .cg-body { padding: 1rem; }
        .cg-input, .cg-textarea, .cg-select {
            width: 100%; min-height: 2.6rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; padding: 0 .72rem; font-size: .9rem;
        }
        .cg-textarea { min-height: 96px; padding: .62rem .72rem; resize: vertical; }
        .cg-input:focus, .cg-textarea:focus, .cg-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, .16); }
        .cg-label { display: block; margin-bottom: .34rem; font-size: .73rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; }
        .cg-btn { min-height: 2.45rem; padding: 0 .9rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
        .cg-btn:hover { background: #f8fafc; }
        .cg-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .cg-btn-primary:hover { background: #1e40af; border-color: #1e40af; }
        .cg-btn-icon { width: .9rem; height: .9rem; margin-right: .38rem; vertical-align: -2px; }
        .cg-item { border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem .9rem; display: grid; gap: .65rem; }
        .cg-item-title { margin: 0; font-size: 1rem; font-weight: 600; color: #0f172a; }
        .cg-item-desc { margin: 0; color: #475569; font-size: .82rem; line-height: 1.4; white-space: pre-wrap; }
        .cg-chip { display: inline-flex; align-items: center; padding: .2rem .55rem; border-radius: 999px; border: 1px solid; font-size: .68rem; font-weight: 800; letter-spacing: .06em; }
        .cg-chip-on { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .cg-chip-off { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }
        .cg-actions { display: flex; flex-wrap: wrap; gap: .45rem; }
        .cg-empty { text-align: center; color: #64748b; padding: 1rem; }
        .cg-pagination nav { display: flex; align-items: center; justify-content: space-between; gap: .7rem; flex-wrap: wrap; }
        .cg-pagination nav > div:first-child { display: none; }
        .cg-pagination nav p { margin: 0; font-size: .82rem; color: #64748b; }
        .cg-pagination nav span[aria-current="page"] span,
        .cg-pagination nav a,
        .cg-pagination nav span[aria-disabled="true"] span {
            min-width: 2.2rem;
            height: 2.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-size: .82rem;
            font-weight: 600;
        }
        .cg-pagination nav a:hover { background: #f8fafc; border-color: #bfdbfe; color: #1d4ed8; }
        .cg-pagination nav span[aria-current="page"] span { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .cg-pagination nav span[aria-disabled="true"] span { opacity: .45; }
    </style>

    <div class="cg-shell intro-y space-y-4">
        @if (session('status'))
            <div class="cg-alert cg-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="cg-alert cg-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="cg-grid">
            <div class="cg-card">
                <div class="cg-headbar">
                    <h3 class="cg-card-title">Daftar Kategori</h3>
                </div>
                <div class="cg-body space-y-3">
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_200px_auto] gap-2">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="cg-input" placeholder="Cari nama / deskripsi kategori">
                        <select name="status" class="cg-select">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua Status</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                        </select>
                        <button class="cg-btn cg-btn-primary"><i data-feather="filter" class="cg-btn-icon"></i>Filter</button>
                    </form>

                    <div class="space-y-2">
                        @forelse($categories as $category)
                            <div class="cg-item">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="cg-item-title">{{ $category->name }}</h4>
                                    <span class="cg-chip {{ $category->is_active ? 'cg-chip-on' : 'cg-chip-off' }}">{{ $category->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                                </div>
                                <p class="cg-item-desc">{{ $category->description ?: '-' }}</p>
                                <div class="cg-actions">
                                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="contents">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" value="{{ $category->name }}">
                                        <input type="hidden" name="description" value="{{ $category->description }}">
                                        <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                                        <button class="cg-btn" type="submit"><i data-feather="power" class="cg-btn-icon"></i>{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <button class="cg-btn" type="button" onclick="openEditCategory({{ $category->id }}, @js($category->name), @js($category->description), {{ $category->is_active ? 'true' : 'false' }})"><i data-feather="edit-3" class="cg-btn-icon"></i>Edit</button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="contents" onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="cg-btn" type="submit"><i data-feather="trash-2" class="cg-btn-icon"></i>Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="cg-empty">Belum ada kategori.</div>
                        @endforelse
                    </div>
                    <div class="cg-pagination">{{ $categories->links() }}</div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="cg-card">
                    <div class="cg-headbar"><h3 class="cg-card-title">Tambah Kategori</h3></div>
                    <div class="cg-body space-y-2">
                        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-2">
                            @csrf
                            <label>
                                <span class="cg-label">Nama Kategori</span>
                                <input type="text" name="name" class="cg-input" required>
                            </label>
                            <label>
                                <span class="cg-label">Deskripsi</span>
                                <textarea name="description" class="cg-textarea"></textarea>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="is_active" value="1" checked> Aktif
                            </label>
                            <button class="cg-btn cg-btn-primary w-full" type="submit"><i data-feather="save" class="cg-btn-icon"></i>Simpan Kategori</button>
                        </form>
                    </div>
                </div>

                <div class="cg-card">
                    <div class="cg-headbar"><h3 class="cg-card-title">Ringkasan</h3></div>
                    <div class="cg-body text-sm text-slate-600 space-y-1">
                        <div>Total kategori: <strong>{{ number_format($categories->total(), 0, ',', '.') }}</strong></div>
                        <div>Gunakan status nonaktif jika kategori tidak dipakai sementara.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="edit-category-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/55 p-4">
        <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-4 shadow-2xl">
            <h3 class="text-base font-semibold text-slate-900">Edit Kategori</h3>
            <form id="edit-category-form" method="POST" class="mt-3 space-y-2">
                @csrf
                @method('PUT')
                <label>
                    <span class="cg-label">Nama Kategori</span>
                    <input id="edit-name" type="text" name="name" class="cg-input" required>
                </label>
                <label>
                    <span class="cg-label">Deskripsi</span>
                    <textarea id="edit-description" name="description" class="cg-textarea"></textarea>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input id="edit-is-active" type="checkbox" name="is_active" value="1"> Aktif
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button class="cg-btn" type="button" onclick="closeEditCategory()">Batal</button>
                    <button class="cg-btn cg-btn-primary" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditCategory(id, name, description, isActive) {
            const modal = document.getElementById('edit-category-modal');
            const form = document.getElementById('edit-category-form');
            const nameEl = document.getElementById('edit-name');
            const descEl = document.getElementById('edit-description');
            const activeEl = document.getElementById('edit-is-active');
            if (!modal || !form || !nameEl || !descEl || !activeEl) return;
            form.action = `/admin/categories/${id}`;
            nameEl.value = String(name || '');
            descEl.value = String(description || '');
            activeEl.checked = !!isActive;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditCategory() {
            const modal = document.getElementById('edit-category-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    </script>
</x-app-layout>
