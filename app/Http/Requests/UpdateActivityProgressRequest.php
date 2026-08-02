<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateProgress', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
