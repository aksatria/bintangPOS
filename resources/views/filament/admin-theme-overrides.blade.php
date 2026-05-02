<style>
    :root {
        --brand-900: #0f172a;
        --brand-700: #1d4ed8;
        --brand-100: #dbeafe;
        --ink-700: #334155;
        --ink-500: #64748b;
        --surface-50: #f8fafc;
        --surface-100: #f1f5f9;
        --border-200: #dbe4f0;
        --success-100: #dcfce7;
        --success-700: #15803d;
        --warn-100: #fef3c7;
        --warn-700: #b45309;
        --danger-100: #fee2e2;
        --danger-700: #b91c1c;
    }

    .fi-body, .fi-topbar, .fi-sidebar, .fi-main {
        font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif !important;
    }

    .fi-main {
        background: linear-gradient(180deg, #f8fbff 0%, #f8fafc 44%, #f1f5f9 100%);
    }

    .fi-page {
        max-width: 1320px;
        margin-inline: auto;
    }

    .fi-header-heading {
        color: var(--brand-900) !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em;
    }

    .fi-section,
    .fi-widget,
    .fi-ta,
    .fi-fo-field-wrp,
    .fi-in-entry-wrp {
        border-radius: 14px !important;
        border: 1px solid var(--border-200) !important;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05) !important;
        background: #ffffff !important;
    }

    .fi-wi-stats-overview-stat {
        border-radius: 12px !important;
        border: 1px solid var(--border-200) !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    }

    .fi-wi-stats-overview-stat-label {
        color: var(--ink-500) !important;
        font-weight: 600 !important;
    }

    .fi-wi-stats-overview-stat-value {
        color: var(--brand-900) !important;
        font-weight: 700 !important;
    }

    .fi-btn {
        border-radius: 10px !important;
        font-weight: 600 !important;
    }

    .fi-btn-color-primary {
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%) !important;
        border-color: #1d4ed8 !important;
    }

    .fi-input,
    .fi-select-input,
    .fi-textarea {
        border-radius: 10px !important;
        border-color: #cbd5e1 !important;
    }

    .fi-input:focus,
    .fi-select-input:focus,
    .fi-textarea:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16) !important;
    }

    .fi-ta-table thead th {
        color: var(--ink-500) !important;
        font-weight: 600 !important;
        background: var(--surface-50) !important;
    }

    .fi-ta-table tbody td {
        color: #1e293b !important;
    }

    .fi-badge {
        border-radius: 999px !important;
        padding-inline: .55rem !important;
        font-weight: 700 !important;
    }

    .fi-color-success .fi-badge {
        background: var(--success-100) !important;
        color: var(--success-700) !important;
    }

    .fi-color-warning .fi-badge {
        background: var(--warn-100) !important;
        color: var(--warn-700) !important;
    }

    .fi-color-danger .fi-badge {
        background: var(--danger-100) !important;
        color: var(--danger-700) !important;
    }
</style>
