<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [\App\Models\Realization::class, $this->route('activity')]);
    }

    public function rules(): array
    {
        return [
            'budget_plan_id' => ['required', 'exists:budget_plans,id'],
            'transaction_date' => ['required', 'date'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'gross_amount' => ['required', 'integer', 'min:1'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ];
    }
}
