<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function successMessage(): string
    {
        return 'Warehouse deleted successfully.';
    }

    public function failureMessage(): string
    {
        return 'This warehouse cannot be deleted because it has stock transfer history.';
    }
}
