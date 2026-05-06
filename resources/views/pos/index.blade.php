<x-app-layout>
    <x-slot name="header">
        <div class="pro-page-head">
            <div>
                <p class="page-kicker">Kasir</p>
                <h2 class="pro-page-title">Kasir</h2>
                <p class="pro-page-sub">Proses transaksi cepat, akurat, dan siap audit dalam satu workflow operasional.</p>
            </div>
        </div>
    </x-slot>


    <div class="page-shell" x-data="posApp()" x-init="initApp()">
        <div class="panel-card pos-onboarding-card mb-4" x-show="showOnboarding" x-transition x-cloak>
            <div class="pos-onboarding-head">
                <div>
                    <h3 class="pos-onboarding-title">Panduan Kasir 1 Menit</h3>
                    <p class="pos-onboarding-subtitle">Alur cepat: cari produk -> atur keranjang -> pilih pembayaran -> checkout.</p>
                </div>
                <button type="button" class="customer-btn customer-btn--ghost" @click="dismissOnboarding()">Tutup</button>
            </div>
            <div class="pos-onboarding-grid">
                <div class="pos-onboarding-item">
                    <div class="pos-onboarding-kicker">Langkah 1</div>
                    <p>Cari produk dengan nama/SKU/barcode lalu tekan <strong>Enter</strong> untuk tambah produk pertama.</p>
                </div>
                <div class="pos-onboarding-item">
                    <div class="pos-onboarding-kicker">Langkah 2</div>
                    <p>Atur qty dan diskon item di keranjang. Gunakan tombol cepat untuk nominal bayar.</p>
                </div>
                <div class="pos-onboarding-item">
                    <div class="pos-onboarding-kicker">Langkah 3</div>
                    <p>Pilih metode bayar tunggal atau split payment, lalu checkout dengan <strong>F4</strong>.</p>
                </div>
                <div class="pos-onboarding-item">
                    <div class="pos-onboarding-kicker">Pintasan</div>
                    <p><strong>/</strong> cari, <strong>F2</strong> fokus bayar, <strong>Esc</strong> reset input aktif, <strong>Ctrl+Enter</strong> simpan.</p>
                </div>
            </div>
        </div>

        <div class="pos-toast-stack">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="pos-toast" :class="toast.type === 'error' ? 'pos-toast--error' : (toast.type === 'warning' ? 'pos-toast--warning' : 'pos-toast--success')">
                    <div class="pos-toast-title" x-text="toast.title"></div>
                    <div class="pos-toast-message" x-text="toast.message"></div>
                </div>
            </template>
        </div>
        <div class="pos-success-modal-backdrop" x-show="showCheckoutSuccessModal" x-transition.opacity x-cloak>
            <div class="pos-success-modal pos-success-modal--success" x-ref="checkoutSuccessModal" x-transition tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="checkout-success-title" aria-describedby="checkout-success-desc">
                <div class="pos-success-head">
                    <span class="pos-success-icon-wrap">
                        <i data-feather="check-circle" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 id="checkout-success-title">Transaksi Berhasil Disimpan</h3>
                        <p class="pos-success-subtitle">Data penjualan sudah tercatat dan siap dilanjutkan.</p>
                    </div>
                </div>
                <div class="pos-success-message-box">
                    <p id="checkout-success-desc" x-text="checkoutSuccessMessage"></p>
                </div>
                <div class="pos-modal-actions">
                    <button type="button" class="pos-modal-btn pos-modal-btn--primary" x-ref="checkoutSuccessPrimaryBtn" @click="closeCheckoutSuccessModal()">Lanjut Transaksi</button>
                </div>
            </div>
        </div>
        <x-approval-modal
            id="pos-manager-approval-modal"
            class="pos-success-modal-backdrop"
            x-show="showManagerApprovalModal"
            x-transition.opacity
            x-cloak
            title="Approval Manajer"
            note="Approval manager diperlukan."
            title-id="manager-approval-title"
            note-id="manager-approval-desc"
            reason-id="manager-approval-reason"
            email-id="manager-approval-email"
            password-id="manager-approval-password"
            error-id="manager-approval-error"
            cancel-id="manager-approval-cancel"
            submit-id="manager-approval-submit"
            submit-label="Lanjut Checkout"
            panel-ref="managerApprovalModal"
            email-ref="managerApprovalEmailInput"
            primary-button-ref="managerApprovalPrimaryBtn"
            reason-label="Catatan Approval (opsional)"
            email-label="Email Manajer"
            password-label="Password Manajer"
        />
        <div class="pos-success-modal-backdrop" x-show="showConfirmModal" x-transition.opacity x-cloak>
            <div class="pos-success-modal" x-ref="confirmModal" :class="`pos-success-modal--${confirmModalTone || 'info'}`" x-transition tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-desc">
                <h3 id="confirm-modal-title" x-text="confirmModalTitle || 'Konfirmasi'"></h3>
                <p id="confirm-modal-desc" x-text="confirmModalMessage"></p>
                <template x-if="confirmModalMeta">
                    <div class="pos-confirm-summary">
                        <div class="pos-confirm-summary-grid">
                            <div class="pos-confirm-item pos-confirm-item--total">
                                <div class="pos-confirm-item-label">Total</div>
                                <div class="pos-confirm-item-value" x-text="`Rp ${money(confirmModalMeta.total || 0)}`"></div>
                            </div>
                            <div class="pos-confirm-item">
                                <div class="pos-confirm-item-label">Dibayar</div>
                                <div class="pos-confirm-item-value" x-text="`Rp ${money(confirmModalMeta.paid || 0)}`"></div>
                            </div>
                            <div class="pos-confirm-item">
                                <div class="pos-confirm-item-label">Kembalian</div>
                                <div class="pos-confirm-item-value" x-text="`Rp ${money(confirmModalMeta.change || 0)}`"></div>
                            </div>
                            <div class="pos-confirm-item">
                                <div class="pos-confirm-item-label">Item</div>
                                <div class="pos-confirm-item-value" x-text="`${numberFormat(confirmModalMeta.qty || 0)} produk`"></div>
                            </div>
                        </div>
                        <div class="pos-confirm-status">
                            <span class="pos-confirm-status-dot"></span>
                            <span x-text="`Status: ${String(confirmModalMeta.status || '').toUpperCase()}`"></span>
                        </div>
                        <template x-if="String(confirmModalMeta.qrisRef || '').trim() !== ''">
                            <div class="mt-2 text-xs text-slate-700">
                                <strong>Ref QRIS:</strong>
                                <span x-text="String(confirmModalMeta.qrisRef || '')"></span>
                                <template x-if="String(confirmModalMeta.qrisIssuer || '').trim() !== ''">
                                    <span x-text="` | Issuer: ${String(confirmModalMeta.qrisIssuer || '')}`"></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                <div class="pos-modal-actions">
                    <button type="button" class="pos-modal-btn pos-modal-btn--secondary" @click="rejectConfirm()">Batal</button>
                    <button type="button" class="pos-modal-btn pos-modal-btn--primary" x-ref="confirmPrimaryBtn" @click="acceptConfirm()">Lanjut</button>
                </div>
            </div>
        </div>
        <div class="pos-success-modal-backdrop" x-show="showAlertModal" x-transition.opacity x-cloak>
            <div class="pos-success-modal pos-success-modal--danger" x-ref="alertModal" x-transition tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="alert-modal-title" aria-describedby="alert-modal-desc">
                <h3 id="alert-modal-title" x-text="alertModalTitle || 'Informasi'"></h3>
                <p id="alert-modal-desc" x-text="alertModalMessage"></p>
                <div class="pos-modal-actions">
                    <button type="button" class="pos-modal-btn pos-modal-btn--primary" x-ref="alertPrimaryBtn" @click="closeAlertModal()">Tutup</button>
                </div>
            </div>
        </div>
        <div class="pos-success-modal-backdrop" x-show="showInputModal" x-transition.opacity x-cloak>
            <div class="pos-success-modal pos-success-modal--info" x-ref="inputModal" x-transition tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="input-modal-title" aria-describedby="input-modal-desc">
                <h3 id="input-modal-title" x-text="inputModalTitle || 'Input'"></h3>
                <p id="input-modal-desc" x-text="inputModalMessage"></p>
                <div class="space-y-2 mt-3 text-left">
                    <input type="text" class="pos-input pos-input-standalone" :placeholder="inputModalPlaceholder || ''" x-model="inputModalValue" x-ref="inputModalField" @keydown.enter.prevent="acceptInputModal()">
                </div>
                <div class="pos-modal-actions">
                    <button type="button" class="pos-modal-btn pos-modal-btn--secondary" @click="rejectInputModal()">Batal</button>
                    <button type="button" class="pos-modal-btn pos-modal-btn--primary" x-ref="inputPrimaryBtn" @click="acceptInputModal()">Simpan</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <section class="order-2 xl:order-1 xl:col-span-7 panel-card overflow-hidden">
                <div class="panel-head pos-product-head bg-gradient-to-r from-slate-50 to-amber-50/70">
                    <div class="pos-product-head-title">
                        <h3 class="text-base font-bold text-slate-900">Daftar Produk</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            <span x-text="pagination.total"></span> produk - halaman <span x-text="pagination.current_page"></span>/<span x-text="pagination.last_page"></span>
                        </p>
                    </div>
                    <div class="pos-product-head-controls">
                        <div class="pos-search-wrap">
                            <i data-feather="search" class="w-4 h-4"></i>
                            <input type="text" x-ref="searchInput" x-model="search" @keydown.enter.prevent="addFirstProduct()" @input.debounce.350ms="searchProducts" class="pos-search-input" placeholder="Cari nama, SKU, atau barcode...">
                            <button type="button" class="pos-search-clear" x-show="search" @click="clearSearch" title="Bersihkan pencarian" x-cloak>
                                <i data-feather="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <select class="pos-input pos-category-filter" x-model="categoryId" @change="saveUiPrefs(); currentPage = 1; fetchProducts()">
                            <option value="0">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label class="pos-inline-toggle">
                            <input type="checkbox" x-model="barcodeMode" @change="saveUiPrefs()">
                            <span>Mode Scan Barcode</span>
                        </label>
                    </div>
                </div>
                <div class="pos-shortcut-hint">
                    Pintasan: `/` cari produk, `Enter` tambah produk pertama, `F2` fokus bayar, `F4` / `Ctrl+Enter` simpan, `Esc` reset bayar/pencarian.
                </div>
                <div class="pos-quick-picks" x-show="recentProducts.length > 0" x-cloak>
                    <div class="pos-quick-picks-head">
                        <span>Akses Cepat Produk Terakhir</span>
                        <button type="button" class="btn-danger-lite" @click="clearRecentProducts()">Bersihkan</button>
                    </div>
                    <div class="pos-quick-picks-list">
                        <template x-for="rp in recentProducts" :key="rp.id">
                            <button type="button" class="pos-quick-pick-item" @click="addToCart(rp)">
                                <img :src="rp.image ? (`/storage/${rp.image}`) : '/dist/images/preview-8.jpg'" :alt="rp.name">
                                <div class="pos-quick-pick-meta">
                                    <strong x-text="rp.name"></strong>
                                    <span x-text="`Rp ${money(rp.selling_price)}`"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="overflow-auto max-h-[68vh] p-4 pos-products-scroll">
                    <div class="pos-product-grid" x-show="serverFallbackVisible">
                        @forelse($products as $product)
                            <article class="pos-product-card">
                                <div class="pos-product-image-wrap">
                                    <img
                                        src="{{ $product->image ? asset('storage/'.$product->image) : asset('dist/images/preview-8.jpg') }}"
                                        alt="{{ $product->name }}"
                                        class="pos-product-image"
                                        loading="lazy"
                                    >
                                </div>
                                <div class="p-3 pos-product-card-body">
                                    <div class="pos-product-name">{{ $product->name }}</div>
                                    <div class="pos-product-sku">SKU: {{ $product->sku }}</div>
                                    <div class="pos-product-price-row">
                                        <span class="font-bold text-slate-900 text-sm">Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}</span>
                                        <span class="status-chip">{{ (int) $product->stock }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-primary pos-add-btn w-full"
                                        @click="addProductById({{ (int) $product->id }})"
                                        @disabled($product->stock <= 0)
                                    >
                                        <i data-feather="plus-circle" class="w-4 h-4 mr-1.5"></i>
                                        {{ $product->stock <= 0 ? 'Habis' : 'Tambah ke Keranjang' }}
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full pos-product-empty">
                                <p class="font-semibold text-slate-800">Produk tidak ditemukan</p>
                            </div>
                        @endforelse
                    </div>

                    <template x-if="loadError">
                        <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="loadError"></div>
                    </template>
                    <div class="pos-products-loading" x-show="loading" x-cloak>
                        <div class="pos-spinner"></div>
                        <span>Mencari produk...</span>
                    </div>

                    <div class="pos-product-grid" x-show="!serverFallbackVisible" x-cloak :class="loading ? 'opacity-50 pointer-events-none' : ''">
                        <template x-if="!loading && products.length === 0">
                            <div class="col-span-full pos-product-empty">
                                <div class="pos-product-empty-icon">
                                    <i data-feather="package" class="w-5 h-5"></i>
                                </div>
                                <p class="font-semibold text-slate-800">Produk tidak ditemukan</p>
                                <p class="text-xs text-slate-500 mt-1">Coba kata kunci lain atau bersihkan pencarian.</p>
                            </div>
                        </template>

                        <template x-for="product in displayProducts()" :key="product.id">
                            <article class="pos-product-card" :class="{ 'pos-product-card--added': Number(animProductId) === Number(product.id) }">
                                <div class="pos-product-image-wrap">
                                    <button type="button" class="pos-fav-btn" :class="{ 'is-active': isFavorite(product.id) }" @click.stop="toggleFavorite(product.id)" :title="isFavorite(product.id) ? 'Hapus favorit' : 'Jadikan favorit'">
                                        <i data-feather="star" class="w-4 h-4"></i>
                                    </button>
                                    <img
                                        :src="product.image ? (`/storage/${product.image}`) : '/dist/images/preview-8.jpg'"
                                        :alt="product.name"
                                        class="pos-product-image"
                                        loading="lazy"
                                    >
                                </div>
                                <div class="p-3 pos-product-card-body">
                                    <div class="pos-product-name" x-text="product.name"></div>
                                    <div class="pos-product-sku" x-text="`SKU: ${product.sku}`"></div>
                                    <div class="pos-product-price-row">
                                        <span class="font-bold text-slate-900 text-sm">Rp <span x-text="money(product.selling_price)"></span></span>
                                        <span class="status-chip" :class="stockChipClass(product)" x-text="product.stock"></span>
                                    </div>
                                    <div class="pos-stock-inline-badge" :class="stockInlineClass(product)" x-text="stockInlineText(product)"></div>
                                    <div class="pos-product-stock-note" :class="stockTextClass(product)" x-text="stockText(product)"></div>
                                    <button type="button" @click="addToCart(product)" class="btn-primary pos-add-btn w-full" :disabled="product.stock <= 0">
                                        <i data-feather="plus-circle" class="w-4 h-4 mr-1.5"></i>
                                        <span x-text="product.stock <= 0 ? 'Habis' : 'Tambah ke Keranjang'"></span>
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>

                    <div class="mt-4 pos-pagination">
                        <p class="pos-pagination__summary" x-text="paginationSummary()"></p>
                        <div class="pos-pagination__actions">
                            <button type="button" class="pos-page-btn" @click="goToPage(1)" :disabled="loading || pagination.current_page <= 1">Awal</button>
                            <button type="button" class="pos-page-btn" @click="changePage(-1)" :disabled="loading || pagination.current_page <= 1">Sebelumnya</button>
                            <template x-for="page in paginationWindow()" :key="`page-${page}`">
                                <button type="button" class="pos-page-btn" :class="{ 'is-active': Number(page) === Number(pagination.current_page) }" @click="goToPage(page)" x-text="page" :disabled="loading"></button>
                            </template>
                            <button type="button" class="pos-page-btn" @click="changePage(1)" :disabled="loading || pagination.current_page >= pagination.last_page">Berikutnya</button>
                            <button type="button" class="pos-page-btn" @click="goToPage(pagination.last_page)" :disabled="loading || pagination.current_page >= pagination.last_page">Akhir</button>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="order-1 xl:order-2 xl:col-span-5 pos-sidebar">
                @if(isset($pendingFollowups) && $pendingFollowups->count() > 0)
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        <p class="font-semibold">Reminder Pending (jatuh tempo <= 15 menit)</p>
                        <div class="mt-1 space-y-1">
                            @foreach($pendingFollowups as $pf)
                                <p>
                                    <a href="{{ route('sales.show', $pf) }}" class="underline">{{ $pf->invoice_number }}</a>
                                    -
                                    {{ strtoupper(str_replace('_', ' ', (string) $pf->payment_method)) }}
                                    -
                                    due {{ optional($pf->payment_due_at)->format('H:i') }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="panel-card overflow-hidden shadow-lg shadow-slate-900/[0.06]">
                    <div class="panel-head">
                        <div class="flex items-center justify-between gap-2 w-full">
                            <h3 class="text-base font-bold text-slate-900">Keranjang</h3>
                            <button type="button" class="btn-danger-lite" @click="clearCart()" :disabled="cart.length === 0">Kosongkan Keranjang</button>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('pos.checkout') }}" @submit="prepareSubmit" class="panel-body space-y-4">
                        @csrf
                        <input type="hidden" name="print_after_checkout" :value="printAfterCheckout ? 1 : 0">
                        <input type="hidden" name="open_receipt_pdf" :value="openReceiptPdf ? 1 : 0">
                        <input type="hidden" name="source_hold_id" :value="activeHoldId">
                        <input type="hidden" name="customer_id" x-model="selectedCustomerId">
                        <input type="hidden" name="checkout_token" :value="checkoutToken">
                        <input type="hidden" name="payment_method" :value="effectivePaymentMethod">
                        <input type="hidden" name="payment_method_single" :value="paymentMethod">
                        <input type="hidden" name="split_payments_json" :value="splitPaymentsJson">
                        <input type="hidden" name="debt_mode" :value="debtMode">
                        <input type="hidden" name="debt_existing_id" :value="selectedDebtExistingId">
                        <input type="hidden" name="paid_amount" :value="paid">
                        <input type="hidden" name="manager_approval_email" :value="managerApprovalEmail">
                        <input type="hidden" name="manager_approval_password" :value="managerApprovalPassword">
                        <input type="hidden" name="qris_reference_id" :value="qrisReferenceId">
                        <input type="hidden" name="qris_issuer" :value="qrisIssuer">
                        <input type="hidden" name="installment_enabled" :value="installmentEnabled ? 1 : 0">
                        <input type="hidden" name="installment_tenor_months" :value="installmentTenorMonths">
                        <input type="hidden" name="installment_down_payment" :value="installmentDownPayment">
                        <input type="hidden" name="installment_first_due_date" :value="installmentFirstDueDate">

                        <div class="pos-cart-summary">
                            <div class="pos-cart-summary-item">
                                <span>Item</span>
                                <strong x-text="cart.length"></strong>
                            </div>
                            <div class="pos-cart-summary-item">
                                <span>Qty</span>
                                <strong x-text="totalQty"></strong>
                            </div>
                        </div>

                                <div class="space-y-2 max-h-72 overflow-y-auto pr-1 pos-cart-list">
                            <template x-if="cart.length === 0">
                                <div class="pos-cart-empty">
                                    <div class="pos-cart-empty-icon">
                                        <i data-feather="shopping-bag" class="w-5 h-5"></i>
                                    </div>
                                    <p>Belum ada item di keranjang.</p>
                                </div>
                            </template>

                            <template x-for="item in cart" :key="item.id">
                                <div class="pos-cart-item" :class="{ 'pos-cart-item--fresh': isCartItemFresh(item.id) }">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800" x-text="item.name"></p>
                                            <p class="text-xs text-slate-500" x-text="item.sku"></p>
                                        </div>
                                        <button type="button" @click="removeItem(item.id)" class="btn-danger-icon" title="Hapus">
                                            <i data-feather="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <div class="pos-qty-control">
                                            <button type="button" class="pos-qty-btn" @click="decreaseQty(item)">
                                                <i data-feather="minus" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <input type="number" min="1" :max="item.stock" x-model.number="item.quantity" class="pos-qty-input" @input="recalculate">
                                            <button type="button" class="pos-qty-btn" @click="increaseQty(item)">
                                                <i data-feather="plus" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-slate-500">Rp <span x-text="money(item.price)"></span></p>
                                            <p class="text-sm font-bold text-slate-900">Rp <span x-text="money(lineTotal(item))"></span></p>
                                        </div>
                                    </div>
                                    <template x-if="item.quantity >= item.stock">
                                        <div class="pos-qty-warning">Qty sudah menyentuh stok maksimum.</div>
                                    </template>
                                    <div class="mt-2">
                                        <label class="label-ui">Diskon Item</label>
                                        <div class="pos-input-wrap pos-input-wrap--amber">
                                            <span>Rp</span>
                                            <input type="number" step="0.01" min="0" x-model.number="item.manual_discount_amount" class="pos-input" @input="recalculate">
                                        </div>
                                        <template x-if="Number(item.auto_promo_discount || 0) > 0">
                                            <p class="mt-1 text-[11px] text-emerald-700">
                                                Promo <span x-text="promoLabel(item)"></span>:
                                                -Rp <span x-text="money(item.auto_promo_discount)"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="pos-checkout-form">
                            <div class="pos-field pos-field--wide">
                                <label class="label-ui">Nama Customer</label>
                                <div class="pos-input-wrap">
                                    <i data-feather="user" class="w-4 h-4"></i>
                                    <input type="text" name="customer_name" list="customer-list" class="pos-input" placeholder="Nama / No HP / Email" x-ref="customerNameInput" @input="fillCustomerMeta()" @change="fillCustomerMeta()">
                                </div>
                            </div>
                            <datalist id="customer-list">
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->name }}" label="{{ trim(($customer->phone ?: '-') . ' | ' . ($customer->email ?: '-')) }}"></option>
                                    @if($customer->phone)
                                        <option value="{{ $customer->phone }}" label="{{ $customer->name }} | {{ $customer->email ?: '-' }}"></option>
                                    @endif
                                    @if($customer->email)
                                        <option value="{{ $customer->email }}" label="{{ $customer->name }} | {{ $customer->phone ?: '-' }}"></option>
                                    @endif
                                @endforeach
                            </datalist>

                            <div class="pos-checkout-grid">
                                <div class="pos-field">
                                    <label class="label-ui">No. HP Customer</label>
                                    <div class="pos-input-wrap">
                                        <i data-feather="phone" class="w-4 h-4"></i>
                                        <input type="text" name="customer_phone" class="pos-input" placeholder="Opsional" x-ref="customerPhoneInput">
                                    </div>
                                </div>
                                <div class="pos-field">
                                    <label class="label-ui">Email Customer</label>
                                    <div class="pos-input-wrap">
                                        <i data-feather="mail" class="w-4 h-4"></i>
                                        <input type="email" name="customer_email" class="pos-input" placeholder="Opsional" x-ref="customerEmailInput">
                                    </div>
                                </div>
                                <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                    <label class="label-ui">Alamat Customer</label>
                                    <div class="pos-input-wrap">
                                        <i data-feather="map-pin" class="w-4 h-4"></i>
                                        <input type="text" name="customer_address" class="pos-input" placeholder="Opsional" x-ref="customerAddressInput">
                                    </div>
                                </div>
                                <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                    <label class="label-ui">Catatan Transaksi</label>
                                    <textarea name="note" class="pos-input pos-input-textarea" rows="2" x-model="noteText" placeholder="Contoh: Antar sore jam 17.00 / pelanggan minta invoice."></textarea>
                                    <div class="pos-note-presets">
                                        <button type="button" class="btn-danger-lite" @click="appendNotePreset('Antar sore')">Antar sore</button>
                                        <button type="button" class="btn-danger-lite" @click="appendNotePreset('Pelanggan minta invoice')">Minta invoice</button>
                                        <button type="button" class="btn-danger-lite" @click="appendNotePreset('Pending - follow up')">Tunda follow up</button>
                                    </div>
                                </div>
                                <template x-if="hasOutstandingDebt">
                                    <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                        <label class="label-ui">Keputusan Kredit Pelanggan</label>
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 space-y-2">
                                            <p>
                                                Customer ini punya <strong x-text="customerDebtCount"></strong> piutang aktif.
                                                Total sisa: <strong>Rp <span x-text="money(customerDebtTotal)"></span></strong>
                                            </p>
                                            <p class="text-xs text-amber-800">
                                                Overdue: <strong x-text="customerOverdueCount"></strong> |
                                                Jatuh tempo terdekat: <strong x-text="customerNearestDueDate"></strong>
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <label class="pos-inline-toggle"><input type="radio" x-model="debtMode" value="normal"><span>Normal</span></label>
                                                <label class="pos-inline-toggle"><input type="radio" x-model="debtMode" value="partial"><span>Bayar sebagian + hutang</span></label>
                                                <label class="pos-inline-toggle"><input type="radio" x-model="debtMode" value="merge"><span>Gabung ke hutang lama</span></label>
                                            </div>
                                            <p class="text-xs text-amber-700" x-show="debtMode !== 'normal'">
                                                Estimasi total piutang: Rp <strong x-text="money(projectedDebtAfterCheckout)"></strong>
                                                (limit: Rp <span x-text="money(customerDebtLimit)"></span>)
                                            </p>
                                            @if(!empty($canCustomerDebtManage))
                                                <a href="{{ route('customers.debts.index') }}" class="text-xs font-semibold text-blue-700 underline">Buka halaman Piutang Pelanggan</a>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                                <div class="pos-field">
                                    <label class="label-ui">Diskon</label>
                                    <div class="pos-input-wrap pos-input-wrap--amber">
                                        <span>Rp</span>
                                        <input type="number" step="0.01" min="0" x-model.number="discount" name="discount_amount" class="pos-input" @input="recalculate">
                                    </div>
                                </div>
                                <div class="pos-field">
                                    <label class="label-ui">Pajak</label>
                                    <div class="pos-input-wrap pos-input-wrap--green">
                                        <span>Rp</span>
                                        <input type="number" step="0.01" min="0" x-model.number="tax" name="tax_amount" class="pos-input" @input="recalculate">
                                    </div>
                                </div>
                                <div class="pos-field">
                                    <label class="label-ui">Status</label>
                                    <div class="pos-input-wrap pos-input-wrap--blue">
                                        <i data-feather="credit-card" class="w-4 h-4"></i>
                                        <select x-model="status" name="status" class="pos-input" :disabled="installmentEnabled">
                                            <option value="paid">Lunas</option>
                                            <option value="pending">Menunggu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pos-field pos-field--payment">
                                    <label class="label-ui">Metode Pembayaran</label>
                                    <div class="pos-input-wrap" :class="status !== 'paid' ? 'pos-input-wrap--disabled' : ''">
                                        <template x-if="status === 'paid'">
                                            <div class="pos-payment-control">
                                                <i data-feather="layers" class="w-4 h-4"></i>
                                                <select x-model="paymentMethod" class="pos-input" @change="recalculate">
                                                    <option value="cash">Cash</option>
                                                    <option value="qris">QRIS</option>
                                                    <option value="debit">Debit</option>
                                                    <option value="transfer">Transfer</option>
                                                    <option value="e_wallet">E-Wallet</option>
                                                </select>
                                            </div>
                                        </template>
                                        <template x-if="status !== 'paid'">
                                            <span class="pos-muted-note">Belum dibayar (menunggu pelunasan).</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="pos-field pos-field--payment">
                                    <label class="label-ui">Jumlah Dibayar</label>
                                    <div class="pos-input-wrap pos-input-wrap--cyan" :class="status !== 'paid' ? 'pos-input-wrap--disabled' : ''">
                                        <template x-if="status === 'paid'">
                                            <div class="pos-payment-control">
                                                <span>Rp</span>
                                                <input type="text" inputmode="numeric" x-ref="paidInput" :value="money(paid)" class="pos-input" @input="onPaidInput($event)">
                                            </div>
                                        </template>
                                        <template x-if="status !== 'paid'">
                                            <span class="pos-muted-note">Belum ada nominal pembayaran.</span>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="status === 'paid' && requiresQrisReference()">
                                    <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                        <label class="label-ui">Referensi QRIS (Audit)</label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <div class="pos-input-wrap">
                                                <i data-feather="hash" class="w-4 h-4"></i>
                                                <input type="text" x-model="qrisReferenceId" @input="qrisReferenceAuto = false" class="pos-input" placeholder="ID transaksi / RRN / Ref number">
                                            </div>
                                            <div class="pos-input-wrap">
                                                <i data-feather="credit-card" class="w-4 h-4"></i>
                                                <input type="text" x-model="qrisIssuer" class="pos-input" placeholder="Issuer / Acquirer / Merchant App">
                                            </div>
                                        </div>
                                        <p class="mt-1 text-[11px] text-slate-500" x-show="qrisReferenceAuto">
                                            Referensi otomatis terisi. Anda bisa ubah manual jika ada nomor dari aplikasi pembayaran.
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <button type="button" class="btn-danger-lite !h-9" :disabled="qrisDynamicLoading || qrisDynamicAmount() <= 0" @click="generateMidtransQrisSandbox()">
                                                <span x-text="qrisDynamicLoading ? 'Membuat QRIS...' : 'Buat QRIS Midtrans Sandbox'"></span>
                                            </button>
                                            <button type="button" class="btn-danger-lite !h-9" :disabled="qrisDynamicLoading || !qrisDynamicOrderId" @click="checkMidtransQrisSandboxStatus()">
                                                Cek Status QRIS
                                            </button>
                                        </div>
                                        <template x-if="qrisDynamicError">
                                            <p class="mt-2 text-xs text-rose-600" x-text="qrisDynamicError"></p>
                                        </template>
                                        <template x-if="qrisDynamicOrderId">
                                            <p class="mt-2 text-xs text-slate-600">
                                                Order: <strong x-text="qrisDynamicOrderId"></strong>
                                                <span> | Status: </span><strong x-text="String(qrisDynamicStatus || 'pending').toUpperCase()"></strong>
                                            </p>
                                        </template>
                                        <template x-if="qrisDynamicExpiryAt">
                                            <p class="mt-1 text-xs text-slate-500">Berlaku sampai: <span x-text="qrisDynamicExpiryAt"></span></p>
                                        </template>
                                        <template x-if="qrisDynamicQrUrl">
                                            <div class="mt-3 inline-flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3">
                                                <img :src="qrisDynamicQrUrl" alt="QRIS Midtrans Sandbox" class="w-44 h-44 object-contain">
                                                <span class="text-[11px] text-slate-500">Scan QR dari aplikasi e-wallet/banking (sandbox).</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                    <label class="pos-inline-toggle">
                                        <input type="checkbox" x-model="installmentEnabled" @change="onToggleInstallment()">
                                        <span>Aktifkan Transaksi Cicilan</span>
                                    </label>
                                </div>
                                <template x-if="installmentEnabled">
                                    <div class="pos-field pos-field--wide" style="grid-column: 1 / -1;">
                                        <div class="pos-installment-grid">
                                            <div class="pos-installment-col">
                                                <label class="label-ui">Tenor (Bulan)</label>
                                                <div class="pos-input-wrap">
                                                    <input type="number" min="1" max="36" x-model.number="installmentTenorMonths" class="pos-input" @input="recalculate">
                                                </div>
                                            </div>
                                            <div class="pos-installment-col">
                                                <label class="label-ui">DP Awal</label>
                                                <div class="pos-input-wrap pos-input-wrap--cyan">
                                                    <span>Rp</span>
                                                    <input type="number" min="0" step="0.01" x-model.number="installmentDownPayment" class="pos-input" @input="recalculate">
                                                </div>
                                            </div>
                                            <div class="pos-installment-col">
                                                <label class="label-ui">Jatuh Tempo Pertama</label>
                                                <div class="pos-input-wrap">
                                                    <input type="date" x-model="installmentFirstDueDate" class="pos-input">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="pos-field pos-field--wide mt-2">
                                <label class="pos-inline-toggle">
                                    <input type="checkbox" x-ref="splitPaymentToggle" x-model="splitPaymentEnabled" :disabled="status !== 'paid'" @change="recalculate">
                                    <span>Gunakan Metode Pembayaran Ganda (Split)</span>
                                </label>
                            </div>
                            <template x-if="status === 'paid' && splitPaymentEnabled">
                                <div class="pos-split-grid pos-split-card">
                                    <template x-for="(sp, idx) in splitPayments" :key="`split-${idx}`">
                                        <div class="pos-split-row">
                                            <div class="pos-input-wrap pos-split-method">
                                                <select x-model="sp.method" class="pos-input" @change="recalculate">
                                                    <option value="cash">Cash</option>
                                                    <option value="qris">QRIS</option>
                                                    <option value="debit">Debit</option>
                                                    <option value="transfer">Transfer</option>
                                                    <option value="e_wallet">E-Wallet</option>
                                                </select>
                                            </div>
                                            <div class="pos-input-wrap pos-input-wrap--cyan pos-split-amount">
                                                <span>Rp</span>
                                                <input type="number" min="0" step="0.01" class="pos-input" x-model.number="sp.amount" @input="recalculate">
                                            </div>
                                            <button type="button" class="btn-danger-lite pos-split-remove-btn" @click="removeSplitRow(idx)" :disabled="splitPayments.length <= 2">Hapus</button>
                                        </div>
                                    </template>
                                    <div class="pos-split-actions">
                                        <button type="button" class="btn-danger-lite pos-split-add-btn" @click="addSplitRow()" :disabled="splitPayments.length >= 4">+ Tambah Metode</button>
                                        <p class="pos-split-hint">Maksimal 4 metode, tanpa duplikasi.</p>
                                    </div>
                                    <div class="pos-split-summary">
                                        <div>Total Split: <strong>Rp <span x-text="money(splitTotal)"></span></strong></div>
                                        <div :class="splitDelta === 0 ? 'text-emerald-700' : (splitDelta < 0 ? 'text-rose-700' : 'text-amber-700')">
                                            Selisih ke Total:
                                            <strong>
                                                <span x-text="splitDelta === 0 ? 'Pas' : (`Rp ${money(Math.abs(splitDelta))}`)"></span>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="pos-field pos-field--wide mt-2">
                                <label class="label-ui">Aksi Cepat Pembayaran</label>
                                <div class="pos-pay-row">
                                    <button type="button" class="btn-danger-lite" :class="{ 'is-tapped': quickPayFlashKey === 'exact' }" @click="tapQuickPayExact()" :disabled="status !== 'paid'">Pas</button>
                                    <template x-for="preset in quickPayPresets" :key="`qp-${preset}`">
                                        <button type="button" class="btn-danger-lite" :class="{ 'is-tapped': quickPayFlashKey === String(preset) }" @click="tapQuickPayAmount(String(preset), Number(preset))" :disabled="status !== 'paid'">
                                            +<span x-text="formatQuickPayLabel(preset)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="pos-total-box">
                            <div class="pos-total-line" x-show="Math.abs(Number(subtotal || 0) - Number(total || 0)) > 0.0001"><span>Subtotal</span><span>Rp <span x-text="money(subtotal)"></span></span></div>
                            <div class="pos-total-line pos-total-row"><span>Total</span><span>Rp <span x-text="money(total)"></span></span></div>
                            <div class="pos-total-line pos-change-row"><span>Kembalian</span><span>Rp <span x-text="money(change)"></span></span></div>
                        </div>
                        <div class="pos-hold-note">
                            <p><strong>Preview Struk:</strong> <span x-text="`Item ${totalQty} | Total Rp ${money(total)} | Status ${String(status || '').toUpperCase()}`"></span></p>
                            <p><strong>Catatan:</strong> centang "Buka struk PDF otomatis" untuk melihat pratinjau struk setelah transaksi tersimpan.</p>
                        </div>
                        <template x-if="status === 'paid' && payShortfall > 0">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                Kurang bayar: <strong>Rp <span x-text="money(payShortfall)"></span></strong>
                            </div>
                        </template>
                        <template x-if="status === 'paid' && splitPaymentEnabled && splitValidationError">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" x-text="splitValidationError"></div>
                        </template>
                        <template x-if="status === 'paid' && methodLimitError">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" x-text="methodLimitError"></div>
                        </template>

                        <input type="hidden" name="items_json" x-model="itemsJson">

                        <button type="submit" class="btn-success w-full pos-checkout-btn" :disabled="!canSubmit || isSubmitting">
                            <i data-feather="save" class="w-4 h-4 mr-2"></i>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Transaksi'"></span>
                        </button>
                        <button type="button" class="btn-void w-full !h-10 !text-sm" @click="voidBeforeFinalize()" :disabled="isSubmitting || cart.length === 0">
                            <i data-feather="x-circle" class="w-4 h-4 mr-2"></i>
                            Batalkan Transaksi (Void)
                        </button>
                        <div class="pos-inline-group">
                            <label class="pos-inline-toggle">
                                <input type="checkbox" x-model="printAfterCheckout" @change="saveUiPrefs()">
                                <span>Print struk otomatis setelah simpan</span>
                            </label>
                            <label class="pos-inline-toggle">
                                <input type="checkbox" x-model="openReceiptPdf" @change="saveUiPrefs()">
                                <span>Buka struk PDF otomatis</span>
                            </label>
                            <label class="pos-inline-toggle">
                                <input type="checkbox" x-model="soundEnabled" @change="saveUiPrefs()">
                                <span>Suara Aktif/Nonaktif</span>
                            </label>
                        </div>

                        <div class="pos-hold-actions">
                            <button type="button" class="btn-danger-lite" @click="saveHold()" :disabled="cart.length === 0">Simpan Hold</button>
                            <button type="button" class="btn-danger-lite" @click="loadSelectedHold()" :disabled="holds.length === 0 || !selectedHoldId">Muat Hold</button>
                            <button type="button" class="btn-danger-lite" @click="renameSelectedHold()" :disabled="holds.length === 0 || !selectedHoldId">Ubah Nama Hold</button>
                            <button type="button" class="btn-danger-lite" @click="clearSelectedHold()" :disabled="holds.length === 0 || !selectedHoldId">Hapus Hold</button>
                            <select class="pos-input pos-input-standalone pos-hold-select" x-model="selectedHoldId" :disabled="holds.length === 0">
                                <option value="">Pilih Hold</option>
                                <template x-for="hold in holds" :key="hold.id">
                                    <option :value="hold.id" x-text="`${hold.label} (${hold.totalQty} qty)`"></option>
                                </template>
                            </select>
                            <select class="pos-input pos-input-standalone pos-hold-select" x-model="holdDateFilter" @change="syncHoldState()">
                                <option value="0">Semua Tanggal</option>
                                <option value="1">Hari Ini</option>
                                <option value="3">3 Hari</option>
                                <option value="7">7 Hari</option>
                                <option value="30">30 Hari</option>
                            </select>
                        </div>
                        <template x-if="activeHoldId">
                            <div class="text-xs text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-2 py-1">
                                Hold aktif: <strong x-text="activeHoldId"></strong>
                            </div>
                        </template>
                        <div class="pos-hold-note">
                            <p><strong>Simpan Hold:</strong> menyimpan transaksi sementara agar bisa dilanjutkan nanti.</p>
                            <p><strong>Muat Hold:</strong> memanggil kembali transaksi hold terpilih ke keranjang aktif.</p>
                            <p><strong>Hapus Hold:</strong> menghapus data hold terpilih dari daftar.</p>
                            <p><strong>Auto Bersih:</strong> hold yang berhasil checkout <strong>Lunas</strong> akan hilang otomatis.</p>
                        </div>
                        <div class="pos-hold-note">
                            <p><strong>5 Hold Terakhir:</strong></p>
                            <template x-if="holdRecentList().length === 0">
                                <p>- Belum ada hold tersimpan.</p>
                            </template>
                            <template x-for="h in holdRecentList()" :key="`recent-${h.id}`">
                                <p>
                                    <strong x-text="h.label"></strong>
                                    <span x-text="` | ${h.totalQty} qty | ${holdAgo(h.saved_at)}`"></span>
                                </p>
                            </template>
                        </div>

                        <div id="shift-rekonsiliasi" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold text-slate-700 mb-1">Rekonsiliasi Kas (Akhir Shift)</p>
                            <p class="text-[11px] text-slate-500 mb-2">Cocokkan kas fisik dengan kas sistem agar selisih cepat ketahuan.</p>
                            <p class="text-xs text-slate-500 mb-2">Perkiraan Kas: <strong>Rp <span x-text="money(shiftCashExpected)"></span></strong></p>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="number" min="0" step="0.01" x-model.number="shiftCashActual" class="pos-input pos-reconcile-input" placeholder="Kas aktual dihitung manual">
                                <input type="text" x-model="shiftReconcileNote" class="pos-input pos-reconcile-input" placeholder="Catatan selisih (opsional)">
                                <button type="button" class="btn-primary w-full pos-reconcile-btn" @click="submitReconcile()" :disabled="shiftReconSubmitting">
                                    <i data-feather="check-circle" class="w-4 h-4"></i>
                                    <span x-text="shiftReconSubmitting ? 'Menyimpan...' : 'Simpan Rekonsiliasi'"></span>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="panel-card mt-4 overflow-hidden">
                    <div class="panel-head">
                        <h3 class="text-base font-bold text-slate-900">5 Transaksi Terakhir</h3>
                    </div>
                    <div class="panel-body pos-recent-sales pos-recent-sales--list">
                        @forelse($recentSales as $sale)
                            <a href="{{ route('sales.show', $sale) }}" class="pos-recent-row">
                                <div class="min-w-0">
                                    <div class="pos-recent-title">{{ $sale->invoice_number }}</div>
                                    <div class="pos-recent-meta">{{ $sale->sold_at?->format('d/m H:i') }} | {{ $sale->user?->name }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="status-chip pos-recent-status {{ $sale->status->value === 'paid' ? 'status-paid' : ($sale->status->value === 'pending' ? 'status-pending' : 'status-cancelled') }}">
                                        {{ strtoupper($sale->status->value) }}
                                    </span>
                                    <button type="button" class="btn-danger-lite" @click.prevent="reprintWithReason({{ $sale->id }}, 'pdf')">Cetak Ulang</button>
                                </div>
                            </a>
                        @empty
                            <div class="pos-cart-empty">
                                <p>Belum ada transaksi.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="panel-card mt-4 overflow-hidden">
                    <div class="panel-head">
                        <h3 class="text-base font-bold text-slate-900">Ringkasan Shift Hari Ini</h3>
                    </div>
                    <div class="panel-body pos-recent-sales">
                        <div class="pos-cart-summary">
                            <div class="pos-cart-summary-item"><span>Total</span><strong>{{ number_format($shiftSummary['total_transactions'], 0, ',', '.') }}</strong></div>
                            <div class="pos-cart-summary-item"><span>Lunas</span><strong>{{ number_format($shiftSummary['paid_transactions'], 0, ',', '.') }}</strong></div>
                            <div class="pos-cart-summary-item"><span>Menunggu</span><strong>{{ number_format($shiftSummary['pending_transactions'], 0, ',', '.') }}</strong></div>
                            <div class="pos-cart-summary-item"><span>Omzet</span><strong>Rp {{ number_format($shiftSummary['omzet_paid'], 0, ',', '.') }}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="panel-card mt-4 overflow-hidden">
                    <div class="panel-head">
                        <h3 class="text-base font-bold text-slate-900">Retur Cepat POS</h3>
                    </div>
                    <div class="panel-body space-y-2">
                        <p class="text-xs text-slate-500">Retur penuh berdasarkan nomor invoice. Wajib persetujuan manajer.</p>
                        <input type="text" x-model="quickRefundInvoice" class="pos-input pos-input-standalone" placeholder="Contoh: INV-20260430-0003">
                        <input type="text" x-model="quickRefundReason" class="pos-input pos-input-standalone" placeholder="Alasan retur">
                        <button type="button" class="btn-danger-lite w-full !h-10 !text-sm" @click="submitQuickRefund()" :disabled="quickRefundSubmitting">
                            <span x-text="quickRefundSubmitting ? 'Memproses...' : 'Proses Retur Cepat'"></span>
                        </button>
                    </div>
                </div>

            </aside>
        </div>
    </div>

    @php
        $posPagination = $productPagination ?? ['current_page' => 1, 'last_page' => 1, 'per_page' => 18, 'total' => 0];
        $posCurrentPage = (int) ($posPagination['current_page'] ?? 1);
        $debtMapArray = collect($customerDebtMap ?? [])->mapWithKeys(function ($row, $customerId) {
            return [(int) $customerId => [
                'debt_count' => (int) ($row->debt_count ?? 0),
                'debt_total' => (float) ($row->debt_total ?? 0),
                'overdue_count' => (int) ($row->overdue_count ?? 0),
                'nearest_due_date' => !empty($row->nearest_due_date) ? (string) $row->nearest_due_date : null,
            ]];
        })->all();
        $customerMap = $customers->map(function ($c) use ($debtMapArray) {
            $debt = $debtMapArray[(int) $c->id] ?? ['debt_count' => 0, 'debt_total' => 0, 'overdue_count' => 0, 'nearest_due_date' => null];
            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'address' => $c->address,
                'debt_count' => (int) ($debt['debt_count'] ?? 0),
                'debt_total' => (float) ($debt['debt_total'] ?? 0),
                'overdue_count' => (int) ($debt['overdue_count'] ?? 0),
                'nearest_due_date' => $debt['nearest_due_date'] ?? null,
            ];
        })->values();
    @endphp
    <script>
        function posApp() {
            return {
                products: @json($products),
                pagination: @json($posPagination),
                currentPage: Number(@json($posCurrentPage)),
                cart: [],
                search: @json($q),
                categoryId: @json((string) ($categoryId ?? '0')),
                loading: false,
                loadError: '',
                discount: 0,
                tax: 0,
                paid: 0,
                status: 'paid',
                paymentMethod: 'cash',
                installmentEnabled: false,
                installmentTenorMonths: 3,
                installmentDownPayment: 0,
                installmentFirstDueDate: '',
                splitPaymentEnabled: false,
                splitPayments: [
                    { method: 'cash', amount: 0 },
                    { method: 'qris', amount: 0 },
                ],
                splitPaymentsJson: '',
                splitValidationError: '',
                methodLimitError: '',
                qrisReferenceId: '',
                qrisIssuer: '',
                qrisReferenceAuto: false,
                noteText: '',
                splitTotal: 0,
                splitDelta: 0,
                paymentMethodLimits: @json($paymentMethodLimits ?? []),
                paymentMethodOverpayRules: @json($paymentMethodOverpayRules ?? []),
                buyXGetYRules: @json($buyXGetYRules ?? []),
                quickPayPresets: @json($quickPayPresets ?? []),
                managerApprovalDiscountPct: Number(@json($managerApprovalDiscountPct ?? 30)),
                subtotal: 0,
                total: 0,
                change: 0,
                totalQty: 0,
                payShortfall: 0,
                itemsJson: '[]',
                barcodeMode: false,
                printAfterCheckout: true,
                openReceiptPdf: false,
                reprintEndpointBase: @json(url('/sales')),
                reconcileEndpoint: @json(route('pos.reconcile-shift')),
                shiftCashExpected: Number(@json($shiftSummary['cash_expected'] ?? 0)),
                shiftCashActual: 0,
                shiftReconcileNote: '',
                shiftReconSubmitting: false,
                holdsEndpoint: @json(route('pos.holds.index')),
                holds: [],
                selectedHoldId: '',
                holdDateFilter: '0',
                isSubmitting: false,
                activeHoldId: '',
                selectedCustomerId: '',
                debtMode: 'normal',
                selectedDebtExistingId: '',
                customerDebtLimit: Number(@json($customerDebtLimit ?? 20000000)),
                checkoutToken: '',
                soundEnabled: true,
                uiPrefKey: 'pos_ui_prefs_v1',
                onboardingKey: 'pos_onboarding_hidden_v1',
                showOnboarding: true,
                idleWarnMs: 4 * 60 * 1000,
                idleReloadMs: 6 * 60 * 1000,
                idleWarnTimer: null,
                idleReloadTimer: null,
                stockSyncMs: 30000,
                stockSyncTimer: null,
                toasts: [],
                nextToastId: 1,
                flashSuccess: @json(session('success')),
                flashWarnings: @json(session('stock_warning', [])),
                flashErrors: @json($errors->all()),
                authUserId: @json(auth()->id()),
                customerMap: @json($customerMap),
                recentProductKey: 'pos_recent_products_v1',
                recentProducts: [],
                favoriteProductKey: '',
                favoriteProductIds: [],
                showCheckoutSuccessModal: false,
                checkoutSuccessMessage: '',
                animProductId: null,
                freshCartItemIds: [],
                quickPayFlashKey: '',
                managerApprovalEmail: '',
                managerApprovalPassword: '',
                showManagerApprovalModal: false,
                managerApprovalDraftEmail: '',
                managerApprovalDraftPassword: '',
                managerApprovalTitle: 'Approval Manajer',
                managerApprovalDescription: '',
                managerApprovalPrimaryLabel: 'Lanjut Checkout',
                managerApprovalFlow: 'checkout',
                managerApprovalResolver: null,
                pendingManagerDiscountPct: 0,
                showConfirmModal: false,
                confirmModalTitle: '',
                confirmModalMessage: '',
                confirmModalMeta: null,
                confirmModalTone: 'info',
                confirmResolver: null,
                lastFocusedElement: null,
                showAlertModal: false,
                alertModalTitle: '',
                alertModalMessage: '',
                showInputModal: false,
                inputModalTitle: '',
                inputModalMessage: '',
                inputModalPlaceholder: '',
                inputModalValue: '',
                inputResolver: null,
                quickRefundEndpoint: @json(route('pos.quick-refund')),
                qrisMidtransCreateEndpoint: @json(route('pos.qris.midtrans.create')),
                qrisMidtransStatusEndpoint: @json(route('pos.qris.midtrans.status')),
                quickRefundInvoice: '',
                quickRefundReason: '',
                quickRefundSubmitting: false,
                qrisDynamicLoading: false,
                qrisDynamicError: '',
                qrisDynamicQrUrl: '',
                qrisDynamicOrderId: '',
                qrisDynamicStatus: '',
                qrisDynamicExpiryAt: '',
                barcodeAutoAddLock: false,
                barcodeLastHitCode: '',
                barcodeLastHitAt: 0,
                serverFallbackVisible: true,
                checkoutDraftKey: '',
                checkoutDraftLegacyKey: '',
                checkoutDraftSchemaVersion: 2,
                productFetchController: null,
                productFetchSeq: 0,

                initApp() {
                    this.checkoutToken = this.generateCheckoutToken();
                    if (!this.installmentFirstDueDate) {
                        const base = new Date();
                        base.setMonth(base.getMonth() + 1);
                        this.installmentFirstDueDate = base.toISOString().slice(0, 10);
                    }
                    this.favoriteProductKey = `pos_fav_products_v1_${this.authUserId || 'guest'}`;
                    this.checkoutDraftKey = `pos_checkout_draft_v2_${this.authUserId || 'guest'}`;
                    this.checkoutDraftLegacyKey = `pos_checkout_draft_v1_${this.authUserId || 'guest'}`;
                    this.loadUiPrefs();
                    this.loadOnboardingState();
                    this.restoreCheckoutDraft();
                    this.fetchProducts();
                    this.recalculate();
                    this.syncHoldState();
                    this.loadRecentProducts();
                    this.loadFavoriteProducts();
                    this.registerShortcuts();
                    this.registerIdleWatch();
                    this.startStockSync();
                    this.showFlashToasts();
                    this.bindManagerApprovalModal();
                },
                bindManagerApprovalModal() {
                    const cancelBtn = document.getElementById('manager-approval-cancel');
                    const submitBtn = document.getElementById('manager-approval-submit');
                    const emailInput = document.getElementById('manager-approval-email');
                    const passwordInput = document.getElementById('manager-approval-password');
                    if (cancelBtn && !cancelBtn.dataset.bound) {
                        cancelBtn.addEventListener('click', () => this.cancelManagerApproval());
                        cancelBtn.dataset.bound = '1';
                    }
                    if (submitBtn && !submitBtn.dataset.bound) {
                        submitBtn.addEventListener('click', () => this.confirmManagerApproval());
                        submitBtn.dataset.bound = '1';
                    }
                    if (passwordInput && !passwordInput.dataset.boundEnter) {
                        passwordInput.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                this.confirmManagerApproval();
                            }
                        });
                        passwordInput.dataset.boundEnter = '1';
                    }
                    if (emailInput && !emailInput.dataset.boundInput) {
                        emailInput.addEventListener('input', (event) => {
                            this.managerApprovalDraftEmail = String(event.target?.value || '');
                        });
                        emailInput.dataset.boundInput = '1';
                    }
                    if (passwordInput && !passwordInput.dataset.boundInput) {
                        passwordInput.addEventListener('input', (event) => {
                            this.managerApprovalDraftPassword = String(event.target?.value || '');
                        });
                        passwordInput.dataset.boundInput = '1';
                    }
                },
                syncManagerApprovalUi() {
                    const titleEl = document.getElementById('manager-approval-title');
                    const noteEl = document.getElementById('manager-approval-desc');
                    const submitBtn = document.getElementById('manager-approval-submit');
                    const emailInput = document.getElementById('manager-approval-email');
                    const passwordInput = document.getElementById('manager-approval-password');
                    if (titleEl) titleEl.textContent = String(this.managerApprovalTitle || 'Approval Manajer');
                    if (noteEl) noteEl.textContent = String(this.managerApprovalDescription || '');
                    if (submitBtn) submitBtn.textContent = String(this.managerApprovalPrimaryLabel || 'Lanjut Checkout');
                    if (emailInput) emailInput.value = String(this.managerApprovalDraftEmail || '');
                    if (passwordInput) passwordInput.value = String(this.managerApprovalDraftPassword || '');
                },

                money(value) {
                    return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                },
                numberFormat(value) {
                    return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                },

                qrisDynamicAmount() {
                    if (this.status !== 'paid') return 0;
                    if (this.splitPaymentEnabled) {
                        const row = (Array.isArray(this.splitPayments) ? this.splitPayments : [])
                            .find(item => String(item?.method || '') === 'qris');
                        return Math.max(0, Number(row?.amount || 0));
                    }
                    if (String(this.paymentMethod || '') === 'qris') {
                        return Math.max(0, Number(this.total || 0));
                    }

                    return 0;
                },

                async generateMidtransQrisSandbox() {
                    const amount = Math.round(Number(this.qrisDynamicAmount() || 0));
                    if (amount <= 0) {
                        this.pushToast('warning', 'QRIS Tidak Valid', 'Nominal QRIS harus lebih dari 0.');
                        return;
                    }
                    this.qrisDynamicLoading = true;
                    this.qrisDynamicError = '';
                    try {
                        const res = await fetch(this.qrisMidtransCreateEndpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                amount,
                                invoice_hint: this.checkoutToken || '',
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok || !data?.ok) {
                            const extra = data?.detail ? ` (${typeof data.detail === 'string' ? data.detail : JSON.stringify(data.detail)})` : '';
                            throw new Error(String(data?.message || 'Gagal membuat QRIS Midtrans Sandbox.') + extra);
                        }
                        this.qrisDynamicQrUrl = String(data.qr_url || '');
                        this.qrisDynamicOrderId = String(data.order_id || '');
                        this.qrisDynamicStatus = String(data.transaction_status || 'pending');
                        this.qrisDynamicExpiryAt = String(data.expiry_at || '');
                        this.qrisReferenceId = String(data.reference_id || data.order_id || this.qrisReferenceId || '');
                        this.qrisIssuer = String(data.issuer || 'midtrans-sandbox');
                        this.qrisReferenceAuto = true;
                        this.pushToast('success', 'QRIS Dibuat', 'QRIS Midtrans Sandbox berhasil dibuat.');
                    } catch (error) {
                        this.qrisDynamicError = String(error?.message || 'Gagal membuat QRIS Midtrans Sandbox.');
                        this.pushToast('error', 'Gagal Membuat QRIS', this.qrisDynamicError);
                    } finally {
                        this.qrisDynamicLoading = false;
                    }
                },

                async checkMidtransQrisSandboxStatus() {
                    if (!this.qrisDynamicOrderId) return;
                    this.qrisDynamicLoading = true;
                    this.qrisDynamicError = '';
                    try {
                        const url = `${this.qrisMidtransStatusEndpoint}?order_id=${encodeURIComponent(this.qrisDynamicOrderId)}`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const data = await res.json();
                        if (!res.ok || !data?.ok) {
                            const extra = data?.detail ? ` (${typeof data.detail === 'string' ? data.detail : JSON.stringify(data.detail)})` : '';
                            throw new Error(String(data?.message || 'Gagal cek status QRIS Midtrans Sandbox.') + extra);
                        }
                        this.qrisDynamicStatus = String(data.transaction_status || 'pending');
                        this.qrisDynamicExpiryAt = String(data.expiry_at || this.qrisDynamicExpiryAt || '');
                        this.qrisIssuer = String(data.issuer || this.qrisIssuer || 'midtrans-sandbox');
                        this.pushToast('info', 'Status QRIS', `Status terbaru: ${String(this.qrisDynamicStatus || '').toUpperCase()}`);
                    } catch (error) {
                        this.qrisDynamicError = String(error?.message || 'Gagal cek status QRIS Midtrans Sandbox.');
                        this.pushToast('error', 'Cek Status Gagal', this.qrisDynamicError);
                    } finally {
                        this.qrisDynamicLoading = false;
                    }
                },

                onPaidInput(event) {
                    const raw = String(event?.target?.value || '');
                    const digits = raw.replace(/[^0-9]/g, '');
                    this.paid = Number(digits || 0);
                    if (event?.target) {
                        event.target.value = this.money(this.paid);
                    }
                    this.recalculate();
                },

                formatQuickPayLabel(value) {
                    const n = Number(value || 0);
                    if (n >= 1000000) return `${Math.round(n / 1000000)}JT`;
                    if (n >= 1000) return `${Math.round(n / 1000)}K`;
                    return `${n}`;
                },

                displayProducts() {
                    const fav = new Set((this.favoriteProductIds || []).map(Number));
                    return [...(this.products || [])].sort((a, b) => {
                        const af = fav.has(Number(a.id)) ? 1 : 0;
                        const bf = fav.has(Number(b.id)) ? 1 : 0;
                        if (af !== bf) return bf - af;
                        return String(a.name || '').localeCompare(String(b.name || ''));
                    });
                },

                fetchProducts() {
                    if (this.productFetchController) {
                        try {
                            this.productFetchController.abort();
                        } catch (e) {}
                    }
                    const controller = new AbortController();
                    this.productFetchController = controller;
                    const seq = ++this.productFetchSeq;
                    this.loading = true;
                    this.loadError = '';
                    const url = `{{ route('pos.search-products') }}?q=${encodeURIComponent(this.search || '')}&category_id=${encodeURIComponent(this.categoryId || 0)}&page=${encodeURIComponent(this.currentPage || 1)}`;
                    fetch(url, {
                        signal: controller.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(r => {
                            if (!r.ok) {
                                throw new Error(`HTTP ${r.status}`);
                            }
                            return r.json();
                        })
                        .then(payload => {
                            if (seq !== this.productFetchSeq) return;
                            this.products = Array.isArray(payload?.data) ? payload.data : [];
                            this.pagination = payload?.meta || { current_page: 1, last_page: 1, per_page: 18, total: this.products.length };
                            this.currentPage = Number(this.pagination.current_page || 1);

                            // Fallback aman: jika kategori tersimpan sudah tidak relevan dan hasil 0,
                            // reset ke "Semua Kategori" agar produk langsung tampil lagi.
                            const total = Number(this.pagination.total || 0);
                            const hasSearch = String(this.search || '').trim() !== '';
                            const hasCategoryFilter = String(this.categoryId || '0') !== '0';
                            if (total === 0 && !hasSearch && hasCategoryFilter) {
                                this.categoryId = '0';
                                this.saveUiPrefs();
                                this.fetchProducts();
                                return;
                            }

                            // Jika halaman yang diminta melebihi total halaman, balik ke halaman 1.
                            const lastPage = Number(this.pagination.last_page || 1);
                            if (this.currentPage > lastPage && lastPage >= 1) {
                                this.currentPage = 1;
                                this.fetchProducts();
                                return;
                            }

                            this.tryBarcodeAutoAdd();
                            this.reconcileCartStock();
                            this.serverFallbackVisible = false;
                            this.refreshFeather();
                        })
                        .catch((e) => {
                            if (e?.name === 'AbortError') return;
                            if (seq !== this.productFetchSeq) return;
                            this.loadError = 'Gagal memuat daftar produk. Periksa koneksi lalu coba lagi.';
                            this.pushToast('error', 'Gagal Memuat Produk', this.loadError);
                        })
                        .finally(() => {
                            if (seq !== this.productFetchSeq) return;
                            this.loading = false;
                            if (this.productFetchController === controller) {
                                this.productFetchController = null;
                            }
                            this.refreshFeather();
                        });
                },

                clearSearch() {
                    this.search = '';
                    this.currentPage = 1;
                    this.fetchProducts();
                },

                searchProducts() {
                    this.currentPage = 1;
                    this.fetchProducts();
                },

                changePage(step) {
                    const nextPage = Number(this.currentPage || 1) + Number(step || 0);
                    if (nextPage < 1 || nextPage > Number(this.pagination.last_page || 1)) return;
                    this.currentPage = nextPage;
                    this.fetchProducts();
                },

                goToPage(page) {
                    const targetPage = Number(page || 1);
                    if (!Number.isFinite(targetPage)) return;
                    if (targetPage < 1 || targetPage > Number(this.pagination.last_page || 1)) return;
                    if (targetPage === Number(this.currentPage || 1)) return;
                    this.currentPage = targetPage;
                    this.fetchProducts();
                },

                paginationWindow() {
                    const current = Number(this.pagination.current_page || 1);
                    const last = Number(this.pagination.last_page || 1);
                    const start = Math.max(1, current - 2);
                    const end = Math.min(last, current + 2);
                    const pages = [];
                    for (let i = start; i <= end; i++) pages.push(i);
                    return pages;
                },

                paginationSummary() {
                    const total = Number(this.pagination.total || 0);
                    const perPage = Number(this.pagination.per_page || 18);
                    const current = Number(this.pagination.current_page || 1);
                    if (total <= 0) return `Menampilkan 0 produk`;
                    const from = ((current - 1) * perPage) + 1;
                    const to = Math.min(current * perPage, total);
                    return `Menampilkan ${from}-${to} dari ${total} produk`;
                },

                fillCustomerMeta() {
                    const raw = (this.$refs.customerNameInput?.value || '').trim().toLowerCase();
                    if (!raw) {
                        this.selectedCustomerId = '';
                        this.debtMode = 'normal';
                        return;
                    }
                    const found = (this.customerMap || []).find(c => {
                        const name = String(c.name || '').trim().toLowerCase();
                        const phone = String(c.phone || '').trim().toLowerCase();
                        const email = String(c.email || '').trim().toLowerCase();
                        return raw === name || raw === phone || raw === email;
                    });
                    if (!found) {
                        this.selectedCustomerId = '';
                        this.debtMode = 'normal';
                        return;
                    }
                    this.selectedCustomerId = String(found.id || '');
                    this.debtMode = Number(found.debt_count || 0) > 0 ? 'partial' : 'normal';
                    if (this.$refs.customerNameInput && found.name) this.$refs.customerNameInput.value = found.name;
                    if (this.$refs.customerPhoneInput) this.$refs.customerPhoneInput.value = found.phone || '';
                    if (this.$refs.customerEmailInput) this.$refs.customerEmailInput.value = found.email || '';
                    if (this.$refs.customerAddressInput) this.$refs.customerAddressInput.value = found.address || '';
                },
                get selectedCustomer() {
                    return (this.customerMap || []).find(c => String(c.id) === String(this.selectedCustomerId || '')) || null;
                },
                get hasOutstandingDebt() {
                    return Number(this.selectedCustomer?.debt_count || 0) > 0;
                },
                get customerDebtCount() {
                    return Number(this.selectedCustomer?.debt_count || 0);
                },
                get customerDebtTotal() {
                    return Number(this.selectedCustomer?.debt_total || 0);
                },
                get customerOverdueCount() {
                    return Number(this.selectedCustomer?.overdue_count || 0);
                },
                get customerNearestDueDate() {
                    const raw = String(this.selectedCustomer?.nearest_due_date || '').trim();
                    if (!raw) return '-';
                    const date = new Date(raw + 'T00:00:00');
                    if (Number.isNaN(date.getTime())) return raw;
                    return date.toLocaleDateString('id-ID');
                },
                get projectedDebtAfterCheckout() {
                    const base = this.customerDebtTotal;
                    if (!this.hasOutstandingDebt || this.debtMode === 'normal') return base;
                    if (this.debtMode === 'merge') return base + Number(this.total || 0);
                    const remaining = Math.max(Number(this.total || 0) - Number(this.paid || 0), 0);
                    return base + remaining;
                },

                loadUiPrefs() {
                    try {
                        const raw = localStorage.getItem(this.uiPrefKey);
                        if (!raw) return;
                        const pref = JSON.parse(raw);
                        this.barcodeMode = Boolean(pref.barcodeMode ?? this.barcodeMode);
                        this.printAfterCheckout = Boolean(pref.printAfterCheckout ?? this.printAfterCheckout);
                        this.openReceiptPdf = Boolean(pref.openReceiptPdf ?? this.openReceiptPdf);
                        this.soundEnabled = Boolean(pref.soundEnabled ?? this.soundEnabled);
                        // categoryId sengaja tidak dipersist agar daftar produk tidak terkunci
                        // ke filter lama yang membuat hasil kosong.
                        this.categoryId = String(this.categoryId ?? '0');
                        this.paymentMethod = String(pref.paymentMethod ?? this.paymentMethod ?? 'cash');
                        this.splitPaymentEnabled = Boolean(pref.splitPaymentEnabled ?? this.splitPaymentEnabled);
                    } catch (e) {}
                },

                loadOnboardingState() {
                    try {
                        this.showOnboarding = localStorage.getItem(this.onboardingKey) !== '1';
                    } catch (e) {
                        this.showOnboarding = true;
                    }
                },

                dismissOnboarding() {
                    this.showOnboarding = false;
                    try {
                        localStorage.setItem(this.onboardingKey, '1');
                    } catch (e) {}
                },

                saveUiPrefs() {
                    try {
                        localStorage.setItem(this.uiPrefKey, JSON.stringify({
                            barcodeMode: this.barcodeMode,
                            printAfterCheckout: this.printAfterCheckout,
                            openReceiptPdf: this.openReceiptPdf,
                            soundEnabled: this.soundEnabled,
                        paymentMethod: this.paymentMethod,
                        splitPaymentEnabled: this.splitPaymentEnabled,
                    }));
                    } catch (e) {}
                },

                addProductById(productId) {
                    const id = Number(productId);
                    const product = (this.products || []).find(row => Number(row.id) === id);
                    if (!product) {
                        this.pushToast('error', 'Produk Tidak Siap', 'Muat ulang halaman lalu coba lagi.');
                        return;
                    }
                    this.addToCart(product);
                },

                addToCart(product) {
                    if (product.stock <= 0) return;

                    const existing = this.cart.find(i => i.id === product.id);
                    if (existing) {
                        if (existing.quantity < product.stock) existing.quantity++;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            sku: product.sku,
                            stock: Number(product.stock),
                            price: Number(product.selling_price),
                            quantity: 1,
                            manual_discount_amount: 0,
                            auto_promo_discount: 0,
                            discount_amount: 0,
                        });
                    }
                    this.recalculate();
                    this.refreshFeather();
                    this.focusSearch();
                    this.touchRecentProduct(product);
                    this.triggerProductAddFx(product.id);
                    this.markCartItemFresh(product.id);
                    if (this.soundEnabled) this.beep(760, 0.05);
                },

                triggerProductAddFx(productId) {
                    this.animProductId = Number(productId);
                    setTimeout(() => {
                        if (Number(this.animProductId) === Number(productId)) {
                            this.animProductId = null;
                        }
                    }, 320);
                },

                markCartItemFresh(productId) {
                    const id = Number(productId);
                    this.freshCartItemIds = [id, ...this.freshCartItemIds.filter(v => Number(v) !== id)].slice(0, 20);
                    setTimeout(() => {
                        this.freshCartItemIds = this.freshCartItemIds.filter(v => Number(v) !== id);
                    }, 1200);
                },

                isCartItemFresh(productId) {
                    return this.freshCartItemIds.includes(Number(productId));
                },

                touchRecentProduct(product) {
                    const payload = {
                        id: Number(product.id),
                        name: String(product.name || ''),
                        sku: String(product.sku || ''),
                        stock: Number(product.stock || 0),
                        selling_price: Number(product.selling_price || 0),
                        image: product.image || null,
                    };
                    const filtered = this.recentProducts.filter(p => Number(p.id) !== payload.id);
                    this.recentProducts = [payload, ...filtered].slice(0, 8);
                    this.saveRecentProducts();
                },

                loadRecentProducts() {
                    try {
                        const raw = localStorage.getItem(this.recentProductKey);
                        const parsed = raw ? JSON.parse(raw) : [];
                        this.recentProducts = Array.isArray(parsed) ? parsed.slice(0, 8) : [];
                    } catch (e) {
                        this.recentProducts = [];
                    }
                },

                loadFavoriteProducts() {
                    try {
                        const raw = localStorage.getItem(this.favoriteProductKey);
                        const parsed = raw ? JSON.parse(raw) : [];
                        this.favoriteProductIds = Array.isArray(parsed) ? parsed.map(Number).filter(v => Number.isFinite(v)) : [];
                    } catch (e) {
                        this.favoriteProductIds = [];
                    }
                },

                saveFavoriteProducts() {
                    try {
                        localStorage.setItem(this.favoriteProductKey, JSON.stringify(this.favoriteProductIds.slice(0, 200)));
                    } catch (e) {}
                },

                isFavorite(productId) {
                    return this.favoriteProductIds.includes(Number(productId));
                },

                toggleFavorite(productId) {
                    const id = Number(productId);
                    if (this.isFavorite(id)) {
                        this.favoriteProductIds = this.favoriteProductIds.filter(v => Number(v) !== id);
                    } else {
                        this.favoriteProductIds = [id, ...this.favoriteProductIds.filter(v => Number(v) !== id)];
                    }
                    this.saveFavoriteProducts();
                    this.refreshFeather();
                },

                saveRecentProducts() {
                    try {
                        localStorage.setItem(this.recentProductKey, JSON.stringify(this.recentProducts.slice(0, 8)));
                    } catch (e) {}
                },

                clearRecentProducts() {
                    this.recentProducts = [];
                    try {
                        localStorage.removeItem(this.recentProductKey);
                    } catch (e) {}
                    this.pushToast('warning', 'Akses Cepat Dihapus', 'Daftar produk terakhir sudah dibersihkan.');
                },

                addFirstProduct() {
                    if (this.loading || this.products.length === 0) return;
                    const query = (this.search || '').trim().toLowerCase();
                    let pick = this.products[0];
                    if (this.barcodeMode && query !== '') {
                        const exact = this.products.find(p =>
                            String(p.barcode || '').toLowerCase() === query ||
                            String(p.sku || '').toLowerCase() === query
                        );
                        pick = exact || null;
                    }

                    if (!pick) {
                        this.pushToast('warning', 'Produk Tidak Ditemukan', 'Barcode/SKU tidak cocok.');
                        if (this.soundEnabled) this.beep(220, 0.08);
                        return;
                    }

                    this.addToCart(pick);
                    this.pushToast('success', 'Produk Ditambahkan', pick.name);
                    // Jangan reset query otomatis agar hasil pencarian tidak "kedip hilang".
                    // Kasir bisa scan/ketik berikutnya secara manual sesuai kebutuhan.
                },

                tryBarcodeAutoAdd() {
                    const query = String(this.search || '').trim().toLowerCase();
                    if (!this.barcodeMode || !query || this.barcodeAutoAddLock) return;
                    const now = Date.now();
                    if (this.barcodeLastHitCode === query && (now - Number(this.barcodeLastHitAt || 0)) < 1500) return;
                    const exact = (this.products || []).find(p =>
                        String(p.barcode || '').toLowerCase() === query ||
                        String(p.sku || '').toLowerCase() === query
                    );
                    if (!exact) return;
                    this.barcodeAutoAddLock = true;
                    this.barcodeLastHitCode = query;
                    this.barcodeLastHitAt = now;
                    this.addToCart(exact);
                    this.pushToast('success', 'Scan Berhasil', `${exact.name} masuk keranjang.`);
                    setTimeout(() => {
                        this.barcodeAutoAddLock = false;
                    }, 120);
                },

                removeItem(id) {
                    this.cart = this.cart.filter(i => i.id !== id);
                    this.recalculate();
                    this.refreshFeather();
                },

                clearCart() {
                    if (this.cart.length === 0) return;
                    this.cart = [];
                    this.activeHoldId = '';
                    this.noteText = '';
                    this.qrisReferenceId = '';
                    this.qrisIssuer = '';
                    this.qrisReferenceAuto = false;
                    this.qrisDynamicError = '';
                    this.qrisDynamicQrUrl = '';
                    this.qrisDynamicOrderId = '';
                    this.qrisDynamicStatus = '';
                    this.qrisDynamicExpiryAt = '';
                    this.recalculate();
                    this.clearCheckoutDraft();
                    this.pushToast('warning', 'Keranjang Dikosongkan', 'Semua item dihapus dari keranjang.');
                    this.sendAudit('cart_cleared', { at: new Date().toISOString() });
                },

                async voidBeforeFinalize() {
                    if (this.cart.length === 0) return;
                    if (!await this.askConfirm('Batalkan Transaksi', 'Batalkan transaksi ini sebelum disimpan? Keranjang dan input pembayaran akan direset.')) return;
                    const reasonInput = await this.askInput('Alasan Void', 'Isi alasan void (wajib diisi singkat).', 'Salah input kasir', 'Contoh: salah input kasir');
                    const reason = String(reasonInput || '').trim();
                    if (!reason) {
                        this.pushToast('warning', 'Void Dibatalkan', 'Alasan void wajib diisi.');
                        return;
                    }
                    this.cart = [];
                    this.activeHoldId = '';
                    this.noteText = '';
                    this.discount = 0;
                    this.tax = 0;
                    this.paid = 0;
                    this.status = 'paid';
                    this.paymentMethod = 'cash';
                    this.splitPaymentEnabled = false;
                    this.splitPayments = [
                        { method: 'cash', amount: 0 },
                        { method: 'qris', amount: 0 },
                    ];
                    this.qrisReferenceId = '';
                    this.qrisIssuer = '';
                    this.qrisReferenceAuto = false;
                    this.qrisDynamicError = '';
                    this.qrisDynamicQrUrl = '';
                    this.qrisDynamicOrderId = '';
                    this.qrisDynamicStatus = '';
                    this.qrisDynamicExpiryAt = '';
                    this.splitValidationError = '';
                    this.methodLimitError = '';
                    this.recalculate();
                    this.checkoutToken = this.generateCheckoutToken();
                    this.clearCheckoutDraft();
                    this.pushToast('warning', 'Transaksi Dibatalkan', 'Keranjang direset sebelum checkout.');
                    this.sendAudit('cart_cleared', {
                        at: new Date().toISOString(),
                        reason: 'void_before_finalize',
                        reason_text: reason
                    });
                    if (this.soundEnabled) this.beep(320, 0.08);
                    this.refreshFeather();
                },

                appendNotePreset(text) {
                    const current = String(this.noteText || '').trim();
                    if (current === '') {
                        this.noteText = text;
                        return;
                    }
                    if (!current.toLowerCase().includes(text.toLowerCase())) {
                        this.noteText = `${current}; ${text}`;
                    }
                },
                requiresQrisReference() {
                    if (this.status !== 'paid') return false;
                    if (this.splitPaymentEnabled) {
                        return (Array.isArray(this.splitPayments) ? this.splitPayments : [])
                            .some(row => String(row?.method || '') === 'qris' && Number(row?.amount || 0) > 0);
                    }
                    return String(this.paymentMethod || '') === 'qris';
                },
                ensureQrisReferenceFallback() {
                    if (!this.requiresQrisReference()) return;
                    const current = String(this.qrisReferenceId || '').trim();
                    if (current !== '') return;
                    const stamp = new Date();
                    const ts = `${stamp.getFullYear()}${String(stamp.getMonth() + 1).padStart(2, '0')}${String(stamp.getDate()).padStart(2, '0')}${String(stamp.getHours()).padStart(2, '0')}${String(stamp.getMinutes()).padStart(2, '0')}${String(stamp.getSeconds()).padStart(2, '0')}`;
                    const token = String(this.checkoutToken || '').replace(/^pos-/, '').slice(0, 8).toUpperCase();
                    this.qrisReferenceId = `AUTO-DRAFT-${token || 'POS'}-${ts}`;
                    this.qrisReferenceAuto = true;
                },

                async saveHold() {
                    if (this.cart.length === 0) return;
                    let holdId = `HOLD-${Date.now()}`;
                    let overwrite = false;
                    if (this.selectedHoldId) {
                        overwrite = await this.askConfirm('Timpa Hold', `Timpa hold ${this.selectedHoldId} dengan keranjang saat ini?`);
                        if (overwrite) holdId = this.selectedHoldId;
                    }
                    const payload = {
                        id: holdId,
                        label: holdId,
                        cart: this.cart,
                        discount: Number(this.discount || 0),
                        tax: Number(this.tax || 0),
                        paid: Number(this.paid || 0),
                        status: this.status,
                        payment_method: this.paymentMethod,
                        split_payment_enabled: this.splitPaymentEnabled,
                        split_payments: this.splitPayments.map(row => ({
                            method: row.method,
                            amount: Number(row.amount || 0),
                        })),
                        qris_reference_id: String(this.qrisReferenceId || ''),
                        qris_issuer: String(this.qrisIssuer || ''),
                        customer_id: this.selectedCustomerId || '',
                        customer_name: document.querySelector('input[name=\"customer_name\"]')?.value || '',
                        customer_phone: this.$refs.customerPhoneInput?.value || '',
                        customer_email: this.$refs.customerEmailInput?.value || '',
                        customer_address: this.$refs.customerAddressInput?.value || '',
                        note: String(this.noteText || ''),
                        saved_at: new Date().toISOString()
                    };
                    try {
                        const response = await fetch(this.holdsEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload),
                        });
                        const result = await response.json();
                        if (!response.ok || !result?.ok) {
                            throw new Error(this.extractApiError(result, `HTTP ${response.status}`, 'Gagal menyimpan hold'));
                        }
                        await this.syncHoldState();
                        this.selectedHoldId = String(result?.data?.id || holdId);
                        this.pushToast('success', 'Hold Disimpan', `${this.selectedHoldId} berhasil disimpan.`);
                        this.sendAudit(overwrite ? 'hold_overwritten' : 'hold_saved', { hold_id: this.selectedHoldId, total_qty: this.totalQty });
                    } catch (e) {
                        this.pushToast('error', 'Gagal Simpan Hold', String(e?.message || 'Coba ulang sebentar lagi.'));
                    }
                },

                async loadSelectedHold() {
                    if (!this.selectedHoldId) return;
                    if (this.cart.length > 0 && !await this.askConfirm('Muat Hold', 'Keranjang saat ini akan diganti dengan data hold terpilih. Lanjutkan?')) {
                        return;
                    }
                    try {
                        const response = await fetch(`${this.holdsEndpoint}/${encodeURIComponent(this.selectedHoldId)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            throw new Error(this.extractApiError(result, `HTTP ${response.status}`, 'Gagal memuat hold.'));
                        }
                        const payload = result?.data || null;
                        if (!payload) throw new Error('Data hold tidak ditemukan.');
                        this.cart = Array.isArray(payload.cart)
                            ? payload.cart.map(item => ({
                                ...item,
                                manual_discount_amount: Number(item.manual_discount_amount ?? (item.discount_amount || 0)),
                                auto_promo_discount: Number(item.auto_promo_discount || 0),
                                discount_amount: Number(item.discount_amount || 0),
                            }))
                            : [];
                        this.discount = Number(payload.discount || 0);
                        this.tax = Number(payload.tax || 0);
                        this.paid = Number(payload.paid || 0);
                        this.status = payload.status || 'paid';
                        this.paymentMethod = payload.payment_method || 'cash';
                        this.splitPaymentEnabled = Boolean(payload.split_payment_enabled || false);
                        if (Array.isArray(payload.split_payments) && payload.split_payments.length > 0) {
                            this.splitPayments = payload.split_payments.map(row => ({
                                method: row.method || 'cash',
                                amount: Number(row.amount || 0),
                            }));
                        } else {
                            this.splitPayments = [
                                { method: payload.split_method_1 || 'cash', amount: Number(payload.split_amount_1 || 0) },
                                { method: payload.split_method_2 || 'qris', amount: Number(payload.split_amount_2 || 0) },
                            ];
                        }
                        if (this.splitPayments.length < 2) {
                            this.splitPayments.push({ method: 'qris', amount: 0 });
                        }
                        this.qrisReferenceId = String(payload.qris_reference_id || '');
                        this.qrisIssuer = String(payload.qris_issuer || '');
                        this.qrisReferenceAuto = false;
                        const customerEl = document.querySelector('input[name=\"customer_name\"]');
                        if (customerEl) customerEl.value = payload.customer_name || '';
                        if (this.$refs.customerPhoneInput) this.$refs.customerPhoneInput.value = payload.customer_phone || '';
                        if (this.$refs.customerEmailInput) this.$refs.customerEmailInput.value = payload.customer_email || '';
                        if (this.$refs.customerAddressInput) this.$refs.customerAddressInput.value = payload.customer_address || '';
                        this.noteText = String(payload.note || '');
                        this.selectedCustomerId = String(payload.customer_id || '');
                        this.fillCustomerMeta();
                        this.activeHoldId = payload.id || '';
                        this.recalculate();
                        this.refreshFeather();
                        this.pushToast('success', 'Hold Dimuat', `${payload.label} berhasil dimuat.`);
                        this.sendAudit('hold_loaded', { hold_id: payload.id || null, total_qty: this.totalQty });
                    } catch (e) {
                        this.pushToast('error', 'Hold Rusak', String(e?.message || 'Data hold tidak valid.'));
                    }
                },

                async clearSelectedHold() {
                    if (!this.selectedHoldId) return;
                    if (!await this.askConfirm('Hapus Hold', 'Hapus hold terpilih? Tindakan ini tidak bisa dibatalkan.')) {
                        return;
                    }
                    const removed = this.selectedHoldId;
                    try {
                        const response = await fetch(`${this.holdsEndpoint}/${encodeURIComponent(removed)}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                            }
                        });
                        if (!response.ok) {
                            let payload = null;
                            try { payload = await response.json(); } catch (_) {}
                            throw new Error(this.extractApiError(payload, `HTTP ${response.status}`, 'Data hold tidak bisa dihapus.'));
                        }
                        await this.syncHoldState();
                        this.pushToast('warning', 'Hold Dihapus', `${removed} telah dihapus.`);
                        this.sendAudit('hold_deleted', { hold_id: removed });
                    } catch (e) {
                        this.pushToast('error', 'Gagal Hapus Hold', String(e?.message || 'Data hold tidak bisa dihapus.'));
                    }
                },

                async renameSelectedHold() {
                    if (!this.selectedHoldId) return;
                    const current = this.holds.find(h => h.id === this.selectedHoldId);
                    const currentLabel = String(current?.label || this.selectedHoldId);
                    const nextLabel = await this.askInput('Ubah Nama Hold', 'Masukkan nama hold baru.', currentLabel, 'Nama hold');
                    if (nextLabel === null) return;
                    const label = nextLabel.trim();
                    if (!label) {
                        this.pushToast('warning', 'Nama Hold Kosong', 'Nama hold tidak boleh kosong.');
                        return;
                    }
                    try {
                        const response = await fetch(`${this.holdsEndpoint}/${encodeURIComponent(this.selectedHoldId)}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({ label }),
                        });
                        const result = await response.json();
                        if (!response.ok || !result?.ok) throw new Error(this.extractApiError(result, `HTTP ${response.status}`, 'Gagal ubah nama hold.'));
                        await this.syncHoldState();
                        this.pushToast('success', 'Hold Diubah', `Nama hold menjadi "${label}".`);
                        this.sendAudit('hold_renamed', { hold_id: this.selectedHoldId, label });
                    } catch (e) {
                        this.pushToast('error', 'Gagal Ubah Nama Hold', String(e?.message || 'Coba ulang sebentar lagi.'));
                    }
                },

                async syncHoldState() {
                    const days = Number(this.holdDateFilter || 0);
                    const url = `${this.holdsEndpoint}?days=${encodeURIComponent(days)}`;
                    return fetch(url)
                        .then(async r => {
                            const payload = await r.json().catch(() => ({}));
                            if (!r.ok) {
                                throw new Error(this.extractApiError(payload, `HTTP ${r.status}`, 'Gagal sinkron hold.'));
                            }
                            return payload;
                        })
                        .then(payload => {
                            const rows = Array.isArray(payload?.data) ? payload.data : [];
                            this.holds = rows.map(h => ({
                                ...h,
                                totalQty: Number(h.totalQty || (Array.isArray(h.cart) ? h.cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0) : 0)),
                            }));
                            const selectedStillExists = this.holds.some(h => h.id === this.selectedHoldId);
                            if (!selectedStillExists) {
                                this.selectedHoldId = this.holds[0]?.id || '';
                            }
                        })
                        .catch((e) => {
                            this.holds = [];
                            this.selectedHoldId = '';
                            this.pushToast('error', 'Hold Sync Gagal', String(e?.message || 'Gagal sinkron data hold.'));
                        });
                },

                increaseQty(item) {
                    if (item.quantity < item.stock) {
                        item.quantity++;
                        this.recalculate();
                    }
                },

                decreaseQty(item) {
                    if (item.quantity > 1) {
                        item.quantity--;
                        this.recalculate();
                    }
                },

                recalculate() {
                    if (this.installmentEnabled) {
                        this.status = 'pending';
                        this.paymentMethod = 'installment';
                    }
                    this.cart.forEach(item => {
                        if (item.quantity < 1) item.quantity = 1;
                        if (item.quantity > item.stock) item.quantity = item.stock;
                        item.manual_discount_amount = Math.max(Number(item.manual_discount_amount ?? item.discount_amount ?? 0), 0);
                        item.auto_promo_discount = this.computeAutoPromoDiscount(item);
                        item.discount_amount = Number(item.manual_discount_amount || 0) + Number(item.auto_promo_discount || 0);
                        const maxDiscount = item.price * item.quantity;
                        if (item.discount_amount > maxDiscount) item.discount_amount = maxDiscount;
                    });

                    this.subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    const itemDiscountTotal = this.cart.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0);
                    this.totalQty = this.cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
                    const baseTotal = Math.max(this.subtotal - itemDiscountTotal - Number(this.discount || 0) + Number(this.tax || 0), 0);
                    this.total = Math.max(Number(baseTotal || 0), 0);
                    if (this.status !== 'paid') {
                        this.splitPaymentEnabled = false;
                        this.splitValidationError = '';
                        this.methodLimitError = '';
                        this.splitPaymentsJson = '';
                        this.splitTotal = 0;
                        this.splitDelta = 0;
                        this.payShortfall = 0;
                        this.change = 0;
                        this.paid = this.installmentEnabled ? Math.max(Number(this.installmentDownPayment || 0), 0) : 0;
                        if (this.installmentEnabled && this.paid > this.total) {
                            this.paid = this.total;
                            this.installmentDownPayment = this.total;
                        }
                        this.qrisReferenceAuto = false;
                        this.itemsJson = JSON.stringify(this.cart.map(item => ({
                            product_id: item.id,
                            quantity: item.quantity,
                            discount_amount: Number(item.discount_amount || 0),
                        })));
                        return;
                    }
                    if (this.status === 'paid' && this.splitPaymentEnabled) {
                        this.splitValidationError = '';
                        this.methodLimitError = '';
                        this.splitPayments = (Array.isArray(this.splitPayments) ? this.splitPayments : []).map((row, idx) => ({
                            method: row?.method || (idx === 0 ? 'cash' : 'qris'),
                            amount: Math.max(Number(row?.amount || 0), 0),
                        })).slice(0, 4);
                        if (this.splitPayments.length < 2) {
                            this.splitPayments.push({ method: 'qris', amount: 0 });
                        }
                        const methods = this.splitPayments.map(row => row.method);
                        const unique = new Set(methods);
                        if (unique.size !== methods.length) {
                            this.splitValidationError = 'Metode split tidak boleh duplikat.';
                        }
                        const invalidAmount = this.splitPayments.some(row => Number(row.amount || 0) <= 0);
                        if (invalidAmount) {
                            this.splitValidationError = 'Nominal tiap metode split harus lebih dari 0.';
                        }
                        for (const row of this.splitPayments) {
                            const limit = Number(this.paymentMethodLimits?.[row.method] || 0);
                            if (limit > 0 && Number(row.amount || 0) > limit) {
                                this.methodLimitError = `Nominal ${String(row.method).toUpperCase()} melebihi batas Rp ${this.money(limit)}.`;
                                break;
                            }
                            const nonCash = ['qris', 'debit', 'transfer', 'e_wallet'];
                            if (nonCash.includes(String(row.method || '')) && Number(row.amount || 0) > this.total) {
                                this.methodLimitError = `Nominal ${String(row.method).toUpperCase()} tidak boleh melebihi total transaksi.`;
                                break;
                            }
                            const hasOverpayRule = Object.prototype.hasOwnProperty.call(this.paymentMethodOverpayRules || {}, row.method);
                            const maxOverpay = Number(this.paymentMethodOverpayRules?.[row.method]?.max_overpay || 0);
                            if (hasOverpayRule && (Number(row.amount || 0) - this.total) > maxOverpay) {
                                this.methodLimitError = `Overpay ${String(row.method).toUpperCase()} melebihi batas Rp ${this.money(maxOverpay)}.`;
                                break;
                            }
                        }
                        const splitTotal = this.splitPayments.reduce((sum, row) => sum + Number(row.amount || 0), 0);
                        this.paid = splitTotal;
                        this.splitTotal = splitTotal;
                        this.splitDelta = splitTotal - this.total;
                        this.splitPaymentsJson = JSON.stringify(this.splitPayments.filter(row => Number(row.amount || 0) > 0));
                    } else {
                        this.splitValidationError = '';
                        this.splitPaymentsJson = '';
                        this.methodLimitError = '';
                        this.splitTotal = 0;
                        this.splitDelta = 0;
                        const activeLimit = Number(this.paymentMethodLimits?.[this.paymentMethod] || 0);
                        if (activeLimit > 0 && Number(this.paid || 0) > activeLimit) {
                            this.methodLimitError = `Nominal ${String(this.paymentMethod).toUpperCase()} melebihi batas Rp ${this.money(activeLimit)}.`;
                        }
                        const nonCash = ['qris', 'debit', 'transfer', 'e_wallet'];
                        if (nonCash.includes(String(this.paymentMethod || '')) && Number(this.paid || 0) > this.total) {
                            this.methodLimitError = `Nominal ${String(this.paymentMethod).toUpperCase()} tidak boleh melebihi total transaksi.`;
                        }
                        const hasOverpayRule = Object.prototype.hasOwnProperty.call(this.paymentMethodOverpayRules || {}, this.paymentMethod);
                        const maxOverpay = Number(this.paymentMethodOverpayRules?.[this.paymentMethod]?.max_overpay || 0);
                        if (hasOverpayRule && (Number(this.paid || 0) - this.total) > maxOverpay) {
                            this.methodLimitError = `Overpay ${String(this.paymentMethod).toUpperCase()} melebihi batas Rp ${this.money(maxOverpay)}.`;
                        }
                    }
                    if (this.requiresQrisReference()) {
                        this.ensureQrisReferenceFallback();
                    } else {
                        this.qrisReferenceAuto = false;
                    }
                    this.change = this.status === 'paid' ? Math.max(Number(this.paid || 0) - this.total, 0) : 0;
                    this.payShortfall = this.status === 'paid' ? Math.max(this.total - Number(this.paid || 0), 0) : 0;

                    this.itemsJson = JSON.stringify(this.cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        discount_amount: Number(item.discount_amount || 0),
                    })));
                    this.saveCheckoutDraft();
                },

                onToggleInstallment() {
                    if (this.installmentEnabled) {
                        this.status = 'pending';
                        this.paymentMethod = 'installment';
                        this.splitPaymentEnabled = false;
                    } else if (this.status !== 'paid') {
                        this.status = 'paid';
                        this.paymentMethod = 'cash';
                    }
                    this.recalculate();
                },

                async prepareSubmit(event) {
                    // Hard sync from real checkbox UI to prevent stale split state.
                    if (this.$refs.splitPaymentToggle) {
                        this.splitPaymentEnabled = Boolean(this.$refs.splitPaymentToggle.checked);
                    }
                    this.recalculate();
                    event.preventDefault();
                    if (!this.canSubmit) {
                        const message = this.cart.length === 0
                            ? 'Keranjang masih kosong.'
                            : (this.splitValidationError || this.methodLimitError || 'Jumlah dibayar masih kurang.');
                        this.pushToast('error', 'Checkout Gagal', message);
                        if (this.soundEnabled) this.beep(220, 0.09);
                        this.sendAudit('checkout_failed_client', {
                            reason: this.cart.length === 0 ? 'empty_cart' : (this.splitValidationError ? 'split_invalid' : (this.methodLimitError ? 'payment_limit_exceeded' : 'paid_shortfall')),
                            total: this.total,
                            paid: this.paid
                        });
                        return;
                    }
                    if (this.hasOutstandingDebt && this.debtMode !== 'normal' && this.projectedDebtAfterCheckout > Number(this.customerDebtLimit || 0)) {
                        this.pushToast('error', 'Limit Piutang Terlampaui', `Estimasi piutang melebihi limit Rp ${this.money(this.customerDebtLimit)}.`);
                        return;
                    }
                    if (this.debtMode === 'merge') {
                        this.status = 'pending';
                        this.paid = 0;
                        this.recalculate();
                    }

                    const form = event.target?.closest?.('form');
                    if (!form) {
                        this.pushToast('error', 'Checkout Gagal', 'Form checkout tidak ditemukan.');
                        return;
                    }

                    if (this.discount > (this.subtotal * 0.6)) {
                        if (!await this.askConfirm('Diskon Besar', 'Diskon global lebih dari 60% subtotal. Lanjutkan transaksi?')) {
                            return;
                        }
                    }

                    this.managerApprovalEmail = '';
                    this.managerApprovalPassword = '';
                    const threshold = Number(this.managerApprovalDiscountPct || 30);
                    const discountPct = this.subtotal > 0 ? ((Number(this.discount || 0) / this.subtotal) * 100) : 0;
                    if (discountPct >= threshold) {
                        this.managerApprovalDraftEmail = '';
                        this.managerApprovalDraftPassword = '';
                        this.managerApprovalFlow = 'checkout';
                        this.managerApprovalTitle = 'Approval Manajer';
                        this.managerApprovalDescription = `Diskon ${discountPct.toFixed(1)}% membutuhkan persetujuan manajer.`;
                        this.managerApprovalPrimaryLabel = 'Lanjut Checkout';
                        this.pendingManagerDiscountPct = discountPct;
                        this.rememberFocusBeforeModal();
                        this.showManagerApprovalModal = true;
                        this.$nextTick(() => this.syncManagerApprovalUi());
                        this.$nextTick(() => this.$refs.managerApprovalEmailInput?.focus());
                        return;
                    }

                    if (this.paid > (this.total * 5) && this.status === 'paid') {
                        if (!await this.askConfirm('Nominal Dibayar Besar', 'Jumlah dibayar terlihat sangat besar dari total. Lanjutkan transaksi?')) {
                            return;
                        }
                    }

                    if (this.isSubmitting) {
                        return;
                    }
                    if (this.requiresQrisReference() && !String(this.qrisReferenceId || '').trim()) {
                        this.pushToast('warning', 'Referensi QRIS Wajib', 'Isi ID transaksi QRIS untuk audit.');
                        return;
                    }
                    if (!await this.askConfirm(
                        'Konfirmasi Checkout',
                        'Pastikan data pembayaran berikut sudah benar sebelum transaksi disimpan.',
                        {
                            total: this.total,
                            paid: this.paid,
                            change: this.change,
                            status: this.status,
                            qty: this.totalQty,
                            qrisRef: String(this.qrisReferenceId || ''),
                            qrisIssuer: String(this.qrisIssuer || ''),
                        }
                    )) {
                        return;
                    }
                    this.sendAudit('checkout_submit_started', { total: this.total, paid: this.paid, status: this.status });
                    this.isSubmitting = true;
                    form.submit();
                },

                cancelManagerApproval() {
                    this.showManagerApprovalModal = false;
                    if (typeof this.managerApprovalResolver === 'function') {
                        this.managerApprovalResolver(null);
                    }
                    this.managerApprovalResolver = null;
                    this.managerApprovalDraftEmail = '';
                    this.managerApprovalDraftPassword = '';
                    const emailInput = document.getElementById('manager-approval-email');
                    const passwordInput = document.getElementById('manager-approval-password');
                    if (emailInput) emailInput.value = '';
                    if (passwordInput) passwordInput.value = '';
                    this.pendingManagerDiscountPct = 0;
                    this.pushToast('warning', 'Approval Dibatalkan', 'Checkout dibatalkan karena approval manajer belum diisi.');
                    this.restoreFocusAfterModal();
                },

                requestManagerApproval({ title = 'Approval Manajer', description = '', primaryLabel = 'Lanjut' } = {}) {
                    return new Promise((resolve) => {
                        this.managerApprovalResolver = resolve;
                        this.managerApprovalFlow = 'generic';
                        this.managerApprovalTitle = String(title || 'Approval Manajer');
                        this.managerApprovalDescription = String(description || '');
                        this.managerApprovalPrimaryLabel = String(primaryLabel || 'Lanjut');
                        this.managerApprovalDraftEmail = '';
                        this.managerApprovalDraftPassword = '';
                        this.rememberFocusBeforeModal();
                        this.showManagerApprovalModal = true;
                        this.$nextTick(() => this.syncManagerApprovalUi());
                        this.$nextTick(() => this.$refs.managerApprovalEmailInput?.focus());
                    });
                },

                async confirmManagerApproval() {
                    const emailInput = document.getElementById('manager-approval-email');
                    const passwordInput = document.getElementById('manager-approval-password');
                    const email = String(emailInput?.value ?? this.managerApprovalDraftEmail ?? '').trim();
                    const password = String(passwordInput?.value ?? this.managerApprovalDraftPassword ?? '');
                    if (!email) {
                        this.pushToast('warning', 'Approval Belum Lengkap', 'Email manajer wajib diisi.');
                        return;
                    }
                    if (!password) {
                        this.pushToast('warning', 'Approval Belum Lengkap', 'Password manajer wajib diisi.');
                        return;
                    }
                    this.managerApprovalEmail = email;
                    this.managerApprovalPassword = password;
                    this.showManagerApprovalModal = false;
                    if (typeof this.managerApprovalResolver === 'function') {
                        this.managerApprovalResolver({ email, password });
                    }
                    this.managerApprovalResolver = null;
                    this.managerApprovalDraftEmail = '';
                    this.managerApprovalDraftPassword = '';
                    this.pendingManagerDiscountPct = 0;
                    this.restoreFocusAfterModal();
                    if (this.managerApprovalFlow !== 'checkout') {
                        return;
                    }
                    if (this.paid > (this.total * 5) && this.status === 'paid') {
                        if (!await this.askConfirm('Nominal Dibayar Besar', 'Jumlah dibayar terlihat sangat besar dari total. Lanjutkan transaksi?')) {
                            return;
                        }
                    }
                    if (this.isSubmitting) {
                        return;
                    }
                    if (this.requiresQrisReference() && !String(this.qrisReferenceId || '').trim()) {
                        this.pushToast('warning', 'Referensi QRIS Wajib', 'Isi ID transaksi QRIS untuk audit.');
                        return;
                    }
                    if (!await this.askConfirm(
                        'Konfirmasi Checkout',
                        'Pastikan data pembayaran berikut sudah benar sebelum transaksi disimpan.',
                        {
                            total: this.total,
                            paid: this.paid,
                            change: this.change,
                            status: this.status,
                            qty: this.totalQty,
                            qrisRef: String(this.qrisReferenceId || ''),
                            qrisIssuer: String(this.qrisIssuer || ''),
                        }
                    )) {
                        return;
                    }
                    this.sendAudit('checkout_submit_started', { total: this.total, paid: this.paid, status: this.status });
                    this.isSubmitting = true;
                    const form = document.querySelector('form[action="{{ route('pos.checkout') }}"]');
                    form?.submit();
                },
                askConfirm(title, message, meta = null) {
                    return new Promise((resolve) => {
                        this.rememberFocusBeforeModal();
                        this.confirmModalTitle = String(title || 'Konfirmasi');
                        this.confirmModalMessage = String(message || '');
                        this.confirmModalMeta = meta && typeof meta === 'object' ? meta : null;
                        const t = String(title || '').toLowerCase();
                        this.confirmModalTone = t.includes('hapus') ? 'danger'
                            : (t.includes('diskon') || t.includes('besar') ? 'warning'
                            : (t.includes('checkout') ? 'success' : 'info'));
                        this.confirmResolver = resolve;
                        this.showConfirmModal = true;
                        this.$nextTick(() => this.$refs.confirmPrimaryBtn?.focus());
                    });
                },
                acceptConfirm() {
                    const resolver = this.confirmResolver;
                    this.confirmResolver = null;
                    this.showConfirmModal = false;
                    this.confirmModalTitle = '';
                    this.confirmModalMessage = '';
                    this.confirmModalMeta = null;
                    this.confirmModalTone = 'info';
                    this.restoreFocusAfterModal();
                    if (typeof resolver === 'function') resolver(true);
                },
                rejectConfirm() {
                    const resolver = this.confirmResolver;
                    this.confirmResolver = null;
                    this.showConfirmModal = false;
                    this.confirmModalTitle = '';
                    this.confirmModalMessage = '';
                    this.confirmModalMeta = null;
                    this.confirmModalTone = 'info';
                    this.restoreFocusAfterModal();
                    if (typeof resolver === 'function') resolver(false);
                },
                askInput(title, message, initialValue = '', placeholder = '') {
                    return new Promise((resolve) => {
                        this.rememberFocusBeforeModal();
                        this.inputModalTitle = String(title || 'Input');
                        this.inputModalMessage = String(message || '');
                        this.inputModalPlaceholder = String(placeholder || '');
                        this.inputModalValue = String(initialValue ?? '');
                        this.inputResolver = resolve;
                        this.showInputModal = true;
                        this.$nextTick(() => this.$refs.inputModalField?.focus());
                    });
                },
                acceptInputModal() {
                    const resolver = this.inputResolver;
                    const value = String(this.inputModalValue || '');
                    this.inputResolver = null;
                    this.showInputModal = false;
                    this.inputModalTitle = '';
                    this.inputModalMessage = '';
                    this.inputModalPlaceholder = '';
                    this.inputModalValue = '';
                    this.restoreFocusAfterModal();
                    if (typeof resolver === 'function') resolver(value);
                },
                rejectInputModal() {
                    const resolver = this.inputResolver;
                    this.inputResolver = null;
                    this.showInputModal = false;
                    this.inputModalTitle = '';
                    this.inputModalMessage = '';
                    this.inputModalPlaceholder = '';
                    this.inputModalValue = '';
                    this.restoreFocusAfterModal();
                    if (typeof resolver === 'function') resolver(null);
                },
                openAlertModal(title, message) {
                    this.rememberFocusBeforeModal();
                    this.alertModalTitle = String(title || 'Informasi');
                    this.alertModalMessage = String(message || '');
                    this.showAlertModal = true;
                    this.$nextTick(() => this.$refs.alertPrimaryBtn?.focus());
                },
                closeAlertModal() {
                    this.showAlertModal = false;
                    this.alertModalTitle = '';
                    this.alertModalMessage = '';
                    this.restoreFocusAfterModal();
                },
                rememberFocusBeforeModal() {
                    this.lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
                },
                restoreFocusAfterModal() {
                    this.$nextTick(() => {
                        if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
                            this.lastFocusedElement.focus({ preventScroll: true });
                        } else {
                            this.$refs.searchInput?.focus({ preventScroll: true });
                        }
                        this.lastFocusedElement = null;
                    });
                },
                getActiveModalElement() {
                    if (this.showManagerApprovalModal) return this.$refs.managerApprovalModal;
                    if (this.showConfirmModal) return this.$refs.confirmModal;
                    if (this.showAlertModal) return this.$refs.alertModal;
                    if (this.showInputModal) return this.$refs.inputModal;
                    if (this.showCheckoutSuccessModal) return this.$refs.checkoutSuccessModal;
                    return null;
                },
                handleModalKeyboard(event) {
                    const modal = this.getActiveModalElement();
                    if (!modal) return false;
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        if (this.showManagerApprovalModal) this.cancelManagerApproval();
                        else if (this.showConfirmModal) this.rejectConfirm();
                        else if (this.showAlertModal) this.closeAlertModal();
                        else if (this.showInputModal) this.rejectInputModal();
                        else if (this.showCheckoutSuccessModal) this.closeCheckoutSuccessModal();
                        return true;
                    }
                    if (event.key !== 'Tab') return false;
                    const focusable = Array.from(modal.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex=\"-1\"])'))
                        .filter((el) => !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
                    if (focusable.length === 0) {
                        event.preventDefault();
                        return true;
                    }
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    const active = document.activeElement;
                    if (event.shiftKey && active === first) {
                        event.preventDefault();
                        last.focus();
                        return true;
                    }
                    if (!event.shiftKey && active === last) {
                        event.preventDefault();
                        first.focus();
                        return true;
                    }
                    return false;
                },

                get canSubmit() {
                    if (this.cart.length === 0) return false;
                    if (this.cart.some(item => Number(item.stock || 0) <= 0 || Number(item.quantity || 0) <= 0)) return false;
                    if (this.installmentEnabled) {
                        const customerName = String(this.$refs?.customerNameInput?.value || '').trim();
                        if (customerName === '' && String(this.selectedCustomerId || '').trim() === '') return false;
                        const dp = Number(this.installmentDownPayment || 0);
                        if (dp < 0) return false;
                        if (dp > Number(this.total || 0)) return false;
                        return true;
                    }
                    if (this.status !== 'paid') return true;
                    if (this.splitPaymentEnabled) {
                        if (this.splitValidationError) return false;
                        if (this.methodLimitError) return false;
                        if (!Array.isArray(this.splitPayments) || this.splitPayments.length < 2) return false;
                    }
                    if (this.methodLimitError) return false;
                    return Number(this.paid || 0) >= this.total;
                },

                get effectivePaymentMethod() {
                    if (this.installmentEnabled) return 'installment';
                    return (this.status === 'paid' && this.splitPaymentEnabled) ? 'mixed' : this.paymentMethod;
                },

                refreshFeather() {
                    this.$nextTick(() => {
                        if (window.feather) {
                            window.feather.replace();
                        }
                    });
                },

                lineTotal(item) {
                    const gross = Number(item.price || 0) * Number(item.quantity || 0);
                    return Math.max(gross - Number(item.discount_amount || 0), 0);
                },

                computeAutoPromoDiscount(item) {
                    const sku = String(item?.sku || '').toUpperCase().trim();
                    if (!sku) return 0;
                    const rules = Array.isArray(this.buyXGetYRules) ? this.buyXGetYRules : [];
                    const match = rules.find(r =>
                        Boolean(r?.active ?? true) &&
                        String(r?.sku || '').toUpperCase().trim() === sku
                    );
                    if (!match) return 0;
                    const buyQty = Math.max(Number(match.buy_qty || 0), 1);
                    const getQty = Math.max(Number(match.get_qty || 0), 1);
                    const qty = Math.max(Number(item.quantity || 0), 0);
                    const cycle = buyQty + getQty;
                    if (cycle <= 0 || qty < cycle) return 0;
                    const freeUnits = Math.floor(qty / cycle) * getQty;
                    if (freeUnits <= 0) return 0;
                    return freeUnits * Number(item.price || 0);
                },

                promoLabel(item) {
                    const sku = String(item?.sku || '').toUpperCase().trim();
                    const rules = Array.isArray(this.buyXGetYRules) ? this.buyXGetYRules : [];
                    const match = rules.find(r =>
                        Boolean(r?.active ?? true) &&
                        String(r?.sku || '').toUpperCase().trim() === sku
                    );
                    if (!match) return '';
                    return `Beli ${match.buy_qty} Gratis ${match.get_qty}`;
                },

                setPaidExact() {
                    this.paid = this.total;
                    this.recalculate();
                },

                addPaid(amount) {
                    this.paid = Number(this.paid || 0) + Number(amount || 0);
                    this.recalculate();
                },

                tapQuickPay(key) {
                    this.quickPayFlashKey = String(key || '');
                    setTimeout(() => {
                        if (this.quickPayFlashKey === String(key || '')) {
                            this.quickPayFlashKey = '';
                        }
                    }, 220);
                },

                tapQuickPayExact() {
                    this.tapQuickPay('exact');
                    this.setPaidExact();
                },

                tapQuickPayAmount(key, amount) {
                    this.tapQuickPay(key);
                    this.addPaid(amount);
                },

                addSplitRow() {
                    if (!Array.isArray(this.splitPayments)) this.splitPayments = [];
                    if (this.splitPayments.length >= 4) return;
                    const fallbackMethods = ['cash', 'qris', 'debit', 'transfer', 'e_wallet'];
                    const used = new Set(this.splitPayments.map(r => r.method));
                    const pick = fallbackMethods.find(m => !used.has(m)) || 'cash';
                    this.splitPayments.push({ method: pick, amount: 0 });
                    this.recalculate();
                },

                removeSplitRow(index) {
                    if (!Array.isArray(this.splitPayments) || this.splitPayments.length <= 2) return;
                    this.splitPayments.splice(index, 1);
                    this.recalculate();
                },

                focusSearch() {
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus({ preventScroll: true });
                        this.$refs.searchInput?.select();
                    });
                },

                registerShortcuts() {
                    window.addEventListener('keydown', (event) => {
                        if (this.handleModalKeyboard(event)) {
                            return;
                        }
                        const tag = (event.target?.tagName || '').toLowerCase();
                        const inEditable = ['input', 'textarea', 'select'].includes(tag) || event.target?.isContentEditable;

                        if (event.key === '/' && !inEditable) {
                            event.preventDefault();
                            this.$refs.searchInput?.focus();
                            this.$refs.searchInput?.select();
                        }

                        if (event.key === 'Escape') {
                            event.preventDefault();
                            if (this.status === 'paid' && Number(this.paid || 0) > 0) {
                                this.paid = 0;
                                this.recalculate();
                                this.pushToast('warning', 'Pembayaran Direset', 'Jumlah dibayar dikosongkan.');
                            } else {
                                this.clearSearch();
                            }
                        }

                        if (event.key === 'F2') {
                            event.preventDefault();
                            this.$refs.paidInput?.focus();
                            this.$refs.paidInput?.select();
                        }
                        if (event.key === 'F1') {
                            event.preventDefault();
                            this.openAlertModal('Shortcut Kasir', 'Pintasan:\n/ = fokus pencarian\nF2 = fokus jumlah dibayar\nF4 = checkout\nCtrl+Enter = checkout\nEsc = reset pencarian/pembayaran');
                        }

                        if (event.key === 'F4') {
                            event.preventDefault();
                            if (this.canSubmit && !this.isSubmitting) {
                                const form = event.target?.closest?.('form') || document.querySelector('form[action="{{ route('pos.checkout') }}"]');
                                form?.requestSubmit();
                            }
                        }

                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            if (this.canSubmit && !this.isSubmitting) {
                                const form = document.querySelector('form[action="{{ route('pos.checkout') }}"]');
                                form?.requestSubmit();
                            }
                        }
                    });
                },

                registerIdleWatch() {
                    const resetTimers = () => {
                        if (this.idleWarnTimer) clearTimeout(this.idleWarnTimer);
                        if (this.idleReloadTimer) clearTimeout(this.idleReloadTimer);

                        this.idleWarnTimer = setTimeout(() => {
                            this.pushToast('warning', 'Sesi Idle', 'Tidak ada aktivitas. Halaman akan dimuat ulang otomatis jika tetap idle.');
                            this.sendAudit('idle_warning', { idle_ms: this.idleWarnMs });
                        }, this.idleWarnMs);

                        this.idleReloadTimer = setTimeout(() => {
                            window.location.reload();
                        }, this.idleReloadMs);
                    };

                    ['mousemove', 'keydown', 'click', 'touchstart'].forEach(evt => {
                        window.addEventListener(evt, resetTimers, { passive: true });
                    });

                    resetTimers();
                },
                startStockSync() {
                    if (this.stockSyncTimer) clearInterval(this.stockSyncTimer);
                    this.stockSyncTimer = setInterval(() => {
                        if (!document.hidden) {
                            this.fetchProducts();
                        }
                    }, this.stockSyncMs);
                },
                reconcileCartStock() {
                    if (!Array.isArray(this.cart) || this.cart.length === 0) return;
                    let adjusted = false;
                    let removedOutOfStock = false;
                    const map = new Map((this.products || []).map(p => [Number(p.id), Number(p.stock || 0)]));
                    this.cart.forEach(item => {
                        const latestStock = map.get(Number(item.id));
                        if (latestStock === undefined) return;
                        if (item.stock !== latestStock) {
                            item.stock = latestStock;
                        }
                        if (latestStock <= 0) {
                            removedOutOfStock = true;
                        }
                        if (item.quantity > latestStock) {
                            item.quantity = Math.max(latestStock, 0);
                            adjusted = true;
                        }
                    });
                    if (removedOutOfStock) {
                        this.cart = this.cart.filter(item => Number(item.stock || 0) > 0);
                        adjusted = true;
                    }
                    if (adjusted) {
                        this.recalculate();
                        this.pushToast('warning', 'Stok Diperbarui', 'Qty keranjang disesuaikan dengan stok terbaru.');
                        this.sendAudit('stock_sync_adjusted', { cart_items: this.cart.length });
                    }
                },
                sendAudit(action, context = {}) {
                    fetch('{{ route('pos.audit-event') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ action, context })
                    }).catch(() => {});
                },

                submitReconcile() {
                    const actual = Number(this.shiftCashActual || 0);
                    if (actual < 0) return;
                    this.shiftReconSubmitting = true;
                    fetch(this.reconcileEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            actual_cash: actual,
                            note: this.shiftReconcileNote || ''
                        })
                    })
                        .then(async r => {
                            const payload = await r.json().catch(() => ({}));
                            if (!r.ok) throw new Error(this.extractApiError(payload, `HTTP ${r.status}`, 'Tidak dapat menyimpan rekonsiliasi kas.'));
                            return payload;
                        })
                        .then(payload => {
                            if (!payload?.ok) throw new Error('failed');
                            this.shiftCashExpected = Number(payload?.data?.expected_cash || this.shiftCashExpected);
                            this.pushToast('success', 'Rekonsiliasi Tersimpan', 'Data kas akhir shift berhasil disimpan.');
                        })
                        .catch((e) => {
                            this.pushToast('error', 'Rekonsiliasi Gagal', String(e?.message || 'Tidak dapat menyimpan rekonsiliasi kas.'));
                        })
                        .finally(() => {
                        this.shiftReconSubmitting = false;
                    });
                },

                async submitQuickRefund() {
                    if (this.quickRefundSubmitting) return;
                    const invoice = String(this.quickRefundInvoice || '').trim();
                    const reason = String(this.quickRefundReason || '').trim();
                    if (!invoice || !reason) {
                        this.pushToast('warning', 'Data Belum Lengkap', 'Invoice dan alasan retur wajib diisi.');
                        return;
                    }
                    if (!await this.askConfirm('Retur Cepat', `Retur cepat invoice ${invoice}?`)) return;
                    const approval = await this.requestManagerApproval({
                        title: 'Approval Retur Cepat',
                        description: 'Retur cepat membutuhkan persetujuan manager untuk audit.',
                        primaryLabel: 'Lanjut Retur',
                    });
                    if (!approval) return;
                    const email = String(approval.email || '').trim();
                    const password = String(approval.password || '');

                    this.quickRefundSubmitting = true;
                    try {
                        const resp = await fetch(this.quickRefundEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                invoice_number: invoice,
                                reason,
                                manager_approval_email: email,
                                manager_approval_password: password,
                            }),
                        });
                        const data = await resp.json();
                        if (!resp.ok || !data?.ok) {
                            const msg = data?.message
                                || data?.errors?.invoice_number?.[0]
                                || data?.errors?.manager_approval_email?.[0]
                                || 'Retur cepat gagal.';
                            throw new Error(msg);
                        }
                        this.pushToast('success', 'Retur Berhasil', String(data?.message || 'Retur cepat berhasil.'));
                        this.quickRefundInvoice = '';
                        this.quickRefundReason = '';
                    } catch (err) {
                        this.pushToast('error', 'Retur Gagal', String(err?.message || 'Terjadi kesalahan saat retur cepat.'));
                    } finally {
                        this.quickRefundSubmitting = false;
                    }
                },

                async reprintWithReason(saleId, mode = 'pdf') {
                    const reason = await this.askInput('Cetak Ulang Struk', 'Masukkan alasan cetak ulang struk.', '', 'Minimal 5 karakter');
                    if (!reason || String(reason).trim().length < 5) {
                        this.pushToast('warning', 'Alasan Kurang', 'Minimal 5 karakter untuk reprint.');
                        return;
                    }
                    const endpoint = `${String(this.reprintEndpointBase || '')}/${String(saleId)}/reprint-receipt`;
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '');
                    formData.append('reason', String(reason).trim());
                    formData.append('mode', mode);
                    fetch(endpoint, { method: 'POST', body: formData })
                        .then(resp => {
                            if (!resp.redirected) throw new Error('not redirected');
                            window.open(resp.url, '_blank');
                            this.pushToast('success', 'Cetak Ulang Dicatat', 'Struk dibuka di tab baru.');
                        })
                        .catch(() => this.pushToast('error', 'Cetak Ulang Gagal', 'Tidak bisa memproses cetak ulang sekarang.'));
                },

                pushToast(type, title, message) {
                    const popupTitles = new Set([
                        'Checkout Gagal',
                        'Validasi Gagal',
                        'Approval Belum Lengkap',
                        'Data Belum Lengkap',
                        'Retur Gagal',
                        'Gagal Simpan Hold',
                        'Gagal Hapus Hold',
                        'Gagal Ubah Nama Hold',
                        'Rekonsiliasi Gagal',
                        'Hold Rusak',
                    ]);
                    if (popupTitles.has(String(title || ''))) {
                        this.openAlertModal(title, message);
                        return;
                    }
                    const id = this.nextToastId++;
                    this.toasts.push({ id, type, title, message });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 2600);
                },

                showFlashToasts() {
                    if (this.flashSuccess) {
                        this.pushToast('success', 'Berhasil', this.flashSuccess);
                        this.checkoutSuccessMessage = String(this.flashSuccess);
                        this.rememberFocusBeforeModal();
                        this.showCheckoutSuccessModal = true;
                        this.$nextTick(() => this.$refs.checkoutSuccessPrimaryBtn?.focus());
                    }

                    if (Array.isArray(this.flashWarnings) && this.flashWarnings.length > 0) {
                        this.pushToast('warning', 'Peringatan Stok', `Stok menipis: ${this.flashWarnings.join(', ')}`);
                    }

                    if (Array.isArray(this.flashErrors) && this.flashErrors.length > 0) {
                        this.flashErrors.forEach(msg => this.pushToast('error', 'Validasi Gagal', msg));
                    }
                },

                closeCheckoutSuccessModal() {
                    this.showCheckoutSuccessModal = false;
                    this.restoreFocusAfterModal();
                },
                extractApiError(payload, statusText = '', fallback = 'Terjadi kesalahan.') {
                    const fromMessage = String(payload?.message || '').trim();
                    const errors = payload?.errors && typeof payload.errors === 'object'
                        ? Object.values(payload.errors).flat().filter(Boolean)
                        : [];
                    const fromErrors = errors.length > 0 ? String(errors[0]).trim() : '';
                    const fromStatus = String(statusText || '').trim();
                    return fromMessage || fromErrors || fromStatus || fallback;
                },
                saveCheckoutDraft() {
                    try {
                        if (!this.checkoutDraftKey) return;
                        const hasData = Array.isArray(this.cart) && this.cart.length > 0;
                        if (!hasData) return;
                        const draft = {
                            schemaVersion: this.checkoutDraftSchemaVersion,
                            cart: this.cart,
                            discount: Number(this.discount || 0),
                            tax: Number(this.tax || 0),
                            paid: Number(this.paid || 0),
                            status: String(this.status || 'paid'),
                            paymentMethod: String(this.paymentMethod || 'cash'),
                            installmentEnabled: Boolean(this.installmentEnabled),
                            installmentTenorMonths: Number(this.installmentTenorMonths || 3),
                            installmentDownPayment: Number(this.installmentDownPayment || 0),
                            installmentFirstDueDate: String(this.installmentFirstDueDate || ''),
                            splitPaymentEnabled: Boolean(this.splitPaymentEnabled),
                            splitPayments: Array.isArray(this.splitPayments) ? this.splitPayments : [],
                            qrisReferenceId: String(this.qrisReferenceId || ''),
                            qrisIssuer: String(this.qrisIssuer || ''),
                            noteText: String(this.noteText || ''),
                            selectedCustomerId: String(this.selectedCustomerId || ''),
                            savedAt: new Date().toISOString(),
                        };
                        localStorage.setItem(this.checkoutDraftKey, JSON.stringify(draft));
                    } catch (e) {}
                },
                normalizeCheckoutDraft(rawDraft) {
                    if (!rawDraft || typeof rawDraft !== 'object') return null;
                    if (!Array.isArray(rawDraft.cart) || rawDraft.cart.length === 0) return null;
                    return {
                        schemaVersion: Number(rawDraft.schemaVersion || 1),
                        cart: rawDraft.cart.map(item => ({
                            ...item,
                            manual_discount_amount: Number(item.manual_discount_amount ?? (item.discount_amount || 0)),
                            auto_promo_discount: Number(item.auto_promo_discount || 0),
                            discount_amount: Number(item.discount_amount || 0),
                        })),
                        discount: Number(rawDraft.discount || 0),
                        tax: Number(rawDraft.tax || 0),
                        paid: Number(rawDraft.paid || 0),
                        status: String(rawDraft.status || 'paid'),
                        paymentMethod: String(rawDraft.paymentMethod || 'cash'),
                        installmentEnabled: Boolean(rawDraft.installmentEnabled || false),
                        installmentTenorMonths: Number(rawDraft.installmentTenorMonths || 3),
                        installmentDownPayment: Number(rawDraft.installmentDownPayment || 0),
                        installmentFirstDueDate: String(rawDraft.installmentFirstDueDate || ''),
                        splitPaymentEnabled: Boolean(rawDraft.splitPaymentEnabled),
                        splitPayments: Array.isArray(rawDraft.splitPayments) && rawDraft.splitPayments.length > 0
                            ? rawDraft.splitPayments
                            : [{ method: 'cash', amount: 0 }, { method: 'qris', amount: 0 }],
                        qrisReferenceId: String(rawDraft.qrisReferenceId || ''),
                        qrisIssuer: String(rawDraft.qrisIssuer || ''),
                        noteText: String(rawDraft.noteText || ''),
                        selectedCustomerId: String(rawDraft.selectedCustomerId || ''),
                    };
                },
                clearCheckoutDraft() {
                    try {
                        if (!this.checkoutDraftKey) return;
                        localStorage.removeItem(this.checkoutDraftKey);
                        if (this.checkoutDraftLegacyKey) {
                            localStorage.removeItem(this.checkoutDraftLegacyKey);
                        }
                    } catch (e) {}
                },
                restoreCheckoutDraft() {
                    try {
                        if (!this.checkoutDraftKey) return;
                        const rawV2 = localStorage.getItem(this.checkoutDraftKey);
                        const rawV1 = this.checkoutDraftLegacyKey ? localStorage.getItem(this.checkoutDraftLegacyKey) : null;
                        const chosenRaw = rawV2 || rawV1;
                        if (!chosenRaw) return;
                        const draft = this.normalizeCheckoutDraft(JSON.parse(chosenRaw));
                        if (!draft) return;
                        this.cart = draft.cart;
                        this.discount = draft.discount;
                        this.tax = draft.tax;
                        this.paid = draft.paid;
                        this.status = draft.status;
                        this.paymentMethod = draft.paymentMethod;
                        this.installmentEnabled = draft.installmentEnabled;
                        this.installmentTenorMonths = draft.installmentTenorMonths;
                        this.installmentDownPayment = draft.installmentDownPayment;
                        this.installmentFirstDueDate = draft.installmentFirstDueDate;
                        this.splitPaymentEnabled = draft.splitPaymentEnabled;
                        this.splitPayments = draft.splitPayments;
                        this.qrisReferenceId = draft.qrisReferenceId;
                        this.qrisIssuer = draft.qrisIssuer;
                        this.qrisReferenceAuto = false;
                        this.noteText = draft.noteText;
                        this.selectedCustomerId = draft.selectedCustomerId;
                        if (!rawV2) {
                            localStorage.setItem(this.checkoutDraftKey, JSON.stringify({
                                ...draft,
                                schemaVersion: this.checkoutDraftSchemaVersion,
                                savedAt: new Date().toISOString(),
                            }));
                        }
                        if (rawV1 && this.checkoutDraftLegacyKey) {
                            localStorage.removeItem(this.checkoutDraftLegacyKey);
                        }
                        this.pushToast('warning', 'Draft Dipulihkan', 'Transaksi terakhir dipulihkan otomatis.');
                    } catch (e) {}
                },

                holdRecentList() {
                    return [...(this.holds || [])].slice(0, 5);
                },

                holdAgo(iso) {
                    if (!iso) return '-';
                    const diffMs = Date.now() - new Date(iso).getTime();
                    const min = Math.max(1, Math.floor(diffMs / 60000));
                    if (min < 60) return `${min} menit lalu`;
                    const hr = Math.floor(min / 60);
                    if (hr < 24) return `${hr} jam lalu`;
                    const day = Math.floor(hr / 24);
                    return `${day} hari lalu`;
                },
                generateCheckoutToken() {
                    const rnd = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
                    return `pos-${rnd}`;
                },
                stockChipClass(product) {
                    const stock = Number(product.stock || 0);
                    const min = Number(product.low_stock_threshold || 0);
                    if (stock <= 0) return 'status-cancelled';
                    if (stock <= min) return 'status-pending';
                    return 'status-paid';
                },
                stockTextClass(product) {
                    const stock = Number(product.stock || 0);
                    const min = Number(product.low_stock_threshold || 0);
                    if (stock <= 0) return 'pos-stock-critical';
                    if (stock <= min) return 'pos-stock-low';
                    return 'pos-stock-ok';
                },
                stockText(product) {
                    const stock = Number(product.stock || 0);
                    const min = Number(product.low_stock_threshold || 0);
                    if (stock <= 0) return 'Stok habis';
                    if (stock <= min) return `Menipis (batas: ${min})`;
                    return 'Stok aman';
                },
                stockInlineClass(product) {
                    const stock = Number(product.stock || 0);
                    const min = Number(product.low_stock_threshold || 0);
                    if (stock <= 0) return 'is-out';
                    if (stock <= min) return 'is-low';
                    return 'is-safe';
                },
                stockInlineText(product) {
                    const stock = Number(product.stock || 0);
                    const min = Number(product.low_stock_threshold || 0);
                    if (stock <= 0) return 'Habis';
                    if (stock <= min) return `Sisa ${stock}`;
                    return `Ready ${stock}`;
                },
                beep(freq = 600, duration = 0.05) {
                    try {
                        const Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        const ctx = new Ctx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        gain.gain.value = 0.03;
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        setTimeout(() => {
                            osc.stop();
                            ctx.close();
                        }, Math.max(20, duration * 1000));
                    } catch (e) {}
                }
            }
        }
    </script>
</x-app-layout>
