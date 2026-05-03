<x-app-layout>
    <x-slot name="header">
        <div class="ad-head pro-page-head intro-y">
            <div>
                <p class="ad-kicker">Master Data</p>
                <h2 class="ad-title pro-page-title">Pengguna</h2>
                <p class="ad-sub pro-page-sub">Kelola akun, role, dan assignment cabang dengan standar kontrol akses yang rapi.</p>
            </div>
        </div>
    </x-slot>


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
            <div class="ad-card pro-panel">
                <div class="ad-headbar pro-headbar"><h3 class="ad-card-title pro-section-title">Daftar Pengguna</h3></div>
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
