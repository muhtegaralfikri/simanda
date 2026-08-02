<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('budgetPlan'));
    }

    public function rules(): array
    {
        return [
            'expense_type_id' => ['required', 'exists:expense_types,id'],
            'account_code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'volume' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
