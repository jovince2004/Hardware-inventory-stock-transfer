<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function successMessage(): string
    {
        return 'Item archived successfully.';
    }
}
