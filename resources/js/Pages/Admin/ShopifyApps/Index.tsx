import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import { PageProps, PaginatedResponse } from '@/types';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface AppRow {
    id: number;
    name: string;
    developer_name: string;
    average_rating: number;
    total_reviews: number;
    scraping_status: string;
    unlisted_at: string | null;
    category: Category | null;
}

interface Props extends PageProps {
    apps: PaginatedResponse<AppRow>;
    filters: Record<string, string>;
}

const scrapingStatusColor = (status: string): 'success' | 'warning' | 'danger' | 'gray' => {
    switch (status) {
        case 'scraped': return 'success';
        case 'pending': return 'warning';
        case 'error': return 'danger';
        default: return 'gray';
    }
};

export default function ShopifyAppsIndex({ apps, filters }: Props) {
    const handleUnlist = (id: number) => {
        router.post(`/admin/shopify-apps/${id}/unlist`, {}, { preserveScroll: true });
    };

    const handleRelist = (id: number) => {
        router.post(`/admin/shopify-apps/${id}/relist`, {}, { preserveScroll: true });
    };

    const columns: Column<AppRow>[] = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'developer_name', label: 'Developer' },
        {
            key: 'category',
            label: 'Category',
            render: (row) =>
                row.category ? (
                    <Badge color="info">{row.category.name}</Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'average_rating',
            label: 'Rating',
            render: (row) => `★ ${Number(row.average_rating).toFixed(2)}`,
        },
        {
            key: 'total_reviews',
            label: 'Reviews',
            render: (row) => row.total_reviews.toLocaleString(),
        },
        {
            key: 'scraping_status',
            label: 'Scraping',
            render: (row) => (
                <Badge color={scrapingStatusColor(row.scraping_status)}>{row.scraping_status}</Badge>
            ),
        },
        {
            key: 'unlisted_at',
            label: 'Unlisted',
            render: (row) =>
                row.unlisted_at ? (
                    <Badge color="danger">Unlisted</Badge>
                ) : null,
        },
    ];

    return (
        <AdminLayout>
            <Head title="Shopify Apps" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Shopify Apps</h1>
                </div>

                <DataTable
                    columns={columns}
                    data={apps.data}
                    meta={apps.meta}
                    links={apps.links}
                    filters={[
                        { key: 'search', label: 'Search', type: 'text' },
                        { key: 'show_unlisted', label: 'Show Unlisted', type: 'toggle' },
                    ]}
                    currentFilters={filters}
                    rowActions={(row) => (
                        <div className="flex items-center gap-2 justify-end">
                            <Link
                                href={`/admin/shopify-apps/${row.id}`}
                                className="text-sm text-shopify-500 hover:text-shopify-700"
                            >
                                View
                            </Link>
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
