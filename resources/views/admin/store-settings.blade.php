<x-app-layout>
    <x-slot name="header">
        <div class="ss-head intro-y">
            <div>
                <p class="ss-kicker">Administration</p>
                <h2 class="ss-title">Pengaturan Toko</h2>
                <p class="ss-subtitle">Kelola identitas toko, kebijakan pembayaran, dan aturan operasional kasir.</p>
            </div>
            <div class="ss-badge">Corporate Control Panel</div>
        </div>
    </x-slot>

    <style>
        .ss-shell { max-width: 1240px; margin: 0 auto; }
        .ss-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
        .ss-kicker { margin: 0; font-size: .73rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: #64748b; }
        .ss-title { margin: .22rem 0 0; font-size: 2rem; line-height: 1.06; font-weight: 700; color: #0f172a; }
        .ss-subtitle { margin: .38rem 0 0; font-size: .92rem; color: #64748b; }
        .ss-badge { display: inline-flex; align-items: center; min-height: 2.3rem; padding: 0 .85rem; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: .78rem; font-weight: 700; }

        .ss-alert { border-radius: 12px; border: 1px solid; padding: .78rem .95rem; font-size: .82rem; }
        .ss-alert-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .ss-alert-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .ss-alert-warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }

        .ss-form { display: grid; gap: 1rem; }
        .ss-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15, 23, 42, .04); overflow: hidden; }
        .ss-card-head { padding: .92rem 1.05rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%); }
        .ss-card-head-profile { background: linear-gradient(180deg, #eff6ff 0%, #f8fbff 100%); }
        .ss-card-head-payment { background: linear-gradient(180deg, #ecfeff 0%, #f8feff 100%); }
        .ss-card-head-overpay { background: linear-gradient(180deg, #f0fdf4 0%, #f8fff9 100%); }
        .ss-card-head-promo { background: linear-gradient(180deg, #fff7ed 0%, #fffdfa 100%); }
        .ss-card-title { margin: 0; font-size: .86rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; color: #334155; }
        .ss-card-note { margin: .25rem 0 0; color: #64748b; font-size: .78rem; }
        .ss-card-body { padding: 1rem 1.05rem 1.1rem; }

        .ss-grid-2 { display: grid; grid-template-columns: 1fr; gap: .85rem; }
        .ss-grid-3 { display: grid; grid-template-columns: 1fr; gap: .85rem; }
        @media (min-width: 920px) {
            .ss-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ss-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .ss-col-span-2 { grid-column: 1 / -1; }
        }

        .ss-field { display: block; }
        .ss-label { display: block; margin-bottom: .38rem; font-size: .74rem; font-weight: 700; color: #64748b; letter-spacing: .05em; text-transform: uppercase; }
        .ss-help { margin-top: .33rem; font-size: .72rem; color: #94a3b8; }

        .ss-input, .ss-textarea, .ss-file {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 10px;
            color: #0f172a;
            font-size: .92rem;
            line-height: 1.25;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .ss-input { min-height: 2.65rem; padding: 0 .75rem; }
        .ss-textarea { min-height: 108px; padding: .65rem .75rem; resize: vertical; }
        .ss-file { min-height: 2.65rem; padding: .45rem .55rem; }
        .ss-input:focus, .ss-textarea:focus, .ss-file:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .16);
        }

        .ss-logo-wrap { margin-top: .5rem; display: inline-flex; align-items: center; gap: .55rem; border: 1px solid #dbe4f0; background: #f8fafc; border-radius: 10px; padding: .45rem .55rem; }
        .ss-logo { height: 42px; width: auto; border-radius: 6px; background: #fff; border: 1px solid #e2e8f0; }
        .ss-logo-label { font-size: .73rem; color: #64748b; font-weight: 700; }

        .ss-sticky-footer {
            position: sticky;
            bottom: 0;
            z-index: 30;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 -8px 20px rgba(15, 23, 42, .07);
            padding: .8rem .95rem;
        }
        .ss-footer-note { margin: 0; color: #64748b; font-size: .78rem; }
        .ss-footer-actions { display: inline-flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
        .ss-dirty-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-height: 2.1rem;
            padding: 0 .72rem;
            border: 1px solid #fde68a;
            border-radius: 999px;
            background: #fffbeb;
            color: #92400e;
            font-size: .75rem;
            font-weight: 600;
        }
        .ss-submit {
            min-height: 2.55rem;
            padding: 0 1.2rem;
            border: 1px solid #1d4ed8;
            background: #1d4ed8;
            border-radius: 10px;
            color: #fff;
            font-size: .82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ss-submit:hover { background: #1e40af; border-color: #1e40af; }
        .ss-rupiah-wrap { position: relative; }
        .ss-rupiah-prefix { position: absolute; left: .72rem; top: 50%; transform: translateY(-50%); color: #64748b; font-size: .86rem; font-weight: 700; pointer-events: none; }
        .ss-rupiah-input { padding-left: 2.15rem; }
        .ss-promo-list { display: grid; gap: .6rem; }
        .ss-promo-row { display: grid; grid-template-columns: 1fr 140px 140px auto; gap: .55rem; align-items: end; }
        .ss-btn-light { min-height: 2.35rem; padding: 0 .8rem; border-radius: 9px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .78rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
        .ss-btn-light:hover { background: #f8fafc; }
        .ss-btn-icon { width: .9rem; height: .9rem; margin-right: .35rem; vertical-align: -2px; }
        .ss-btn-test {
            min-height: 2.55rem;
            padding: 0 1rem;
            border-radius: 10px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: .8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ss-btn-test:hover { background: #dbeafe; }
        .ss-btn-reset-draft {
            min-height: 2.55rem;
            padding: 0 .9rem;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            font-size: .8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ss-btn-reset-draft:hover { background: #ffe4e6; }
        @media (max-width: 920px) { .ss-promo-row { grid-template-columns: 1fr; } }
    </style>

    <div class="ss-shell intro-y space-y-4">
        @if (session('status'))
            <div class="ss-alert ss-alert-ok">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="ss-alert ss-alert-warn">{{ session('warning') }}</div>
        @endif

        @if ($errors->any())
            <div class="ss-alert ss-alert-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @php($runtimeConfigDiff = (array) session('runtime_config_diff', []))
        @if(!empty($runtimeConfigDiff))
            <div class="ss-alert ss-alert-warn">
                <div class="font-semibold mb-1">Preview Perubahan Runtime Config (Dry-Run)</div>
                @if(!empty($runtimeConfigDiff['approval_rules_changed']))
                    <div class="mb-1">Approval Rules berubah: {{ count((array) $runtimeConfigDiff['approval_rules_changed']) }} key</div>
                @endif
                @if(!empty($runtimeConfigDiff['rbac_changed']))
                    <div>RBAC role berubah: {{ implode(', ', array_keys((array) $runtimeConfigDiff['rbac_changed'])) }}</div>
                    <div class="mt-2 text-xs">
                        @foreach((array) $runtimeConfigDiff['rbac_changed'] as $role => $changes)
                            <div class="mb-1">
                                <strong>{{ strtoupper($role) }}</strong>:
                                +{{ implode(', ', (array) data_get($changes, 'added', [])) ?: '-' }}
                                / -{{ implode(', ', (array) data_get($changes, 'removed', [])) ?: '-' }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div id="ss-dirty-alert" class="ss-alert ss-alert-warn hidden">Ada perubahan belum disimpan.</div>
        <form id="ss-form" method="POST" action="{{ route('store-settings.update') }}" enctype="multipart/form-data" class="ss-form">
            @csrf
            @method('PUT')

            <section class="ss-card">
                <div class="ss-card-head ss-card-head-profile">
                    <h3 class="ss-card-title">Profil Toko</h3>
                    <p class="ss-card-note">Identitas utama yang muncul pada aplikasi dan struk pelanggan.</p>
                </div>
                <div class="ss-card-body ss-grid-2">
                    <label class="ss-field">
                        <span class="ss-label">Nama Toko</span>
                        <input type="text" name="name" class="ss-input" value="{{ old('name', $setting->name) }}" required>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">WhatsApp</span>
                        <input type="text" name="whatsapp" class="ss-input" value="{{ old('whatsapp', $setting->whatsapp) }}" placeholder="62812xxxxxxx">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Logo Toko</span>
                        <input type="file" name="logo" class="ss-file" accept="image/*">
                        <span class="ss-help">Digunakan di sidebar, header, dan struk (print/PDF). Rekomendasi PNG/JPG rasio 1:1, minimal 256x256 px.</span>
                        @if(!empty($setting->logo))
                            <div class="ss-logo-wrap">
                                <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo toko" class="ss-logo">
                                <span class="ss-logo-label">Logo aktif</span>
                            </div>
                        @endif
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Footer Struk</span>
                        <textarea name="receipt_footer" rows="3" class="ss-textarea">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                    </label>
                    <label class="ss-field ss-col-span-2">
                        <span class="ss-label">Alamat</span>
                        <textarea name="address" rows="2" class="ss-textarea">{{ old('address', $setting->address) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head ss-card-head-payment">
                    <h3 class="ss-card-title">Pembayaran & Stock Opname</h3>
                    <p class="ss-card-note">Pengaturan default kasir dan validasi outlier saat sesi stock opname.</p>
                </div>
                <div class="ss-card-body ss-grid-3">
                    <label class="ss-field">
                        <span class="ss-label">Nominal Tombol Cepat</span>
                        <input type="text" name="quick_pay_presets" class="ss-input" value="{{ old('quick_pay_presets', $quickPayPresetsText) }}" placeholder="10000,20000,50000,100000">
                        <span class="ss-help">Pisahkan dengan koma, maksimal 6 tombol.</span>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Timeout Pending Non-Cash (menit)</span>
                        <input type="number" min="1" max="1440" name="pending_non_cash_timeout_minutes" class="ss-input" value="{{ old('pending_non_cash_timeout_minutes', $setting->pending_non_cash_timeout_minutes ?? 30) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Threshold Outlier Opname</span>
                        <input type="number" min="1" max="1000000" name="stock_opname_outlier_threshold" class="ss-input" value="{{ old('stock_opname_outlier_threshold', $setting->stock_opname_outlier_threshold ?? 10) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Budget Pengeluaran Harian</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="expense_daily_budget" class="ss-input ss-rupiah-input" value="{{ old('expense_daily_budget', (int) ($setting->expense_daily_budget ?? 1000000)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Budget Pengeluaran Bulanan</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="expense_monthly_budget" class="ss-input ss-rupiah-input" value="{{ old('expense_monthly_budget', (int) ($setting->expense_monthly_budget ?? 30000000)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Threshold Nominal Besar Pengeluaran</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="expense_large_threshold" class="ss-input ss-rupiah-input" value="{{ old('expense_large_threshold', (int) ($setting->expense_large_threshold ?? 1000000)) }}">
                        </div>
                        <span class="ss-help">Jika nominal >= nilai ini, catatan wajib diisi pada form pengeluaran.</span>
                    </label>
                </div>
            </section>

            @php($overpay = (array) ($setting->payment_overpay_rules ?? []))
            @php($approvalRules = (array) ($setting->approval_rules ?? \App\Models\StoreSetting::DEFAULT_APPROVAL_RULES))
            <section class="ss-card">
                <div class="ss-card-head ss-card-head-overpay">
                    <h3 class="ss-card-title">Batas Overpay Per Metode</h3>
                    <p class="ss-card-note">Kontrol maksimum lebih bayar agar transaksi tetap aman dan terukur.</p>
                </div>
                <div class="ss-card-body ss-grid-3">
                    <label class="ss-field">
                        <span class="ss-label">QRIS</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="payment_overpay_qris" class="ss-input ss-rupiah-input" value="{{ old('payment_overpay_qris', (int) data_get($overpay, 'qris.max_overpay', 0)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Debit</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="payment_overpay_debit" class="ss-input ss-rupiah-input" value="{{ old('payment_overpay_debit', (int) data_get($overpay, 'debit.max_overpay', 0)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Transfer</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="payment_overpay_transfer" class="ss-input ss-rupiah-input" value="{{ old('payment_overpay_transfer', (int) data_get($overpay, 'transfer.max_overpay', 0)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">E-Wallet</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="payment_overpay_e_wallet" class="ss-input ss-rupiah-input" value="{{ old('payment_overpay_e_wallet', (int) data_get($overpay, 'e_wallet.max_overpay', 0)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Cash</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="payment_overpay_cash" class="ss-input ss-rupiah-input" value="{{ old('payment_overpay_cash', (int) data_get($overpay, 'cash.max_overpay', 500000)) }}">
                        </div>
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <h3 class="ss-card-title">Rule Approval Engine</h3>
                    <p class="ss-card-note">Atur ambang aksi yang wajib lewat Approval Queue.</p>
                </div>
                <div class="ss-card-body ss-grid-3">
                    <label class="ss-field">
                        <span class="ss-label">Export Wajib Approval (Min Baris)</span>
                        <input type="number" min="1" max="1000000" name="approval_export_min_rows" class="ss-input" value="{{ old('approval_export_min_rows', (int) data_get($approvalRules, 'export_min_rows', 300)) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Export Wajib Approval (Min Nominal)</span>
                        <div class="ss-rupiah-wrap">
                            <span class="ss-rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" data-rupiah name="approval_export_min_total" class="ss-input ss-rupiah-input" value="{{ old('approval_export_min_total', (int) data_get($approvalRules, 'export_min_total', 100000000)) }}">
                        </div>
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Auto Expire Approval (Menit)</span>
                        <input type="number" min="10" max="10080" name="approval_auto_expire_minutes" class="ss-input" value="{{ old('approval_auto_expire_minutes', (int) data_get($approvalRules, 'auto_expire_minutes', 1440)) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">SLA Refund/Void (Menit)</span>
                        <input type="number" min="5" max="10080" name="approval_sla_minutes_sale" class="ss-input" value="{{ old('approval_sla_minutes_sale', (int) data_get($approvalRules, 'sla_minutes_sale', 120)) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">SLA Export Besar (Menit)</span>
                        <input type="number" min="5" max="10080" name="approval_sla_minutes_export" class="ss-input" value="{{ old('approval_sla_minutes_export', (int) data_get($approvalRules, 'sla_minutes_export', 360)) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Threshold Alert Overdue (jumlah request)</span>
                        <input type="number" min="1" max="1000" name="approval_overdue_alert_threshold" class="ss-input" value="{{ old('approval_overdue_alert_threshold', (int) data_get($approvalRules, 'overdue_alert_threshold', 5)) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Jam Kerja SLA (Mulai)</span>
                        <input type="time" name="approval_business_start" class="ss-input" value="{{ old('approval_business_start', (string) data_get($approvalRules, 'business_hours.start', '08:00')) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Jam Kerja SLA (Selesai)</span>
                        <input type="time" name="approval_business_end" class="ss-input" value="{{ old('approval_business_end', (string) data_get($approvalRules, 'business_hours.end', '22:00')) }}">
                    </label>
                    <label class="ss-field">
                        <span class="ss-label">Hari Kerja SLA (1=Senin ... 7=Minggu)</span>
                        <input type="text" name="approval_business_workdays" class="ss-input" value="{{ old('approval_business_workdays', implode(',', (array) data_get($approvalRules, 'business_hours.workdays', [1,2,3,4,5,6,7]))) }}" placeholder="1,2,3,4,5,6,7">
                        <span class="ss-help">Contoh umum: `1,2,3,4,5,6` (Minggu libur).</span>
                    </label>
                    <label class="ss-field ss-col-span-2">
                        <span class="ss-label">Preset Alasan Reject (1 baris 1 alasan)</span>
                        <textarea name="approval_reject_reason_presets" rows="4" class="ss-textarea">{{ old('approval_reject_reason_presets', implode("\n", (array) data_get($approvalRules, 'reject_reason_presets', []))) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <h3 class="ss-card-title">Backup & Restore Runtime Config</h3>
                    <p class="ss-card-note">Backup atau pulihkan konfigurasi `approval_rules` + RBAC permission dalam format JSON.</p>
                </div>
                <div class="ss-card-body ss-grid-2">
                    @php($lastBackup = (array) data_get($backupStatus ?? [], 'last_backup', []))
                    @php($lastRestore = data_get($backupStatus ?? [], 'last_restore'))
                    <div class="ss-field ss-col-span-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                            <div><strong>Status Backup:</strong> {{ data_get($lastBackup, 'name', '-') }} | {{ number_format(((int) data_get($lastBackup, 'size', 0))/1024, 1, ',', '.') }} KB | {{ data_get($lastBackup, 'updated_at', '-') }}</div>
                            <div><strong>Total File Backup:</strong> {{ number_format((int) data_get($backupStatus ?? [], 'backups_count', 0), 0, ',', '.') }}</div>
                            <div><strong>Restore Terakhir:</strong> {{ $lastRestore ? ((string) $lastRestore->action.' @ '.optional($lastRestore->created_at)->format('d/m/Y H:i:s')) : '-' }}</div>
                        </div>
                    </div>
                    <div class="ss-field">
                        <span class="ss-label">Export Config Saat Ini</span>
                        <a href="{{ route('store-settings.export-runtime-config') }}" class="ss-btn-light inline-flex"><i data-feather="download" class="ss-btn-icon"></i>Export Runtime JSON</a>
                        <div class="ss-help">Simpan file ini sebelum melakukan perubahan besar RBAC/approval rule.</div>
                    </div>
                    <div class="ss-field">
                        <span class="ss-label">Import Runtime JSON</span>
                        <textarea form="runtime-import-form" name="config_json" rows="6" class="ss-textarea" placeholder='Paste JSON runtime config di sini...'>{{ old('config_json') }}</textarea>
                        <div class="ss-help">Import akan mengganti `approval_rules` dan pemetaan permission role owner/admin/kasir.</div>
                        <form id="runtime-import-form" method="POST" action="{{ route('store-settings.import-runtime-config') }}" class="mt-2">
                            @csrf
                            <label class="inline-flex items-center gap-2 text-xs text-slate-600 mb-2">
                                <input type="checkbox" name="dry_run" value="1" @checked(old('dry_run'))>
                                <span>Dry-run dulu (preview diff, tanpa apply)</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="ss-btn-test"><i data-feather="upload" class="ss-btn-icon"></i>Import Runtime JSON</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head ss-card-head-promo">
                    <h3 class="ss-card-title">Promo Buy X Get Y</h3>
                    <p class="ss-card-note">Isi per baris rule promo. Lebih mudah dibanding format teks mentah.</p>
                </div>
                <div class="ss-card-body">
                    <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        Contoh 3 baris:
                        <div class="mt-1 font-medium text-slate-700">1) K24-123 | Buy 2 | Get 1</div>
                        <div class="font-medium text-slate-700">2) K24-456 | Buy 3 | Get 1</div>
                        <div class="font-medium text-slate-700">3) K24-789 | Buy 5 | Get 2</div>
                    </div>

                    <div id="promo-rule-list" class="ss-promo-list">
                        @foreach(($promoRows ?? [['sku' => '', 'buy_qty' => '1', 'get_qty' => '1']]) as $row)
                            <div class="ss-promo-row" data-promo-row>
                                <label class="ss-field">
                                    <span class="ss-label">SKU Produk</span>
                                    <input type="text" name="promo_sku[]" class="ss-input" value="{{ $row['sku'] }}" placeholder="Contoh: K24-123">
                                </label>
                                <label class="ss-field">
                                    <span class="ss-label">Buy Qty</span>
                                    <input type="number" min="1" name="promo_buy_qty[]" class="ss-input" value="{{ $row['buy_qty'] }}">
                                </label>
                                <label class="ss-field">
                                    <span class="ss-label">Get Qty</span>
                                    <input type="number" min="1" name="promo_get_qty[]" class="ss-input" value="{{ $row['get_qty'] }}">
                                </label>
                        <button type="button" class="ss-btn-light" data-remove-promo><i data-feather="trash-2" class="ss-btn-icon"></i>Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <button type="button" id="add-promo-row" class="ss-btn-light"><i data-feather="plus" class="ss-btn-icon"></i>Tambah Rule Promo</button>
                    </div>
                </div>
            </section>

            <div class="ss-sticky-footer">
                <p class="ss-footer-note">Review semua kebijakan pembayaran sebelum menyimpan perubahan.</p>
                <div class="ss-footer-actions">
                    <span id="ss-dirty-pill" class="ss-dirty-pill hidden"><i data-feather="alert-circle" class="ss-btn-icon"></i>Perubahan belum disimpan</span>
                    <button id="ss-reset-draft" class="ss-btn-reset-draft" type="button"><i data-feather="trash-2" class="ss-btn-icon"></i>Reset Draft</button>
                    <button id="ss-test-telegram" class="ss-btn-test" type="button"><i data-feather="send" class="ss-btn-icon"></i>Tes Telegram</button>
                    <button class="ss-submit" type="submit"><i data-feather="save" class="ss-btn-icon"></i>Simpan Pengaturan Toko</button>
                </div>
            </div>
        </form>
    </div>

    <template id="promo-row-template">
        <div class="ss-promo-row" data-promo-row>
            <label class="ss-field">
                <span class="ss-label">SKU Produk</span>
                <input type="text" name="promo_sku[]" class="ss-input" value="" placeholder="Contoh: K24-123">
            </label>
            <label class="ss-field">
                <span class="ss-label">Buy Qty</span>
                <input type="number" min="1" name="promo_buy_qty[]" class="ss-input" value="1">
            </label>
            <label class="ss-field">
                <span class="ss-label">Get Qty</span>
                <input type="number" min="1" name="promo_get_qty[]" class="ss-input" value="1">
            </label>
            <button type="button" class="ss-btn-light" data-remove-promo><i data-feather="trash-2" class="ss-btn-icon"></i>Hapus</button>
        </div>
    </template>

    <script>
        (function () {
            const form = document.getElementById('ss-form');
            if (!form) return;
            const draftKey = 'store-settings-draft-v1';
            const dirtyAlert = document.getElementById('ss-dirty-alert');
            const dirtyPill = document.getElementById('ss-dirty-pill');
            const toast = (message, type = 'ok') => {
                const klass = type === 'ok' ? 'ss-alert-ok' : 'ss-alert-err';
                const container = document.createElement('div');
                container.className = `ss-alert ${klass}`;
                container.textContent = message;
                form.parentElement?.insertBefore(container, form);
                setTimeout(() => container.remove(), 2500);
            };
            const getFields = () => Array.from(form.querySelectorAll('input, textarea, select'))
                .filter((el) => el.name && !['_token', '_method'].includes(el.name));
            const serialize = () => {
                const out = {};
                getFields().forEach((el) => {
                    if (el.type === 'file') return;
                    if (el.type === 'checkbox') {
                        out[el.name] = !!el.checked;
                    } else {
                        out[el.name] = el.value ?? '';
                    }
                });
                return out;
            };
            const initialState = JSON.stringify(serialize());
            let isDirty = false;
            const setDirty = (flag) => {
                isDirty = !!flag;
                dirtyAlert?.classList.toggle('hidden', !isDirty);
                dirtyPill?.classList.toggle('hidden', !isDirty);
            };
            const persistDraft = () => {
                try {
                    const payload = {
                        updated_at: Date.now(),
                        data: serialize(),
                    };
                    localStorage.setItem(draftKey, JSON.stringify(payload));
                } catch (_) {}
            };
            const loadDraft = () => {
                try {
                    const raw = localStorage.getItem(draftKey);
                    if (!raw) return;
                    const parsed = JSON.parse(raw);
                    const data = parsed?.data;
                    if (!data || typeof data !== 'object') return;
                    getFields().forEach((el) => {
                        if (!(el.name in data)) return;
                        if (el.type === 'file') return;
                        if (el.type === 'checkbox') {
                            el.checked = !!data[el.name];
                        } else {
                            el.value = String(data[el.name] ?? '');
                        }
                    });
                    if (JSON.stringify(serialize()) !== initialState) {
                        setDirty(true);
                        toast('Draft pengaturan berhasil dipulihkan.', 'ok');
                    }
                } catch (_) {}
            };
            loadDraft();
            const toDigits = (value) => String(value || '').replace(/[^\d]/g, '');
            const formatIdr = (digits) => {
                if (!digits) return '';
                return new Intl.NumberFormat('id-ID').format(Number(digits));
            };
            document.querySelectorAll('[data-rupiah]').forEach((el) => {
                const apply = () => {
                    const digits = toDigits(el.value);
                    el.value = formatIdr(digits);
                };
                apply();
                el.addEventListener('input', apply);
                el.addEventListener('blur', apply);
            });
            const watchDirty = () => {
                const currentState = JSON.stringify(serialize());
                const changed = currentState !== initialState;
                setDirty(changed);
                if (changed) persistDraft();
            };
            getFields().forEach((el) => {
                const evt = el.tagName === 'SELECT' || el.type === 'checkbox' ? 'change' : 'input';
                el.addEventListener(evt, watchDirty);
            });

            const list = document.getElementById('promo-rule-list');
            const addBtn = document.getElementById('add-promo-row');
            const template = document.getElementById('promo-row-template');
            if (!list || !addBtn || !template) return;

            const bindRemove = (root) => {
                root.querySelectorAll('[data-remove-promo]').forEach((btn) => {
                    btn.onclick = () => {
                        const rows = list.querySelectorAll('[data-promo-row]');
                        if (rows.length <= 1) return;
                        btn.closest('[data-promo-row]')?.remove();
                    };
                });
            };
            bindRemove(list);

            addBtn.addEventListener('click', () => {
                const node = template.content.cloneNode(true);
                list.appendChild(node);
                bindRemove(list);
                watchDirty();
                if (window.feather && typeof window.feather.replace === 'function') {
                    window.feather.replace();
                }
            });

            form.addEventListener('submit', () => {
                try { localStorage.removeItem(draftKey); } catch (_) {}
                setDirty(false);
            });

            window.addEventListener('beforeunload', (event) => {
                if (!isDirty) return;
                event.preventDefault();
                event.returnValue = '';
            });

            const testBtn = document.getElementById('ss-test-telegram');
            const resetDraftBtn = document.getElementById('ss-reset-draft');
            testBtn?.addEventListener('click', async () => {
                testBtn.disabled = true;
                try {
                    const response = await fetch(`{{ route('notification-settings.test') }}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            'Accept': 'application/json',
                        },
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(result?.message || 'Gagal kirim tes Telegram.');
                    toast(result?.message || 'Tes Telegram berhasil dikirim.', 'ok');
                } catch (error) {
                    toast(error.message || 'Gagal kirim tes Telegram.', 'err');
                } finally {
                    testBtn.disabled = false;
                }
            });

            resetDraftBtn?.addEventListener('click', () => {
                if (!confirm('Hapus draft perubahan lokal dan kembalikan nilai form saat ini?')) return;
                try { localStorage.removeItem(draftKey); } catch (_) {}
                form.reset();
                document.querySelectorAll('[data-rupiah]').forEach((el) => {
                    const digits = toDigits(el.value);
                    el.value = formatIdr(digits);
                });
                setDirty(false);
                toast('Draft berhasil dihapus.', 'ok');
            });

            if (window.feather && typeof window.feather.replace === 'function') {
                window.feather.replace();
            }
        })();
    </script>
</x-app-layout>
