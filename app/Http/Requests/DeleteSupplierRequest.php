<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function successMessage(): string
    {
        return 'Supplier deleted successfully.';
    }

    public function failureMessage(): string
    {
        return 'This supplier cannot be deleted because it is assigned to items.';
    }
}
