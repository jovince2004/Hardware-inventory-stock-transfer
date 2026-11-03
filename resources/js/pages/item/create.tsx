import InventoryShell from '@/components/inventory-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Props = {
    suppliers: { id: number; name: string }[];
    warehouses: { id: number; name: string }[];
};

type StockEntry = { warehouse_id: string; quantity: string };

export default function ItemCreate({ suppliers, warehouses }: Props) {
    const [supplierDialogOpen, setSupplierDialogOpen] = useState(false);
    const [warehouseDialogOpen, setWarehouseDialogOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        item_code: '',
        item_description: '',
        supplier_id: '',
        stocks: [] as StockEntry[],
    });
    const supplierForm = useForm({ name: '' });
    const warehouseForm = useForm({ name: '', location: '' });
    const error = errors as Record<string, string>;

    const setStock = (index: number, key: keyof StockEntry, value: string) =>
        setData(
            'stocks',
            data.stocks.map((stock, currentIndex) => (currentIndex === index ? { ...stock, [key]: value } : stock)),
        );

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/items');
    };

    const createSupplier = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        supplierForm.post('/suppliers', {
            preserveScroll: true,
            onSuccess: () => {
                supplierForm.reset();
                setSupplierDialogOpen(false);
            },
        });
    };

    const createWarehouse = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        warehouseForm.post('/warehouses', {
            preserveScroll: true,
            onSuccess: () => {
                warehouseForm.reset();
                setWarehouseDialogOpen(false);
            },
        });
    };

    return (
        <InventoryShell title="Add item">
            <Head title="Add item" />

            <div className="mx-auto max-w-3xl space-y-6">
                <Button asChild variant="ghost" className="-ml-3">
                    <Link href="/items">
                        <ArrowLeft aria-hidden="true" />
                        Back to items
                    </Link>
                </Button>

                <div className="space-y-1">
                    <h2 className="text-2xl font-semibold tracking-tight">Create a new item</h2>
                    <p className="text-muted-foreground text-sm">Add an item to your catalog and optionally record its opening stock.</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Item details</CardTitle>
                            <CardDescription>Enter the item’s identification and supplier information.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="item_code">Item code</Label>
                                <Input
                                    id="item_code"
                                    autoFocus
                                    value={data.item_code}
                                    onChange={(event) => setData('item_code', event.target.value)}
                                    aria-invalid={Boolean(error.item_code)}
                                    aria-describedby={error.item_code ? 'item_code-error' : 'item_code-hint'}
                                    placeholder="e.g. HW-1001"
                                />
                                <p id="item_code-hint" className="text-muted-foreground text-xs">
                                    Saved in uppercase automatically.
                                </p>
                                {error.item_code && (
                                    <p id="item_code-error" className="text-destructive text-sm">
                                        {error.item_code}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="supplier_id">Supplier</Label>
                                    <Button type="button" variant="link" size="sm" className="h-auto p-0" onClick={() => setSupplierDialogOpen(true)}>
                                        <Plus aria-hidden="true" />
                                        Add supplier
                                    </Button>
                                </div>
                                <Select value={data.supplier_id} onValueChange={(value) => setData('supplier_id', value)}>
                                    <SelectTrigger id="supplier_id" aria-invalid={Boolean(error.supplier_id)}>
                                        <SelectValue placeholder="Select a supplier" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {suppliers.map((supplier) => (
                                            <SelectItem key={supplier.id} value={String(supplier.id)}>
                                                {supplier.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {error.supplier_id && <p className="text-destructive text-sm">{error.supplier_id}</p>}
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="item_description">Description</Label>
                                <Input
                                    id="item_description"
                                    value={data.item_description}
                                    onChange={(event) => setData('item_description', event.target.value)}
                                    aria-invalid={Boolean(error.item_description)}
                                    placeholder="Describe this item"
                                />
                                {error.item_description && <p className="text-destructive text-sm">{error.item_description}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div className="space-y-1">
                                <CardTitle>
                                    Opening stock{' '}
                                    <Badge variant="secondary" className="ml-1 align-middle font-normal">
                                        Optional
                                    </Badge>
                                </CardTitle>
                                <CardDescription>Record starting quantities by warehouse. You can also add stock later.</CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setWarehouseDialogOpen(true)}>
                                    <Plus aria-hidden="true" />
                                    Add warehouse
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setData('stocks', [...data.stocks, { warehouse_id: '', quantity: '' }])}
                                >
                                    <Plus aria-hidden="true" />
                                    Add stock
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {data.stocks.length === 0 ? (
                                <div className="rounded-md border border-dashed px-4 py-8 text-center">
                                    <p className="text-muted-foreground text-sm">No opening stock added.</p>
                                    <p className="text-muted-foreground mt-1 text-xs">Use “Add stock” to record quantities by warehouse.</p>
                                </div>
                            ) : (
                                data.stocks.map((stock, index) => (
                                    <div key={index} className="grid gap-3 rounded-md border p-3 sm:grid-cols-[1fr_9rem_auto] sm:items-start">
                                        <div className="space-y-2">
                                            <Label htmlFor={`warehouse-${index}`}>Warehouse</Label>
                                            <Select value={stock.warehouse_id} onValueChange={(value) => setStock(index, 'warehouse_id', value)}>
                                                <SelectTrigger
                                                    id={`warehouse-${index}`}
                                                    aria-invalid={Boolean(error[`stocks.${index}.warehouse_id`])}
                                                >
                                                    <SelectValue placeholder="Select a warehouse" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {warehouses.map((warehouse) => (
                                                        <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                                            {warehouse.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {error[`stocks.${index}.warehouse_id`] && (
                                                <p className="text-destructive text-sm">{error[`stocks.${index}.warehouse_id`]}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor={`quantity-${index}`}>Quantity</Label>
                                            <Input
                                                id={`quantity-${index}`}
                                                type="number"
                                                min={0}
                                                step={1}
                                                inputMode="numeric"
                                                value={stock.quantity}
                                                onChange={(event) => setStock(index, 'quantity', event.target.value)}
                                                aria-invalid={Boolean(error[`stocks.${index}.quantity`])}
                                                placeholder="0"
                                            />
                                            {error[`stocks.${index}.quantity`] && (
                                                <p className="text-destructive text-sm">{error[`stocks.${index}.quantity`]}</p>
                                            )}
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="text-muted-foreground hover:text-destructive sm:mt-7"
                                            aria-label={`Remove stock entry ${index + 1}`}
                                            onClick={() =>
                                                setData(
                                                    'stocks',
                                                    data.stocks.filter((_, currentIndex) => currentIndex !== index),
                                                )
                                            }
                                        >
                                            <Trash2 aria-hidden="true" />
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                        <Button asChild variant="outline">
                            <Link href="/items">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing || suppliers.length === 0}>
                            <Plus aria-hidden="true" />
                            {processing ? 'Creating item…' : 'Create item'}
                        </Button>
                    </div>
                    {suppliers.length === 0 && <p className="text-muted-foreground text-right text-sm">Add a supplier before creating an item.</p>}
                </form>
            </div>

            <Dialog open={supplierDialogOpen} onOpenChange={setSupplierDialogOpen}>
                <DialogContent>
                    <form onSubmit={createSupplier} className="space-y-5">
                        <DialogHeader>
                            <DialogTitle>Add supplier</DialogTitle>
                            <DialogDescription>Create a supplier, then select it for this item.</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="new_supplier_name">Supplier name</Label>
                            <Input
                                id="new_supplier_name"
                                autoFocus
                                value={supplierForm.data.name}
                                onChange={(event) => supplierForm.setData('name', event.target.value)}
                                aria-invalid={Boolean(supplierForm.errors.name)}
                                placeholder="e.g. Acme Supplies"
                            />
                            {supplierForm.errors.name && <p className="text-destructive text-sm">{supplierForm.errors.name}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSupplierDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={supplierForm.processing}>
                                {supplierForm.processing ? 'Saving supplier…' : 'Save supplier'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={warehouseDialogOpen} onOpenChange={setWarehouseDialogOpen}>
                <DialogContent>
                    <form onSubmit={createWarehouse} className="space-y-5">
                        <DialogHeader>
                            <DialogTitle>Add warehouse</DialogTitle>
                            <DialogDescription>Create a warehouse, then select it when recording this item’s opening stock.</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="new_warehouse_name">Warehouse name</Label>
                            <Input
                                id="new_warehouse_name"
                                autoFocus
                                value={warehouseForm.data.name}
                                onChange={(event) => warehouseForm.setData('name', event.target.value)}
                                aria-invalid={Boolean(warehouseForm.errors.name)}
                                placeholder="e.g. Main Warehouse"
                            />
                            {warehouseForm.errors.name && <p className="text-destructive text-sm">{warehouseForm.errors.name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_warehouse_location">
                                Location <span className="text-muted-foreground">(optional)</span>
                            </Label>
                            <Input
                                id="new_warehouse_location"
                                value={warehouseForm.data.location}
                                onChange={(event) => warehouseForm.setData('location', event.target.value)}
                                aria-invalid={Boolean(warehouseForm.errors.location)}
                                placeholder="e.g. Building A, Floor 2"
                            />
                            {warehouseForm.errors.location && <p className="text-destructive text-sm">{warehouseForm.errors.location}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setWarehouseDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={warehouseForm.processing}>
                                {warehouseForm.processing ? 'Saving warehouse…' : 'Save warehouse'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </InventoryShell>
    );
}
