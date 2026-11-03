import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import InventoryShell, { btnCls, Err, inputCls } from '@/components/inventory-shell';

type Props = {
    warehouses: { id: number; name: string }[];
    items: { id: number; item_code: string; item_description: string }[];
};

export default function TransferCreate({ warehouses, items }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        source_warehouse_id: '',
        target_warehouse_id: '',
        transfer_date: new Date().toISOString().slice(0, 10),
        transfer_reason: '',
        approved_by_staff: '',
        items: [{ item_code: '', quantity: '' }] as { item_code: string; quantity: string }[],
    });
    const err = errors as Record<string, string>;

    const setLine = (i: number, key: 'item_code' | 'quantity', value: string) =>
        setData('items', data.items.map((l, idx) => (idx === i ? { ...l, [key]: value } : l)));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/transfers');
    };

    return (
        <InventoryShell title="New Stock Transfer">
            <Head title="New Transfer" />
            <form onSubmit={submit} className="max-w-2xl space-y-4 rounded border bg-white p-6">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="mb-1 block font-medium">Source warehouse</label>
                        <select className={inputCls} value={data.source_warehouse_id} onChange={(e) => setData('source_warehouse_id', e.target.value)}>
                            <option value="">Select</option>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select>
                        <Err msg={err.source_warehouse_id} />
                    </div>
                    <div>
                        <label className="mb-1 block font-medium">Target warehouse</label>
                        <select className={inputCls} value={data.target_warehouse_id} onChange={(e) => setData('target_warehouse_id', e.target.value)}>
                            <option value="">Select</option>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select>
                        <Err msg={err.target_warehouse_id} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="mb-1 block font-medium">Transfer date</label>
                        <input type="date" className={inputCls} value={data.transfer_date} onChange={(e) => setData('transfer_date', e.target.value)} />
                        <Err msg={err.transfer_date} />
                    </div>
                    <div>
                        <label className="mb-1 block font-medium">Approved by</label>
                        <input className={inputCls} value={data.approved_by_staff} onChange={(e) => setData('approved_by_staff', e.target.value)} />
                        <Err msg={err.approved_by_staff} />
                    </div>
                </div>

                <div>
                    <label className="mb-1 block font-medium">Reason</label>
                    <input className={inputCls} value={data.transfer_reason} onChange={(e) => setData('transfer_reason', e.target.value)} />
                    <Err msg={err.transfer_reason} />
                </div>

                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <label className="font-medium">Items</label>
                        <button type="button" className="text-sm text-blue-600"
                            onClick={() => setData('items', [...data.items, { item_code: '', quantity: '' }])}>
                            + Add item
                        </button>
                    </div>
                    {data.items.map((line, i) => (
                        <div key={i} className="mb-2 flex gap-2">
                            <div className="flex-1">
                                <select className={inputCls} value={line.item_code} onChange={(e) => setLine(i, 'item_code', e.target.value)}>
                                    <option value="">Select item</option>
                                    {items.map((it) => (
                                        <option key={it.id} value={it.item_code}>{it.item_code} - {it.item_description}</option>
                                    ))}
                                </select>
                                <Err msg={err[`items.${i}.item_code`]} />
                            </div>
                            <div className="w-28">
                                <input type="number" min={1} className={inputCls} placeholder="Qty" value={line.quantity}
                                    onChange={(e) => setLine(i, 'quantity', e.target.value)} />
                                <Err msg={err[`items.${i}.quantity`]} />
                            </div>
                            {data.items.length > 1 && (
                                <button type="button" className="text-red-600"
                                    onClick={() => setData('items', data.items.filter((_, idx) => idx !== i))}>✕</button>
                            )}
                        </div>
                    ))}
                    <Err msg={err.items} />
                </div>

                <div className="flex gap-3">
                    <button className={btnCls} disabled={processing}>Transfer stock</button>
                    <Link href="/transfers" className="px-4 py-2 text-gray-600">Cancel</Link>
                </div>
            </form>
        </InventoryShell>
    );
}
