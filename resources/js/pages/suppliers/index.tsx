import InventoryShell, { PageLink, Pagination } from '@/components/inventory-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Truck } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Supplier = { id: number; name: string; items_count: number };
type Props = {
    suppliers: { data: Supplier[]; links: PageLink[]; from: number | null; to: number | null; total: number };
};

export default function SupplierIndex({ suppliers }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Supplier | null>(null);
    const form = useForm({ name: '', return_to: 'suppliers.index' });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (supplier: Supplier) => {
        setEditing(supplier);
        form.setData('name', supplier.name);
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
            form.put(`/suppliers/${editing.id}`, options);
        } else {
            form.post('/suppliers', options);
        }
    };

    const remove = (supplier: Supplier) => {
        if (window.confirm(`Delete ${supplier.name}? Suppliers assigned to items cannot be deleted.`)) {
            router.delete(`/suppliers/${supplier.id}`, { preserveScroll: true });
        }
    };

    return (
        <InventoryShell title="Suppliers">
            <Head title="Suppliers" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="space-y-1">
<<<<<<< HEAD
                        <p className="text-muted-foreground text-sm font-medium">Inventory management</p>
                        <h2 className="text-2xl font-semibold tracking-tight">Supplier dashboard</h2>
                        <p className="text-muted-foreground text-sm">Manage suppliers and their item assignments.</p>
=======
                        <p className="text-muted-foreground text-sm font-medium">Management Suppliers</p>
>>>>>>> f8d19867cdb18c751737ebaec6838da910cb6ea4
                    </div>
                    <Button onClick={openCreate}>
                        <Plus aria-hidden="true" />
                        Add supplier
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All suppliers</CardTitle>
                        <CardDescription>{suppliers.total.toLocaleString()} suppliers in the catalog</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Supplier name</TableHead>
                                    <TableHead className="text-right">Items</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {suppliers.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="h-32 text-center">
                                            <div className="flex flex-col items-center gap-1">
                                                <Truck aria-hidden="true" className="text-muted-foreground/60 mb-1 size-8" />
                                                <p className="font-medium">No suppliers yet</p>
                                                <p className="text-muted-foreground text-sm">Add a supplier to connect it to your items.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    suppliers.data.map((supplier) => (
                                        <TableRow key={supplier.id}>
                                            <TableCell className="font-medium">{supplier.name}</TableCell>
                                            <TableCell className="text-right">{supplier.items_count.toLocaleString()}</TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        aria-label={`Edit ${supplier.name}`}
                                                        onClick={() => openEdit(supplier)}
                                                    >
                                                        <Pencil aria-hidden="true" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        aria-label={`Delete ${supplier.name}`}
                                                        onClick={() => remove(supplier)}
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
                                {suppliers.total === 0
                                    ? 'No suppliers to display'
                                    : `Showing ${suppliers.from ?? 0}–${suppliers.to ?? 0} of ${suppliers.total.toLocaleString()} suppliers`}
                            </p>
                            <Pagination links={suppliers.links} />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <form onSubmit={submit} className="space-y-5">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit supplier' : 'Add supplier'}</DialogTitle>
                            <DialogDescription>
                                {editing ? 'Update the supplier name.' : 'Create a supplier for your inventory items.'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <Label htmlFor="supplier_name">Supplier name</Label>
                            <Input
                                id="supplier_name"
                                autoFocus
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                aria-invalid={Boolean(form.errors.name)}
                                placeholder="e.g. Acme Supplies"
                            />
                            {form.errors.name && <p className="text-destructive text-sm">{form.errors.name}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving…' : editing ? 'Save changes' : 'Add supplier'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </InventoryShell>
    );
}
