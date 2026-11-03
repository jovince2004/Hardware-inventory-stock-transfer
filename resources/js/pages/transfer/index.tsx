import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import InventoryShell, { btnCls, inputCls, PageLink, Pagination } from '@/components/inventory-shell';

type Transfer = {
    id: number;
    transfer_date: string;
    transfer_reason: string;
    approved_by_staff: string;
    transfer_status: string;
    source: { name: string } | null;
    target: { name: string } | null;
    details: { id: number; quantity_transferred: number; item: { item_code: string } }[];
};

type Props = {
    transfers: { data: Transfer[]; links: PageLink[] };
    filters: { search: string };
};

const statusColor: Record<string, string> = {
    completed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    failed: 'bg-red-100 text-red-800',
};

export default function TransferIndex({ transfers, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/transfers', { search }, { preserveState: true });
    };

    return (
        <InventoryShell title="Stock Transfers">
            <Head title="Stock Transfers" />
            <div className="mb-4 flex items-center justify-between gap-3">
                <form onSubmit={submit} className="flex gap-2">
                    <input className={inputCls} placeholder="Search reason or approver" value={search}
                        onChange={(e) => setSearch(e.target.value)} />
                    <button className={btnCls}>Search</button>
                </form>
                <Link href="/transfers/create" className={btnCls}>+ New Transfer</Link>
            </div>

            <div className="overflow-x-auto rounded border bg-white">
                <table className="w-full text-left text-sm">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="p-3">Date</th>
                            <th className="p-3">From → To</th>
                            <th className="p-3">Items</th>
                            <th className="p-3">Reason</th>
                            <th className="p-3">Approved by</th>
                            <th className="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {transfers.data.length === 0 && (
                            <tr><td colSpan={6} className="p-4 text-center text-gray-500">No transfers yet.</td></tr>
                        )}
                        {transfers.data.map((t) => (
                            <tr key={t.id} className="border-t align-top">
                                <td className="p-3">{t.transfer_date?.slice(0, 10)}</td>
                                <td className="p-3">{t.source?.name ?? 'Supplier / opening'} → {t.target?.name}</td>
                                <td className="p-3">
                                    {t.details.map((d) => (
                                        <div key={d.id}><span className="font-mono">{d.item.item_code}</span> × {d.quantity_transferred}</div>
                                    ))}
                                </td>
                                <td className="p-3">{t.transfer_reason}</td>
                                <td className="p-3">{t.approved_by_staff}</td>
                                <td className="p-3">
                                    <span className={`rounded px-2 py-1 text-xs ${statusColor[t.transfer_status] ?? ''}`}>{t.transfer_status}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={transfers.links} />
        </InventoryShell>
    );
}
