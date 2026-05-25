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
                    data={stores.data}
                    meta={stores.meta}
                    links={stores.links}
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
                                    className="text-sm text-green-600 hover:text-green-800"
                                >
                                    Relist
                                </button>
                            ) : (
                                <button
                                    onClick={() => handleUnlist(row.id)}
                                    className="text-sm text-red-600 hover:text-red-800"
                                >
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
