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

    protected function prepareForValidation(): void
    {
        $description = $this->input('item_description');
        if (is_string($description)) {
            $this->merge(['item_description' => trim(strip_tags($description))]);
        }
    }

    public function rules(): array
    {
        return [
            'item_description'   => ['required', 'string', 'max:255', 'not_regex:/^[a-z]{10,}\d*$/'],
            'supplier_id'        => ['required', 'integer', 'exists:suppliers,id'],
            // optional opening stock per warehouse
            'stocks'                  => ['sometimes', 'array'],
            'stocks.*.warehouse_id'   => ['required_with:stocks', 'integer', 'exists:warehouses,id', 'distinct'],
            'stocks.*.quantity'       => ['required_with:stocks', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_description.not_regex' => 'Please enter a readable item description instead of a random string.',
            'supplier_id.exists'     => 'The selected supplier does not exist.',
            'stocks.*.warehouse_id.distinct' => 'A warehouse can only appear once in the opening stock.',
            'stocks.*.quantity.min'  => 'Opening stock must be at least 1.',
            'stocks.*.quantity.max'  => 'Opening stock cannot exceed 500 per warehouse.',
        ];
    }
}
