<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActiveBranchController;
use App\Http\Controllers\CashierAuditLogController;
use App\Http\Controllers\AdminMasterDataController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'branch.access'])->group(function () {
    Route::post('/context/active-branch', [ActiveBranchController::class, 'update'])
        ->middleware('role:owner,admin')
        ->name('context.active-branch.update');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportSnapshotPdf'])->name('dashboard.export.pdf');
    Route::get('/dashboard/audit-anomaly-status', [DashboardController::class, 'auditAnomalyStatus'])->name('dashboard.audit-anomaly-status');
    Route::post('/dashboard/audit-anomaly-ack', [DashboardController::class, 'acknowledgeAuditAnomaly'])->name('dashboard.audit-anomaly-ack');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:owner,admin,kasir')->group(function () {
        Route::get('/kasir/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/kasir/search-products', [PosController::class, 'search'])->name('pos.search-products');
        Route::post('/kasir/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('/kasir/quick-refund', [PosController::class, 'quickRefund'])->name('pos.quick-refund');
        Route::post('/kasir/audit-event', [PosController::class, 'auditEvent'])->name('pos.audit-event');
        Route::post('/kasir/reconcile-shift', [PosController::class, 'reconcileShift'])->name('pos.reconcile-shift');
        Route::get('/kasir/holds', [PosController::class, 'listHolds'])->name('pos.holds.index');
        Route::post('/kasir/holds', [PosController::class, 'saveHold'])->name('pos.holds.store');
        Route::get('/kasir/holds/{hold}', [PosController::class, 'loadHold'])->name('pos.holds.show');
        Route::delete('/kasir/holds/{hold}', [PosController::class, 'deleteHold'])->name('pos.holds.delete');
        Route::patch('/kasir/holds/{hold}', [PosController::class, 'renameHold'])->name('pos.holds.rename');

        Route::get('/sales/pending-attempts/history', [SaleController::class, 'pendingAttempts'])->name('sales.pending-attempts');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('/sales/{sale}/receipt-print', [SaleController::class, 'receiptPrint'])->name('sales.receipt-print');
        Route::post('/sales/{sale}/quick-settle-pending', [SaleController::class, 'quickSettlePending'])->name('sales.quick-settle-pending');
        Route::post('/sales/{sale}/reprint-receipt', [SaleController::class, 'reprintReceipt'])->name('sales.reprint-receipt');
        Route::post('/sales/{sale}/quick-partial-refund', [SaleController::class, 'quickPartialRefund'])->middleware('permission:sales.correction.manage')->name('sales.quick-partial-refund');
    });

    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/admin', fn () => redirect()->route('admin.categories.index'))->name('admin.home');
        Route::get('/admin/users', [AdminMasterDataController::class, 'usersIndex'])->middleware('permission:users.manage')->name('admin.users.index');
        Route::get('/admin/users/suggest', [AdminMasterDataController::class, 'usersSuggest'])->middleware('permission:users.manage')->name('admin.users.suggest');
        Route::post('/admin/users', [AdminMasterDataController::class, 'usersStore'])->middleware('permission:users.manage')->name('admin.users.store');
        Route::put('/admin/users/{user}', [AdminMasterDataController::class, 'usersUpdate'])->middleware('permission:users.manage')->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminMasterDataController::class, 'usersDestroy'])->middleware('permission:users.manage')->name('admin.users.destroy');
        Route::post('/admin/branches', [AdminMasterDataController::class, 'branchesStore'])->middleware('permission:users.manage')->name('admin.branches.store');
        Route::put('/admin/branches/{branch}', [AdminMasterDataController::class, 'branchesUpdate'])->middleware('permission:users.manage')->name('admin.branches.update');
        Route::delete('/admin/branches/{branch}', [AdminMasterDataController::class, 'branchesDestroy'])->middleware('permission:users.manage')->name('admin.branches.destroy');
        Route::get('/admin/expenses', [AdminMasterDataController::class, 'expensesIndex'])->middleware('permission:master-data.manage')->name('admin.expenses.index');
        Route::post('/admin/expenses', [AdminMasterDataController::class, 'expensesStore'])->middleware('permission:master-data.manage')->name('admin.expenses.store');
        Route::put('/admin/expenses/{expense}', [AdminMasterDataController::class, 'expensesUpdate'])->middleware('permission:master-data.manage')->name('admin.expenses.update');
        Route::delete('/admin/expenses/{expense}', [AdminMasterDataController::class, 'expensesDestroy'])->middleware('permission:master-data.manage')->name('admin.expenses.destroy');
        Route::post('/admin/expenses/{expense}/duplicate', [AdminMasterDataController::class, 'expensesDuplicate'])->middleware('permission:master-data.manage')->name('admin.expenses.duplicate');
        Route::get('/admin/expenses/export/csv', [AdminMasterDataController::class, 'expensesExportCsv'])->middleware('permission:master-data.manage')->name('admin.expenses.export.csv');
        Route::get('/dashboard/telegram-test', [DashboardController::class, 'testTelegramAlert'])->name('dashboard.telegram-test');
        Route::get('/admin/categories', [CategoryManagementController::class, 'index'])->middleware('permission:master-data.manage')->name('admin.categories.index');
        Route::post('/admin/categories', [CategoryManagementController::class, 'store'])->middleware('permission:master-data.manage')->name('admin.categories.store');
        Route::put('/admin/categories/{category}', [CategoryManagementController::class, 'update'])->middleware('permission:master-data.manage')->name('admin.categories.update');
        Route::delete('/admin/categories/{category}', [CategoryManagementController::class, 'destroy'])->middleware('permission:master-data.manage')->name('admin.categories.destroy');
        Route::get('/admin/products', [ProductManagementController::class, 'index'])->middleware('permission:master-data.manage')->name('admin.products.index');
        Route::post('/admin/products', [ProductManagementController::class, 'store'])->middleware('permission:master-data.manage')->name('admin.products.store');
        Route::post('/admin/products/bulk-update', [ProductManagementController::class, 'bulkUpdate'])->middleware('permission:master-data.manage')->name('admin.products.bulk-update');
        Route::put('/admin/products/{product}', [ProductManagementController::class, 'update'])->middleware('permission:master-data.manage')->name('admin.products.update');
        Route::delete('/admin/products/{product}', [ProductManagementController::class, 'destroy'])->middleware('permission:master-data.manage')->name('admin.products.destroy');
        Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('permission:customers.manage')->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.manage')->name('customers.store');
        Route::get('/customers/export/excel', [CustomerController::class, 'exportExcel'])->middleware('permission:customers.manage')->name('customers.export.excel');
        Route::get('/customers/export/followup/excel', [CustomerController::class, 'exportFollowUp'])->middleware('permission:customers.followup.manage')->name('customers.export.followup.excel');
        Route::get('/customers/followups', [CustomerController::class, 'followUpQueue'])->middleware('permission:customers.followup.manage')->name('customers.followups');
        Route::get('/customers/suggest', [CustomerController::class, 'suggest'])->middleware('permission:customers.manage')->name('customers.suggest');
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.manage')->name('customers.index');
        Route::get('/customers/{customer}/export-history/excel', [CustomerController::class, 'exportPurchaseHistory'])->middleware('permission:customers.manage')->name('customers.export-history.excel');
        Route::get('/customers/{customer}/merge-suggest', [CustomerController::class, 'mergeSuggest'])->middleware('permission:customers.manage')->name('customers.merge-suggest');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('permission:customers.manage')->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.manage')->name('customers.update');
        Route::post('/customers/{customer}/merge', [CustomerController::class, 'merge'])->middleware('permission:customers.manage')->name('customers.merge');
        Route::post('/customers/{customer}/quick-followup', [CustomerController::class, 'quickFollowUp'])->middleware('permission:customers.followup.manage')->name('customers.quick-followup');
        Route::post('/customers/{customer}/quick-followup-inline', [CustomerController::class, 'quickCreateFollowUp'])->middleware('permission:customers.followup.manage')->name('customers.quick-followup-inline');
        Route::patch('/customers/followups/{followup}/status', [CustomerController::class, 'updateFollowUpStatus'])->middleware('permission:customers.followup.manage')->name('customers.followups.status');
        Route::post('/customers/{customer}/sales/{sale}/settle-pending', [CustomerController::class, 'settlePending'])->middleware('permission:customers.followup.manage')->name('customers.sales.settle-pending');
        Route::post('/customers/{customer}/sales/{sale}/cancel-pending', [CustomerController::class, 'cancelPending'])->middleware('permission:customers.followup.manage')->name('customers.sales.cancel-pending');
        Route::post('/customers/{customer}/sales/{sale}/reschedule-pending', [CustomerController::class, 'reschedulePending'])->middleware('permission:customers.followup.manage')->name('customers.sales.reschedule-pending');
        Route::patch('/customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->middleware('permission:customers.manage')->name('customers.toggle-active');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.manage')->name('customers.show');
        Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->middleware('permission:reports.export')->middleware('throttle:12,1')->name('reports.export.excel');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->middleware('permission:reports.export')->middleware('throttle:12,1')->name('reports.export.pdf');
        Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->middleware('permission:stock-opname.view')->name('stock-opnames.index');
        Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->middleware('permission:stock-opname.manage')->name('stock-opnames.store');
        Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->middleware('permission:stock-opname.view')->name('stock-opnames.show');
        Route::post('/stock-opnames/{stockOpname}/post', [StockOpnameController::class, 'post'])->middleware('permission:stock-opname.manage')->name('stock-opnames.post');
        Route::post('/stock-opnames/{stockOpname}/duplicate', [StockOpnameController::class, 'duplicate'])->middleware('permission:stock-opname.manage')->name('stock-opnames.duplicate');
        Route::get('/stock-opnames/{stockOpname}/export/csv', [StockOpnameController::class, 'exportCsv'])->middleware('permission:stock-opname.view')->name('stock-opnames.export.csv');
        Route::get('/stock-opnames/{stockOpname}/export/pdf', [StockOpnameController::class, 'exportPdf'])->middleware('permission:stock-opname.view')->name('stock-opnames.export.pdf');
        Route::get('/stock-opnames/{stockOpname}/template/csv', [StockOpnameController::class, 'templateCsv'])->middleware('permission:stock-opname.manage')->name('stock-opnames.template.csv');
        Route::post('/stock-opnames/{stockOpname}/import/csv', [StockOpnameController::class, 'importCsv'])->middleware('permission:stock-opname.manage')->name('stock-opnames.import.csv');
        Route::get('/stock-transfers', [StockTransferController::class, 'index'])->middleware('permission:stock-transfer.view')->name('stock-transfers.index');
        Route::get('/stock-transfers/{transferId}', [StockTransferController::class, 'show'])->middleware('permission:stock-transfer.view')->name('stock-transfers.show');
        Route::post('/stock-transfers', [StockTransferController::class, 'store'])->middleware('permission:stock-transfer.request')->name('stock-transfers.store');
        Route::post('/stock-transfers/{transferId}/approve', [StockTransferController::class, 'approve'])->middleware('permission:stock-transfer.approve')->name('stock-transfers.approve');
        Route::post('/stock-transfers/{transferId}/reject', [StockTransferController::class, 'reject'])->middleware('permission:stock-transfer.approve')->name('stock-transfers.reject');
        Route::post('/stock-transfers/{transferId}/receive', [StockTransferController::class, 'receive'])->middleware('permission:stock-transfer.receive')->name('stock-transfers.receive');
        Route::post('/stock-transfers/{transferId}/cancel', [StockTransferController::class, 'cancel'])->middleware('permission:stock-transfer.request')->name('stock-transfers.cancel');
        Route::get('/stock-transfers/{transferId}/export/csv', [StockTransferController::class, 'exportCsv'])->middleware('permission:stock-transfer.export')->name('stock-transfers.export.csv');
        Route::get('/stock-transfers/{transferId}/export/pdf', [StockTransferController::class, 'exportPdf'])->middleware('permission:stock-transfer.export')->name('stock-transfers.export.pdf');
        Route::get('/admin/store-settings', [StoreSettingController::class, 'edit'])->middleware('permission:settings.store.manage')->name('store-settings.edit');
        Route::put('/admin/store-settings', [StoreSettingController::class, 'update'])->middleware('permission:settings.store.manage')->name('store-settings.update');
        Route::get('/admin/store-settings/export-runtime-config', [StoreSettingController::class, 'exportRuntimeConfig'])->middleware('permission:settings.store.manage')->name('store-settings.export-runtime-config');
        Route::post('/admin/store-settings/import-runtime-config', [StoreSettingController::class, 'importRuntimeConfig'])->middleware('permission:settings.store.manage')->name('store-settings.import-runtime-config');
        Route::post('/sales/{sale}/quick-refund', [SaleController::class, 'quickRefund'])->middleware('permission:sales.correction.manage')->name('sales.quick-refund');
        Route::post('/sales/{sale}/quick-void', [SaleController::class, 'quickVoid'])->middleware('permission:sales.correction.manage')->name('sales.quick-void');
        Route::get('/audit-logs', [CashierAuditLogController::class, 'index'])->middleware('permission:audit-logs.view')->name('audit-logs.index');
        Route::get('/audit-logs/export/excel', [CashierAuditLogController::class, 'exportExcel'])->middleware('permission:audit-logs.view')->name('audit-logs.export.excel');
        Route::get('/audit-logs/export/pdf', [CashierAuditLogController::class, 'exportPdf'])->middleware('permission:audit-logs.view')->name('audit-logs.export.pdf');
        Route::get('/admin/notification-settings', [NotificationSettingController::class, 'edit'])->middleware('permission:settings.notification.manage')->name('notification-settings.edit');
        Route::put('/admin/notification-settings', [NotificationSettingController::class, 'update'])->middleware('permission:settings.notification.manage')->name('notification-settings.update');
        Route::post('/admin/notification-settings/test', [NotificationSettingController::class, 'test'])->middleware('permission:settings.notification.manage')->name('notification-settings.test');
        Route::post('/admin/notification-settings/preview-weekly-sla', [NotificationSettingController::class, 'previewWeeklySla'])->middleware('permission:settings.notification.manage')->name('notification-settings.preview-weekly-sla');
        Route::get('/admin/system-health', [SystemHealthController::class, 'index'])->middleware('permission:audit-logs.view')->name('admin.system-health.index');
        Route::post('/admin/system-health/run-now', [SystemHealthController::class, 'runNow'])->middleware('permission:audit-logs.view')->name('admin.system-health.run-now');
        Route::get('/admin/rbac', [RbacController::class, 'index'])->middleware('permission:permissions.manage')->name('admin.rbac.index');
        Route::put('/admin/rbac', [RbacController::class, 'update'])->middleware('permission:permissions.manage')->name('admin.rbac.update');
        Route::post('/admin/rbac/reset-default', [RbacController::class, 'resetDefault'])->middleware('permission:permissions.manage')->name('admin.rbac.reset-default');
        Route::post('/admin/rbac/temp-grants', [RbacController::class, 'grantTemporary'])->middleware('permission:permissions.manage')->name('admin.rbac.temp-grants.store');
        Route::post('/admin/rbac/temp-grants/{grant}/revoke', [RbacController::class, 'revokeTemporary'])->middleware('permission:permissions.manage')->name('admin.rbac.temp-grants.revoke');
        Route::get('/admin/approvals', [ApprovalRequestController::class, 'index'])->middleware('permission:approvals.manage')->name('admin.approvals.index');
        Route::post('/admin/approvals/bulk-approve', [ApprovalRequestController::class, 'approveBulk'])->middleware('permission:approvals.manage')->name('admin.approvals.bulk-approve');
        Route::post('/admin/approvals/bulk-reject', [ApprovalRequestController::class, 'rejectBulk'])->middleware('permission:approvals.manage')->name('admin.approvals.bulk-reject');
        Route::post('/admin/approvals/bulk-execute-exports', [ApprovalRequestController::class, 'bulkExecuteExports'])->middleware('permission:approvals.manage')->name('admin.approvals.bulk-execute-exports');
        Route::post('/admin/approvals/{approval}/approve', [ApprovalRequestController::class, 'approve'])->middleware('permission:approvals.manage')->name('admin.approvals.approve');
        Route::post('/admin/approvals/{approval}/reject', [ApprovalRequestController::class, 'reject'])->middleware('permission:approvals.manage')->name('admin.approvals.reject');
        Route::post('/admin/approvals/{approval}/assign', [ApprovalRequestController::class, 'assign'])->middleware('permission:approvals.manage')->name('admin.approvals.assign');
        Route::post('/admin/approvals/{approval}/snooze', [ApprovalRequestController::class, 'snooze'])->middleware('permission:approvals.manage')->name('admin.approvals.snooze');
        Route::post('/admin/approvals/{approval}/unsnooze', [ApprovalRequestController::class, 'unsnooze'])->middleware('permission:approvals.manage')->name('admin.approvals.unsnooze');
        Route::get('/admin/approvals/{approval}/execute-export', [ApprovalRequestController::class, 'executeExport'])->middleware('permission:approvals.manage')->name('admin.approvals.execute-export');
    });
});

require __DIR__.'/auth.php';
