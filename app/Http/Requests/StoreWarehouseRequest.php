<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['name', 'location'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $data[$field] = trim(strip_tags($value));
            }
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/^[a-z]{10,}\d*$/', 'unique:warehouses,name'],
            'location' => ['nullable', 'string', 'max:255', 'not_regex:/^[a-z]{10,}\d*$/'],
            'return_to' => ['sometimes', 'string', Rule::in(['items.create', 'warehouses.index'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.not_regex' => 'Please enter a readable warehouse name instead of a random string.',
            'location.not_regex' => 'Please enter a readable location instead of a random string.',
        ];
    }
}
