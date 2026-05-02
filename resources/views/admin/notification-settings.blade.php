<x-app-layout>
    <x-slot name="header">
        <div class="nt-head intro-y">
            <div>
                <p class="nt-kicker">Administration</p>
                <h2 class="nt-title">Pengaturan Notifikasi Telegram</h2>
                <p class="nt-sub">Atur alert operasional, ringkasan harian, dan validasi penerima pesan Telegram.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .nt-shell { max-width: 1240px; margin: 0 auto; }
        .nt-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .nt-kicker { margin: 0; font-size: .73rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .nt-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.08; font-weight: 700; color: #0f172a; }
        .nt-sub { margin: .35rem 0 0; font-size: .9rem; color: #64748b; }
        .nt-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        .nt-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15, 23, 42, .04); overflow: hidden; }
        .nt-headbar { padding: .92rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%); }
        .nt-card-title { margin: 0; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .nt-card-note { margin: .25rem 0 0; color: #64748b; font-size: .78rem; }
        .nt-body { padding: 1rem; }

        .nt-alert { border-radius: 12px; border: 1px solid; padding: .78rem .92rem; font-size: .82rem; }
        .nt-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .nt-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }

        .nt-form-grid { display: grid; gap: .8rem; grid-template-columns: 1fr; }
        @media (min-width: 980px) { .nt-form-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .nt-col-3 { grid-column: 1 / -1; }

        .nt-label { display: block; margin-bottom: .34rem; font-size: .73rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; }
        .nt-help { margin-top: .3rem; font-size: .72rem; color: #94a3b8; }
        .nt-input {
            width: 100%;
            min-height: 2.6rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            padding: 0 .72rem;
            font-size: .9rem;
        }
        .nt-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, .16); }
        .nt-check { display: inline-flex; align-items: center; gap: .5rem; font-size: .88rem; color: #334155; min-height: 2.6rem; }
        .nt-check input[type="checkbox"] { width: .95rem; height: .95rem; accent-color: #1d4ed8; }

        .nt-btn { min-height: 2.45rem; padding: 0 .9rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
        .nt-btn:hover { background: #f8fafc; }
        .nt-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .nt-btn-primary:hover { background: #1e40af; border-color: #1e40af; }
        .nt-btn-icon { width: .9rem; height: .9rem; margin-right: .38rem; vertical-align: -2px; }
        .nt-test-row { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        .nt-test-row .nt-input { max-width: 280px; }
        .nt-test-result { margin-top: .45rem; font-size: .84rem; }
        .nt-test-ok { color: #047857; }
        .nt-test-err { color: #be123c; }
        .nt-test-wait { color: #475569; }
        .nt-preview-box { margin-top: .75rem; border: 1px solid #dbe4f0; border-radius: 10px; background: #f8fafc; padding: .75rem; white-space: pre-wrap; font-size: .83rem; color: #334155; min-height: 88px; }

        .nt-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; }
        .nt-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .nt-table thead th {
            text-align: left;
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: 700;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: .62rem .7rem;
        }
        .nt-table tbody td { padding: .62rem .7rem; border-top: 1px solid #eef2f7; font-size: .86rem; color: #334155; vertical-align: top; }
        .nt-chip { display: inline-flex; align-items: center; padding: .18rem .55rem; border-radius: 999px; border: 1px solid; font-size: .68rem; font-weight: 800; letter-spacing: .05em; }
        .nt-chip-ok { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .nt-chip-err { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }
        .nt-empty { text-align: center; color: #64748b; padding: 1rem; }
    </style>

    <div class="nt-shell intro-y space-y-4">
        @if (session('status'))
            <div class="nt-alert nt-ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="nt-alert nt-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="nt-grid">
            <section class="nt-card">
                <div class="nt-headbar">
                    <h3 class="nt-card-title">Konfigurasi Notifikasi</h3>
                    <p class="nt-card-note">Kontrol alert checkout, ringkasan harian, dan otorisasi posting opname.</p>
                </div>
                <div class="nt-body">
                    <form method="POST" action="{{ route('notification-settings.update') }}" class="nt-form-grid">
                        @csrf
                        @method('PUT')

                        <label>
                            <span class="nt-label">Status Telegram</span>
                            <span class="nt-check">
                                <input type="checkbox" name="telegram_enabled" value="1" @checked($setting->telegram_enabled)>
                                Aktifkan notifikasi Telegram
                            </span>
                        </label>

                        <label>
                            <span class="nt-label">Alert Checkout Gagal</span>
                            <span class="nt-check">
                                <input type="checkbox" name="telegram_notify_checkout_anomaly" value="1" @checked($setting->telegram_notify_checkout_anomaly)>
                                Kirim alert anomali checkout
                            </span>
                        </label>

                        <label>
                            <span class="nt-label">Threshold Checkout Gagal/Jam</span>
                            <input type="number" min="1" max="100" name="telegram_checkout_fail_threshold" class="nt-input" value="{{ old('telegram_checkout_fail_threshold', $setting->telegram_checkout_fail_threshold ?? 5) }}">
                        </label>

                        <label>
                            <span class="nt-label">SLA Escalation Approval (Menit)</span>
                            <input type="number" min="5" max="1440" name="telegram_approval_sla_minutes" class="nt-input" value="{{ old('telegram_approval_sla_minutes', $setting->telegram_approval_sla_minutes ?? 120) }}">
                            <span class="nt-help">Jika approval pending melewati nilai ini, sistem kirim alert Telegram otomatis.</span>
                        </label>

                        <label>
                            <span class="nt-label">Ringkasan Harian</span>
                            <span class="nt-check">
                                <input type="checkbox" name="telegram_daily_summary_enabled" value="1" @checked($setting->telegram_daily_summary_enabled)>
                                Aktifkan ringkasan harian
                            </span>
                        </label>

                        <label>
                            <span class="nt-label">Jam Ringkasan Harian</span>
                            <input type="time" name="telegram_daily_summary_time" class="nt-input" value="{{ old('telegram_daily_summary_time', $setting->telegram_daily_summary_time ?? '21:00') }}">
                        </label>

                        <label>
                            <span class="nt-label">Override Chat ID (Opsional)</span>
                            <input type="text" name="telegram_override_chat_id" class="nt-input" placeholder="{{ $defaultChatId }}" value="{{ old('telegram_override_chat_id', $setting->telegram_override_chat_id) }}">
                            <span class="nt-help">Kosongkan untuk pakai default Chat ID aplikasi.</span>
                        </label>

                        <label class="nt-col-3">
                            <span class="nt-label">Approval Posting Opname</span>
                            <span class="nt-check">
                                <input type="checkbox" name="stock_opname_require_manager_approval" value="1" @checked($setting->stock_opname_require_manager_approval)>
                                Wajibkan approval manager saat posting stock opname
                            </span>
                        </label>

                        <div class="nt-col-3">
                            <button class="nt-btn nt-btn-primary" type="submit"><i data-feather="save" class="nt-btn-icon"></i>Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="nt-card">
                <div class="nt-headbar">
                    <h3 class="nt-card-title">Tes Penerima</h3>
                    <p class="nt-card-note">Uji kirim pesan + preview isi Weekly SLA Report sebelum dijadwalkan.</p>
                </div>
                <div class="nt-body">
                    <div class="nt-test-row">
                        <input id="telegram-test-chat-id" type="text" class="nt-input" placeholder="Contoh: 5711502419">
                        <button type="button" id="telegram-test-btn" class="nt-btn"><i data-feather="send" class="nt-btn-icon"></i>Kirim Tes</button>
                        <button type="button" id="telegram-preview-weekly-btn" class="nt-btn"><i data-feather="eye" class="nt-btn-icon"></i>Preview Weekly SLA</button>
                    </div>
                    <div id="telegram-test-result" class="nt-test-result"></div>
                    <div id="telegram-weekly-preview" class="nt-preview-box">Klik "Preview Weekly SLA" untuk melihat isi pesan.</div>
                </div>
            </section>

            <section class="nt-card">
                <div class="nt-headbar">
                    <h3 class="nt-card-title">Log Kirim Telegram (20 Terakhir)</h3>
                </div>
                <div class="nt-body">
                    <div class="nt-table-wrap">
                        <table class="nt-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Tujuan</th>
                                    <th>Konteks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTelegramLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            <span class="nt-chip {{ $log->action === 'telegram_send_ok' ? 'nt-chip-ok' : 'nt-chip-err' }}">
                                                {{ $log->action === 'telegram_send_ok' ? 'BERHASIL' : 'GAGAL' }}
                                            </span>
                                        </td>
                                        <td>{{ data_get($log->context, 'target_chat_id', '-') }}</td>
                                        <td>
                                            {{ data_get($log->context, 'purpose', '-') }}
                                            @if(data_get($log->context, 'error'))
                                                <div class="text-rose-500 text-xs mt-1">Error: {{ data_get($log->context, 'error') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="nt-empty">Belum ada log kirim Telegram.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('telegram-test-btn');
            const input = document.getElementById('telegram-test-chat-id');
            const result = document.getElementById('telegram-test-result');
            const endpoint = @json(route('notification-settings.test'));
            const previewEndpoint = @json(route('notification-settings.preview-weekly-sla'));
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const previewBtn = document.getElementById('telegram-preview-weekly-btn');
            const previewBox = document.getElementById('telegram-weekly-preview');

            btn?.addEventListener('click', async () => {
                btn.disabled = true;
                result.textContent = 'Mengirim tes...';
                result.className = 'nt-test-result nt-test-wait';
                try {
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ chat_id: input.value.trim() }),
                    });
                    const data = await res.json();
                    result.textContent = data.message || 'Selesai.';
                    result.className = 'nt-test-result ' + (data.ok ? 'nt-test-ok' : 'nt-test-err');
                } catch (e) {
                    result.textContent = 'Gagal kirim tes.';
                    result.className = 'nt-test-result nt-test-err';
                } finally {
                    btn.disabled = false;
                }
            });

            previewBtn?.addEventListener('click', async () => {
                previewBtn.disabled = true;
                if (previewBox) previewBox.textContent = 'Memuat preview...';
                try {
                    const res = await fetch(previewEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    });
                    const data = await res.json();
                    if (previewBox) previewBox.textContent = data?.preview || 'Tidak ada preview.';
                } catch (e) {
                    if (previewBox) previewBox.textContent = 'Gagal memuat preview Weekly SLA.';
                } finally {
                    previewBtn.disabled = false;
                }
            });

            if (window.feather && typeof window.feather.replace === 'function') {
                window.feather.replace();
            }
        })();
    </script>
</x-app-layout>
