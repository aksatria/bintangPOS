<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir Modern</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f4f7ff;
            --card:#ffffff;
            --line:#d9e2ef;
            --text:#1f2937;
            --muted:#6b7280;
            --brand:#2563eb;
            --brand2:#1d4ed8;
            --ok:#059669;
            --danger:#d64545;
            --accent-a:#eff6ff;
            --accent-b:#eefbf4;
            --accent-c:#fff7ed;
            --space-1:4px;
            --space-2:8px;
            --space-3:12px;
            --space-4:16px;
            --radius-sm:10px;
            --radius-md:14px;
            --radius-lg:16px;
            --fx-fast:140ms;
            --fx-mid:200ms;
        }
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;font-family:"Poppins",sans-serif;background:
            radial-gradient(700px 340px at 15% -10%, #dfeeff 0%, transparent 58%),
            radial-gradient(620px 300px at 95% 10%, #e7f9ee 0%, transparent 60%),
            var(--bg);color:var(--text);line-height:1.45;max-width:100%;overflow-x:hidden;overflow-y:auto}
        .k-wrap,.k-main,.k-products,.k-grid{max-width:100%;overflow-x:hidden}
        .k-wrap{min-height:100vh;display:grid;grid-template-rows:auto 1fr auto}
        .k-head{display:none}
        .k-title{margin:0;font-size:20px;font-weight:800;letter-spacing:.2px;line-height:1.2}
        .k-sub{margin:4px 0 0;font-size:12px;opacity:.9;line-height:1.4}
        .k-legacy{border:1px solid rgba(255,255,255,.4);color:#fff;text-decoration:none;padding:9px 12px;border-radius:10px;font-size:12px;font-weight:700}

        .k-main{padding:var(--space-3);display:grid;gap:var(--space-3);grid-template-columns:1fr}
        .k-panel{background:#fff;border:1px solid var(--line);border-radius:var(--radius-lg)}
        .k-products{padding:var(--space-3)}
        .k-tools-wrap{
            position: sticky;
            top: 0;
            z-index: 50;
            background: #fff;
            border-bottom: 1px solid #e5edf7;
            padding-bottom: 8px;
            margin-bottom: 8px;
            transform: none !important;
            transition: none !important;
            will-change: auto;
        }
        #toolsSpacer{display:none}
        .k-tools{display:grid;grid-template-columns:1fr;gap:8px}
        .k-tools-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
        }
        .k-scan-toggle{
            border:1px solid #cfe0f5;
            border-radius:999px;
            background:#f8fbff;
            color:#1e3a8a;
            min-height:34px;
            padding:0 12px;
            font-family:inherit;
            font-size:12px;
            font-weight:700;
            cursor:pointer;
        }
        .k-scan-toggle.active{
            border-color:#2563eb;
            background:#2563eb;
            color:#fff;
        }
        .k-mini-sum{
            display:none;
            position:sticky;
            top:0;
            z-index:45;
            margin-top:6px;
            border:1px solid #dbe7f4;
            background:#f8fbff;
            border-radius:10px;
            padding:6px 10px;
            font-size:12px;
            font-weight:700;
            color:#334155;
            justify-content:space-between;
            align-items:center;
        }
        .k-search-wrap{position:relative}
        .k-ico{
            width:16px;height:16px;display:inline-block;vertical-align:middle;flex:0 0 16px
        }
        .k-inline{
            display:inline-flex;align-items:center;gap:6px;
        }
        .k-input-icon{
            position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;pointer-events:none;
        }
        .k-input.with-icon{padding-left:36px}
        .k-suggest{
            position:absolute;left:0;right:0;top:46px;z-index:20;
            border:1px solid var(--line);border-radius:10px;background:#fff;
            box-shadow:0 8px 16px rgba(17,24,39,.08);
            max-height:240px;overflow:auto;display:none;
        }
        .k-suggest.show{display:block}
        .k-suggest button{
            width:100%;border:0;background:#fff;padding:10px 12px;text-align:left;
            font-family:inherit;font-size:13px;cursor:pointer;color:var(--text);
        }
        .k-suggest button:hover{background:#f8fafc}
        .k-input,.k-select{
            width:100%;height:44px;border:1px solid #cfd8e6;border-radius:var(--radius-md);padding:0 var(--space-3);
            font-family:inherit;font-size:14px;background:#ffffff;color:var(--text)
        }
        .k-input::placeholder{color:#94a3b8}
        .k-input:focus,.k-select:focus{
            outline:none;
            border-color:#60a5fa;
            box-shadow:0 0 0 3px rgba(96,165,250,.2);
        }
        .k-hint{margin:10px 0 2px;font-size:12px;color:var(--muted);line-height:1.4}
        .k-grid{margin-top:0;display:grid;gap:12px;grid-template-columns:repeat(2,minmax(0,1fr));scroll-margin-top:70px}
        .k-card{
            border:1px solid #c6d4e6;border-radius:var(--radius-lg);background:#fff;padding:10px;display:grid;gap:10px;
            transition:transform var(--fx-fast) ease, box-shadow var(--fx-mid) ease, border-color var(--fx-fast) ease;cursor:pointer;
            min-height: 198px;
            position: relative;
        }
        .k-card:nth-child(4n+1){background:#eaf4ff;border-color:#b8d4f6}
        .k-card:nth-child(4n+2){background:#e9faef;border-color:#b7e7c9}
        .k-card:nth-child(4n+3){background:#fff3e2;border-color:#f5cf9a}
        .k-card:nth-child(4n+4){background:#f2eaff;border-color:#d9c0ff}
        .k-card:hover{transform:translateY(-2px);box-shadow:0 12px 22px rgba(17,24,39,.14)}
        .k-card:focus-visible{outline:none;border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.22)}
        .k-card.added{animation:kpop .28s ease}
        @keyframes kpop{
            0%{transform:scale(1)}
            45%{transform:scale(1.03)}
            100%{transform:scale(1)}
        }
        .k-card:hover .k-thumb{transform:scale(1.02);filter:saturate(1.05)}
        .k-card:active .k-thumb{transform:scale(.99)}
        .k-thumb{
            width:100%;
            aspect-ratio:1/1;
            object-fit:cover;
            border-radius:10px;
            border:1px solid #e5ebf3;
            background:#f3f5f9;
            transition:transform .2s ease, filter .2s ease;
        }
        .k-fly-ghost{
            position: fixed;
            z-index: 120;
            width: 56px;
            height: 56px;
            border-radius: 10px;
            object-fit: cover;
            pointer-events: none;
            box-shadow: 0 12px 24px rgba(17,24,39,.22);
            transition: transform .55s cubic-bezier(.2,.8,.2,1), opacity .55s ease, filter .55s ease;
            opacity: .95;
        }
        .k-thumb-wrap{position:relative}
        .k-badge-stock{display:none}
        .k-name{font-size:14px;font-weight:700;line-height:1.4;margin:0}
        .k-foot{display:flex;align-items:center;justify-content:center;gap:6px}
        .k-price{
            font-size:14px;font-weight:700;color:var(--ok);line-height:1.3;text-align:center;margin-top:10px;
            background: var(--accent-b);
            border:1px solid #bde7cf;
            border-radius:10px;
            padding:4px 10px;
        }
        .k-stock{display:none}

        .k-cart{padding:12px;display:grid;gap:10px}
        .k-cart-head{
            position: sticky;
            top: 0;
            z-index: 6;
            background: #fff;
            padding: 8px 0 10px;
            border-bottom: 1px solid #e6edf6;
        }
        .k-cart-handle{
            width:44px;height:5px;border-radius:999px;background:#dbe4ef;margin:0 auto 8px;
        }
        .k-cart-title{margin:0;font-size:15px;font-weight:700;line-height:1.3}
        .k-cart-close{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            height:34px;
            padding:0 12px;
            font-size:12px;
            font-weight:700;
            border-radius:10px;
            border:1px solid #fecaca;
            background:#fff1f2;
            color:#b42318;
        }
        .k-cart-summary{
            margin-top:8px;
            border:1px solid #dbe7f4;
            background: #f8fbff;
            border-radius:10px;
            padding:8px 10px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            font-size:12px;
            color:#334155;
            font-weight:600;
        }
        .k-cart-summary strong{font-size:13px;color:#0f172a}
        .k-offline-pill{
            display:none;
            align-items:center;
            gap:4px;
            border:1px solid #fdba74;
            background:#fff7ed;
            color:#9a3412;
            font-size:11px;
            font-weight:700;
            border-radius:999px;
            padding:3px 8px;
            white-space:nowrap;
        }
        .k-shift-mini{
            margin-top:8px;
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:6px;
        }
        .k-shift-mini.hide{display:none}
        .k-shift-toggle{margin-top:8px;height:34px;font-size:12px}
        .k-shift-mini-card{
            border:1px solid #dbe7f4;
            background:#f8fbff;
            border-radius:10px;
            padding:6px 7px;
            min-height:52px;
        }
        .k-shift-mini-card span{
            display:block;
            font-size:10px;
            color:#64748b;
            line-height:1.2;
        }
        .k-shift-mini-card strong{
            display:block;
            margin-top:3px;
            font-size:12px;
            color:#0f172a;
            line-height:1.2;
        }
        .k-stock-pill{
            display:inline-flex;align-items:center;gap:4px;
            font-size:10px;font-weight:700;border-radius:999px;padding:3px 7px;
            border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;
        }
        .k-stock-pill.warn{border-color:#fecaca;background:#fff1f2;color:#b91c1c}
        .k-list{
            display:grid;
            gap:8px;
            max-height:calc(100vh - 460px);
            min-height:160px;
            overflow:auto;
            padding-right:2px;
            padding-bottom:14px;
        }
        .k-row{border:1px solid #dbe4f0;border-radius:12px;padding:5px;display:grid;gap:4px}
        .k-row.flash{animation:krowflash .6s ease}
        @keyframes krowflash{
            0%{box-shadow:0 0 0 0 rgba(37,99,235,.35)}
            100%{box-shadow:0 0 0 10px rgba(37,99,235,0)}
        }
        .k-row-top{display:grid;grid-template-columns:34px 1fr;gap:6px;align-items:center}
        .k-row-thumb{width:34px;height:34px;object-fit:cover;border-radius:8px;border:1px solid #e3ebf3;background:#eef3fb}
        .k-row-name{font-size:11px;font-weight:600;line-height:1.3}
        .k-row-foot{display:flex;align-items:center;justify-content:space-between;gap:5px}
        .k-step{display:inline-flex;align-items:center;border:1px solid #d5deea;border-radius:9px;overflow:hidden}
        .k-step button{border:0;background:#f8fafc;padding:3px 7px;cursor:pointer;font-size:11px}
        .k-step span{padding:0 8px;font-size:11px;font-weight:700}
        .k-step-input{
            width:38px;
            height:22px;
            border:0;
            border-left:1px solid #d5deea;
            border-right:1px solid #d5deea;
            text-align:center;
            font-family:inherit;
            font-size:11px;
            font-weight:700;
            background:#fff;
            color:#0f172a;
            outline:none;
        }
        .k-del{border:0;background:#fff;color:var(--danger);font-size:10px;cursor:pointer;min-width:44px;min-height:32px}
        .k-pay{display:grid;gap:8px;border-top:1px solid var(--line);padding-top:10px}
        .k-pay-core{
            border:1px solid #dbe7f4;
            border-radius:12px;
            background:#f8fbff;
            padding:8px;
            display:grid;
            gap:8px;
        }
        .k-pay-advanced{
            border:1px solid #dbe7f4;
            border-radius:12px;
            background:#fff;
            overflow:hidden;
        }
        .k-acc{
            border:1px solid #dbe7f4;
            border-radius:12px;
            background:#f8fbff;
            overflow:hidden;
        }
        .k-acc-btn{
            width:100%;
            border:0;
            background:#eef5ff;
            color:#0f172a;
            height:40px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            padding:0 12px;
            font-family:inherit;
            font-size:12px;
            font-weight:700;
            cursor:pointer;
        }
        .k-acc-body{display:none;padding:8px;border-top:1px solid #dbe7f4}
        .k-acc.open .k-acc-body{display:grid;gap:8px}
        .k-inline-add{
            height:34px;
            border:1px dashed #93c5fd;
            border-radius:10px;
            background:#eff6ff;
            color:#1d4ed8;
            font-size:12px;
            font-weight:700;
            font-family:inherit;
            cursor:pointer;
        }
        .k-debt-mini{
            display:none;
            border:1px solid #fed7aa;
            background:#fff7ed;
            border-radius:10px;
            padding:8px;
            font-size:11px;
            color:#7c2d12;
            line-height:1.35;
        }
        .k-debt-mini.show{display:block}
        .k-debt-mini strong{display:block;font-size:12px;color:#9a3412;margin-bottom:3px}
        .k-debt-fab{
            position:fixed;
            right:12px;
            bottom:146px;
            z-index:140;
            width:48px;
            height:48px;
            border-radius:999px;
            border:0;
            background:#f59e0b;
            color:#fff;
            box-shadow:0 10px 24px rgba(245,158,11,.35);
            display:none;
            place-items:center;
            font-size:22px;
            pointer-events:auto;
            touch-action:manipulation;
        }
        .k-debt-fab.show{display:grid}
        .k-debt-pop{
            position:fixed;
            inset:0;
            z-index:130;
            display:none;
            background:rgba(15,23,42,.45);
            padding:12px;
            align-items:flex-end;
        }
        .k-debt-pop.show{display:flex}
        .k-debt-card{
            width:100%;
            border-radius:14px;
            background:#fff;
            border:1px solid #fed7aa;
            padding:12px;
        }
        .k-debt-card h4{margin:0 0 6px;font-size:14px;color:#9a3412}
        .k-debt-card p{margin:0;font-size:12px;color:#7c2d12;line-height:1.45}
        .k-debt-close{
            margin-top:10px;
            width:100%;
            height:38px;
            border-radius:10px;
            border:1px solid #fdba74;
            background:#fff7ed;
            color:#9a3412;
            font-weight:700;
            font-family:inherit;
        }
        .k-input.error,.k-select.error{border-color:#ef4444 !important;box-shadow:0 0 0 3px rgba(239,68,68,.12) !important}
        .k-split-toggle{
            display:flex;align-items:center;justify-content:space-between;gap:8px;
            border:1px solid #dbe7f4;background:#f8fbff;border-radius:10px;padding:8px 10px;
            font-size:12px;font-weight:600;color:#334155;
        }
        .k-switch{
            border:1px solid #bfdbfe;background:#fff;color:#1d4ed8;border-radius:999px;height:30px;
            padding:0 12px;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;
        }
        .k-switch.active{background:#2563eb;color:#fff;border-color:#2563eb}
        .k-split-box{
            display:none;border:1px solid #dbe7f4;background:#f8fbff;border-radius:10px;padding:8px;
        }
        .k-split-box.show{display:grid;gap:8px}
        .k-split-row{display:grid;grid-template-columns:1fr 1fr;gap:6px}
        .k-split-hint{font-size:11px;color:#64748b}
        .k-split-hint strong{color:#0f172a}
        .k-split-hint .ok{color:#15803d}
        .k-split-hint .bad{color:#b91c1c}
        .k-pay-quick{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
        .k-pay-btn{
            height:40px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--text);
            font-family:inherit;font-size:12px;font-weight:800;cursor:pointer;
        }
        .k-pay-btn.active{
            border-color:#8dc5f3;
            background:#eaf5ff;
            color:#0b4f8a;
        }
        #paymentMethod{display:none}
        .k-pay-sticky{
            position: sticky;
            bottom: 0;
            z-index: 3;
            background: #fff;
            padding-top: 10px;
            border-top: 1px solid #edf2f8;
        }
        .k-mobile-actions{display:none}

        .k-bottom{
            position: sticky;
            bottom: 0;
            z-index: 70;
            border-top:1px solid var(--line);background:#fff;padding:10px 12px;
            box-shadow:0 -4px 10px rgba(17,24,39,.05);display:flex;align-items:center;justify-content:space-between;gap:10px
        }
        .k-total small{display:block;color:var(--muted);font-size:11px;line-height:1.4;margin-bottom:2px}
        .k-total strong{font-size:20px;line-height:1.2}
        .k-actions{display:flex;gap:8px}
        .k-btn{height:42px;border-radius:12px;padding:0 14px;font-family:inherit;font-weight:700;cursor:pointer;border:1px solid #d0dbe8;background:#fff;color:var(--text);transition:background var(--fx-fast) ease, border-color var(--fx-fast) ease, transform var(--fx-fast) ease}
        .k-btn-primary{border:0;background:var(--brand);color:#fff;min-width:150px}
        .k-btn-sync{height:34px;font-size:12px;padding:0 10px}
        .k-btn:hover{background:#f8fafc}
        .k-btn-primary:hover{background:var(--brand2)}
        .k-btn:active{transform:translateY(1px)}
        .k-btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
        #clearBtnMobile,#payBtnMobile{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            width:100%;
            min-width:0;
            height:50px;
            border-radius:12px;
            font-size:16px;
            font-weight:700;
            line-height:1;
            white-space:nowrap;
            padding:0 12px;
        }
        #clearBtnMobile{border:1px solid #d3deea;background:#fff;color:#1f2937}
        #payBtnMobile{background:var(--brand);color:#fff}
        .k-pay-label{
            display:inline-block;
            min-width:0;
            max-width:none;
            overflow:visible;
            text-overflow:clip;
            white-space:nowrap;
            flex:1 1 auto;
            text-align:center;
        }
        #payBtnMobile .k-spin,#payBtn .k-spin{display:none}
        #payBtnMobile.busy .k-spin,#payBtn.busy .k-spin{display:inline-block;animation:kspin .8s linear infinite}
        @keyframes kspin{to{transform:rotate(360deg)}}
        #clearBtnMobile .k-ico,#payBtnMobile .k-ico{width:17px;height:17px}
        .k-pay-btn:disabled,.k-switch:disabled{opacity:.55;cursor:not-allowed}
        .k-pay-btn:focus-visible,.k-switch:focus-visible,.k-scan-toggle:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(96,165,250,.2)}
        .k-empty{font-size:12px;color:var(--muted)}
        .k-empty-state{
            border:1px dashed #cfd8e6;
            border-radius:12px;
            padding:20px 14px;
            text-align:center;
            color:var(--muted);
            background:#fbfcfe;
            display:none;
        }
        .k-empty-state.show{display:block}
        #stateHint{display:block;min-height:16px}
        .k-skeleton{
            border:1px solid var(--line);
            border-radius:12px;
            background:#fff;
            padding:10px;
            display:grid;
            gap:10px;
            min-height:198px;
        }
        .k-shimmer{
            background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 50%, #eef2f7 75%);
            background-size: 200% 100%;
            animation: shimmer 1.1s infinite linear;
            border-radius:8px;
        }
        @keyframes shimmer {
            0%{background-position:200% 0}
            100%{background-position:-200% 0}
        }

        @media (min-width:1101px){
            .k-main{grid-template-columns:1fr 360px;align-items:start;padding:12px}
            .k-tools{grid-template-columns:1fr 180px}
            .k-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .k-thumb{aspect-ratio:1/1}
            .k-cart{
                position: sticky;
                top: 12px;
                max-height: calc(100vh - 110px);
                overflow: auto;
            }
            .k-list{max-height: none}
            .k-pay-quick{display:none}
        }
        @media (max-width:767px){
            .k-tools-wrap{
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 98;
                margin: 0;
                padding: 8px 12px 4px;
                border-bottom: 1px solid #e5edf7;
                box-shadow: 0 4px 10px rgba(17,24,39,.05);
            }
            #toolsSpacer{display:block;height:0}
            #paymentMethod{display:none}
            .k-bottom{display:none}
            .k-mobile-actions{
                display:grid;
                grid-template-columns: 1fr 1fr;
                gap:10px;
                position: sticky;
                bottom: 0;
                background:#fff;
                border-top:1px solid var(--line);
                padding:10px 2px calc(10px + env(safe-area-inset-bottom, 0px));
                margin-top:10px;
            }
            .k-mobile-actions .k-btn{height:50px;font-size:16px}
            .k-mobile-actions #payBtnMobile{font-size:15px}
            .k-mobile-actions #payBtnMobile .k-ico:not(.k-spin){display:none}
            .k-step button{min-width:44px;min-height:44px}
            .k-step-input{height:44px;width:46px}
            .k-mobile-actions .k-btn-primary{
                min-width:0;
                width:100%;
            }
            .k-pay-quick{
                display:flex;
                gap:6px;
                overflow-x:auto;
                overflow-y:hidden;
                scroll-snap-type:x mandatory;
                -webkit-overflow-scrolling:touch;
                padding-bottom:2px;
            }
            .k-pay-quick::-webkit-scrollbar{display:none}
            .k-pay-btn{
                flex:0 0 auto;
                min-width:96px;
                height:44px;
                font-size:11px;
                scroll-snap-align:start;
            }
            .k-cart-overlay{
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, .45);
                z-index: 80;
                display: none;
            }
            .k-cart-overlay.show{display:block}
            .k-cart{
                position: fixed;
                top: 10px;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 90;
                border-radius: 18px 18px 0 0;
                max-height: calc(100vh - 10px);
                overflow: hidden;
                display:flex;
                flex-direction:column;
                transform: translateY(104%);
                transition: transform .22s cubic-bezier(.2,.8,.2,1);
                background:#fff;
                border:1px solid var(--line);
            }
            .k-cart.show{transform: translateY(0)}
            .k-list{
                flex:1 1 auto;
                max-height:none;
                min-height:160px;
                overflow-y:auto;
                overflow-x:hidden;
                padding-bottom:10px;
            }
            .k-pay-sticky{flex:0 0 auto}
            .k-cart-fab{
                position: fixed;
                right: 14px;
                bottom: 92px;
                z-index: 95;
                width: 52px;
                height: 52px;
                border-radius: 999px;
                border: 0;
                background: linear-gradient(135deg, #2563eb, #0ea5e9);
                color: #fff;
                font-size: 20px;
                box-shadow: 0 10px 24px rgba(11,123,220,.35);
                display:grid;
                place-items:center;
            }
        .k-cart-fab svg{width:24px;height:24px;display:block}
        .k-cart-fab-total{
            position:absolute;
            right:58px;
            top:50%;
            transform:translateY(-50%);
            display:none;
            padding:4px 8px;
            border-radius:999px;
            background:#0f172a;
            color:#fff;
            font-size:11px;
            font-weight:700;
            white-space:nowrap;
            box-shadow:0 8px 20px rgba(15,23,42,.28);
        }
        .k-cart-fab.has-total .k-cart-fab-total{display:inline-block}
        .k-cart-badge{
            position:absolute;
            top:-6px;
            right:-6px;
                min-width:20px;
                height:20px;
                padding:0 5px;
                border-radius:999px;
                background:#ef4444;
                color:#fff;
                font-size:11px;
                font-weight:800;
                display:grid;
                place-items:center;
                border:2px solid #fff;
            }
            .k-cart-close{display:inline-flex}
        .k-focus-mode .k-main{padding-bottom:6px}
        .k-focus-mode .k-bottom,
        .k-focus-mode .k-cart-fab{display:none !important}
            body.k-cart-open{overflow:hidden}
            body.k-cart-open .k-cart-fab{display:none !important}
            .k-cart{overscroll-behavior:contain;-webkit-overflow-scrolling:touch}
            body.k-cart-open .k-tools-wrap,
            body.k-cart-open #toolsSpacer,
            body.k-cart-open #productGrid,
            body.k-cart-open #emptyState,
            body.k-cart-open #stateHint{display:none !important}
            .k-cart.k-empty-cart #checkoutInfoAcc,
            .k-cart.k-empty-cart #paymentAdvAcc{
                display:none;
            }
            .k-cart.k-empty-cart .k-mobile-actions{
                grid-template-columns:1fr;
            }
            .k-cart.k-empty-cart .k-pay-core{
                border-style:dashed;
                background:#f8fafc;
            }
        .k-toast{
            position:fixed;
            left:50%;
            top:50%;
            transform:translate(-50%, calc(-50% + 10px));
            width:min(92vw, 420px);
            z-index:120;
            border-radius:12px;
            border:1px solid #86efac;
            background:#ecfdf3;
            color:#14532d;
            padding:10px 12px;
            font-size:12px;
            font-weight:600;
            box-shadow:0 10px 20px rgba(15,23,42,.14);
            opacity:0;
            pointer-events:none;
            transition:opacity .2s ease, transform .2s ease;
        }
        .k-toast.show{
            opacity:1;
            transform:translate(-50%, -50%);
            pointer-events:auto;
        }
        .k-toast.error{
            top:24px;
            transform:translate(-50%, -10px);
            border-color:#fecaca;
            background:#fef2f2;
            color:#991b1b;
        }
        .k-toast.error.show{
            transform:translate(-50%, 0);
        }
        .k-toast.error .k-toast-action{background:#991b1b}
        .k-toast-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
        .k-toast-action{
            border:0;
            background:#14532d;
            color:#fff;
            height:28px;
            border-radius:8px;
            padding:0 10px;
            font-size:11px;
            font-weight:700;
            font-family:inherit;
            cursor:pointer;
        }
        }
        @media (min-width:768px) and (max-width:1366px){
            .k-main{grid-template-columns:60% 40%;align-items:start;padding:12px}
            .k-mini-sum{display:flex}
            .k-tools-wrap{
                position:sticky;
                top:0;
                left:auto;
                right:auto;
                z-index:50;
                margin:0 0 6px;
                padding:0 0 6px;
                box-shadow:none;
            }
            #toolsSpacer{display:none !important;height:0 !important}
            .k-tools{display:grid;grid-template-columns:1fr 220px;gap:8px}
            .k-tools-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
            .k-tools .k-tools-top{grid-column:1/-1}
            .k-tools .k-search-wrap{grid-column:1/2}
            .k-tools #category{grid-column:2/3;max-width:220px;justify-self:end}
            .k-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
            .k-card{min-height:170px;padding:8px;gap:8px}
            .k-thumb{border-radius:8px}
            .k-name{
                font-size:13px;
                line-height:1.3;
                display:-webkit-box;
                -webkit-line-clamp:2;
                -webkit-box-orient:vertical;
                overflow:hidden;
                min-height:34px;
            }
            .k-price{font-size:13px;margin-top:6px}
            .k-cart-overlay,.k-cart-fab,.k-cart-handle,.k-cart-close{display:none !important}
            .k-cart{
                position:sticky;
                top:12px;
                max-height:calc(100vh - 24px);
                overflow:auto;
                transform:none !important;
                transition:none;
                border-radius:16px;
                display:grid;
            }
            .k-list{max-height:none;min-height:220px}
            .k-pay-sticky{position:sticky;bottom:0}
            .k-debt-fab{
                width:40px;
                height:40px;
                font-size:18px;
                right:10px;
                bottom:96px;
            }
            .k-debt-pop{padding:10px;align-items:flex-end}
            .k-debt-card{
                max-width:360px;
                margin-left:auto;
                border-radius:12px;
                padding:10px;
            }
            .k-debt-card h4{font-size:13px;margin-bottom:4px}
            .k-debt-card p{font-size:11px;line-height:1.35}
            .k-debt-close{height:34px;font-size:12px}
        }
        @media (min-width:768px) and (max-width:980px){
            .k-wrap.k-split-mobile .k-main{grid-template-columns:1fr}
            .k-wrap.k-split-mobile .k-cart-overlay{display:none}
            .k-wrap.k-split-mobile .k-cart{
                position:fixed;
                top:10px;
                left:0;right:0;bottom:0;
                z-index:90;
                border-radius:18px 18px 0 0;
                transform:translateY(104%);
                transition:transform .22s cubic-bezier(.2,.8,.2,1);
            }
            .k-wrap.k-split-mobile .k-cart.show{transform:translateY(0)}
            .k-wrap.k-split-mobile .k-cart-fab{display:grid !important}
            .k-wrap.k-split-mobile .k-cart-close,.k-wrap.k-split-mobile .k-cart-handle{display:inline-flex !important}
        }
        .k-density-compact .k-grid{gap:8px}
        .k-density-compact .k-card{min-height:156px;padding:7px;gap:7px}
        .k-density-compact .k-name{font-size:12px;line-height:1.25}
        .k-density-compact .k-price{font-size:12px;padding:3px 8px}
        @media (min-width:768px) and (max-width:1366px) and (orientation:landscape){
            html,body{height:100%}
            .k-wrap{height:100vh;overflow:hidden}
            .k-main{
                grid-template-columns:65% 35%;
                align-items:start;
                height:100vh;
                overflow:hidden;
            }
            .k-products{
                height:calc(100vh - 24px);
                overflow-y:auto;
                overflow-x:hidden;
                -webkit-overflow-scrolling:touch;
            }
            .k-cart{
                position:sticky !important;
                top:8px;
                align-self:start;
                height:calc(100vh - 16px);
                overflow:auto;
            }
        }
        @media (min-width:1367px){
            .k-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
        }
        .k-modal{
            position:fixed;
            inset:0;
            z-index:140;
            display:none;
            align-items:flex-end;
            justify-content:center;
            padding:12px;
        }
        .k-modal.show{display:flex}
        .k-modal-backdrop{
            position:absolute;
            inset:0;
            background:rgba(15,23,42,.45);
        }
        .k-modal-card{
            position:relative;
            width:min(420px,100%);
            border-radius:16px;
            border:1px solid #dbe7f4;
            background:#fff;
            padding:14px;
            box-shadow:0 18px 36px rgba(15,23,42,.2);
        }
        .k-modal-title{margin:0;font-size:15px;font-weight:700}
        .k-modal-sub{margin:4px 0 0;font-size:12px;color:#64748b}
        .k-modal-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}
        .k-success-sheet{position:fixed;inset:0;z-index:150;display:none;align-items:flex-end;background:rgba(15,23,42,.45)}
        .k-success-sheet.show{display:flex}
        .k-success-card{
            width:100%;background:#fff;border-radius:16px 16px 0 0;border:1px solid #dbe7f4;padding:14px;
            box-shadow:0 -10px 26px rgba(15,23,42,.18);display:grid;gap:10px
        }
        .k-success-title{margin:0;font-size:16px;font-weight:800;color:#166534}
        .k-success-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .k-success-item{border:1px solid #dbe7f4;background:#f8fbff;border-radius:10px;padding:8px}
        .k-success-item span{display:block;font-size:11px;color:#64748b}
        .k-success-item strong{display:block;margin-top:2px;font-size:13px;color:#0f172a}
        .k-scan-modal{
            position:fixed;inset:0;z-index:140;display:none;align-items:flex-end;background:rgba(2,6,23,.75)
        }
        .k-scan-modal.show{display:flex}
        .k-scan-sheet{
            width:100%;background:#0f172a;color:#e2e8f0;border-radius:16px 16px 0 0;padding:10px;
            max-height:85vh;display:grid;gap:8px
        }
        .k-scan-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .k-scan-head strong{font-size:14px}
        .k-scan-controls{display:flex;gap:8px}
        .k-scan-btn{
            border:1px solid #334155;background:#1e293b;color:#e2e8f0;border-radius:10px;height:34px;padding:0 10px;font:700 12px Poppins,sans-serif
        }
        .k-scan-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}
        .k-scan-video{
            width:100%;aspect-ratio:16/11;background:#020617;border-radius:12px;object-fit:cover;border:1px solid #334155
        }
        .k-scan-video.flash-ok{
            border-color:#22c55e;
            box-shadow:0 0 0 3px rgba(34,197,94,.35);
        }
        .k-scan-note{font-size:12px;color:#cbd5e1}
        @media (prefers-reduced-motion: reduce){
            *{animation:none !important;transition:none !important;scroll-behavior:auto !important}
        }
    </style>
</head>
<body>
<div class="k-wrap" id="app">
    <main class="k-main">
            <section class="k-panel k-products">
                <div class="k-tools-wrap">
                    <div class="k-tools">
                        <div class="k-tools-top">
                            <span class="k-hint" style="margin:0;">POS Friendly</span>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <button type="button" class="k-scan-toggle" id="cameraScanBtn">Scan Kamera</button>
                                <button type="button" class="k-scan-toggle" id="scanOnlyToggle" aria-pressed="false">Scan-only: OFF</button>
                                <button type="button" class="k-scan-toggle" id="densityToggle" aria-pressed="false">Density: Comfort</button>
                            </div>
                        </div>
                        <div class="k-search-wrap">
                            <span class="k-input-icon" aria-hidden="true">
                                <svg class="k-ico" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <input id="search" class="k-input with-icon" type="text" placeholder="Cari produk / SKU / barcode">
                            <div id="suggestBox" class="k-suggest"></div>
                        </div>
                        <select id="category" class="k-select">
                            <option value="0">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="toolsSpacer"></div>
                <div class="k-mini-sum" id="tabletMiniSummary"><span>Item: 0</span><strong>Rp 0</strong></div>
                <p class="k-hint" id="stateHint" style="display:none;"></p>
            <div class="k-grid" id="productGrid"></div>
            <div class="k-empty-state" id="emptyState">
                <div class="k-inline" style="justify-content:center;margin-bottom:2px;">
                    <svg class="k-ico" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#64748b" stroke-width="2"/><path d="M9 9l6 6M15 9l-6 6" stroke="#64748b" stroke-width="2" stroke-linecap="round"/></svg>
                    <span style="font-weight:700;color:#334155;">Produk tidak ditemukan</span>
                </div>
                <div style="font-size:12px;margin-top:4px;">Coba kata kunci lain atau reset filter.</div>
                <button type="button" class="k-btn" id="resetFilterBtn" style="margin-top:10px;height:36px;font-size:12px;">Reset Filter</button>
            </div>
        </section>

        <div class="k-cart-overlay" id="cartOverlay"></div>
        <aside class="k-panel k-cart" id="cartAside">
            <div class="k-cart-head">
                <div class="k-cart-handle"></div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <h2 class="k-cart-title k-inline">
                        <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.6" fill="currentColor"/><circle cx="18" cy="20" r="1.6" fill="currentColor"/></svg>
                        Keranjang
                    </h2>
                    <button type="button" id="cartCloseBtn" class="k-cart-close k-inline">
                        <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Tutup
                    </button>
                </div>
                <div class="k-cart-summary">
                    <span>Item: <span id="cartHeadCount">0</span></span>
                    <div class="k-inline">
                        <span class="k-offline-pill" id="offlineQueuePill">Pending Sync: <span id="offlineQueueCount">0</span></span>
                        <button type="button" class="k-btn k-btn-sync" id="syncStockBtn">Sinkron Stok</button>
                        <strong>Total: Rp <span id="cartHeadTotal">0</span></strong>
                    </div>
                </div>
                <button type="button" class="k-acc-btn k-shift-toggle" id="shiftMiniToggle" aria-expanded="false">
                    <span>Ringkasan Shift</span>
                    <span id="shiftMiniChevron">+</span>
                </button>
                <div class="k-shift-mini hide">
                    <div class="k-shift-mini-card">
                        <span>Total</span>
                        <strong id="shiftTotalText">{{ number_format((int) ($shiftSummary['total_transactions'] ?? 0), 0, ',', '.') }}</strong>
                    </div>
                    <div class="k-shift-mini-card">
                        <span>Lunas</span>
                        <strong id="shiftPaidText">{{ number_format((int) ($shiftSummary['paid_transactions'] ?? 0), 0, ',', '.') }}</strong>
                    </div>
                    <div class="k-shift-mini-card">
                        <span>Pending</span>
                        <strong id="shiftPendingText">{{ number_format((int) ($shiftSummary['pending_transactions'] ?? 0), 0, ',', '.') }}</strong>
                    </div>
                    <div class="k-shift-mini-card">
                        <span>Omzet</span>
                        <strong id="shiftOmzetText">Rp {{ number_format((float) ($shiftSummary['omzet_paid'] ?? 0), 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
            <div class="k-list" id="cartList"></div>
            <p class="k-empty" id="cartEmpty">Belum ada item dipilih.</p>
            <div class="k-pay-sticky">
                <div class="k-pay">
                    <div class="k-pay-core">
                        <div class="k-inline" style="justify-content:space-between;">
                            <strong style="font-size:13px;">Langkah 2: Pembayaran</strong>
                            <span style="font-size:11px;color:#64748b;">Ringkas</span>
                        </div>
                        <div class="k-pay-quick" id="paymentQuick">
                            <button type="button" class="k-pay-btn active" data-method="cash">Cash</button>
                            <button type="button" class="k-pay-btn" data-method="qris">QRIS</button>
                            <button type="button" class="k-pay-btn" data-method="debit">Debit</button>
                            <button type="button" class="k-pay-btn" data-method="transfer">Transfer</button>
                            <button type="button" class="k-pay-btn" data-method="e_wallet">E-Wallet</button>
                        </div>
                    </div>
                    <div class="k-acc" id="checkoutInfoAcc">
                        <button type="button" class="k-acc-btn" id="checkoutInfoToggle" aria-expanded="false">
                            <span>Transaksi Member</span>
                            <span id="checkoutInfoChevron">+</span>
                        </button>
                        <div class="k-acc-body" id="checkoutInfoBody">
                            <div class="k-search-wrap">
                                <input type="text" id="customerName" class="k-input" placeholder="Nama / HP / Email customer (opsional)" autocomplete="off">
                                <div id="customerSuggestBox" class="k-suggest"></div>
                            </div>
                            <div id="customerDebtMini" class="k-debt-mini">
                                <strong>Ringkasan Hutang</strong>
                                <div id="customerDebtMiniText">-</div>
                            </div>
                            <button type="button" class="k-inline-add" id="quickAddCustomerBtn" style="display:none;">+ Tambah customer baru dari nama ini</button>
                            <select id="statusField" class="k-select">
                                <option value="paid">Lunas</option>
                                <option value="pending">Menunggu / Hutang</option>
                            </select>
                            <select id="debtModeField" class="k-select" style="display:none;">
                                <option value="normal">Normal</option>
                                <option value="partial">Bayar Sebagian + Hutang</option>
                                <option value="merge">Gabung Hutang Lama</option>
                            </select>
                        </div>
                    </div>
                    <div class="k-pay-advanced k-acc" id="paymentAdvAcc">
                        <button type="button" class="k-acc-btn" id="paymentAdvToggle" aria-expanded="false">
                            <span>Lanjutan Pembayaran</span>
                            <span id="paymentAdvChevron">+</span>
                        </button>
                        <div class="k-acc-body" id="paymentAdvBody">
                            <div class="k-split-toggle">
                                <span>Split payment (2 metode)</span>
                                <button type="button" class="k-switch" id="splitToggleBtn" aria-pressed="false">OFF</button>
                            </div>
                            <div class="k-split-box" id="splitBox">
                                <div class="k-split-row">
                                    <select id="splitMethodA" class="k-select">
                                        <option value="cash">Cash</option>
                                        <option value="qris">QRIS</option>
                                        <option value="debit">Debit</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                    <input id="splitAmountA" class="k-input" type="text" inputmode="numeric" placeholder="Nominal A">
                                </div>
                                <div class="k-split-row">
                                    <select id="splitMethodB" class="k-select">
                                        <option value="qris">QRIS</option>
                                        <option value="cash">Cash</option>
                                        <option value="debit">Debit</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                    <input id="splitAmountB" class="k-input" type="text" inputmode="numeric" placeholder="Nominal B">
                                </div>
                                <div class="k-split-hint">
                                    Total split: <strong id="splitTotalText">Rp 0</strong> | Selisih: <strong id="splitDiffText">Rp 0</strong>
                                </div>
                            </div>
                            <input id="qrisRef" class="k-input" type="text" placeholder="Referensi QRIS" style="display:none">
                        </div>
                    </div>
                    <select id="paymentMethod" class="k-select">
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="debit">Debit</option>
                        <option value="transfer">Transfer</option>
                        <option value="e_wallet">E-Wallet</option>
                    </select>
                </div>
                <div class="k-mobile-actions">
                    <button type="button" class="k-btn k-inline" id="clearBtnMobile">
                        <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V5h6v2m-8 0l1 12h8l1-12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Kosongkan
                    </button>
                    <button type="button" class="k-btn k-btn-primary k-inline" id="payBtnMobile">
                        <svg class="k-ico k-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-opacity=".35"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3z" stroke="currentColor" stroke-width="2"/><path d="M3 11h18" stroke="currentColor" stroke-width="2"/></svg>
                        <span class="k-pay-label">Bayar & Simpan</span>
                    </button>
                </div>
            </div>
        </aside>
        <button type="button" class="k-cart-fab" id="cartFab" title="Buka Keranjang" aria-label="Buka Keranjang">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="10" cy="20" r="1.6" fill="currentColor"/>
                <circle cx="18" cy="20" r="1.6" fill="currentColor"/>
            </svg>
            <span class="k-cart-fab-total" id="cartFabTotal">Rp 0</span>
            <span class="k-cart-badge" id="cartFabCount">0</span>
        </button>
        <button type="button" class="k-debt-fab" id="debtFab" title="Info Hutang" aria-label="Info Hutang">!</button>
    </main>
    <div class="k-debt-pop" id="debtPop" aria-hidden="true">
        <div class="k-debt-card">
            <h4>Ringkasan Hutang Customer</h4>
            <p id="debtPopText">-</p>
            <button type="button" class="k-debt-close" id="debtPopClose">Tutup</button>
        </div>
    </div>
    <div class="k-toast" id="kToast" role="status" aria-live="polite">
        <div class="k-toast-row">
            <span id="kToastText"></span>
            <button type="button" class="k-toast-action" id="kToastUndo" style="display:none;">Undo</button>
        </div>
    </div>
    <div class="k-modal" id="clearConfirmModal" aria-hidden="true">
        <div class="k-modal-backdrop" id="clearConfirmBackdrop"></div>
        <div class="k-modal-card" role="dialog" aria-modal="true" aria-labelledby="clearConfirmTitle">
            <h3 class="k-modal-title" id="clearConfirmTitle">Kosongkan keranjang?</h3>
            <p class="k-modal-sub">Semua item akan dihapus dari transaksi ini.</p>
            <div class="k-modal-actions">
                <button type="button" class="k-btn" id="clearConfirmCancel">Batal</button>
                <button type="button" class="k-btn k-btn-primary" id="clearConfirmOk">Ya, kosongkan</button>
            </div>
        </div>
    </div>
    <div class="k-scan-modal" id="cameraScanModal" aria-hidden="true">
        <div class="k-scan-sheet">
            <div class="k-scan-head">
                <strong>Scan Barcode Kamera</strong>
                <div class="k-scan-controls">
                    <button type="button" class="k-scan-btn" id="scanTorchBtn">Flash OFF</button>
                    <button type="button" class="k-scan-btn primary" id="scanCloseBtn">Tutup</button>
                </div>
            </div>
            <video id="scanVideo" class="k-scan-video" playsinline muted></video>
            <div class="k-scan-note" id="scanNote">Arahkan kamera ke barcode. Produk akan otomatis ditambahkan.</div>
            <div class="k-search-wrap">
                <input id="scanManualCode" class="k-input" type="text" inputmode="numeric" placeholder="Fallback: masukkan barcode/SKU manual">
            </div>
            <button type="button" class="k-scan-btn primary" id="scanManualSubmit">Tambah dari Kode</button>
        </div>
    </div>
    <div class="k-success-sheet" id="successSheet" aria-hidden="true">
        <div class="k-success-card">
            <h3 class="k-success-title">Transaksi Berhasil</h3>
            <div class="k-success-grid">
                <div class="k-success-item"><span>Total</span><strong id="successTotal">Rp 0</strong></div>
                <div class="k-success-item"><span>Metode</span><strong id="successMethod">-</strong></div>
            </div>
            <button type="button" class="k-btn k-btn-primary" id="successNewTxnBtn">Transaksi Baru</button>
        </div>
    </div>

    <form id="checkoutForm" method="POST" action="{{ route('pos.checkout') }}">
        @csrf
        <input type="hidden" name="items_json" id="itemsJson">
        <input type="hidden" name="customer_id" id="customerId">
        <input type="hidden" name="customer_name" id="hiddenCustomerName">
        <input type="hidden" name="status" id="hiddenStatus">
        <input type="hidden" name="debt_mode" id="hiddenDebtMode">
        <input type="hidden" name="paid_amount" id="paidAmount">
        <input type="hidden" name="payment_method" id="hiddenPaymentMethod">
        <input type="hidden" name="payment_method_single" id="hiddenPaymentMethodSingle">
        <input type="hidden" name="checkout_token" id="checkoutToken">
        <input type="hidden" name="qris_reference_id" id="hiddenQrisRef">
        <input type="hidden" name="qris_issuer" value="manual">
        <input type="hidden" name="split_payments_json" id="splitPaymentsJson">

        <footer class="k-bottom">
            <div class="k-total">
                <small id="itemCount">Item: 0</small>
                <strong>Rp <span id="totalText">0</span></strong>
            </div>
            <div class="k-actions">
                <button type="button" class="k-btn k-inline" id="clearBtn">
                    <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V5h6v2m-8 0l1 12h8l1-12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Kosongkan
                </button>
                <button type="submit" class="k-btn k-btn-primary k-inline" id="payBtn">
                    <svg class="k-ico k-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-opacity=".35"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <svg class="k-ico" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3z" stroke="currentColor" stroke-width="2"/><path d="M3 11h18" stroke="currentColor" stroke-width="2"/></svg>
                    <span class="k-pay-label">Bayar & Simpan</span>
                </button>
            </div>
        </footer>
    </form>
</div>

<script>
(() => {
    const productsSeed = @json($products);
    const storageBaseUrl = @json(asset('storage'));
    const fallbackImageUrl = @json(asset('dist/images/preview-8.jpg'));
    const productGrid = document.getElementById('productGrid');
    const cartList = document.getElementById('cartList');
    const cartEmpty = document.getElementById('cartEmpty');
    const searchInput = document.getElementById('search');
    const suggestBox = document.getElementById('suggestBox');
    const categorySelect = document.getElementById('category');
    const scanOnlyToggle = document.getElementById('scanOnlyToggle');
    const densityToggle = document.getElementById('densityToggle');
    const cameraScanBtn = document.getElementById('cameraScanBtn');
    const stateHint = document.getElementById('stateHint');
    const tabletMiniSummary = document.getElementById('tabletMiniSummary');
    const paymentMethod = document.getElementById('paymentMethod');
    const paymentQuick = document.getElementById('paymentQuick');
    const qrisRef = document.getElementById('qrisRef');
    const customerName = document.getElementById('customerName');
    const customerSuggestBox = document.getElementById('customerSuggestBox');
    const customerDebtMini = document.getElementById('customerDebtMini');
    const customerDebtMiniText = document.getElementById('customerDebtMiniText');
    const debtFab = document.getElementById('debtFab');
    const debtPop = document.getElementById('debtPop');
    const debtPopText = document.getElementById('debtPopText');
    const debtPopClose = document.getElementById('debtPopClose');
    const customerId = document.getElementById('customerId');
    const hiddenCustomerName = document.getElementById('hiddenCustomerName');
    const hiddenStatus = document.getElementById('hiddenStatus');
    const hiddenDebtMode = document.getElementById('hiddenDebtMode');
    const statusField = document.getElementById('statusField');
    const debtModeField = document.getElementById('debtModeField');
    const quickAddCustomerBtn = document.getElementById('quickAddCustomerBtn');
    const itemCount = document.getElementById('itemCount');
    const totalText = document.getElementById('totalText');
    const cartHeadCount = document.getElementById('cartHeadCount');
    const cartHeadTotal = document.getElementById('cartHeadTotal');
    const shiftTotalText = document.getElementById('shiftTotalText');
    const shiftPaidText = document.getElementById('shiftPaidText');
    const shiftPendingText = document.getElementById('shiftPendingText');
    const shiftOmzetText = document.getElementById('shiftOmzetText');
    const shiftMiniToggle = document.getElementById('shiftMiniToggle');
    const shiftMiniChevron = document.getElementById('shiftMiniChevron');
    const shiftMiniWrap = document.querySelector('.k-shift-mini');
    const syncStockBtn = document.getElementById('syncStockBtn');
    const offlineQueuePill = document.getElementById('offlineQueuePill');
    const offlineQueueCount = document.getElementById('offlineQueueCount');
    const itemsJson = document.getElementById('itemsJson');
    const paidAmount = document.getElementById('paidAmount');
    const hiddenPaymentMethod = document.getElementById('hiddenPaymentMethod');
    const hiddenPaymentMethodSingle = document.getElementById('hiddenPaymentMethodSingle');
    const hiddenQrisRef = document.getElementById('hiddenQrisRef');
    const splitPaymentsJson = document.getElementById('splitPaymentsJson');
    const checkoutToken = document.getElementById('checkoutToken');
    const clearBtn = document.getElementById('clearBtn');
    const clearBtnMobile = document.getElementById('clearBtnMobile');
    const payBtn = document.getElementById('payBtn');
    const payBtnLabel = payBtn ? payBtn.querySelector('.k-pay-label') : null;
    const cartCloseBtnMobile = document.getElementById('cartCloseBtnMobile');
    const payBtnMobile = document.getElementById('payBtnMobile');
    const payBtnMobileLabel = payBtnMobile ? payBtnMobile.querySelector('.k-pay-label') : null;
    const checkoutForm = document.getElementById('checkoutForm');
    const productsPanel = document.querySelector('.k-products');
    const toolsWrap = document.querySelector('.k-tools-wrap');
    const toolsSpacer = document.getElementById('toolsSpacer');
    const cartFab = document.getElementById('cartFab');
    const cartFabCount = document.getElementById('cartFabCount');
    const cartFabTotal = document.getElementById('cartFabTotal');
    const cartAside = document.getElementById('cartAside');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartCloseBtn = document.getElementById('cartCloseBtn');
    const cartHead = document.querySelector('.k-cart-head');
    const emptyState = document.getElementById('emptyState');
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    const kToast = document.getElementById('kToast');
    const kToastText = document.getElementById('kToastText');
    const kToastUndo = document.getElementById('kToastUndo');
    const clearConfirmModal = document.getElementById('clearConfirmModal');
    const clearConfirmBackdrop = document.getElementById('clearConfirmBackdrop');
    const clearConfirmCancel = document.getElementById('clearConfirmCancel');
    const clearConfirmOk = document.getElementById('clearConfirmOk');
    const splitToggleBtn = document.getElementById('splitToggleBtn');
    const cameraScanModal = document.getElementById('cameraScanModal');
    const scanVideo = document.getElementById('scanVideo');
    const scanCloseBtn = document.getElementById('scanCloseBtn');
    const scanTorchBtn = document.getElementById('scanTorchBtn');
    const scanNote = document.getElementById('scanNote');
    const scanManualCode = document.getElementById('scanManualCode');
    const scanManualSubmit = document.getElementById('scanManualSubmit');
    const splitBox = document.getElementById('splitBox');
    const splitMethodA = document.getElementById('splitMethodA');
    const splitMethodB = document.getElementById('splitMethodB');
    const splitAmountA = document.getElementById('splitAmountA');
    const splitAmountB = document.getElementById('splitAmountB');
    const splitTotalText = document.getElementById('splitTotalText');
    const splitDiffText = document.getElementById('splitDiffText');
    const checkoutInfoAcc = document.getElementById('checkoutInfoAcc');
    const checkoutInfoToggle = document.getElementById('checkoutInfoToggle');
    const checkoutInfoChevron = document.getElementById('checkoutInfoChevron');
    const paymentAdvAcc = document.getElementById('paymentAdvAcc');
    const paymentAdvToggle = document.getElementById('paymentAdvToggle');
    const paymentAdvChevron = document.getElementById('paymentAdvChevron');
    const successSheet = document.getElementById('successSheet');
    const successTotal = document.getElementById('successTotal');
    const successMethod = document.getElementById('successMethod');
    const successNewTxnBtn = document.getElementById('successNewTxnBtn');

    let products = Array.isArray(productsSeed) ? productsSeed : [];
    let productsPage = 1;
    let productsHasMore = true;
    let loadingMore = false;
    let cart = [];
    let fetchTimer = null;
    let suggestTimer = null;
    let loading = false;
    let toastTimer = null;
    let undoTimer = null;
    let undoSnapshot = null;
    let cartTouchStartY = 0;
    let cartTouchDeltaY = 0;
    let cartDragging = false;
    let scannerBuffer = '';
    let scannerTimer = null;
    let scannerLastTs = 0;
    let splitEnabled = false;
    let splitAutoFillLock = false;
    let shiftMiniVisible = false;
    let customerSuggestTimer = null;
    let selectedCustomer = null;
    let latestCustomerSuggestions = [];
    let isSubmitting = false;
    let scanOnlyMode = false;
    let densityMode = 'comfort';
    let syncingOfflineQueue = false;
    let scanStream = null;
    let scanLoopTimer = null;
    let scanLastCode = '';
    let scanLastAt = 0;
    let scanTorchOn = false;
    let currentDebtText = '';
    let currentDebtData = null;
    let lastActiveEl = null;
    let lastAddedProductId = 0;
    let lastAddedAt = 0;
    let focusLastAddedQty = false;
    const productTapGuard = new Map();
    const PREF_KEY = 'pos_friendly_checkout_pref_v1';
    const CART_DRAFT_KEY = 'pos_cart_draft_v1';
    const OFFLINE_QUEUE_KEY = 'pos_offline_checkout_queue_v1';
    const SUCCESS_SHEET_KEY = 'pos_checkout_success_v1';
    const skeletonCount = () => (window.matchMedia('(min-width:1200px)').matches ? 8 : 6);
    const stockMap = new Map();

    const money = (v) => Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const methodLabel = (method) => ({
        cash: 'Cash',
        qris: 'QRIS',
        debit: 'Debit',
        transfer: 'Transfer',
        e_wallet: 'E-Wallet',
        mixed: 'Split (Mixed)',
    }[String(method || '').toLowerCase()] || String(method || '-'));
    const parseRupiahInput = (v) => {
        const digits = String(v || '').replace(/[^\d]/g, '');
        if (!digits) return 0;
        return Number(digits);
    };
    const formatRupiahInput = (v) => money(parseRupiahInput(v));
    const formatRupiahWithCaret = (el) => {
        if (!el) return;
        const raw = String(el.value || '');
        const caret = Number(el.selectionStart || 0);
        const left = raw.slice(0, caret);
        const leftDigits = (left.match(/\d/g) || []).length;
        const numeric = parseRupiahInput(raw);
        const formatted = numeric > 0 ? formatRupiahInput(numeric) : '';
        el.value = formatted;
        if (!formatted) return;

        let digitCount = 0;
        let nextPos = formatted.length;
        for (let i = 0; i < formatted.length; i += 1) {
            if (/\d/.test(formatted[i])) {
                digitCount += 1;
                if (digitCount >= leftDigits) {
                    nextPos = i + 1;
                    break;
                }
            }
        }
        try {
            el.setSelectionRange(nextPos, nextPos);
        } catch (_) {}
    };
    const triggerHaptic = (type = 'ok') => {
        try {
            if (!navigator.vibrate) return;
            if (type === 'error') navigator.vibrate([24, 40, 24]);
            else navigator.vibrate(18);
        } catch (_) {}
    };
    const setFieldError = (el) => {
        if (!el) return;
        el.classList.add('error');
        if (typeof el.scrollIntoView === 'function') {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        try { el.focus({ preventScroll: true }); } catch (_) {}
        setTimeout(() => el.classList.remove('error'), 1800);
    };
    const failCheckout = (message, fieldEl = null) => {
        if (message) alert(message);
        triggerHaptic('error');
        setFieldError(fieldEl);
    };
    const getPayLabelByStatus = () => (String(statusField?.value || 'paid') === 'pending' ? 'Simpan Pending' : 'Bayar & Simpan');
    const syncPayLabelByStatus = () => {
        const label = getPayLabelByStatus();
        if (payBtnLabel && !isSubmitting) payBtnLabel.textContent = label;
        if (payBtnMobileLabel && !isSubmitting) payBtnMobileLabel.textContent = label;
    };
    const applySubmitState = (busy) => {
        isSubmitting = Boolean(busy);
        const label = busy ? (String(statusField?.value || 'paid') === 'pending' ? 'Menyimpan Pending...' : 'Menyimpan...') : getPayLabelByStatus();
        if (payBtn) {
            payBtn.disabled = busy;
            payBtn.classList.toggle('busy', busy);
            if (payBtnLabel) payBtnLabel.textContent = label;
        }
        if (payBtnMobile) {
            payBtnMobile.disabled = busy;
            payBtnMobile.classList.toggle('busy', busy);
            if (payBtnMobileLabel) payBtnMobileLabel.textContent = label;
        }
        if (clearBtn) clearBtn.disabled = busy;
        if (clearBtnMobile) clearBtnMobile.disabled = busy;
        if (!busy) updateSplitSummary();
    };
    const savePrefs = () => {
        try {
            localStorage.setItem(PREF_KEY, JSON.stringify({
                payment_method: String(paymentMethod?.value || 'cash'),
                status: String(statusField?.value || 'paid'),
                split_enabled: Boolean(splitEnabled),
                debt_mode: String(debtModeField?.value || 'normal'),
                scan_only: Boolean(scanOnlyMode),
                density_mode: String(densityMode || 'comfort'),
                shift_mini_visible: Boolean(shiftMiniVisible),
            }));
        } catch (_) {}
    };
    const loadPrefs = () => {
        try {
            const raw = localStorage.getItem(PREF_KEY);
            if (!raw) return;
            const pref = JSON.parse(raw);
            if (statusField && (pref.status === 'paid' || pref.status === 'pending')) statusField.value = pref.status;
            if (debtModeField && ['normal', 'partial', 'merge'].includes(String(pref.debt_mode || ''))) debtModeField.value = pref.debt_mode;
            if (paymentMethod && ['cash', 'qris', 'debit', 'transfer', 'e_wallet'].includes(String(pref.payment_method || ''))) paymentMethod.value = pref.payment_method;
            setSplitMode(Boolean(pref.split_enabled));
            scanOnlyMode = Boolean(pref.scan_only);
            densityMode = (String(pref.density_mode || 'comfort') === 'compact') ? 'compact' : 'comfort';
            shiftMiniVisible = Boolean(pref.shift_mini_visible);
        } catch (_) {}
    };
    const syncDensityUI = () => {
        const isTabletRange = window.matchMedia('(min-width:768px) and (max-width:1366px)').matches;
        if (!localStorage.getItem(PREF_KEY) && isTabletRange && densityMode !== 'compact') {
            densityMode = 'compact';
        }
        document.body.classList.toggle('k-density-compact', densityMode === 'compact');
        if (!densityToggle) return;
        const isCompact = densityMode === 'compact';
        densityToggle.classList.toggle('active', isCompact);
        densityToggle.textContent = `Density: ${isCompact ? 'Compact' : 'Comfort'}`;
        densityToggle.setAttribute('aria-pressed', isCompact ? 'true' : 'false');
    };
    const syncShiftMiniUI = () => {
        if (shiftMiniWrap) shiftMiniWrap.classList.toggle('hide', !shiftMiniVisible);
        if (shiftMiniToggle) {
            shiftMiniToggle.setAttribute('aria-expanded', shiftMiniVisible ? 'true' : 'false');
        }
        if (shiftMiniChevron) shiftMiniChevron.textContent = shiftMiniVisible ? '-' : '+';
    };
    const readOfflineQueue = () => {
        try {
            const raw = localStorage.getItem(OFFLINE_QUEUE_KEY);
            const rows = raw ? JSON.parse(raw) : [];
            return Array.isArray(rows) ? rows : [];
        } catch (_) {
            return [];
        }
    };
    const writeOfflineQueue = (rows) => {
        try {
            localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(Array.isArray(rows) ? rows : []));
        } catch (_) {}
        updateOfflineQueueUI();
    };
    const updateOfflineQueueUI = () => {
        const count = readOfflineQueue().length;
        if (offlineQueueCount) offlineQueueCount.textContent = String(count);
        if (offlineQueuePill) offlineQueuePill.style.display = count > 0 ? 'inline-flex' : 'none';
    };
    const saveSuccessSnapshot = (total, method) => {
        try {
            localStorage.setItem(SUCCESS_SHEET_KEY, JSON.stringify({
                total: Number(total || 0),
                method: String(method || ''),
                at: Date.now(),
            }));
        } catch (_) {}
    };
    const openSuccessSheet = (data) => {
        if (!successSheet) return;
        const total = Number(data?.total || 0);
        const method = methodLabel(data?.method || '-');
        if (successTotal) successTotal.textContent = `Rp ${money(total)}`;
        if (successMethod) successMethod.textContent = method;
        successSheet.classList.add('show');
        successSheet.setAttribute('aria-hidden', 'false');
    };
    const closeSuccessSheet = () => {
        if (!successSheet) return;
        successSheet.classList.remove('show');
        successSheet.setAttribute('aria-hidden', 'true');
    };
    const restoreSuccessSheet = () => {
        try {
            const raw = localStorage.getItem(SUCCESS_SHEET_KEY);
            if (!raw) return;
            localStorage.removeItem(SUCCESS_SHEET_KEY);
            const parsed = JSON.parse(raw);
            if (!parsed || !parsed.at) return;
            if (Date.now() - Number(parsed.at) > 60000) return;
            openSuccessSheet(parsed);
        } catch (_) {}
    };
    const buildCheckoutPayload = () => {
        const payload = new URLSearchParams();
        payload.set('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        payload.set('items_json', itemsJson?.value || '[]');
        payload.set('customer_id', customerId?.value || '');
        payload.set('customer_name', hiddenCustomerName?.value || '');
        payload.set('status', hiddenStatus?.value || 'paid');
        payload.set('debt_mode', hiddenDebtMode?.value || 'normal');
        payload.set('paid_amount', paidAmount?.value || '0');
        payload.set('payment_method', hiddenPaymentMethod?.value || 'cash');
        payload.set('payment_method_single', hiddenPaymentMethodSingle?.value || 'cash');
        payload.set('checkout_token', checkoutToken?.value || token());
        payload.set('qris_reference_id', hiddenQrisRef?.value || '');
        payload.set('qris_issuer', 'manual');
        payload.set('split_payments_json', splitPaymentsJson?.value || '');
        return payload.toString();
    };
    const queueOfflineCheckout = () => {
        const body = buildCheckoutPayload();
        const queue = readOfflineQueue();
        queue.push({
            body,
            created_at: Date.now(),
            token: checkoutToken?.value || '',
            tries: 0,
        });
        writeOfflineQueue(queue);
    };
    const processOfflineQueue = async () => {
        if (syncingOfflineQueue || !navigator.onLine) return;
        const queue = readOfflineQueue();
        if (!queue.length) return;
        syncingOfflineQueue = true;
        let rows = [...queue];
        const initialCount = rows.length;
        let sentCount = 0;
        try {
            for (let i = 0; i < rows.length; i += 1) {
                const row = rows[i];
                const res = await fetch(`{{ route('pos.checkout') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html,application/xhtml+xml',
                    },
                    body: row.body,
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    rows[i].tries = Number(rows[i].tries || 0) + 1;
                    break;
                }
                rows.shift();
                sentCount += 1;
                i -= 1;
                writeOfflineQueue(rows);
            }
        } catch (_) {
            // network still not stable, keep queue as is
        } finally {
            writeOfflineQueue(rows);
            if (sentCount > 0) {
                const remaining = rows.length;
                if (remaining === 0) {
                    showToast(`Sync selesai: ${sentCount}/${initialCount} transaksi terkirim.`);
                } else {
                    showToast(`Sync sebagian: ${sentCount} terkirim, sisa ${remaining}.`);
                }
            }
            syncingOfflineQueue = false;
        }
    };
    const token = () => `POSN-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
    const syncScanOnlyUI = () => {
        if (!scanOnlyToggle) return;
        scanOnlyToggle.classList.toggle('active', scanOnlyMode);
        scanOnlyToggle.textContent = `Scan-only: ${scanOnlyMode ? 'ON' : 'OFF'}`;
        scanOnlyToggle.setAttribute('aria-pressed', scanOnlyMode ? 'true' : 'false');
        if (categorySelect) categorySelect.style.display = scanOnlyMode ? 'none' : '';
        if (stateHint) stateHint.textContent = scanOnlyMode ? 'Mode scan-only aktif. Scan barcode untuk tambah cepat.' : stateHint.textContent;
    };
    const supportsBarcodeDetector = () => typeof window !== 'undefined' && 'BarcodeDetector' in window;
    const closeCameraScanner = () => {
        if (scanLoopTimer) {
            clearTimeout(scanLoopTimer);
            scanLoopTimer = null;
        }
        if (scanStream) {
            scanStream.getTracks().forEach((t) => t.stop());
            scanStream = null;
        }
        if (scanVideo) {
            scanVideo.pause();
            scanVideo.srcObject = null;
        }
        if (cameraScanModal) {
            cameraScanModal.classList.remove('show');
            cameraScanModal.setAttribute('aria-hidden', 'true');
        }
        scanTorchOn = false;
        if (scanTorchBtn) scanTorchBtn.textContent = 'Flash OFF';
        if (lastActiveEl && typeof lastActiveEl.focus === 'function') {
            try { lastActiveEl.focus(); } catch (_) {}
        }
    };
    const findProductByCode = (code) => {
        const q = String(code || '').trim().toLowerCase();
        if (!q) return null;
        return (products || []).find((p) => {
            const barcode = String(p?.barcode || '').trim().toLowerCase();
            const sku = String(p?.sku || '').trim().toLowerCase();
            return barcode === q || sku === q;
        }) || null;
    };
    const processDetectedCode = async (rawCode) => {
        const code = String(rawCode || '').trim();
        if (code.length < 3) return;
        const now = Date.now();
        if (scanLastCode === code && (now - scanLastAt) < 450) return;
        scanLastCode = code;
        scanLastAt = now;
        const local = findProductByCode(code);
        if (local) {
            addToCart(local);
            if (scanNote) scanNote.textContent = `Terdeteksi: ${code} -> ${local.name}`;
            return;
        }
        if (searchInput) searchInput.value = code;
        await fetchProducts();
        const afterFetch = findProductByCode(code);
        if (afterFetch) {
            addToCart(afterFetch);
            if (scanNote) scanNote.textContent = `Terdeteksi: ${code} -> ${afterFetch.name}`;
        } else if (scanNote) {
            scanNote.textContent = `Barcode ${code} tidak ditemukan di produk aktif.`;
        }
    };
    const scanFrameLoop = async (detector) => {
        if (!scanVideo || !scanStream) return;
        try {
            const codes = await detector.detect(scanVideo);
            if (Array.isArray(codes) && codes[0]?.rawValue) {
                processDetectedCode(codes[0].rawValue);
            }
        } catch (_) {}
        scanLoopTimer = setTimeout(() => scanFrameLoop(detector), 120);
    };
    const openCameraScanner = async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            showToast('Kamera tidak didukung di browser ini.');
            return;
        }
        if (!supportsBarcodeDetector()) {
            if (cameraScanModal) {
                cameraScanModal.classList.add('show');
                cameraScanModal.setAttribute('aria-hidden', 'false');
            }
            if (scanNote) scanNote.textContent = 'Browser belum mendukung scan realtime. Pakai input kode manual di bawah.';
            if (scanVideo) scanVideo.style.display = 'none';
            if (scanManualCode) {
                scanManualCode.value = '';
                try { scanManualCode.focus(); } catch (_) {}
            }
            return;
        } else if (scanVideo) {
            scanVideo.style.display = '';
        }
        try {
            lastActiveEl = document.activeElement;
            closeCameraScanner();
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });
            scanStream = stream;
            if (scanVideo) {
                scanVideo.srcObject = stream;
                await scanVideo.play();
            }
            if (cameraScanModal) {
                cameraScanModal.classList.add('show');
                cameraScanModal.setAttribute('aria-hidden', 'false');
            }
            if (scanNote) scanNote.textContent = 'Arahkan kamera ke barcode. Produk akan otomatis ditambahkan.';
            if (scanManualCode) scanManualCode.value = '';
            const detector = new window.BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'itf', 'qr_code'],
            });
            scanFrameLoop(detector);
        } catch (err) {
            closeCameraScanner();
            showToast(`Gagal buka kamera${err?.message ? `: ${err.message}` : '.'}`);
        }
    };
    const submitManualScanCode = async () => {
        const code = String(scanManualCode?.value || '').trim();
        if (code.length < 3) {
            showToast('Minimal 3 karakter kode.');
            return;
        }
        await processDetectedCode(code);
        if (scanManualCode) {
            scanManualCode.value = '';
            try { scanManualCode.focus(); } catch (_) {}
        }
    };
    const toggleTorch = async () => {
        try {
            if (!scanStream) return;
            const track = scanStream.getVideoTracks()[0];
            if (!track) return;
            const caps = track.getCapabilities ? track.getCapabilities() : {};
            if (!caps.torch) {
                showToast('Flash tidak didukung di perangkat ini.');
                return;
            }
            scanTorchOn = !scanTorchOn;
            await track.applyConstraints({ advanced: [{ torch: scanTorchOn }] });
            if (scanTorchBtn) scanTorchBtn.textContent = scanTorchOn ? 'Flash ON' : 'Flash OFF';
        } catch (_) {
            showToast('Gagal mengatur flash kamera.');
        }
    };
    const normalizePath = (v) => String(v || '').replace(/^\/+/, '');
    const toStockNumber = (v) => {
        const n = Number(v);
        return Number.isFinite(n) ? Math.max(0, n) : null;
    };
    const syncStockMap = (rows) => {
        if (!Array.isArray(rows)) return;
        rows.forEach((p) => {
            const id = Number(p?.id);
            if (!Number.isFinite(id) || id <= 0) return;
            const stock = toStockNumber(p?.stock ?? p?.current_stock ?? p?.quantity ?? p?.qty);
            if (stock !== null) stockMap.set(id, stock);
        });
    };
    const getItemStock = (id) => {
        if (!stockMap.has(Number(id))) return null;
        return stockMap.get(Number(id));
    };
    const saveCartDraft = () => {
        try {
            localStorage.setItem(CART_DRAFT_KEY, JSON.stringify(cart));
        } catch (_) {}
    };
    const loadCartDraft = () => {
        try {
            const raw = localStorage.getItem(CART_DRAFT_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return;
            cart = parsed
                .map((x) => ({
                    id: Number(x?.id),
                    name: String(x?.name || ''),
                    image: String(x?.image || ''),
                    price: Number(x?.price || 0),
                    qty: Math.max(1, Number(x?.qty || 1)),
                }))
                .filter((x) => Number.isFinite(x.id) && x.id > 0 && x.name);
        } catch (_) {}
    };
    const clearCartDraft = () => {
        try {
            localStorage.removeItem(CART_DRAFT_KEY);
        } catch (_) {}
    };
    checkoutToken.value = token();

    const renderProducts = () => {
        syncStockMap(products);
        if (emptyState) emptyState.classList.remove('show');
        productGrid.innerHTML = '';
        if (loading) {
            for (let i = 0; i < skeletonCount(); i += 1) {
                const sk = document.createElement('div');
                sk.className = 'k-skeleton';
                sk.innerHTML = `
                    <div class="k-shimmer" style="aspect-ratio:1/1"></div>
                    <div class="k-shimmer" style="height:14px;width:86%"></div>
                    <div class="k-shimmer" style="height:14px;width:56%"></div>
                `;
                productGrid.appendChild(sk);
            }
            stateHint.textContent = 'Mengambil data...';
            return;
        }
        if (!products.length) {
            stateHint.textContent = '0 produk tampil';
            if (emptyState) emptyState.classList.add('show');
            return;
        }
        products.forEach((p) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'k-card';
            card.innerHTML = `
                <div class="k-thumb-wrap">
                    <img class="k-thumb" src="${p.image ? `${storageBaseUrl}/${normalizePath(p.image)}` : fallbackImageUrl}" alt="${String(p.name || '')}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${fallbackImageUrl}'">
                </div>
                <div>
                    <p class="k-name">${String(p.name || '')}</p>
                    <div class="k-foot">
                        <div class="k-price">Rp ${money(p.selling_price)}</div>
                    </div>
                </div>
            `;
            card.addEventListener('click', () => guardedAddToCart(p, card));
            productGrid.appendChild(card);
        });
        stateHint.textContent = `${products.length} produk tampil`;
    };

    const renderCart = () => {
        cartList.innerHTML = '';
        const isEmptyCart = cart.length === 0;
        if (cart.length === 0) {
            cartEmpty.style.display = '';
        } else {
            cartEmpty.style.display = 'none';
        }
        if (cartAside) cartAside.classList.toggle('k-empty-cart', isEmptyCart);
        if (clearBtn) clearBtn.style.display = isEmptyCart ? 'none' : '';
        if (clearBtnMobile) clearBtnMobile.style.display = isEmptyCart ? 'none' : '';

        cart.forEach((row) => {
            const stock = getItemStock(row.id);
            const stockText = stock === null ? 'Stok: -' : `Stok: ${stock}`;
            const stockWarn = stock !== null && Number(row.qty) > Number(stock);
            const el = document.createElement('div');
            el.className = 'k-row';
            el.setAttribute('data-row-id', String(row.id));
            if (Number(lastAddedProductId) === Number(row.id) && (Date.now() - lastAddedAt) < 800) {
                el.classList.add('flash');
            }
            el.innerHTML = `
                <div class="k-row-top">
                    <img class="k-row-thumb" src="${row.image ? `${storageBaseUrl}/${normalizePath(row.image)}` : fallbackImageUrl}" alt="${row.name}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${fallbackImageUrl}'">
                    <div>
                        <div class="k-row-name">${row.name}</div>
                        <span class="k-stock-pill ${stockWarn ? 'warn' : ''}">${stockText}</span>
                    </div>
                </div>
                <div class="k-row-foot">
                    <div class="k-step">
                        <button type="button" data-act="min">-</button>
                        <input type="number" min="1" step="1" class="k-step-input" data-act="qty" data-role="qty" value="${row.qty}">
                        <button type="button" data-act="plus">+</button>
                    </div>
                    <strong>Rp ${money(row.qty * row.price)}</strong>
                    <button type="button" class="k-del" data-act="del">Hapus</button>
                </div>
            `;
            el.querySelector('[data-act="min"]').addEventListener('click', () => updateQty(row.id, -1));
            el.querySelector('[data-act="plus"]').addEventListener('click', () => updateQty(row.id, +1));
            el.querySelector('[data-act="del"]').addEventListener('click', () => removeItem(row.id));
            const qtyInput = el.querySelector('[data-act="qty"]');
            if (qtyInput) {
                qtyInput.addEventListener('change', () => setQtyDirect(row.id, qtyInput.value));
                qtyInput.addEventListener('blur', () => setQtyDirect(row.id, qtyInput.value));
            }
            cartList.appendChild(el);
        });

        const total = cart.reduce((s, r) => s + (r.qty * r.price), 0);
        const qty = cart.reduce((s, r) => s + r.qty, 0);
        if (tabletMiniSummary) tabletMiniSummary.innerHTML = `<span>Item: ${qty}</span><strong>Rp ${money(total)}</strong>`;
        itemCount.textContent = `Item: ${qty}`;
        totalText.textContent = money(total);
        if (cartHeadCount) cartHeadCount.textContent = String(qty);
        if (cartHeadTotal) cartHeadTotal.textContent = money(total);
        if (cartFabCount) cartFabCount.textContent = String(qty);
        if (cartFabTotal) cartFabTotal.textContent = `Rp ${money(total)}`;
        if (cartFab) cartFab.classList.toggle('has-total', qty > 0);
        itemsJson.value = JSON.stringify(cart.map((r) => ({ product_id: r.id, quantity: r.qty, discount_amount: 0 })));
        const status = String(statusField?.value || 'paid');
        paidAmount.value = status === 'pending' ? '0' : String(total);
        saveCartDraft();
        updateSplitSummary();
        if (focusLastAddedQty && Number(lastAddedProductId) > 0) {
            const qtyInput = cartList.querySelector(`.k-row[data-row-id="${lastAddedProductId}"] [data-role="qty"]`);
            if (qtyInput) {
                try {
                    qtyInput.focus({ preventScroll: true });
                    qtyInput.select();
                } catch (_) {}
            }
            focusLastAddedQty = false;
        }
    };

    const hideKeyboard = () => {
        try {
            const el = document.activeElement;
            if (el && typeof el.blur === 'function') el.blur();
        } catch (_) {}
    };
    const showToast = (text, withUndo = false, type = 'ok') => {
        if (!kToast || !kToastText || !text) return;
        kToastText.textContent = text;
        kToast.classList.toggle('error', type === 'error');
        if (kToastUndo) kToastUndo.style.display = withUndo ? '' : 'none';
        kToast.classList.add('show');
        if (toastTimer) clearTimeout(toastTimer);
        if (undoTimer) clearTimeout(undoTimer);
        const toastDuration = withUndo ? 3000 : 1500;
        toastTimer = setTimeout(() => {
            kToast.classList.remove('show');
            if (kToastUndo) kToastUndo.style.display = 'none';
            undoSnapshot = null;
        }, toastDuration);
        if (withUndo) {
            undoTimer = setTimeout(() => {
                undoSnapshot = null;
                if (kToastUndo) kToastUndo.style.display = 'none';
            }, 3000);
        }
    };
    const getCartTotal = () => cart.reduce((s, r) => s + (r.qty * r.price), 0);
    const updateSplitSummary = () => {
        if (!splitTotalText || !splitDiffText) return;
        const total = getCartTotal();
        const a = parseRupiahInput(splitAmountA?.value || 0);
        const b = parseRupiahInput(splitAmountB?.value || 0);
        const sum = a + b;
        const diff = total - sum;
        splitTotalText.textContent = `Rp ${money(sum)}`;
        const isExact = Math.abs(diff) <= 0.01;
        splitDiffText.textContent = isExact ? 'Pas' : `Rp ${money(Math.abs(diff))}${diff > 0 ? ' kurang' : ' lebih'}`;
        if (splitDiffText) {
            splitDiffText.classList.toggle('ok', isExact);
            splitDiffText.classList.toggle('bad', !isExact);
        }
        const disabledBySplit = splitEnabled && !isExact;
        if (payBtn) payBtn.disabled = disabledBySplit || isSubmitting;
        if (payBtnMobile) payBtnMobile.disabled = disabledBySplit || isSubmitting;
    };
    const autoFillSplitB = () => {
        if (!splitEnabled || !splitAmountA || !splitAmountB || splitAutoFillLock) return;
        const total = getCartTotal();
        const a = parseRupiahInput(splitAmountA.value || 0);
        const nextB = Math.max(total - a, 0);
        splitAutoFillLock = true;
        splitAmountB.value = formatRupiahInput(nextB);
        splitAutoFillLock = false;
        updateSplitSummary();
    };
    const setSplitMode = (enabled) => {
        splitEnabled = Boolean(enabled);
        if (splitToggleBtn) {
            splitToggleBtn.classList.toggle('active', splitEnabled);
            splitToggleBtn.textContent = splitEnabled ? 'ON' : 'OFF';
            splitToggleBtn.setAttribute('aria-pressed', splitEnabled ? 'true' : 'false');
        }
        if (splitBox) splitBox.classList.toggle('show', splitEnabled);
        if (splitEnabled) setPaymentAdvOpen(true);
        if (!splitEnabled && splitPaymentsJson) splitPaymentsJson.value = '';
        if (splitEnabled && splitAmountA && splitAmountB) {
            const total = getCartTotal();
            if (!parseRupiahInput(splitAmountA.value) && !parseRupiahInput(splitAmountB.value)) {
                splitAmountA.value = formatRupiahInput(total);
                splitAmountB.value = '';
            }
        }
        if (!splitEnabled && qrisRef) {
            const isQrisSingle = (paymentMethod.value || 'cash') === 'qris';
            qrisRef.style.display = isQrisSingle ? '' : 'none';
        }
        updateSplitSummary();
    };
    const hideCustomerSuggestions = () => {
        if (!customerSuggestBox) return;
        customerSuggestBox.classList.remove('show');
        customerSuggestBox.innerHTML = '';
    };
    const renderCustomerDebtMini = (row) => {
        if (!customerDebtMini || !customerDebtMiniText) return;
        const debtCount = Number(row?.debt_count || 0);
        const debtTotal = Number(row?.debt_total || 0);
        const due = String(row?.nearest_due_date || '').trim();
        if (debtCount <= 0 || debtTotal <= 0) {
            customerDebtMini.classList.remove('show');
            customerDebtMiniText.textContent = '';
            currentDebtText = '';
            currentDebtData = null;
            if (debtFab) debtFab.classList.remove('show');
            return;
        }
        const dueText = due !== '' ? ` | Jatuh tempo terdekat: ${due}` : '';
        currentDebtText = `${debtCount} invoice | Total: Rp ${money(debtTotal)}${dueText}`;
        currentDebtData = {
            name: String(row?.name || ''),
            debtCount,
            debtTotal,
            due,
            debtItems: Array.isArray(row?.debt_items) ? row.debt_items : [],
        };
        customerDebtMiniText.textContent = currentDebtText;
        // sembunyikan inline agar area form tidak sempit, pakai overlay icon
        customerDebtMini.classList.remove('show');
        if (debtFab) debtFab.classList.add('show');
    };
    const selectCustomer = (row) => {
        selectedCustomer = row || null;
        if (customerName) customerName.value = String(row?.name || '');
        if (customerId) customerId.value = row?.id ? String(row.id) : '';
        renderCustomerDebtMini(row || null);
        hideCustomerSuggestions();
    };
    const renderCustomerSuggestions = (rows) => {
        if (!customerSuggestBox) return;
        latestCustomerSuggestions = Array.isArray(rows) ? rows : [];
        customerSuggestBox.innerHTML = '';
        if (!Array.isArray(rows) || rows.length === 0) {
            hideCustomerSuggestions();
            return;
        }
        rows.forEach((row) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            const phone = String(row.phone || '').trim();
            const email = String(row.email || '').trim();
            const meta = [phone, email].filter(Boolean).join(' | ');
            btn.textContent = meta ? `${row.name} - ${meta}` : `${row.name}`;
            btn.addEventListener('click', () => selectCustomer(row));
            customerSuggestBox.appendChild(btn);
        });
        customerSuggestBox.classList.add('show');
    };
    const fetchCustomerSuggestions = async () => {
        const q = String(customerName?.value || '').trim();
        if (q.length < 2) {
            hideCustomerSuggestions();
            return;
        }
        try {
            const url = `{{ route('pos.search-customers') }}?q=${encodeURIComponent(q)}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const payload = await res.json();
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            renderCustomerSuggestions(rows);
        } catch (_) {
            hideCustomerSuggestions();
        }
    };
    const syncCustomerField = () => {
        if (!customerName || !customerId) return;
        const nameNow = String(customerName.value || '').trim();
        if (!selectedCustomer || nameNow.toLowerCase() !== String(selectedCustomer.name || '').trim().toLowerCase()) {
            customerId.value = '';
            if (nameNow.length >= 2 && Array.isArray(latestCustomerSuggestions) && latestCustomerSuggestions.length > 0) {
                const exact = latestCustomerSuggestions.find((row) => {
                    const n = String(row?.name || '').trim().toLowerCase();
                    const p = String(row?.phone || '').trim().toLowerCase();
                    const e = String(row?.email || '').trim().toLowerCase();
                    const q = nameNow.toLowerCase();
                    return n === q || p === q || e === q;
                });
                if (exact) {
                    selectCustomer(exact);
                    return;
                }
            }
        }
        if (quickAddCustomerBtn) {
            const canQuickAdd = nameNow.length >= 2 && String(customerId.value || '') === '';
            quickAddCustomerBtn.style.display = canQuickAdd ? '' : 'none';
        }
        if (!selectedCustomer || String(customerId.value || '') === '') {
            renderCustomerDebtMini(null);
        }
    };
    const refreshShiftSummary = async () => {
        try {
            const res = await fetch(`{{ route('pos.shift-summary') }}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const payload = await res.json();
            const summary = payload?.summary || {};
            if (shiftTotalText) shiftTotalText.textContent = money(Number(summary.total_transactions || 0));
            if (shiftPaidText) shiftPaidText.textContent = money(Number(summary.paid_transactions || 0));
            if (shiftPendingText) shiftPendingText.textContent = money(Number(summary.pending_transactions || 0));
            if (shiftOmzetText) shiftOmzetText.textContent = `Rp ${money(Number(summary.omzet_paid || 0))}`;
        } catch (_) {}
    };
    const syncCheckoutMode = () => {
        const status = String(statusField?.value || 'paid');
        const isPending = status === 'pending';
        setCheckoutInfoOpen(isPending);
        if (debtModeField) debtModeField.style.display = isPending ? '' : 'none';
    };
    const setCheckoutInfoOpen = (open) => {
        if (!checkoutInfoAcc || !checkoutInfoToggle) return;
        checkoutInfoAcc.classList.toggle('open', Boolean(open));
        checkoutInfoToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (checkoutInfoChevron) checkoutInfoChevron.textContent = open ? '-' : '+';
    };
    const setPaymentAdvOpen = (open) => {
        if (!paymentAdvAcc || !paymentAdvToggle) return;
        paymentAdvAcc.classList.toggle('open', Boolean(open));
        paymentAdvToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (paymentAdvChevron) paymentAdvChevron.textContent = open ? '-' : '+';
    };
    const syncSplitMethodState = () => {
        if (!splitMethodA || !splitMethodB) return;
        const methodA = String(splitMethodA.value || '');
        const methodB = String(splitMethodB.value || '');
        if (methodA !== '' && methodA === methodB) {
            const fallback = ['cash', 'qris', 'debit', 'transfer', 'e_wallet'].find((m) => m !== methodA) || 'cash';
            splitMethodB.value = fallback;
            showToast('Metode split tidak boleh sama.', false, 'error');
        }
        const hasQrisSplit = splitEnabled && ((splitMethodA.value || '') === 'qris' || (splitMethodB.value || '') === 'qris');
        const isQrisSingle = (paymentMethod.value || 'cash') === 'qris';
        if (qrisRef) qrisRef.style.display = (hasQrisSplit || isQrisSingle) ? '' : 'none';
    };

    const triggerAddFeedback = () => {
        try {
            if (navigator.vibrate) navigator.vibrate(18);
        } catch (_) {}
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.03, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.08);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.08);
        } catch (_) {}
    };

    const addToCart = (p, cardEl = null) => {
        const wasEmpty = cart.length === 0;
        const id = Number(p.id);
        const liveStock = toStockNumber(p?.stock ?? p?.current_stock ?? p?.quantity ?? p?.qty);
        if (liveStock !== null) stockMap.set(id, liveStock);
        const idx = cart.findIndex((x) => Number(x.id) === Number(p.id));
        if (idx >= 0) {
            const nextQty = cart[idx].qty + 1;
            const stock = getItemStock(id);
            if (stock !== null && nextQty > stock) {
                showToast(`Stok ${p.name} tidak cukup (stok: ${stock}).`, false, 'error');
                return;
            }
            cart[idx].qty = nextQty;
            focusLastAddedQty = true;
        }
        else {
            cart.push({ id: Number(p.id), name: String(p.name || ''), image: String(p.image || ''), price: Number(p.selling_price || 0), qty: 1 });
            focusLastAddedQty = false;
        }
        lastAddedProductId = id;
        lastAddedAt = Date.now();
        const target = cart.find((x) => Number(x.id) === Number(p.id));
        renderCart();
        if (wasEmpty && isMobile()) {
            setCheckoutInfoOpen(false);
            setPaymentAdvOpen(false);
        }
        if (target) showToast(`${target.name} ditambahkan (x${target.qty})`);
        if (scanVideo) {
            scanVideo.classList.remove('flash-ok');
            void scanVideo.offsetWidth;
            scanVideo.classList.add('flash-ok');
            setTimeout(() => scanVideo.classList.remove('flash-ok'), 180);
        }
        triggerAddFeedback();
        if (cardEl) {
            cardEl.classList.remove('added');
            void cardEl.offsetWidth;
            cardEl.classList.add('added');
            animateFlyToCart(cardEl);
        }
        if (cartFab) {
            cartFab.style.transform = 'scale(1.06)';
            setTimeout(() => { cartFab.style.transform = ''; }, 120);
        }
    };
    const guardedAddToCart = (p, cardEl = null) => {
        const id = Number(p?.id || 0);
        if (id <= 0) {
            addToCart(p, cardEl);
            return;
        }
        const now = Date.now();
        const prev = Number(productTapGuard.get(id) || 0);
        if ((now - prev) < 200) return;
        productTapGuard.set(id, now);
        addToCart(p, cardEl);
    };

    const animateFlyToCart = (cardEl) => {
        try {
            const thumb = cardEl.querySelector('.k-thumb');
            if (!thumb) return;
            const sourceRect = thumb.getBoundingClientRect();
            const mobile = isMobile();
            let targetEl = null;
            if (mobile && cartFab) targetEl = cartFab;
            if (!mobile) targetEl = document.getElementById('cartAside') || cartFab;
            if (!targetEl) return;
            const targetRect = targetEl.getBoundingClientRect();

            const ghost = document.createElement('img');
            ghost.className = 'k-fly-ghost';
            ghost.src = thumb.getAttribute('src') || fallbackImageUrl;
            ghost.style.left = `${sourceRect.left + sourceRect.width / 2 - 28}px`;
            ghost.style.top = `${sourceRect.top + sourceRect.height / 2 - 28}px`;
            document.body.appendChild(ghost);

            requestAnimationFrame(() => {
                const tx = (targetRect.left + targetRect.width / 2) - (sourceRect.left + sourceRect.width / 2);
                const ty = (targetRect.top + targetRect.height / 2) - (sourceRect.top + sourceRect.height / 2);
                ghost.style.transform = `translate(${tx}px, ${ty}px) scale(.24)`;
                ghost.style.opacity = '0.1';
                ghost.style.filter = 'blur(1px)';
            });

            setTimeout(() => {
                ghost.remove();
            }, 620);
        } catch (_) {}
    };

    const updateQty = (id, delta) => {
        const row = cart.find((x) => Number(x.id) === Number(id));
        if (!row) return;
        const nextQty = Math.max(1, Number(row.qty) + delta);
        const stock = getItemStock(id);
        if (delta > 0 && stock !== null && nextQty > stock) {
            showToast(`Stok ${row.name} tidak cukup (stok: ${stock}).`, false, 'error');
            return;
        }
        row.qty = nextQty;
        renderCart();
    };
    const setQtyDirect = (id, rawValue) => {
        const row = cart.find((x) => Number(x.id) === Number(id));
        if (!row) return;
        const parsed = Number(String(rawValue || '').replace(/[^\d]/g, ''));
        const nextQty = Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
        const stock = getItemStock(id);
        if (stock !== null && nextQty > stock) {
            showToast(`Stok ${row.name} tidak cukup (stok: ${stock}).`, false, 'error');
            row.qty = Math.max(1, Math.min(Number(row.qty || 1), Number(stock)));
            renderCart();
            return;
        }
        row.qty = nextQty;
        renderCart();
    };

    const removeItem = (id) => {
        const before = cart.map((x) => ({ ...x }));
        cart = cart.filter((x) => Number(x.id) !== Number(id));
        renderCart();
        undoSnapshot = before;
        showToast('Item dihapus.', true);
    };
    const validateCartStock = async (opts = {}) => {
        const { adjust = false, silent = false } = opts;
        if (!Array.isArray(cart) || cart.length === 0) return { ok: true, issues: [] };
        try {
            const res = await fetch(`{{ route('pos.validate-stock') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    items: cart.map((r) => ({ product_id: Number(r.id), quantity: Number(r.qty) })),
                }),
            });
            const payload = await res.json().catch(() => ({}));
            if (!res.ok) {
                let message = 'Gagal sinkron stok.';
                if (res.status === 419 || res.status === 401) message = 'Sesi login habis. Refresh halaman lalu login ulang.';
                else if (res.status >= 500) message = 'Server error saat sinkron stok. Cek log server.';
                else if (typeof payload?.message === 'string' && payload.message.trim()) message = payload.message.trim();
                if (!silent) showToast(message);
                return { ok: false, issues: [{ message }] };
            }
            const stocks = Array.isArray(payload?.stocks) ? payload.stocks : [];
            stocks.forEach((row) => {
                const id = Number(row?.id || 0);
                if (id > 0) stockMap.set(id, Number(row?.stock || 0));
            });
            const issues = Array.isArray(payload?.issues) ? payload.issues : [];
            if (adjust && issues.length > 0) {
                let changed = false;
                const next = [];
                cart.forEach((row) => {
                    const issue = issues.find((x) => Number(x?.product_id) === Number(row.id));
                    if (!issue) {
                        next.push(row);
                        return;
                    }
                    const available = Number(issue?.stock ?? getItemStock(row.id) ?? 0);
                    if (available <= 0) {
                        changed = true;
                        return;
                    }
                    if (Number(row.qty) > available) {
                        changed = true;
                        next.push({ ...row, qty: available });
                        return;
                    }
                    next.push(row);
                });
                if (changed) {
                    cart = next;
                    renderCart();
                    if (!silent) showToast('Qty keranjang disesuaikan dengan stok terbaru.');
                } else {
                    renderCart();
                }
            } else {
                renderCart();
            }
            return { ok: res.ok && payload?.ok !== false, issues };
        } catch (err) {
            const isOffline = typeof navigator !== 'undefined' && navigator && navigator.onLine === false;
            const message = isOffline
                ? 'Internet terputus. Sinkron stok butuh koneksi.'
                : `Gagal sinkron stok${err?.message ? `: ${err.message}` : '.'}`;
            if (!silent) showToast(message);
            return { ok: false, issues: [{ message }] };
        }
    };

    const fetchProducts = async (opts = {}) => {
        const { reset = true } = opts;
        if (loadingMore) return;
        const q = searchInput.value || '';
        const cat = categorySelect.value || '0';
        if (reset) {
            productsPage = 1;
            productsHasMore = true;
            loading = true;
            renderProducts();
        } else {
            if (!productsHasMore) return;
            loadingMore = true;
            if (stateHint) stateHint.textContent = 'Memuat produk berikutnya...';
        }
        try {
            const nextPage = reset ? 1 : (productsPage + 1);
            const url = `{{ route('pos.search-products') }}?q=${encodeURIComponent(q)}&category_id=${encodeURIComponent(cat)}&page=${nextPage}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const payload = await res.json();
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            if (reset) {
                products = rows;
            } else {
                const seen = new Set(products.map((p) => Number(p?.id || 0)));
                rows.forEach((r) => {
                    const id = Number(r?.id || 0);
                    if (!seen.has(id)) products.push(r);
                });
            }
            productsPage = nextPage;
            productsHasMore = rows.length > 0;
            loading = false;
            loadingMore = false;
            renderProducts();
        } catch (_) {
            loading = false;
            loadingMore = false;
            if (reset) {
                products = [];
                renderProducts();
                stateHint.textContent = 'Gagal memuat produk.';
            } else if (stateHint) {
                stateHint.textContent = 'Gagal memuat produk lanjutan.';
            }
        }
    };

    const hideSuggestions = () => {
        if (!suggestBox) return;
        suggestBox.classList.remove('show');
        suggestBox.innerHTML = '';
    };

    const renderSuggestions = (rows) => {
        if (!suggestBox) return;
        suggestBox.innerHTML = '';
        if (!Array.isArray(rows) || rows.length === 0) {
            hideSuggestions();
            return;
        }
        rows.slice(0, 8).forEach((row) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(row.name || '');
            btn.addEventListener('click', () => {
                searchInput.value = String(row.name || '');
                hideSuggestions();
                fetchProducts();
            });
            suggestBox.appendChild(btn);
        });
        suggestBox.classList.add('show');
    };

    const fetchSuggestions = async () => {
        const q = String(searchInput.value || '').trim();
        if (q.length < 2) {
            hideSuggestions();
            return;
        }
        const cat = categorySelect.value || '0';
        try {
            const url = `{{ route('pos.search-products') }}?q=${encodeURIComponent(q)}&category_id=${encodeURIComponent(cat)}&page=1`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const payload = await res.json();
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            renderSuggestions(rows);
        } catch (_) {
            hideSuggestions();
        }
    };

    searchInput.addEventListener('input', () => {
        const q = String(searchInput.value || '').trim();
        if (q.length < 2) hideSuggestions();
        if (fetchTimer) clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchProducts, 250);
        if (suggestTimer) clearTimeout(suggestTimer);
        suggestTimer = setTimeout(fetchSuggestions, 180);
    });
    searchInput.addEventListener('blur', () => {
        setTimeout(hideSuggestions, 120);
    });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hideSuggestions();
    });
    categorySelect.addEventListener('change', () => fetchProducts({ reset: true }));
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            searchInput.value = '';
            categorySelect.value = '0';
            fetchProducts({ reset: true });
            hideSuggestions();
        });
    }
    document.addEventListener('click', (e) => {
        if (!suggestBox) return;
        if (!e.target.closest('.k-search-wrap')) hideSuggestions();
    });
    const maybeLoadMoreProducts = () => {
        if (loading || loadingMore || !productsHasMore) return;
        let remain = 99999;
        if (productsPanel && productsPanel.scrollHeight > productsPanel.clientHeight + 20) {
            remain = productsPanel.scrollHeight - (productsPanel.scrollTop + productsPanel.clientHeight);
        } else {
            remain = document.documentElement.scrollHeight - (window.scrollY + window.innerHeight);
        }
        if (remain < 420) fetchProducts({ reset: false });
    };
    window.addEventListener('scroll', hideSuggestions, { passive: true });
    window.addEventListener('scroll', maybeLoadMoreProducts, { passive: true });
    if (productsPanel) {
        productsPanel.addEventListener('scroll', hideSuggestions, { passive: true });
        productsPanel.addEventListener('scroll', maybeLoadMoreProducts, { passive: true });
    }
    document.addEventListener('keydown', (e) => {
        if (!isMobile()) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;
        const active = document.activeElement;
        const tag = active ? String(active.tagName || '').toLowerCase() : '';
        const isTyping = tag === 'input' || tag === 'textarea' || active?.isContentEditable;
        if (isTyping && active !== searchInput) return;
        if (e.key === 'Enter') {
            if (scannerBuffer.length >= 4) {
                searchInput.focus();
                searchInput.value = scannerBuffer;
                hideSuggestions();
                if (scanOnlyMode) {
                    const q = String(scannerBuffer).trim().toLowerCase();
                    const exact = (products || []).find((p) => {
                        const barcode = String(p?.barcode || '').trim().toLowerCase();
                        const sku = String(p?.sku || '').trim().toLowerCase();
                        return q && (barcode === q || sku === q);
                    });
                    if (exact) {
                        addToCart(exact);
                        searchInput.value = '';
                    } else {
                        fetchProducts();
                    }
                } else {
                    fetchProducts();
                }
            }
            scannerBuffer = '';
            return;
        }
        if (e.key.length !== 1) return;
        const now = Date.now();
        if (now - scannerLastTs > 120) scannerBuffer = '';
        scannerLastTs = now;
        scannerBuffer += e.key;
        if (scannerTimer) clearTimeout(scannerTimer);
        scannerTimer = setTimeout(() => { scannerBuffer = ''; }, 160);
        if (scannerBuffer.length >= 4) {
            searchInput.focus();
        }
    });
    const clearCart = () => {
        const before = cart.map((x) => ({ ...x }));
        cart = [];
        renderCart();
        undoSnapshot = before;
        showToast('Keranjang dikosongkan.', true);
    };
    if (kToastUndo) {
        kToastUndo.addEventListener('click', () => {
            if (!undoSnapshot) return;
            cart = undoSnapshot.map((x) => ({ ...x }));
            undoSnapshot = null;
            renderCart();
            showToast('Keranjang dikembalikan.');
        });
    }
    const openClearConfirm = () => {
        if (!clearConfirmModal) return;
        if (cart.length === 0) return;
        clearConfirmModal.classList.add('show');
        clearConfirmModal.setAttribute('aria-hidden', 'false');
    };
    const closeClearConfirm = () => {
        if (!clearConfirmModal) return;
        clearConfirmModal.classList.remove('show');
        clearConfirmModal.setAttribute('aria-hidden', 'true');
    };
    clearBtn.addEventListener('click', openClearConfirm);
    if (clearBtnMobile) clearBtnMobile.addEventListener('click', openClearConfirm);
    if (clearConfirmBackdrop) clearConfirmBackdrop.addEventListener('click', closeClearConfirm);
    if (clearConfirmCancel) clearConfirmCancel.addEventListener('click', closeClearConfirm);
    if (clearConfirmOk) {
        clearConfirmOk.addEventListener('click', () => {
            clearCart();
            closeClearConfirm();
        });
    }
    const isMobile = () => window.matchMedia('(max-width: 767px)').matches;
    const openCart = () => {
        if (!isMobile()) return;
        cartAside.style.transform = '';
        cartAside.classList.add('show');
        cartOverlay.classList.add('show');
        if (cartFab) cartFab.style.display = 'none';
        document.body.classList.add('k-cart-open');
    };
    const closeCart = () => {
        if (!isMobile()) return;
        cartAside.style.transform = '';
        cartAside.classList.remove('show');
        cartOverlay.classList.remove('show');
        if (cartFab) cartFab.style.display = 'grid';
        document.body.classList.remove('k-cart-open');
    };
    if (cartFab) cartFab.addEventListener('click', openCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);
    if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeCart);
    if (cartCloseBtnMobile) cartCloseBtnMobile.addEventListener('click', closeCart);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeCart();
        if (e.key === 'Escape') closeClearConfirm();
        if (e.key === 'Escape') closeCameraScanner();
        if (e.key === 'Escape') closeSuccessSheet();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) closeCameraScanner();
    });

    const onCartTouchStart = (e) => {
        if (!isMobile() || !cartAside.classList.contains('show')) return;
        if (!e.touches || e.touches.length !== 1) return;
        const startTarget = e.target;
        if (cartHead && !cartHead.contains(startTarget)) return;
        if (cartAside.scrollTop > 2) return;
        cartTouchStartY = e.touches[0].clientY;
        cartTouchDeltaY = 0;
        cartDragging = true;
        cartAside.style.transition = 'none';
    };
    const onCartTouchMove = (e) => {
        if (!cartDragging || !e.touches || e.touches.length !== 1) return;
        const currentY = e.touches[0].clientY;
        const delta = currentY - cartTouchStartY;
        cartTouchDeltaY = Math.max(0, delta);
        if (cartTouchDeltaY > 0) {
            cartAside.style.transform = `translateY(${Math.min(cartTouchDeltaY, 220)}px)`;
        }
    };
    const onCartTouchEnd = () => {
        if (!cartDragging) return;
        cartDragging = false;
        cartAside.style.transition = '';
        if (cartTouchDeltaY > 140) {
            closeCart();
        } else {
            cartAside.style.transform = '';
        }
        cartTouchDeltaY = 0;
    };
    if (cartAside) {
        cartAside.addEventListener('touchstart', onCartTouchStart, { passive: true });
        cartAside.addEventListener('touchmove', onCartTouchMove, { passive: true });
        cartAside.addEventListener('touchend', onCartTouchEnd, { passive: true });
        cartAside.addEventListener('touchcancel', onCartTouchEnd, { passive: true });
    }
    if (customerName) {
        customerName.addEventListener('input', () => {
            syncCustomerField();
            if (customerSuggestTimer) clearTimeout(customerSuggestTimer);
            customerSuggestTimer = setTimeout(fetchCustomerSuggestions, 180);
        });
        customerName.addEventListener('focus', () => {
            if (String(customerName.value || '').trim().length >= 2) fetchCustomerSuggestions();
        });
        customerName.addEventListener('blur', () => {
            setTimeout(hideCustomerSuggestions, 120);
        });
        customerName.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideCustomerSuggestions();
        });
    }
    if (checkoutInfoToggle) {
        checkoutInfoToggle.addEventListener('click', () => {
            const isOpen = checkoutInfoAcc?.classList.contains('open');
            setCheckoutInfoOpen(!isOpen);
        });
    }
    if (paymentAdvToggle) {
        paymentAdvToggle.addEventListener('click', () => {
            const isOpen = paymentAdvAcc?.classList.contains('open');
            setPaymentAdvOpen(!isOpen);
        });
    }
    if (statusField) {
        statusField.addEventListener('change', () => {
            syncCheckoutMode();
            renderCart();
            syncPayLabelByStatus();
            savePrefs();
        });
    }
    if (debtModeField) {
        debtModeField.addEventListener('change', savePrefs);
    }
    paymentMethod.addEventListener('change', () => {
        const isQris = paymentMethod.value === 'qris';
        const hasQrisSplit = splitEnabled && ((splitMethodA?.value || '') === 'qris' || (splitMethodB?.value || '') === 'qris');
        qrisRef.style.display = (isQris || hasQrisSplit) ? '' : 'none';
        hideKeyboard();
        savePrefs();
    });
    if (paymentQuick) {
        paymentQuick.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-method]');
            if (!btn) return;
            const method = String(btn.getAttribute('data-method') || 'cash');
            paymentMethod.value = method;
            paymentQuick.querySelectorAll('.k-pay-btn').forEach((x) => x.classList.remove('active'));
            btn.classList.add('active');
            const isQris = method === 'qris';
            const hasQrisSplit = splitEnabled && ((splitMethodA?.value || '') === 'qris' || (splitMethodB?.value || '') === 'qris');
            qrisRef.style.display = (isQris || hasQrisSplit) ? '' : 'none';
            hideKeyboard();
            savePrefs();
        });
    }
    if (splitToggleBtn) {
        splitToggleBtn.addEventListener('click', () => {
            setSplitMode(!splitEnabled);
            savePrefs();
        });
    }
    [splitAmountA, splitAmountB, splitMethodA, splitMethodB].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', updateSplitSummary);
        el.addEventListener('change', updateSplitSummary);
    });
    [splitAmountA, splitAmountB].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', updateSplitSummary);
        el.addEventListener('blur', () => {
            const raw = parseRupiahInput(el.value);
            el.value = raw > 0 ? formatRupiahInput(raw) : '';
            updateSplitSummary();
        });
    });
    if (splitMethodA) {
        splitMethodA.addEventListener('change', () => {
            syncSplitMethodState();
            updateSplitSummary();
            savePrefs();
        });
    }
    if (splitMethodB) {
        splitMethodB.addEventListener('change', () => {
            syncSplitMethodState();
            updateSplitSummary();
            savePrefs();
        });
    }
    if (quickAddCustomerBtn) {
        quickAddCustomerBtn.addEventListener('click', () => {
            const nameNow = String(customerName?.value || '').trim();
            if (nameNow.length < 2) return;
            selectedCustomer = null;
            if (customerId) customerId.value = '';
            hideCustomerSuggestions();
            syncCustomerField();
            showToast(`Customer baru akan dibuat: ${nameNow}`);
        });
    }
    if (splitAmountA) {
        splitAmountA.addEventListener('input', autoFillSplitB);
        splitAmountA.addEventListener('change', autoFillSplitB);
    }
    if (shiftMiniToggle) {
        shiftMiniToggle.addEventListener('click', () => {
            shiftMiniVisible = !shiftMiniVisible;
            syncShiftMiniUI();
            savePrefs();
        });
    }
    if (scanOnlyToggle) {
        scanOnlyToggle.addEventListener('click', () => {
            scanOnlyMode = !scanOnlyMode;
            syncScanOnlyUI();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
            savePrefs();
        });
    }
    if (densityToggle) {
        densityToggle.addEventListener('click', () => {
            densityMode = densityMode === 'compact' ? 'comfort' : 'compact';
            syncDensityUI();
            savePrefs();
        });
    }
    if (cameraScanBtn) cameraScanBtn.addEventListener('click', openCameraScanner);
    if (scanCloseBtn) scanCloseBtn.addEventListener('click', closeCameraScanner);
    if (scanManualSubmit) scanManualSubmit.addEventListener('click', submitManualScanCode);
    if (scanManualCode) {
        scanManualCode.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitManualScanCode();
            }
        });
    }
    if (scanTorchBtn) scanTorchBtn.addEventListener('click', toggleTorch);
    if (cameraScanModal) {
        cameraScanModal.addEventListener('click', (e) => {
            if (e.target === cameraScanModal) closeCameraScanner();
        });
    }
    if (successNewTxnBtn) {
        successNewTxnBtn.addEventListener('click', () => {
            closeSuccessSheet();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        });
    }
    if (successSheet) {
        successSheet.addEventListener('click', (e) => {
            if (e.target === successSheet) closeSuccessSheet();
        });
    }
    const openDebtPop = () => {
        if (!currentDebtText) return;
        lastActiveEl = document.activeElement;
        if (debtPopText) {
            if (currentDebtData) {
                const lines = [
                    currentDebtData.name ? `Customer: ${currentDebtData.name}` : '',
                    `Jumlah invoice hutang: ${currentDebtData.debtCount}`,
                    `Total hutang: Rp ${money(currentDebtData.debtTotal)}`,
                    currentDebtData.due ? `Jatuh tempo terdekat: ${currentDebtData.due}` : 'Jatuh tempo terdekat: -',
                ].filter(Boolean);
                const detailRows = Array.isArray(currentDebtData.debtItems) ? currentDebtData.debtItems : [];
                if (detailRows.length > 0) {
                    lines.push('Rincian invoice:');
                    detailRows.forEach((item, idx) => {
                        const amt = Number(item?.remaining_amount || 0);
                        const dueDate = String(item?.due_date || '-');
                        const st = String(item?.status || '-').toUpperCase();
                        lines.push(`${idx + 1}. Rp ${money(amt)} | ${st} | due ${dueDate}`);
                    });
                }
                debtPopText.innerHTML = lines.join('<br>');
            } else {
                debtPopText.textContent = currentDebtText;
            }
        }
        if (debtPop) {
            debtPop.classList.add('show');
            debtPop.setAttribute('aria-hidden', 'false');
        }
    };
    if (debtFab) {
        debtFab.addEventListener('click', openDebtPop);
        debtFab.addEventListener('touchend', (e) => { e.preventDefault(); openDebtPop(); }, { passive: false });
        debtFab.addEventListener('pointerup', openDebtPop);
    }
    const closeDebtPop = () => {
        if (!debtPop) return;
        debtPop.classList.remove('show');
        debtPop.setAttribute('aria-hidden', 'true');
        if (lastActiveEl && typeof lastActiveEl.focus === 'function') {
            try { lastActiveEl.focus(); } catch (_) {}
        }
    };
    if (debtPopClose) debtPopClose.addEventListener('click', closeDebtPop);
    if (debtPop) {
        debtPop.addEventListener('click', (e) => {
            if (e.target === debtPop) closeDebtPop();
        });
    }
    if (syncStockBtn) {
        syncStockBtn.addEventListener('click', async () => {
            syncStockBtn.disabled = true;
            const prev = syncStockBtn.textContent;
            syncStockBtn.textContent = 'Sinkron...';
            const result = await validateCartStock({ adjust: true });
            if (result.ok) showToast('Stok keranjang sudah sinkron.');
            else if (Array.isArray(result.issues) && result.issues[0]?.message) showToast(String(result.issues[0].message));
            syncStockBtn.textContent = prev || 'Sinkron Stok';
            syncStockBtn.disabled = false;
            fetchProducts();
        });
    }

    const attemptCheckout = (e) => {
        const run = async () => {
        if (isSubmitting) {
            if (e) e.preventDefault();
            return;
        }
        if (cart.length === 0) {
            if (e) e.preventDefault();
            failCheckout('Keranjang masih kosong.', searchInput);
            return;
        }
        const status = String(statusField?.value || 'paid');
        const debtMode = String(debtModeField?.value || 'normal');
        const customerNameValue = String(customerName?.value || '').trim();
        if (hiddenCustomerName) hiddenCustomerName.value = customerNameValue;
        if (hiddenStatus) hiddenStatus.value = status;
        if (hiddenDebtMode) hiddenDebtMode.value = debtMode;
        if (status === 'pending' && (debtMode === 'partial' || debtMode === 'merge') && customerNameValue === '') {
            if (e) e.preventDefault();
            failCheckout('Isi nama customer dulu untuk transaksi hutang.', customerName);
            return;
        }
        const singleMethod = paymentMethod.value || 'cash';
        hiddenPaymentMethodSingle.value = singleMethod;
        hiddenPaymentMethod.value = singleMethod;
        hiddenQrisRef.value = qrisRef.value || '';
        if (splitEnabled) {
            const methodA = String(splitMethodA?.value || '');
            const methodB = String(splitMethodB?.value || '');
            const amountA = parseRupiahInput(splitAmountA?.value || 0);
            const amountB = parseRupiahInput(splitAmountB?.value || 0);
            const total = getCartTotal();
            if (!methodA || !methodB || methodA === methodB) {
                if (e) e.preventDefault();
                failCheckout('Metode split harus 2 metode berbeda.', splitMethodB);
                return;
            }
            if (amountA <= 0 || amountB <= 0) {
                if (e) e.preventDefault();
                failCheckout('Nominal split harus lebih dari 0.', splitAmountA);
                return;
            }
            if (Math.abs((amountA + amountB) - total) > 0.01) {
                if (e) e.preventDefault();
                failCheckout(`Total split harus sama dengan total transaksi (Rp ${money(total)}).`, splitAmountB);
                return;
            }
            if ((methodA === 'qris' || methodB === 'qris') && !hiddenQrisRef.value.trim()) {
                if (e) e.preventDefault();
                failCheckout('Isi referensi QRIS dulu karena split memakai QRIS.', qrisRef);
                return;
            }
            hiddenPaymentMethod.value = 'mixed';
            if (splitPaymentsJson) {
                splitPaymentsJson.value = JSON.stringify([
                    { method: methodA, amount: amountA },
                    { method: methodB, amount: amountB },
                ]);
            }
        } else if (splitPaymentsJson) {
            splitPaymentsJson.value = '';
        }
        if (status === 'paid' && hiddenPaymentMethod.value === 'qris' && !hiddenQrisRef.value.trim()) {
            if (e) e.preventDefault();
            failCheckout('Isi referensi QRIS dulu.', qrisRef);
            return;
        }
        const stockCheck = await validateCartStock({ adjust: true, silent: true });
        if (!stockCheck.ok) {
            const firstIssue = Array.isArray(stockCheck.issues) ? stockCheck.issues[0] : null;
            const message = String(firstIssue?.message || 'Stok berubah. Muat ulang produk lalu coba lagi.');
            failCheckout(message, searchInput);
            fetchProducts();
            return;
        }
        if (cart.length === 0) {
            failCheckout('Semua item habis stok. Keranjang dikosongkan otomatis.', searchInput);
            return;
        }
        applySubmitState(true);
        triggerHaptic('ok');
        if (!navigator.onLine) {
            queueOfflineCheckout();
            cart = [];
            checkoutToken.value = token();
            renderCart();
            clearCartDraft();
            applySubmitState(false);
            showToast('Offline: transaksi masuk antrean sync.');
            return;
        }
        clearCartDraft();
        saveSuccessSnapshot(getCartTotal(), hiddenPaymentMethod.value || singleMethod);
        hideKeyboard();
        if (isMobile()) closeCart();
        checkoutForm.submit();
        };
        if (e) e.preventDefault();
        run();
    };
    checkoutForm.addEventListener('submit', attemptCheckout);
    if (payBtnMobile) payBtnMobile.addEventListener('click', () => attemptCheckout(null));
    window.addEventListener('pageshow', () => applySubmitState(false));

    document.addEventListener('click', (e) => {
        if (!customerSuggestBox) return;
        if (!e.target.closest('#customerName') && !e.target.closest('#customerSuggestBox')) {
            hideCustomerSuggestions();
        }
    });

    loadCartDraft();
    renderProducts();
    loadPrefs();
    syncShiftMiniUI();
    syncScanOnlyUI();
    syncDensityUI();
    updateOfflineQueueUI();
    processOfflineQueue();
    syncCustomerField();
    syncCheckoutMode();
    setCheckoutInfoOpen(false);
    setPaymentAdvOpen(Boolean(splitEnabled));
    renderCart();
    syncPayLabelByStatus();
    restoreSuccessSheet();
    applySubmitState(false);
    refreshShiftSummary();
    setInterval(refreshShiftSummary, 25000);
    const syncFab = () => {
        if (!cartFab) return;
        cartFab.style.display = isMobile() ? 'grid' : 'none';
        if (!isMobile()) {
            cartAside.classList.remove('show');
            cartOverlay.classList.remove('show');
            document.body.classList.remove('k-cart-open');
        }
    };
    syncFab();
    window.addEventListener('resize', syncFab);
    const syncSplitViewMode = () => {
        const app = document.getElementById('app');
        if (!app) return;
        const width = Math.max(window.innerWidth || 0, window.visualViewport?.width || 0);
        const coarse = window.matchMedia('(pointer:coarse)').matches;
        const splitLike = coarse && width >= 768 && width <= 980;
        app.classList.toggle('k-split-mobile', splitLike);
        if (!splitLike) {
            cartAside.classList.remove('show');
            cartOverlay.classList.remove('show');
            document.body.classList.remove('k-cart-open');
        }
    };
    syncSplitViewMode();
    window.addEventListener('resize', syncSplitViewMode);

    const syncToolsSpacer = () => {
        if (!toolsWrap || !toolsSpacer) return;
        if (isMobile()) {
            toolsSpacer.style.height = `${Math.ceil(toolsWrap.getBoundingClientRect().height)}px`;
        } else {
            toolsSpacer.style.height = '0px';
        }
    };
    syncToolsSpacer();
    window.addEventListener('resize', syncToolsSpacer);

    const syncFocusMode = () => {
        if (!window.visualViewport) return;
        const mobile = isMobile();
        const compact = (window.innerHeight - window.visualViewport.height) > 150;
        document.body.classList.toggle('k-focus-mode', mobile && compact);
        syncToolsSpacer();
    };
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', syncFocusMode);
        window.visualViewport.addEventListener('scroll', syncFocusMode);
    }
    window.addEventListener('resize', syncFocusMode);
    syncFocusMode();
    window.addEventListener('online', processOfflineQueue);
    setInterval(processOfflineQueue, 15000);
    window.addEventListener('beforeunload', (e) => {
        if (!Array.isArray(cart) || cart.length === 0 || isSubmitting) return;
        e.preventDefault();
        e.returnValue = '';
    });
})();
</script>
</body>
</html>
