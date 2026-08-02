<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadDocument', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'exists:document_types,id'],
            'realization_id' => ['nullable', 'exists:realizations,id'],
            'file' => ['required', 'file'],
        ];
    }
}
