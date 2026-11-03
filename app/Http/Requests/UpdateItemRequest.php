<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('item_code')) {
            $this->merge(['item_code' => strtoupper(trim((string) $this->item_code))]);
        }
    }

    public function rules(): array
    {
        // {item} comes from the route: PUT /items/{item}
        $id = $this->route('item');

        return [
            'item_code'        => ['sometimes', 'string', 'max:30', Rule::unique('items', 'item_code')->ignore($id)],
            'item_description' => ['sometimes', 'string', 'max:255'],
            'supplier_id'      => ['sometimes', 'integer', 'exists:suppliers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_code.unique'   => 'Another item already uses this code.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
        ];
    }
}
