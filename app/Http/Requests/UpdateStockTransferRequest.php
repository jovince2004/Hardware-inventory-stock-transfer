<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('transfer_reason');
        if (is_string($reason)) {
            $this->merge(['transfer_reason' => trim(strip_tags($reason))]);
        }
    }

    /**
     * Only descriptive fields can be edited. Warehouses, items, quantities and
     * status are NOT editable because stock has already moved.
     */
    public function rules(): array
    {
        return [
            'transfer_date'     => ['sometimes', 'date', 'before_or_equal:today'],
            'transfer_reason'   => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'transfer_date.date'          => 'Transfer date must be a valid date.',
            'transfer_date.before_or_equal' => 'Transfer date cannot be in the future.',
            'transfer_reason.max'         => 'Reason may not exceed 255 characters.',
        ];
    }

    public function successMessage(): string
    {
        return 'Stock transfer updated.';
    }
}
