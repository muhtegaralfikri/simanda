<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Activity::class);
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'exists:units,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'person_in_charge_id' => ['required', 'exists:users,id'],
            'funding_source_id' => ['required', 'exists:funding_sources,id'],
            'activity_code' => ['required', 'string', 'max:50'],
            'activity_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'target' => ['nullable', 'string', 'max:255'],
            'budget_ceiling' => ['required', 'integer', 'min:0'],
        ];
    }
}
