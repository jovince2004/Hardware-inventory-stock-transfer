<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => trim(strip_tags($name))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/^[a-z]{10,}\d*$/', 'unique:suppliers,name'],
            'return_to' => ['sometimes', 'string', Rule::in(['items.create', 'suppliers.index'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.not_regex' => 'Please enter a readable supplier name instead of a random string.',
        ];
    }

    public function successMessage(): string
    {
        return 'Supplier created successfully.';
    }
}
