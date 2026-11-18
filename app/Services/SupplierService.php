<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return Supplier::create([
            'name' => $this->sanitizeText($data['name']),
        ]);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Supplier::withCount('items')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update([
            'name' => $this->sanitizeText($data['name']),
        ]);

        return $supplier;
    }

    public function delete(Supplier $supplier): bool
    {
        if ($supplier->items()->exists()) {
            return false;
        }

        return (bool) $supplier->delete();
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
