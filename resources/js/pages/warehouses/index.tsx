import InventoryShell, { PageLink, Pagination } from '@/components/inventory-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Warehouse } from 'lucide-react';
import { FormEvent, useState } from 'react';

type WarehouseRecord = {
    id: number;
    name: string;
    location: string | null;
    incoming_transfers_count: number;
    outgoing_transfers_count: number;
};
type Props = {
    warehouses: { data: WarehouseRecord[]; links: PageLink[]; from: number | null; to: number | null; total: number };
};

export default function WarehouseIndex({ warehouses }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<WarehouseRecord | null>(null);
    const form = useForm({ name: '', location: '', return_to: 'warehouses.index' });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (warehouse: WarehouseRecord) => {
        setEditing(warehouse);
        form.setData({ name: warehouse.name, location: warehouse.location ?? '', return_to: 'warehouses.index' });
        form.clearErrors();
        setDialogOpen(true);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                form.reset();
                setEditing(null);
            },
        };

        if (editing) {
            form.put(`/warehouses/${editing.id}`, options);
        } else {
            form.post('/warehouses', options);
        }
    };

    const remove = (warehouse: WarehouseRecord) => {
        if (window.confirm(`Delete ${warehouse.name}? Warehouses with stock transfer history cannot be deleted.`)) {
            router.delete(`/warehouses/${warehouse.id}`, { preserveScroll: true });
        }
    };

    return (
        <InventoryShell title="Warehouses">
            <Head title="Warehouses" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="space-y-1">
                        <p className="text-muted-foreground text-sm">Manage warehouse locations and review transfer activity.</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus aria-hidden="true" />
                        Add warehouse
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All warehouses</CardTitle>
                        <CardDescription>{warehouses.total.toLocaleString()} warehouses in the catalog</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Warehouse</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead className="text-right">Transfers</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {warehouses.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-32 text-center">
                                            <div className="flex flex-col items-center gap-1">
                                                <Warehouse aria-hidden="true" className="text-muted-foreground/60 mb-1 size-8" />
                                                <p className="font-medium">No warehouses yet</p>
                                                <p className="text-muted-foreground text-sm">Add a warehouse to track item stock.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    warehouses.data.map((warehouse) => (
                                        <TableRow key={warehouse.id}>
                                            <TableCell className="font-medium">{warehouse.name}</TableCell>
                                            <TableCell className="text-muted-foreground">{warehouse.location || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                {(warehouse.incoming_transfers_count + warehouse.outgoing_transfers_count).toLocaleString()}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        aria-label={`Edit ${warehouse.name}`}
                                                        onClick={() => openEdit(warehouse)}
                                                    >
                                                        <Pencil aria-hidden="true" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        aria-label={`Delete ${warehouse.name}`}
                                                        onClick={() => remove(warehouse)}
                                                    >
                                                        <Trash2 aria-hidden="true" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <div className="mt-4 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                {warehouses.total === 0
                                    ? 'No warehouses to display'
                                    : `Showing ${warehouses.from ?? 0}–${warehouses.to ?? 0} of ${warehouses.total.toLocaleString()} warehouses`}
                            </p>
                            <Pagination links={warehouses.links} />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <form onSubmit={submit} className="space-y-5">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit warehouse' : 'Add warehouse'}</DialogTitle>
                            <DialogDescription>
                                {editing ? 'Update the warehouse details.' : 'Create a warehouse for inventory and stock transfers.'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="warehouse_name">Warehouse name</Label>
                            <Input
                                id="warehouse_name"
                                autoFocus
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                aria-invalid={Boolean(form.errors.name)}
                                placeholder="e.g. Main Warehouse"
                            />
                            {form.errors.name && <p className="text-destructive text-sm">{form.errors.name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="warehouse_location">Location (optional)</Label>
                            <Input
                                id="warehouse_location"
                                value={form.data.location}
                                onChange={(event) => form.setData('location', event.target.value)}
                                aria-invalid={Boolean(form.errors.location)}
                                placeholder="e.g. Building A, Floor 2"
                            />
                            {form.errors.location && <p className="text-destructive text-sm">{form.errors.location}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving…' : editing ? 'Save changes' : 'Add warehouse'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </InventoryShell>
    );
}
