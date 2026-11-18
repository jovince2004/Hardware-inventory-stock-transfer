<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    public function index(): Response
    {
        return Inertia::render('suppliers/index', [
            'suppliers' => $this->supplierService->paginate(),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $returnTo = $data['return_to'] ?? 'items.create';
        unset($data['return_to']);

        $this->supplierService->create($data);

        return redirect()->route($returnTo)->with('success', 'Supplier created successfully.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->update($supplier, $request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if (! $this->supplierService->delete($supplier)) {
            return redirect()->route('suppliers.index')
                ->with('error', 'This supplier cannot be deleted because it is assigned to items.');
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
