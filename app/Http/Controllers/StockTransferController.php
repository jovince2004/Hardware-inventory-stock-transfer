<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $stockTransferService
    ) {}

    /** Display a listing of the resource. */
    public function index(Request $req): Response
    {
        $search = $req->search;
        $transfers = $this->stockTransferService->paginate(is_string($search) ? $search : null);

        return Inertia::render('transfer/index', [
            'transfers' => $transfers,
            'filters'   => [
                'search' => is_string($search) ? $search : '',
            ],
        ]);
    }

    /** Show the form for creating a new resource. */
    public function create(): Response
    {
        return Inertia::render('transfer/create', $this->stockTransferService->createFormData());
    }

    /** Store a newly created resource in storage. */
    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $this->stockTransferService->create($request->validated());

        return redirect()->route('transfers.index')->with('success', 'Stock transfer completed.');
    }

    /** Update the specified resource in storage. */
    public function update(UpdateStockTransferRequest $request, $id): RedirectResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        $this->stockTransferService->update($transfer, $request->validated());

        return redirect()->route('transfers.index')->with('success', 'Stock transfer updated.');
    }
}
