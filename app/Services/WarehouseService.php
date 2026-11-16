<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

class WarehouseService
{
    public function create(array $data): Warehouse
    {
        return Warehouse::create([
            'name' => $this->sanitizeText($data['name']),
            'location' => isset($data['location'])
                ? $this->sanitizeText($data['location'])
                : null,
        ]);
    }

    /** Warehouses available to item and transfer forms. */
    public function listForSelect(): Collection
    {
        return Warehouse::orderBy('name')->get(['id', 'name']);
    }

    public function count(): int
    {
        return Warehouse::count();
    }

    private function sanitizeText(string $text): string
    {
        return trim(strip_tags($text));
    }
}
