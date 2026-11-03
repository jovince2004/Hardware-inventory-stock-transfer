import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function InventoryShell({ title, children }: PropsWithChildren<{ title: string }>) {
    const { props } = usePage<{ flash?: { success?: string } }>();
    const success = props.flash?.success;

    return (
        <div className="min-h-screen bg-gray-50 text-gray-900">
            <nav className="border-b bg-white">
                <div className="mx-auto flex max-w-5xl items-center gap-6 px-4 py-3">
                    <span className="font-semibold">Hardware Store</span>
                    <Link href="/items" className="text-blue-600 hover:underline">
                        Items
                    </Link>
                    <Link href="/transfers" className="text-blue-600 hover:underline">
                        Stock Transfers
                    </Link>
                </div>
            </nav>
            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 className="mb-4 text-2xl font-bold">{title}</h1>
                {success && <div className="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-green-800">{success}</div>}
                {children}
            </main>
        </div>
    );
}

export type PageLink = { url: string | null; label: string; active: boolean };

export function Pagination({ links }: { links: PageLink[] }) {
    if (links.length <= 3) return null;
    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        preserveScroll
                        className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100'}`}
                        dangerouslySetInnerHTML={{ __html: l.label }}
                    />
                ) : (
                    <span
                        key={i}
                        className="rounded border bg-gray-100 px-3 py-1 text-sm text-gray-400"
                        dangerouslySetInnerHTML={{ __html: l.label }}
                    />
                ),
            )}
        </div>
    );
}

export const inputCls = 'w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none';
export const btnCls = 'rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50';
export const Err = ({ msg }: { msg?: string }) => (msg ? <p className="mt-1 text-sm text-red-600">{msg}</p> : null);
