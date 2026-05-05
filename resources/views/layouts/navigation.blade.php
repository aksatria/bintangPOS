@php($user = auth()->user())
@php($activeBranchId = \App\Support\ActiveBranchContext::resolveBranchId($user))
@php($availableBranches = \App\Support\ActiveBranchContext::availableBranchesFor($user))
@php($isOwnerOrAdmin = $user?->hasAnyRole(['owner', 'admin']) ?? false)
@php($canReportsView = $user?->hasPermission('reports.view') ?? false)
@php($canStockOpnameView = $user?->hasPermission('stock-opname.view') ?? false)
@php($canTransferView = $user?->hasPermission('stock-transfer.view') ?? false)
@php($canMasterData = $user?->hasPermission('master-data.manage') ?? false)
@php($canUsersManage = $user?->hasPermission('users.manage') ?? false)
@php($canSuppliersManage = $user?->hasPermission('suppliers.manage') ?? false)
@php($canPermissionsManage = $user?->hasPermission('permissions.manage') ?? false)
@php($canCustomersManage = $user?->hasPermission('customers.manage') ?? false)
@php($canCustomersFollowup = $user?->hasPermission('customers.followup.manage') ?? false)
@php($canCustomersDebt = $user?->hasPermission('customers.debt.manage') ?? false)
@php($canAuditLogsView = $user?->hasPermission('audit-logs.view') ?? false)
@php($canStoreSettings = $user?->hasPermission('settings.store.manage') ?? false)
@php($canNotificationSettings = $user?->hasPermission('settings.notification.manage') ?? false)
@php($canApprovalsManage = $user?->hasPermission('approvals.manage') ?? false)
@php($canAccountingView = $canSuppliersManage)
@php($supplierPendingApprovals = (int) data_get($navigationBadges ?? [], 'supplier_pending_approvals', 0))
@php($supplierOverdueDebts = (int) data_get($navigationBadges ?? [], 'supplier_overdue_debts', 0))
@php($supplierOpenDebts = (int) data_get($navigationBadges ?? [], 'supplier_open_debts', 0))
@php($hasAnyMasterDataMenu = $canMasterData || $canSuppliersManage || $canUsersManage || $canCustomersManage || $canCustomersFollowup || $canCustomersDebt)
@php($hasAnyControlSystemMenu = $canAuditLogsView || $canStoreSettings || $canNotificationSettings || $canPermissionsManage || $canApprovalsManage)
@php($isMasterDataActive = request()->is('admin/categories*') || request()->is('admin/products*') || request()->is('admin/expenses*') || request()->is('admin/suppliers*') || request()->is('admin/supplier-debts*') || request()->is('admin/supplier-purchases*') || request()->is('backoffice/expenses*') || request()->is('admin/users*') || request()->is('backoffice/users*') || request()->routeIs('customers.*'))
@php($isCustomerFollowupsActive = request()->routeIs('customers.followups'))
@php($isCustomersDebtActive = request()->routeIs('customers.debts.*'))
@php($isCustomersActive = request()->routeIs('customers.*') && ! $isCustomerFollowupsActive && ! $isCustomersDebtActive)
@php($isPosActive = request()->routeIs('pos.*') || request()->routeIs('sales.*'))
@php($isStockOpnameActive = request()->routeIs('stock-opnames.*'))
@php($isStockTransferActive = request()->routeIs('stock-transfers.*'))
@php($isOperationalActive = $isPosActive || request()->routeIs('reports.*') || $isStockOpnameActive || $isStockTransferActive)
@php($isAccountingActive = request()->routeIs('admin.accounting.*'))
@php($isControlSystemActive = request()->routeIs('audit-logs.*') || request()->is('admin/store-settings*') || request()->is('admin/notification-settings*') || request()->is('admin/rbac*') || request()->is('admin/approvals*'))

