<?php

namespace App\Http\Controllers;

use App\Exceptions\StockTransferException;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $stockTransferService
    ) {}

    /** Display a listing of the resource. */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $search = $this->sanitizeSearch($search);
        $transfers = $this->stockTransferService->paginate($search);

        return Inertia::render('transfer/index', [
            'transfers' => $transfers,
            'filters'   => [
                'search' => $search ?? '',
            ],
        ]);
    }

    private function sanitizeSearch(mixed $search): ?string
    {
        if (!is_string($search)) {
            return null;
        }

        $clean = trim(strip_tags($search));

        return $clean === '' ? null : substr($clean, 0, 100);
    }

    /** Show the form for creating a new resource. */
    public function create(Request $request): Response
    {
        return Inertia::render('transfer/create', [
            ...$this->stockTransferService->createFormData(),
            'approvedBy' => $request->user()->name,
        ]);
    }

    /** Store a newly created resource in storage. */
    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        try {
            $this->stockTransferService->create($request->validated(), $request->user()->name);
        } catch (StockTransferException $exception) {
            throw ValidationException::withMessages($request->serviceErrorMessages($exception));
        }

        return redirect()->route('transfers.index')->with('success', $request->successMessage());
    }

    /** Update the specified resource in storage. */
    public function update(UpdateStockTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        $this->stockTransferService->update($transfer, $request->validated());

        return redirect()->route('transfers.index')->with('success', $request->successMessage());
    }
}
