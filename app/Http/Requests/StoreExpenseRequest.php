<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
