import InventoryShell from '@/components/inventory-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';

type Props = {
    item: { id: number; item_code: string; item_description: string; supplier_id: number };
    suppliers: { id: number; name: string }[];
};

export default function ItemEdit({ item, suppliers }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        item_code: item.item_code,
        item_description: item.item_description,
        supplier_id: String(item.supplier_id),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(`/items/${item.id}`);
    };

    return (
        <InventoryShell title="Edit item">
            <Head title={`Edit ${item.item_code}`} />

            <div className="mx-auto max-w-3xl space-y-6">
                <Button asChild variant="ghost" className="-ml-3">
                    <Link href={`/items/${item.id}`}>
                        <ArrowLeft aria-hidden="true" />
                        Back to item
                    </Link>
                </Button>

                <div className="space-y-1">
                    <h2 className="text-2xl font-semibold tracking-tight">Edit item</h2>
                    <p className="text-muted-foreground text-sm">Update the catalog details for {item.item_code}.</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Item details</CardTitle>
                            <CardDescription>Changes here will not alter the item’s stock history.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="item_code">Item code</Label>
                                <Input
                                    id="item_code"
                                    value={data.item_code}
                                    onChange={(event) => setData('item_code', event.target.value)}
                                    aria-invalid={Boolean(errors.item_code)}
                                />
                                {errors.item_code && <p className="text-destructive text-sm">{errors.item_code}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="supplier_id">Supplier</Label>
                                <Select value={data.supplier_id} onValueChange={(value) => setData('supplier_id', value)}>
                                    <SelectTrigger id="supplier_id" aria-invalid={Boolean(errors.supplier_id)}>
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
                                {errors.supplier_id && <p className="text-destructive text-sm">{errors.supplier_id}</p>}
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="item_description">Description</Label>
                                <Input
                                    id="item_description"
                                    value={data.item_description}
                                    onChange={(event) => setData('item_description', event.target.value)}
                                    aria-invalid={Boolean(errors.item_description)}
                                />
                                {errors.item_description && <p className="text-destructive text-sm">{errors.item_description}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                        <Button asChild variant="outline">
                            <Link href={`/items/${item.id}`}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing || suppliers.length === 0}>
                            <Save aria-hidden="true" />
                            {processing ? 'Saving changes…' : 'Save changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </InventoryShell>
    );
}
