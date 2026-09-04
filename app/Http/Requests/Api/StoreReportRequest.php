<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'os_number' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:100'],
            'sectors' => ['required', 'array', 'min:1'],
            'sectors.*' => ['required', 'string', 'max:100'],
            'history' => ['nullable', 'string'],
            'technicians' => ['nullable', 'string', 'max:255'],
        ];
    }
}
