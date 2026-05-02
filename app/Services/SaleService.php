<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SaleService
{
    private const PAYMENT_METHOD_LIMITS = [
        'cash' => 100000000.0,
        'qris' => 50000000.0,
        'debit' => 50000000.0,
        'transfer' => 100000000.0,
        'e_wallet' => 20000000.0,
    ];

    public function createSale(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload) {
            $store = StoreSetting::query()->first();
            $checkoutToken = (string) ($payload['checkout_token'] ?? '');
            if ($checkoutToken === '') {
                throw ValidationException::withMessages([
                    'items' => 'Token checkout tidak valid.',
                ]);
            }

            $lockKey = 'pos_checkout_token:'.$user->id.':'.$checkoutToken;
            $isFresh = Cache::add($lockKey, now()->toDateTimeString(), now()->addMinutes(5));
            if (! $isFresh) {
                throw ValidationException::withMessages([
                    'items' => 'Checkout duplikat terdeteksi. Silakan cek transaksi terakhir Anda.',
                ]);
            }

            $itemsPayload = $payload['items'];
            $productIds = collect($itemsPayload)->pluck('product_id')->unique()->values();

            $products = Product::query()
                ->where('branch_id', $user->branch_id)
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $totalModal = 0;
            $preparedItems = [];

            foreach ($itemsPayload as $item) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Produk ID {$item['product_id']} tidak tersedia.",
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $itemDiscount = (float) ($item['discount_amount'] ?? 0);

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} tidak mencukupi.",
                    ]);
                }

                $itemSubtotal = ($product->selling_price * $quantity) - $itemDiscount;
                $subtotal += $itemSubtotal;
                $totalModal += $product->purchase_price * $quantity;

                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $discount = (float) ($payload['discount_amount'] ?? 0);
            $tax = (float) ($payload['tax_amount'] ?? 0);
            $baseTotal = max($subtotal - $discount + $tax, 0);
            $this->validateManagerApprovalForLargeDiscount($user, $payload, $subtotal, $discount);
            $roundingAmount = 0.0;
            $adminFeeAmount = 0.0;
            $paidAmount = (float) ($payload['paid_amount'] ?? 0);
            $paymentMethod = (string) ($payload['payment_method'] ?? 'cash');
            $qrisReferenceId = trim((string) ($payload['qris_reference_id'] ?? ''));
            $qrisIssuer = trim((string) ($payload['qris_issuer'] ?? ''));
            $splitPaymentsRaw = is_array($payload['split_payments'] ?? null) ? $payload['split_payments'] : [];
            $requestedStatus = $payload['status'] ?? SaleStatus::Paid->value;

            $status = $requestedStatus;
            $paymentBreakdown = null;
            if ($status === SaleStatus::Paid->value && ! empty($splitPaymentsRaw)) {
                $normalized = collect($splitPaymentsRaw)
                    ->map(function ($row) {
                        return [
                            'method' => (string) ($row['method'] ?? ''),
                            'amount' => (float) ($row['amount'] ?? 0),
                        ];
                    })
                    ->filter(fn ($row) => $row['method'] !== '' && $row['amount'] > 0)
                    ->values();

                if ($normalized->count() >= 2) {
                    $methodUniqCount = $normalized->pluck('method')->unique()->count();
                    if ($methodUniqCount !== $normalized->count()) {
                        throw ValidationException::withMessages([
                            'split_payments' => 'Metode pembayaran split tidak boleh duplikat.',
                        ]);
                    }
                    foreach ($normalized as $row) {
                        $limit = (float) (self::PAYMENT_METHOD_LIMITS[$row['method']] ?? 0);
                        if ($limit > 0 && (float) $row['amount'] > $limit) {
                            throw ValidationException::withMessages([
                                'split_payments' => 'Nominal '.$row['method'].' melebihi batas: Rp '.number_format($limit, 0, ',', '.'),
                            ]);
                        }
                    }
                    $paymentMethod = 'mixed';
                    $paymentBreakdown = $normalized->map(function (array $row) use ($qrisReferenceId, $qrisIssuer) {
                        if (($row['method'] ?? '') !== 'qris') {
                            return $row;
                        }
                        if ($qrisReferenceId !== '') {
                            $row['reference_id'] = $qrisReferenceId;
                        }
                        if ($qrisIssuer !== '') {
                            $row['issuer'] = $qrisIssuer;
                        }

                        return $row;
                    })->all();
                    $paidAmount = (float) $normalized->sum('amount');
                }
            }

            if ($status === SaleStatus::Paid->value && $paymentMethod === 'mixed' && (! is_array($paymentBreakdown) || count($paymentBreakdown) < 2)) {
                $singleMethod = (string) ($payload['payment_method_single'] ?? 'cash');
                if (! in_array($singleMethod, ['cash', 'qris', 'debit', 'transfer', 'e_wallet'], true)) {
                    $singleMethod = 'cash';
                }
                $paymentMethod = $singleMethod;
                $paymentBreakdown = null;
            }

            $total = $baseTotal;

            $overpayRules = is_array($store?->payment_overpay_rules) ? $store->payment_overpay_rules : [];

            if ($status === SaleStatus::Paid->value && $paymentMethod !== 'mixed') {
                $limit = (float) (self::PAYMENT_METHOD_LIMITS[$paymentMethod] ?? 0);
                if ($limit > 0 && $paidAmount > $limit) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Nominal pembayaran melebihi batas metode '.strtoupper(str_replace('_', ' ', $paymentMethod)).': Rp '.number_format($limit, 0, ',', '.'),
                    ]);
                }
                if (in_array($paymentMethod, ['qris', 'debit', 'transfer', 'e_wallet'], true) && $paidAmount > $total) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Nominal metode non-cash tidak boleh melebihi total transaksi.',
                    ]);
                }
                $this->validateMethodOverpay($paymentMethod, $paidAmount, $total, $overpayRules);
            } elseif ($status === SaleStatus::Paid->value && $paymentMethod === 'mixed' && is_array($paymentBreakdown)) {
                foreach ($paymentBreakdown as $row) {
                    $methodRow = (string) ($row['method'] ?? '');
                    $amountRow = (float) ($row['amount'] ?? 0);
                    if (in_array($methodRow, ['qris', 'debit', 'transfer', 'e_wallet'], true) && $amountRow > $total) {
                        throw ValidationException::withMessages([
                            'split_payments' => 'Nominal '.strtoupper(str_replace('_', ' ', $methodRow)).' tidak boleh melebihi total transaksi.',
                        ]);
                    }
                    $this->validateMethodOverpay(
                        $methodRow,
                        $amountRow,
                        $total,
                        $overpayRules
                    );
                }
            }

            if ($status === SaleStatus::Paid->value && $paidAmount < $total) {
                $status = SaleStatus::Pending->value;
            }
            if ($status !== SaleStatus::Paid->value) {
                $paymentBreakdown = null;
                $paidAmount = 0;
                if ($paymentMethod === 'mixed') {
                    $paymentMethod = 'cash';
                }
            }

            $change = $status === SaleStatus::Paid->value ? max($paidAmount - $total, 0) : 0;
            $paymentDueAt = null;
            if ($status === SaleStatus::Pending->value && in_array($paymentMethod, ['qris', 'debit', 'transfer', 'e_wallet'], true)) {
                $timeoutMinutes = max(1, (int) ($store?->pending_non_cash_timeout_minutes ?? 30));
                $paymentDueAt = now()->addMinutes($timeoutMinutes);
            }
            $usesQris = $paymentMethod === 'qris'
                || ($paymentMethod === 'mixed' && is_array($paymentBreakdown)
                    && collect($paymentBreakdown)->contains(fn ($row) => (string) ($row['method'] ?? '') === 'qris'));

            $customer = $this->resolveCustomer($payload);
            $customerName = trim((string) ($payload['customer_name'] ?? ''));
            $note = (string) ($payload['note'] ?? '');

            $sale = Sale::query()->create([
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
                'customer_id' => $customer?->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_name' => $customerName !== '' ? $customerName : $customer?->name,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'rounding_amount' => $roundingAmount,
                'admin_fee_amount' => $adminFeeAmount,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $change,
                'payment_method' => $paymentMethod,
                'payment_breakdown' => $paymentBreakdown,
                'payment_due_at' => $paymentDueAt,
                'payment_attempt_count' => 0,
                'payment_attempt_logs' => null,
                'status' => $status,
                'note' => $note !== '' ? $note : null,
                'sold_at' => Carbon::now(),
            ]);

            $resolvedQrisReferenceId = $qrisReferenceId;
            if ($usesQris && $resolvedQrisReferenceId === '') {
                $resolvedQrisReferenceId = 'AUTO-'.$sale->invoice_number.'-'.now()->format('YmdHis');
            }
            if ($usesQris && is_array($paymentBreakdown)) {
                $updatedBreakdown = collect($paymentBreakdown)
                    ->map(function (array $row) use ($resolvedQrisReferenceId, $qrisIssuer) {
                        if ((string) ($row['method'] ?? '') !== 'qris') {
                            return $row;
                        }
                        if ($resolvedQrisReferenceId !== '' && empty($row['reference_id'])) {
                            $row['reference_id'] = $resolvedQrisReferenceId;
                        }
                        if ($qrisIssuer !== '' && empty($row['issuer'])) {
                            $row['issuer'] = $qrisIssuer;
                        }

                        return $row;
                    })
                    ->all();
                $sale->payment_breakdown = $updatedBreakdown;
            }
            if ($usesQris) {
                $sale->note = trim(((string) $sale->note)."\n[QRIS] Ref: ".($resolvedQrisReferenceId !== '' ? $resolvedQrisReferenceId : '-')." | Issuer: ".($qrisIssuer !== '' ? $qrisIssuer : '-'));
            }
            if ($usesQris && ($resolvedQrisReferenceId !== '' || $qrisIssuer !== '' || is_array($paymentBreakdown))) {
                $sale->save();
            }

            foreach ($preparedItems as $itemData) {
                $product = $itemData['product'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'unit' => $product->unit,
                    'unit_price' => $product->selling_price,
                    'purchase_price' => $product->purchase_price,
                    'quantity' => $itemData['quantity'],
                    'discount_amount' => $itemData['discount_amount'],
                    'subtotal' => $itemData['subtotal'],
                ]);

                $product->decrement('stock', $itemData['quantity']);
            }

            return $sale->load(['items', 'user']);
        });
    }

    private function validateMethodOverpay(string $method, float $paidAmount, float $totalAmount, array $rules): void
    {
        if ($method === '' || ! isset($rules[$method])) {
            return;
        }

        $rule = $rules[$method];
        if (is_string($rule) && is_numeric($rule)) {
            $rule = ['max_overpay' => (float) $rule];
        }
        if (! is_array($rule)) {
            return;
        }

        $maxOverpay = (float) ($rule['max_overpay'] ?? 0);
        if ($maxOverpay < 0) {
            $maxOverpay = 0;
        }

        $overpay = $paidAmount - $totalAmount;
        if ($overpay > $maxOverpay) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Overpay untuk metode '.strtoupper(str_replace('_', ' ', $method)).' melebihi batas: Rp '.number_format($maxOverpay, 0, ',', '.'),
            ]);
        }
    }

    private function validateManagerApprovalForLargeDiscount(User $user, array $payload, float $subtotal, float $discount): void
    {
        if ($subtotal <= 0 || $discount <= 0) {
            return;
        }

        $thresholdPct = max(1.0, (float) env('POS_MANAGER_APPROVAL_DISCOUNT_PERCENT', 30));
        $discountPct = ($discount / $subtotal) * 100;

        if ($discountPct < $thresholdPct) {
            return;
        }

        // Owner/Admin can self-approve.
        if ($user->hasAnyRole(['owner', 'admin'])) {
            return;
        }

        $email = trim((string) ($payload['manager_approval_email'] ?? ''));
        $password = (string) ($payload['manager_approval_password'] ?? '');

        if ($email === '' || $password === '') {
            throw ValidationException::withMessages([
                'discount_amount' => 'Diskon besar membutuhkan approval manager (email + password).',
            ]);
        }

        $manager = User::query()
            ->where('email', $email)
            ->whereIn('role', ['owner', 'admin'])
            ->first();

        if (! $manager || ! Hash::check($password, (string) $manager->password)) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Approval manager tidak valid untuk diskon besar.',
            ]);
        }
    }

    protected function generateInvoiceNumber(): string
    {
        $today = Carbon::now();
        $prefix = 'INV-'.$today->format('Ymd');

        $lastSale = Sale::query()
            ->whereDate('sold_at', $today->toDateString())
            ->latest('id')
            ->first();

        $sequence = 1;
        if ($lastSale) {
            $parts = explode('-', $lastSale->invoice_number);
            $lastSequence = (int) end($parts);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }

    private function resolveCustomer(array $payload): ?Customer
    {
        $customerId = (int) ($payload['customer_id'] ?? 0);
        $name = trim((string) ($payload['customer_name'] ?? ''));
        $phone = trim((string) ($payload['customer_phone'] ?? ''));
        $email = trim((string) ($payload['customer_email'] ?? ''));
        $address = trim((string) ($payload['customer_address'] ?? ''));

        if ($customerId > 0) {
            $customer = Customer::query()->find($customerId);
            if ($customer) {
                if ($name !== '') {
                    $customer->name = $name;
                }
                if ($phone !== '') {
                    $customer->phone = $phone;
                }
                if ($email !== '') {
                    $customer->email = $email;
                }
                if ($address !== '') {
                    $customer->address = $address;
                }
                $customer->save();

                return $customer;
            }
        }

        if ($name === '' && $phone === '' && $email === '' && $address === '') {
            return null;
        }

        $customer = null;
        if ($phone !== '') {
            $customer = Customer::query()->where('phone', $phone)->first();
        }

        if (! $customer && $name !== '') {
            $customer = Customer::query()->where('name', $name)->when($phone !== '', fn ($q) => $q->where('phone', $phone))->first();
        }

        if (! $customer) {
            $customer = new Customer();
            $customer->is_active = true;
        }

        if ($name !== '') {
            $customer->name = $name;
        } elseif (! $customer->exists) {
            $customer->name = 'Pelanggan Umum';
        }
        if ($phone !== '') {
            $customer->phone = $phone;
        }
        if ($email !== '') {
            $customer->email = $email;
        }
        if ($address !== '') {
            $customer->address = $address;
        }
        $customer->save();

        return $customer;
    }
}
