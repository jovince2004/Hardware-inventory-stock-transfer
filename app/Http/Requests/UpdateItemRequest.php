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
        $description = $this->input('item_description');
        if (is_string($description)) {
            $this->merge(['item_description' => trim(strip_tags($description))]);
        }
    }

    public function rules(): array
    {
        // {item} comes from the route: PUT /items/{item}
        $id = $this->route('item');

        return [
            'item_code'        => ['sometimes', 'string', 'max:30', Rule::unique('items', 'item_code')->ignore($id)],
            'item_description' => ['sometimes', 'required', 'string', 'max:255', 'not_regex:/^(?=(?:[^a-z]*[a-z]){10})[a-z0-9\p{P}\p{S}]+$/iu'],
            'supplier_id'      => ['sometimes', 'integer', 'exists:suppliers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_code.unique'   => 'Another item already uses this code.',
            'item_description.not_regex' => 'Please enter a readable item description.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
        ];
    }

    public function successMessage(): string
    {
        return 'Item updated successfully.';
    }
}
