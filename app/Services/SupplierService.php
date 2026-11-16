<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return Supplier::create([
            'name' => $this->sanitizeText($data['name']),
        ]);
    }

    /** Suppliers available to item forms. */
    public function listForSelect(): Collection
    {
        return Supplier::orderBy('name')->get(['id', 'name']);
    }

    public function count(): int
    {
        return Supplier::count();
    }

    private function sanitizeText(string $text): string
    {
        return trim(strip_tags($text));
    }
}
