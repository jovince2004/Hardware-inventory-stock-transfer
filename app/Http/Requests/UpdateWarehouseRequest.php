<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                'not_regex:/^[a-z]{10,}\d*$/',
                Rule::unique('warehouses', 'name')->ignore($this->route('warehouse')),
            ],
            'location' => ['sometimes', 'nullable', 'string', 'max:255', 'not_regex:/^[a-z]{10,}\d*$/'],
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
