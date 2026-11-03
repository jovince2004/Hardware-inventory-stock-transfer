<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only descriptive fields can be edited. Warehouses, items, quantities and
     * status are NOT editable because stock has already moved.
     */
    public function rules(): array
    {
        return [
            'transfer_date'     => ['sometimes', 'date'],
            'transfer_reason'   => ['sometimes', 'string', 'max:255'],
            'approved_by_staff' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'transfer_date.date'          => 'Transfer date must be a valid date.',
            'transfer_reason.max'         => 'Reason may not exceed 255 characters.',
            'approved_by_staff.string'    => 'Approver name must be text.',
        ];
    }
}
