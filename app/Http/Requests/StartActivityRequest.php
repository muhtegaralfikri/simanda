<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('startExecution', $this->route('activity'));
    }

    public function rules(): array
    {
        return [];
    }
}