<ul>
    @if($isOwnerOrAdmin && $availableBranches->isNotEmpty())
        <li class="mb-4">
            <form method="POST" action="{{ route('context.active-branch.update') }}" class="px-3 app-branch-switcher">
                @csrf
                <label for="active_branch_id" class="app-branch-switcher__label">Cabang Aktif</label>
                <select id="active_branch_id" name="branch_id" class="app-branch-switcher__select" onchange="this.form.submit()">
                    @foreach($availableBranches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) $activeBranchId === (int) $branch->id)>
                            {{ $branch->name }} ({{ $branch->code }})
                        </option>
                    @endforeach
                </select>
            </form>
        </li>
    @endif

    <li>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
            <div class="side-menu__icon menu__icon"><i data-feather="activity"></i></div>
            <div class="side-menu__title menu__title">Dashboard Penjualan</div>
        </a>
    </li>
    @if($isOwnerOrAdmin)
        <li>
            <a href="javascript:;" class="{{ $isOperationalActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                <div class="side-menu__icon menu__icon"><i data-feather="activity"></i></div>
                <div class="side-menu__title menu__title">
                    Operasional
                    <i data-feather="chevron-down" class="side-menu__sub-icon menu__sub-icon"></i>
                </div>
            </a>
            <ul class="{{ $isOperationalActive ? 'side-menu__sub-open menu__sub-open' : '' }}">
                <li>
                    <a href="{{ route('pos.index') }}" class="{{ $isPosActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="shopping-cart"></i></div>
                        <div class="side-menu__title menu__title">Point of Sale</div>
                    </a>
                </li>
                @if($canReportsView)
                <li>
                    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="bar-chart-2"></i></div>
                        <div class="side-menu__title menu__title">Laporan</div>
                    </a>
                </li>
                @endif
                @if($canStockOpnameView)
                <li>
                    <a href="{{ route('stock-opnames.index') }}" class="{{ $isStockOpnameActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="archive"></i></div>
                        <div class="side-menu__title menu__title">Stock Opname</div>
                    </a>
                </li>
                @endif
                @if($canTransferView)
                <li>
                    <a href="{{ route('stock-transfers.index') }}" class="{{ $isStockTransferActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="repeat"></i></div>
                        <div class="side-menu__title menu__title">Mutasi Stok</div>
                    </a>
                </li>
                @endif
            </ul>
        </li>
    @else
        <li>
            <a href="{{ route('pos.index') }}" class="{{ $isPosActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                <div class="side-menu__icon menu__icon"><i data-feather="shopping-cart"></i></div>
                <div class="side-menu__title menu__title">Point of Sale</div>
            </a>
        </li>
    @endif

    <li class="side-nav__devider my-6"></li>
    @if($isOwnerOrAdmin)
        <li>
            <a href="javascript:;" class="{{ $isMasterDataActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                <div class="side-menu__icon menu__icon"><i data-feather="database"></i></div>
                <div class="side-menu__title menu__title">
                    Master Data
                    <i data-feather="chevron-down" class="side-menu__sub-icon menu__sub-icon"></i>
                </div>
            </a>
            <ul class="{{ $isMasterDataActive ? 'side-menu__sub-open menu__sub-open' : '' }}">
                @if($hasAnyMasterDataMenu)
                @if($canMasterData)
                <li>
                    <a href="{{ url('/admin/categories') }}" class="{{ request()->is('admin/categories*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="layers"></i></div>
                        <div class="side-menu__title menu__title">Kategori</div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/admin/products') }}" class="{{ request()->is('admin/products*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="box"></i></div>
                        <div class="side-menu__title menu__title">Produk</div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/admin/expenses') }}" class="{{ request()->is('admin/expenses*') || request()->is('backoffice/expenses*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="credit-card"></i></div>
                        <div class="side-menu__title menu__title">Pengeluaran</div>
                    </a>
                </li>
                @endif
                @if($canSuppliersManage)
                <li>
                    <a href="{{ route('admin.suppliers.index') }}" class="{{ request()->is('admin/suppliers*') || request()->is('admin/supplier-debts*') || request()->is('admin/supplier-purchases*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="truck"></i></div>
                        <div class="side-menu__title menu__title">
                            Vendor
                            @if($supplierOverdueDebts > 0)
                                <span style="margin-left:auto; min-width:1.35rem; height:1.35rem; padding:0 .35rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; background:#fff1f2; color:#be123c; border:1px solid #fecdd3; font-size:.68rem; font-weight:800;">{{ $supplierOverdueDebts }}</span>
                            @elseif($supplierOpenDebts > 0)
                                <span style="margin-left:auto; min-width:1.35rem; height:1.35rem; padding:0 .35rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:.68rem; font-weight:800;">{{ $supplierOpenDebts }}</span>
                            @endif
                        </div>
                    </a>
                </li>
                @endif
                @if($canUsersManage)
                <li>
                    <a href="{{ url('/admin/users') }}" class="{{ request()->is('admin/users*') || request()->is('backoffice/users*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="users"></i></div>
                        <div class="side-menu__title menu__title">Pengguna</div>
                    </a>
                </li>
                @endif
                @if($canCustomersManage)
                <li>
                    <a href="{{ route('customers.index') }}" class="{{ $isCustomersActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="user-check"></i></div>
                        <div class="side-menu__title menu__title">Pelanggan</div>
                    </a>
                </li>
                @endif
                @if($canCustomersFollowup)
                <li>
                    <a href="{{ route('customers.followups') }}" class="{{ $isCustomerFollowupsActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="clock"></i></div>
                        <div class="side-menu__title menu__title">Follow-up Hari Ini</div>
                    </a>
                </li>
                @endif
                @if($canCustomersDebt)
                <li>
                    <a href="{{ route('customers.debts.index') }}" class="{{ $isCustomersDebtActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="file-text"></i></div>
                        <div class="side-menu__title menu__title">Piutang Pelanggan</div>
                    </a>
                </li>
                @endif
                @else
                <li>
                    <span class="side-menu cursor-default opacity-70">
                        <div class="side-menu__icon menu__icon"><i data-feather="slash"></i></div>
                        <div class="side-menu__title menu__title">Belum ada akses modul master data</div>
                    </span>
                </li>
                @endif
            </ul>
        </li>
    @endif

    <li class="side-nav__devider my-6"></li>
    @if($isOwnerOrAdmin && $canAccountingView)
        <li>
            <a href="javascript:;" class="{{ $isAccountingActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                <div class="side-menu__icon menu__icon"><i data-feather="pie-chart"></i></div>
                <div class="side-menu__title menu__title">
                    Akuntansi
                    <i data-feather="chevron-down" class="side-menu__sub-icon menu__sub-icon"></i>
                </div>
            </a>
            <ul class="{{ $isAccountingActive ? 'side-menu__sub-open menu__sub-open' : '' }}">
                <li>
                    <a href="{{ route('admin.accounting.reports') }}" class="{{ request()->routeIs('admin.accounting.reports') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="bar-chart-2"></i></div>
                        <div class="side-menu__title menu__title">Laporan Akuntansi</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.accounting.journals') }}" class="{{ request()->routeIs('admin.accounting.journals') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="book-open"></i></div>
                        <div class="side-menu__title menu__title">Jurnal Umum</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.accounting.ledger') }}" class="{{ request()->routeIs('admin.accounting.ledger') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="list"></i></div>
                        <div class="side-menu__title menu__title">Buku Besar</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="side-nav__devider my-6"></li>
    @endif

    @if($isOwnerOrAdmin)
        <li>
            <a href="javascript:;" class="{{ $isControlSystemActive ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                <div class="side-menu__icon menu__icon"><i data-feather="shield"></i></div>
                <div class="side-menu__title menu__title">
                    Kontrol & Sistem
                    <i data-feather="chevron-down" class="side-menu__sub-icon menu__sub-icon"></i>
                </div>
            </a>
            <ul class="{{ $isControlSystemActive ? 'side-menu__sub-open menu__sub-open' : '' }}">
                @if($hasAnyControlSystemMenu)
                @if($canAuditLogsView)
                <li>
                    <a href="{{ route('audit-logs.index') }}" class="{{ request()->routeIs('audit-logs.*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="clipboard"></i></div>
                        <div class="side-menu__title menu__title">Audit Log Kasir</div>
                    </a>
                </li>
                @endif
                @if($canStoreSettings)
                <li>
                    <a href="{{ url('/admin/store-settings') }}" class="{{ request()->is('admin/store-settings*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="settings"></i></div>
                        <div class="side-menu__title menu__title">Pengaturan Toko</div>
                    </a>
                </li>
                @endif
                @if($canNotificationSettings)
                <li>
                    <a href="{{ url('/admin/notification-settings') }}" class="{{ request()->is('admin/notification-settings*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="send"></i></div>
                        <div class="side-menu__title menu__title">Notif Telegram</div>
                    </a>
                </li>
                @endif
                @if($canPermissionsManage)
                <li>
                    <a href="{{ route('admin.rbac.index') }}" class="{{ request()->is('admin/rbac*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="shield"></i></div>
                        <div class="side-menu__title menu__title">RBAC Permission</div>
                    </a>
                </li>
                @endif
                @if($canApprovalsManage)
                <li>
                    <a href="{{ route('admin.approvals.index') }}" class="{{ request()->is('admin/approvals*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
                        <div class="side-menu__icon menu__icon"><i data-feather="check-square"></i></div>
                        <div class="side-menu__title menu__title">
                            Approval Queue
                            @if($supplierPendingApprovals > 0)
                                <span style="margin-left:auto; min-width:1.35rem; height:1.35rem; padding:0 .35rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; background:#fffbeb; color:#92400e; border:1px solid #fcd34d; font-size:.68rem; font-weight:800;">{{ $supplierPendingApprovals }}</span>
                            @endif
                        </div>
                    </a>
                </li>
                @endif
                @else
                <li>
                    <span class="side-menu cursor-default opacity-70">
                        <div class="side-menu__icon menu__icon"><i data-feather="slash"></i></div>
                        <div class="side-menu__title menu__title">Belum ada akses modul kontrol sistem</div>
                    </span>
                </li>
                @endif
            </ul>
        </li>
        <li class="side-nav__devider my-6"></li>
    @endif

    <li>
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'side-menu menu side-menu--active menu--active' : 'side-menu menu' }}">
            <div class="side-menu__icon menu__icon"><i data-feather="user"></i></div>
            <div class="side-menu__title menu__title">Profile</div>
        </a>
    </li>
    <li>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="side-menu w-full text-left" type="submit">
                <div class="side-menu__icon menu__icon"><i data-feather="log-out"></i></div>
                <div class="side-menu__title menu__title">Log Out</div>
            </button>
        </form>
    </li>
</ul>
