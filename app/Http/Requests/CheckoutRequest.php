<?php

namespace App\Http\Requests;

use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_string($this->input('items_json'))) {
            $decoded = json_decode($this->input('items_json'), true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        $splitPayments = $this->input('split_payments');
        if (is_string($this->input('split_payments_json'))) {
            $decodedSplit = json_decode($this->input('split_payments_json'), true);
            if (is_array($decodedSplit)) {
                $splitPayments = $decodedSplit;
            }
        }

        $paymentMethod = (string) $this->input('payment_method', '');
        $singleMethod = (string) $this->input('payment_method_single', 'cash');
        $allowedMethods = ['cash', 'qris', 'debit', 'transfer', 'e_wallet'];
        if (! in_array($singleMethod, $allowedMethods, true)) {
            $singleMethod = 'cash';
        }

        if ($paymentMethod !== 'mixed') {
            $splitPayments = null;
        } else {
            $count = is_array($splitPayments) ? count($splitPayments) : 0;
            if ($count < 2) {
                // Fallback aman: jika state mixed tidak lengkap dari UI, pakai metode tunggal.
                $paymentMethod = $singleMethod;
                $splitPayments = null;
            }
        }

        $this->merge([
            'items' => $items,
            'payment_method' => $paymentMethod,
            'payment_method_single' => $singleMethod,
            'split_payments' => $splitPayments,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'qris', 'debit', 'transfer', 'e_wallet', 'mixed', 'installment'])],
            'payment_method_single' => ['nullable', Rule::in(['cash', 'qris', 'debit', 'transfer', 'e_wallet'])],
            'split_payments' => ['nullable', 'array', 'min:2'],
            'split_payments.*.method' => ['required_with:split_payments', 'distinct', Rule::in(['cash', 'qris', 'debit', 'transfer', 'e_wallet'])],
            'split_payments.*.amount' => ['required_with:split_payments', 'numeric', 'min:0.01'],
            'status' => ['nullable', Rule::in(array_keys(SaleStatus::options()))],
            'print_after_checkout' => ['nullable', 'boolean'],
            'open_receipt_pdf' => ['nullable', 'boolean'],
            'source_hold_id' => ['nullable', 'string', 'max:60'],
            'checkout_token' => ['required', 'string', 'max:100'],
            'manager_approval_email' => ['nullable', 'email', 'max:150'],
            'manager_approval_password' => ['nullable', 'string', 'max:120'],
            'qris_reference_id' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.\/]{2,119}$/'],
            'qris_issuer' => ['nullable', 'string', 'max:120'],
            'installment_enabled' => ['nullable', 'boolean'],
            'installment_tenor_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'installment_down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_first_due_date' => ['nullable', 'date'],
            'debt_mode' => ['nullable', Rule::in(['normal', 'merge', 'partial'])],
            'debt_existing_id' => ['nullable', 'integer', 'exists:customer_debts,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang tidak boleh kosong.',
            'items.min' => 'Tambahkan minimal satu produk ke keranjang.',
            'split_payments.min' => 'Pembayaran ganda minimal terdiri dari 2 metode.',
            'qris_reference_id.regex' => 'Format Referensi QRIS tidak valid. Gunakan huruf/angka dan simbol - _ . /',
        ];
    }
}
