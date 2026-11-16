<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Services\ItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function __construct(
        private readonly ItemService $itemService
    ) {}

    /** Display a listing of the resource. */
    public function index(Request $req): Response
    {
        $search = $req->search;
        $items = $this->itemService->paginate(is_string($search) ? $search : null);

        return Inertia::render('item/index', [
            'items'   => $items,
            'filters' => [
                'search' => is_string($search) ? $search : '',
            ],
            'summary' => $this->itemService->dashboardSummary(),
        ]);
    }

    /** Show the form for creating a new resource. */
    public function create(): Response
    {
        return Inertia::render('item/create', $this->itemService->createFormData());
    }

    /** Store a newly created resource in storage. */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $this->itemService->create($request->validated());

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    /** Display the specified resource. */
    public function show(int $id): Response
    {
        return Inertia::render('item/show', [
            'item' => $this->itemService->findActive($id),
        ]);
    }

    /** Show the form for editing the specified resource. */
    public function edit(int $id): Response
    {
        $item = $this->itemService->findActiveForEdit($id);

        return Inertia::render('item/edit', [
            'item' => $item,
            'suppliers' => $this->itemService->suppliers(),
        ]);
    }

    /** Update the specified resource in storage. */
    public function update(UpdateItemRequest $request, $id): RedirectResponse
    {
        $item = $this->itemService->findActiveForEdit((int) $id);
        $this->itemService->update($item, $request->validated());

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    /** Archive the specified resource without removing its stock history. */
    public function destroy(int $id): RedirectResponse
    {
        $this->itemService->archive($id);

        return redirect()->route('items.index')->with('success', 'Item archived successfully.');
    }
}
