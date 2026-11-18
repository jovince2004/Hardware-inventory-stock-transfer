<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WarehouseService
{
    public function create(array $data): Warehouse
    {
        return Warehouse::create([
            'name' => $this->sanitizeText($data['name']),
            'location' => $this->sanitizeLocation($data['location'] ?? null),
        ]);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Warehouse::withCount(['incomingTransfers', 'outgoingTransfers'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $clean = ['name' => $this->sanitizeText($data['name'])];
        if (array_key_exists('location', $data)) {
            $clean['location'] = $this->sanitizeLocation($data['location']);
        }

        $warehouse->update($clean);

        return $warehouse;
    }

    public function delete(Warehouse $warehouse): bool
    {
        if ($warehouse->incomingTransfers()->exists() || $warehouse->outgoingTransfers()->exists()) {
            return false;
        }

        return (bool) $warehouse->delete();
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

    private function sanitizeLocation(?string $location): ?string
    {
        if ($location === null) {
            return null;
        }

        $location = $this->sanitizeText($location);

        return $location === '' ? null : $location;
    }
}
