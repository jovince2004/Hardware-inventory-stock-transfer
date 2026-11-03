<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Replace with a real check (e.g. $this->user()->can('manage-inventory')) when auth exists.
        return true;
    }

    // Standardize BEFORE validation so "unique" compares against the uppercase value.
    protected function prepareForValidation(): void
    {
        if ($this->has('item_code')) {
            $this->merge(['item_code' => strtoupper(trim((string) $this->item_code))]);
        }
    }

    public function rules(): array
    {
        return [
            'item_code'          => ['required', 'string', 'max:30', 'unique:items,item_code'],
            'item_description'   => ['required', 'string', 'max:255'],
            'supplier_id'        => ['required', 'integer', 'exists:suppliers,id'],
            // optional opening stock per warehouse
            'stocks'                  => ['sometimes', 'array'],
            'stocks.*.warehouse_id'   => ['required_with:stocks', 'integer', 'exists:warehouses,id', 'distinct'],
            'stocks.*.quantity'       => ['required_with:stocks', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_code.required'     => 'Item code is required.',
            'item_code.unique'       => 'This item code already exists.',
            'supplier_id.exists'     => 'The selected supplier does not exist.',
            'stocks.*.warehouse_id.distinct' => 'A warehouse can only appear once in the opening stock.',
            'stocks.*.quantity.min'  => 'Opening stock cannot be negative.',
        ];
    }
}
