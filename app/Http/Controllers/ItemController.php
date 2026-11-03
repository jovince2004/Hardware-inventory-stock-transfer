<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
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

    /** Update the specified resource in storage. */
    public function update(UpdateItemRequest $request, $id): RedirectResponse
    {
        $item = Item::findOrFail($id);
        $this->itemService->update($item, $request->validated());

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }
}
