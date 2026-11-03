import InventoryShell, { PageLink, Pagination } from '@/components/inventory-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, Link, router } from '@inertiajs/react';
import { Boxes, Package, Plus, Search, Truck, Warehouse } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Item = {
    id: number;
    item_code: string;
    item_description: string;
    supplier: { name: string } | null;
    stock: { warehouse_id: number; warehouse: string | null; quantity: number }[];
};

type Props = {
    items: { data: Item[]; links: PageLink[]; from: number | null; to: number | null; total: number };
    filters: { search: string };
    summary: { items: number; suppliers: number; warehouses: number; stockUnits: number };
};

const metrics = [
    { key: 'items', label: 'Total items', icon: Package, format: (value: number) => value.toLocaleString() },
    { key: 'stockUnits', label: 'Units in stock', icon: Boxes, format: (value: number) => value.toLocaleString() },
    { key: 'suppliers', label: 'Suppliers', icon: Truck, format: (value: number) => value.toLocaleString() },
    { key: 'warehouses', label: 'Warehouses', icon: Warehouse, format: (value: number) => value.toLocaleString() },
] as const;

export default function ItemIndex({ items, filters, summary }: Props) {
    const [search, setSearch] = useState(filters.search);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/items', { search }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearSearch = () => {
        setSearch('');
        router.get('/items', {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <InventoryShell title="Items">
            <Head title="Items" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="space-y-1">
                        <p className="text-muted-foreground text-sm font-medium">Inventory overview</p>
                        <h2 className="text-2xl font-semibold tracking-tight">Item dashboard</h2>
                        <p className="text-muted-foreground text-sm">Manage your catalog, stock levels, and suppliers.</p>
                    </div>
                    <Button asChild>
                        <Link href="/items/create">
                            <Plus aria-hidden="true" />
                            Add item
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map(({ key, label, icon: Icon, format }) => (
                        <Card key={key}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">{label}</CardTitle>
                                <Icon aria-hidden="true" className="text-muted-foreground size-4" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold">{format(summary[key])}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader className="gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle>All items</CardTitle>
                            <p className="text-muted-foreground text-sm">Search and review your inventory catalog.</p>
                        </div>
                        <form onSubmit={submit} role="search" className="flex w-full gap-2 sm:max-w-sm">
                            <div className="relative flex-1">
                                <Search aria-hidden="true" className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    aria-label="Search item code or description"
                                    className="pl-9"
                                    placeholder="Search items..."
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                />
                            </div>
                            <Button type="submit" variant="secondary">
                                Search
                            </Button>
                            {filters.search && (
                                <Button type="button" variant="ghost" onClick={clearSearch}>
                                    Clear
                                </Button>
                            )}
                        </form>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead>Supplier</TableHead>
                                    <TableHead>Stock by warehouse</TableHead>
                                    <TableHead className="text-right">Total units</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-32 text-center">
                                            <div className="flex flex-col items-center gap-1">
                                                <Package aria-hidden="true" className="text-muted-foreground/60 mb-1 size-8" />
                                                <p className="font-medium">{filters.search ? 'No matching items' : 'No items yet'}</p>
                                                <p className="text-muted-foreground text-sm">
                                                    {filters.search
                                                        ? 'Try another search or clear the filter.'
                                                        : 'Add your first item to start tracking inventory.'}
                                                </p>
                                                {!filters.search && (
                                                    <Button asChild variant="link" className="mt-1">
                                                        <Link href="/items/create">Create your first item</Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    items.data.map((item) => {
                                        const totalStock = item.stock.reduce((total, stock) => total + stock.quantity, 0);

                                        return (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <span className="text-muted-foreground font-mono text-xs font-medium">{item.item_code}</span>
                                                        <span className="font-medium">{item.item_description}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">{item.supplier?.name ?? '—'}</TableCell>
                                                <TableCell>
                                                    {item.stock.length > 0 ? (
                                                        <div className="flex flex-wrap gap-1.5">
                                                            {item.stock.map((stock) => (
                                                                <Badge key={stock.warehouse_id} variant="secondary" className="font-normal">
                                                                    {stock.warehouse ?? 'Unknown warehouse'}: {stock.quantity.toLocaleString()}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <Badge variant="outline" className="text-muted-foreground">
                                                            No stock
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">{totalStock.toLocaleString()}</TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>

                        <div className="mt-4 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                {items.total === 0
                                    ? 'No items to display'
                                    : `Showing ${items.from ?? 0}–${items.to ?? 0} of ${items.total.toLocaleString()} items`}
                            </p>
                            <Pagination links={items.links} />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </InventoryShell>
    );
}
