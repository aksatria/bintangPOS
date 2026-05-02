<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'gte:purchase_price'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
