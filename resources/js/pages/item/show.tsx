import InventoryShell from '@/components/inventory-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';

type Item = {
    id: number;
    item_code: string;
    item_description: string;
    supplier: { name: string } | null;
    stock: { warehouse_id: number; warehouse: string | null; quantity: number }[];
};

export default function ItemShow({ item }: { item: Item }) {
    const totalStock = item.stock.reduce((total, stock) => total + stock.quantity, 0);

    return (
        <InventoryShell title="Item details">
            <Head title={item.item_code} />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button asChild variant="ghost" className="-ml-3">
                        <Link href="/items">
                            <ArrowLeft aria-hidden="true" />
                            Back to items
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href={`/items/${item.id}/edit`}>
                            <Pencil aria-hidden="true" />
                            Edit item
                        </Link>
                    </Button>
                </div>

                <div className="space-y-1">
                    <h2 className="text-2xl font-semibold tracking-tight">{item.item_description}</h2>
                    <p className="text-muted-foreground font-mono text-sm">{item.item_code}</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Item information</CardTitle>
                        <CardDescription>Catalog details and current stock by warehouse.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <dt className="text-muted-foreground text-sm">Supplier</dt>
                                <dd className="font-medium">{item.supplier?.name ?? '—'}</dd>
                            </div>
                            <div className="space-y-1">
                                <dt className="text-muted-foreground text-sm">Total units in stock</dt>
                                <dd className="font-medium">{totalStock.toLocaleString()}</dd>
                            </div>
                        </dl>

                        <div className="space-y-2 border-t pt-4">
                            <h3 className="font-medium">Stock by warehouse</h3>
                            {item.stock.length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {item.stock.map((stock) => (
                                        <Badge key={stock.warehouse_id} variant="secondary" className="font-normal">
                                            {stock.warehouse ?? 'Unknown warehouse'}: {stock.quantity.toLocaleString()}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No stock recorded.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </InventoryShell>
    );
}
