import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import { PageProps, PaginatedResponse } from '@/types';

interface StoreRow {
    id: number;
    domain: string;
    store_name: string | null;
    country_code: string | null;
    estimated_monthly_visits: number | null;
    estimated_monthly_sales_usd: number | null;
    apps_installed_count: number | null;
    scraping_status: string | null;
    unlisted_at: string | null;
}

interface Props extends PageProps {
    stores: PaginatedResponse<StoreRow>;
    filters: Record<string, string>;
}

export default function ShopifyStoresIndex({ stores, filters }: Props) {
    const handleUnlist = (id: number) => {
        router.post(`/admin/shopify-stores/${id}/unlist`, {}, { preserveScroll: true });
    };

    const handleRelist = (id: number) => {
        router.post(`/admin/shopify-stores/${id}/relist`, {}, { preserveScroll: true });
    };

    const columns: Column<StoreRow>[] = [
        { key: 'domain', label: 'Domain', sortable: true },
        {
            key: 'store_name',
            label: 'Store Name',
            render: (row) => row.store_name ?? <span className="text-gray-400">—</span>,
        },
        {
            key: 'country_code',
            label: 'Country',
            render: (row) => row.country_code ?? '—',
        },
        {
            key: 'estimated_monthly_visits',
            label: 'Monthly Visits',
            render: (row) =>
                row.estimated_monthly_visits != null
                    ? row.estimated_monthly_visits.toLocaleString()
                    : '—',
        },
        {
            key: 'apps_installed_count',
            label: 'Apps',
            render: (row) => (row.apps_installed_count != null ? String(row.apps_installed_count) : '—'),
        },
        {
            key: 'unlisted_at',
            label: 'Unlisted',
            render: (row) =>
                row.unlisted_at ? <Badge color="danger">Unlisted</Badge> : null,
        },
    ];

    return (
        <AdminLayout>
            <Head title="Shopify Stores" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Shopify Stores</h1>
                </div>

                <DataTable
                    columns={columns}
                    pagination={stores}
                    filters={[
                        { key: 'search', label: 'Search', type: 'text' },
                        { key: 'show_unlisted', label: 'Show Unlisted', type: 'toggle' },
                    ]}
                    currentFilters={filters}
                    rowActions={(row) => (
                        <div className="flex items-center gap-2 justify-end">
                            {row.unlisted_at ? (
                                <button
                                    onClick={() => handleRelist(row.id)}
                                    className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Relist
                                </button>
                            ) : (
                                <button
                                    onClick={() => handleUnlist(row.id)}
                                    className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    Unlist
                                </button>
                            )}
                        </div>
                    )}
                />
            </div>
        </AdminLayout>
    );
}
