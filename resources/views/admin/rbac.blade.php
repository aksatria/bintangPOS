<x-app-layout>
    <x-slot name="header">
        <div>
            <p style="margin:0;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:700;">Kontrol & Sistem</p>
            <h2 style="margin:.25rem 0 0;font-size:1.8rem;font-weight:800;color:#0f172a;">RBAC Permission Matrix</h2>
        </div>
    </x-slot>

    <div style="max-width:1200px;margin:0 auto;display:grid;gap:1rem;">
        @if(session('status'))
            <div style="border:1px solid #86efac;background:#f0fdf4;color:#166534;border-radius:12px;padding:.7rem .9rem;font-size:.88rem;">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div style="border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;padding:.7rem .9rem;font-size:.88rem;">
                {{ $errors->first() }}
            </div>
        @endif
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem;">
            <div style="border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;padding:.55rem .7rem;">
                <div style="font-size:.7rem;color:#92400e;font-weight:700;text-transform:uppercase;">Expiring &lt;24j</div>
                <div style="font-size:1.2rem;font-weight:800;color:#78350f;">{{ number_format((int) data_get($grantRiskCounts ?? [], 'expiring_lt_24h', 0), 0, ',', '.') }}</div>
            </div>
            <div style="border:1px solid #fca5a5;background:#fff1f2;border-radius:10px;padding:.55rem .7rem;">
                <div style="font-size:.7rem;color:#9f1239;font-weight:700;text-transform:uppercase;">Expired tapi aktif</div>
                <div style="font-size:1.2rem;font-weight:800;color:#881337;">{{ number_format((int) data_get($grantRiskCounts ?? [], 'expired_but_active', 0), 0, ',', '.') }}</div>
            </div>
            <div style="border:1px solid #cbd5e1;background:#f8fafc;border-radius:10px;padding:.55rem .7rem;">
                <div style="font-size:.7rem;color:#334155;font-weight:700;text-transform:uppercase;">Tanpa reason</div>
                <div style="font-size:1.2rem;font-weight:800;color:#0f172a;">{{ number_format((int) data_get($grantRiskCounts ?? [], 'without_reason', 0), 0, ',', '.') }}</div>
            </div>
        </div>

        <div style="border:1px solid #dbe4f0;border-radius:14px;background:#fff;overflow:hidden;">

            <div style="padding:.9rem 1rem;border-bottom:1px solid #e7eef7;background:#f8fbff;font-size:.86rem;color:#334155;">
                Centang permission per role. Perubahan akan langsung menggantikan mapping sebelumnya.
            </div>
            <div style="padding:.8rem 1rem;border-bottom:1px solid #e7eef7;background:#fff;display:flex;flex-wrap:wrap;gap:.5rem;">
                <button type="button" data-copy-role="owner:admin" style="min-height:2rem;padding:0 .75rem;border:1px solid #cbd5e1;background:#fff;border-radius:8px;font-size:.78rem;font-weight:700;color:#334155;">Copy OWNER → ADMIN</button>
                <button type="button" data-copy-role="admin:kasir" style="min-height:2rem;padding:0 .75rem;border:1px solid #cbd5e1;background:#fff;border-radius:8px;font-size:.78rem;font-weight:700;color:#334155;">Copy ADMIN → KASIR</button>
                <button type="button" data-copy-role="owner:kasir" style="min-height:2rem;padding:0 .75rem;border:1px solid #cbd5e1;background:#fff;border-radius:8px;font-size:.78rem;font-weight:700;color:#334155;">Copy OWNER → KASIR</button>
                <button type="button" data-role-all="owner" style="min-height:2rem;padding:0 .75rem;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;font-size:.78rem;font-weight:700;color:#1d4ed8;">OWNER All</button>
                <button type="button" data-role-clear="owner" style="min-height:2rem;padding:0 .75rem;border:1px solid #fecaca;background:#fff1f2;border-radius:8px;font-size:.78rem;font-weight:700;color:#b91c1c;">OWNER Clear</button>
                <button type="button" data-role-all="admin" style="min-height:2rem;padding:0 .75rem;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;font-size:.78rem;font-weight:700;color:#1d4ed8;">ADMIN All</button>
                <button type="button" data-role-clear="admin" style="min-height:2rem;padding:0 .75rem;border:1px solid #fecaca;background:#fff1f2;border-radius:8px;font-size:.78rem;font-weight:700;color:#b91c1c;">ADMIN Clear</button>
                <button type="button" data-role-all="kasir" style="min-height:2rem;padding:0 .75rem;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;font-size:.78rem;font-weight:700;color:#1d4ed8;">KASIR All</button>
                <button type="button" data-role-clear="kasir" style="min-height:2rem;padding:0 .75rem;border:1px solid #fecaca;background:#fff1f2;border-radius:8px;font-size:.78rem;font-weight:700;color:#b91c1c;">KASIR Clear</button>
            </div>

            <div style="overflow:auto;">
                <table style="width:100%;min-width:900px;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;color:#334155;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                            <th style="text-align:left;padding:.7rem .8rem;border-bottom:1px solid #e2e8f0;">Permission</th>
                            <th style="text-align:left;padding:.7rem .8rem;border-bottom:1px solid #e2e8f0;">Group</th>
                            @foreach($roles as $role)
                                <th style="text-align:center;padding:.7rem .8rem;border-bottom:1px solid #e2e8f0;">{{ strtoupper($role) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                            <tr style="border-bottom:1px solid #eef2f7;">
                                <td style="padding:.65rem .8rem;">
                                    <div style="font-weight:700;color:#0f172a;">{{ $permission->name }}</div>
                                    <div style="font-size:.76rem;color:#64748b;font-family:monospace;">{{ $permission->code }}</div>
                                </td>
                                <td style="padding:.65rem .8rem;color:#475569;font-size:.82rem;">{{ strtoupper((string) $permission->group) }}</td>
                                @foreach($roles as $role)
                                    @php($checked = in_array($permission->code, (array) ($assigned[$role] ?? []), true))
                                    <td style="text-align:center;padding:.65rem .8rem;">
                                        <input type="checkbox" name="permissions[{{ $role }}][]" value="{{ $permission->code }}" @checked($checked)>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:.85rem 1rem;border-top:1px solid #e7eef7;display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <form method="POST" action="{{ route('admin.rbac.reset-default') }}" onsubmit="return confirm('Reset semua mapping role ke default konfigurasi?');">
                        @csrf
                        <button type="submit" style="min-height:2.25rem;padding:0 .9rem;border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;border-radius:10px;font-size:.78rem;font-weight:700;">Reset ke Default</button>
                    </form>
                    <form method="POST" action="{{ route('admin.rbac.update') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="permissions_payload" id="permissions-payload">
                        <button type="submit" id="submit-rbac-btn" style="min-height:2.45rem;padding:0 1rem;border:1px solid #1d4ed8;background:#1d4ed8;color:#fff;border-radius:10px;font-size:.82rem;font-weight:700;">Simpan Mapping RBAC</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="border:1px solid #dbe4f0;border-radius:14px;background:#fff;overflow:hidden;">
            <div style="padding:.9rem 1rem;border-bottom:1px solid #e7eef7;background:#f8fbff;font-size:.86rem;color:#334155;">
                Temporary Permission Grant (dengan expiry)
            </div>
            <div style="padding:1rem;display:grid;gap:1rem;">
                <form method="POST" action="{{ route('admin.rbac.temp-grants.store') }}" style="display:grid;grid-template-columns:1.1fr .8fr .8fr .8fr 1.2fr auto;gap:.55rem;align-items:end;">
                    @csrf
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Permission Code</span>
                        <input type="text" name="permission_code" style="min-height:2.35rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;" placeholder="reports.export">
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Scope</span>
                        <select name="scope_type" style="min-height:2.35rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="role">Role</option>
                            <option value="user">User</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Role</span>
                        <select name="role" style="min-height:2.35rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="">-</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ strtoupper($role) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">User</span>
                        <select name="user_id" style="min-height:2.35rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="">-</option>
                            @foreach($approvers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ strtoupper((string) ($u->role?->value ?? $u->role ?? '-')) }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Durasi + Alasan</span>
                        <div style="display:grid;grid-template-columns:110px 1fr;gap:.4rem;">
                            <input type="number" min="1" max="720" name="duration_hours" value="24" style="min-height:2.35rem;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <input type="text" name="reason" style="min-height:2.35rem;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;" placeholder="Alasan grant sementara">
                        </div>
                    </label>
                    <button type="submit" style="min-height:2.35rem;padding:0 .9rem;border:1px solid #059669;background:#10b981;color:#fff;border-radius:8px;font-size:.78rem;font-weight:700;">Buat Grant</button>
                </form>

                <form method="GET" action="{{ route('admin.rbac.index') }}" style="display:grid;grid-template-columns:1.2fr .8fr .8fr .9fr auto;gap:.5rem;align-items:end;">
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Cari Permission / Alasan</span>
                        <input type="text" name="q" value="{{ data_get($grantFilters ?? [], 'q', '') }}" style="min-height:2.25rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;" placeholder="reports.export / emergency">
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Status</span>
                        <select name="active" style="min-height:2.25rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="all" @selected(data_get($grantFilters ?? [], 'active') === 'all')>Semua</option>
                            <option value="active" @selected(data_get($grantFilters ?? [], 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(data_get($grantFilters ?? [], 'active') === 'inactive')>Inactive</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Scope</span>
                        <select name="scope" style="min-height:2.25rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="all" @selected(data_get($grantFilters ?? [], 'scope') === 'all')>Semua</option>
                            <option value="role" @selected(data_get($grantFilters ?? [], 'scope') === 'role')>Role</option>
                            <option value="user" @selected(data_get($grantFilters ?? [], 'scope') === 'user')>User</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:.25rem;">Urutkan Expire</span>
                        <select name="sort" style="min-height:2.25rem;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0 .6rem;">
                            <option value="expires_asc" @selected(data_get($grantFilters ?? [], 'sort') === 'expires_asc')>Terdekat Dulu</option>
                            <option value="expires_desc" @selected(data_get($grantFilters ?? [], 'sort') === 'expires_desc')>Terlama Dulu</option>
                        </select>
                    </label>
                    <button type="submit" style="min-height:2.25rem;padding:0 .8rem;border:1px solid #2563eb;background:#2563eb;color:#fff;border-radius:8px;font-size:.76rem;font-weight:700;">Filter</button>
                </form>

                <div style="overflow:auto;">
                    <table style="width:100%;min-width:760px;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                <th style="text-align:left;padding:.55rem .6rem;font-size:.75rem;">Permission</th>
                                <th style="text-align:left;padding:.55rem .6rem;font-size:.75rem;">Scope</th>
                                <th style="text-align:left;padding:.55rem .6rem;font-size:.75rem;">Alasan</th>
                                <th style="text-align:left;padding:.55rem .6rem;font-size:.75rem;">Expire</th>
                                <th style="text-align:left;padding:.55rem .6rem;font-size:.75rem;">Status</th>
                                <th style="text-align:right;padding:.55rem .6rem;font-size:.75rem;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($temporaryGrants as $grant)
                                <tr style="border-bottom:1px solid #eef2f7;">
                                    <td style="padding:.55rem .6rem;font-family:monospace;font-size:.78rem;">{{ $grant->permission_code }}</td>
                                    <td style="padding:.55rem .6rem;font-size:.78rem;">{{ $grant->user_id ? ('User #'.$grant->user_id) : strtoupper((string) $grant->role) }}</td>
                                    <td style="padding:.55rem .6rem;font-size:.78rem;">{{ $grant->reason ?: '-' }}</td>
                                    <td style="padding:.55rem .6rem;font-size:.78rem;">{{ optional($grant->expires_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td style="padding:.55rem .6rem;font-size:.78rem;">
                                        @if($grant->is_active)
                                            <span style="display:inline-block;padding:.15rem .45rem;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;">ACTIVE</span>
                                        @else
                                            <span style="display:inline-block;padding:.15rem .45rem;border-radius:999px;background:#f1f5f9;color:#475569;font-weight:700;">INACTIVE</span>
                                        @endif
                                        @php($hoursLeft = $grant->expires_at ? now()->diffInHours($grant->expires_at, false) : null)
                                        @if($grant->is_active && $grant->expires_at && $hoursLeft < 0)
                                            <span style="display:inline-block;margin-left:.25rem;padding:.15rem .45rem;border-radius:999px;background:#fee2e2;color:#991b1b;font-weight:700;">Expired tapi aktif</span>
                                        @elseif($grant->is_active && $grant->expires_at && $hoursLeft <= 24)
                                            <span style="display:inline-block;margin-left:.25rem;padding:.15rem .45rem;border-radius:999px;background:#fef3c7;color:#92400e;font-weight:700;">Expiring &lt;24j</span>
                                        @endif
                                        @if(trim((string) $grant->reason) === '')
                                            <span style="display:inline-block;margin-left:.25rem;padding:.15rem .45rem;border-radius:999px;background:#e2e8f0;color:#334155;font-weight:700;">Tanpa reason</span>
                                        @endif
                                    </td>
                                    <td style="padding:.55rem .6rem;text-align:right;">
                                        @if($grant->is_active)
                                            <form method="POST" action="{{ route('admin.rbac.temp-grants.revoke', $grant) }}">
                                                @csrf
                                                <button type="submit" style="min-height:1.95rem;padding:0 .65rem;border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;border-radius:7px;font-size:.74rem;font-weight:700;">Revoke</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding:.7rem .6rem;color:#64748b;font-size:.78rem;">Belum ada temporary grant.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const copyButtons = document.querySelectorAll('[data-copy-role]');
            const allButtons = document.querySelectorAll('[data-role-all]');
            const clearButtons = document.querySelectorAll('[data-role-clear]');

            const getRoleChecks = (role) => Array.from(document.querySelectorAll(`input[type="checkbox"][name="permissions[${role}][]"]`));

            copyButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const raw = String(btn.getAttribute('data-copy-role') || '');
                    const [fromRole, toRole] = raw.split(':');
                    if (!fromRole || !toRole) return;
                    const fromChecks = getRoleChecks(fromRole);
                    const toChecks = getRoleChecks(toRole);
                    if (fromChecks.length !== toChecks.length) return;
                    fromChecks.forEach((fromCheck, idx) => {
                        toChecks[idx].checked = fromCheck.checked;
                    });
                });
            });

            allButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const role = String(btn.getAttribute('data-role-all') || '');
                    getRoleChecks(role).forEach((check) => { check.checked = true; });
                });
            });

            clearButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const role = String(btn.getAttribute('data-role-clear') || '');
                    getRoleChecks(role).forEach((check) => { check.checked = false; });
                });
            });

            const submitBtn = document.getElementById('submit-rbac-btn');
            submitBtn?.addEventListener('click', (e) => {
                const payload = {};
                ['owner', 'admin', 'kasir'].forEach((role) => {
                    payload[role] = getRoleChecks(role).filter((c) => c.checked).map((c) => String(c.value || ''));
                });
                const hidden = document.getElementById('permissions-payload');
                if (!hidden) return;
                hidden.value = JSON.stringify(payload);
            });
        })();
    </script>
</x-app-layout>
