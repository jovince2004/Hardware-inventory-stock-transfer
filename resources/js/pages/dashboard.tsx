import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, Boxes, Package, Plus, Truck, Warehouse } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

type Props = {
    summary: { items: number; suppliers: number; warehouses: number; stockUnits: number };
};

export default function Dashboard({ summary }: Props) {
    const metrics = [
        { label: 'Total items', value: summary.items, icon: Package, href: '/items', action: 'View items' },
        { label: 'Units in stock', value: summary.stockUnits, icon: Boxes, href: '/transfers/create', action: 'New transfer' },
        { label: 'Suppliers', value: summary.suppliers, icon: Truck, href: '/suppliers', action: 'View suppliers' },
        { label: 'Warehouses', value: summary.warehouses, icon: Warehouse, href: '/warehouses', action: 'View warehouses' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold">Dashboard</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Hardware inventory overview</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map(({ label, value, icon: Icon, href, action }) => (
                        <Card key={label}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">{label}</CardTitle>
                                <Icon aria-hidden="true" className="text-muted-foreground size-4" />
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-2xl font-bold">{value.toLocaleString()}</p>
                                <Link href={href} className="text-primary flex items-center gap-1 text-sm font-medium hover:underline">
                                    {action}
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Quick actions</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <Link href="/items/create" className="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium">
                            <Plus aria-hidden="true" className="size-4" />
                            Add item
                        </Link>
                        <Link href="/transfers/create" className="border-input inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium">
                            <ArrowLeftRight aria-hidden="true" className="size-4" />
                            New stock transfer
                        </Link>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
