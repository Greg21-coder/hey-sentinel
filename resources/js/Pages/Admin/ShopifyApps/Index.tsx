import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import Modal from '@/Components/ui/Modal';
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
    avatar_url: string | null;
    ai_summary: string | null;
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
    const [summaryModal, setSummaryModal] = useState<{ name: string; text: string } | null>(null);

    const handleUnlist = (id: number) => {
        router.post(`/admin/shopify-apps/${id}/unlist`, {}, { preserveScroll: true });
    };

    const handleRelist = (id: number) => {
        router.post(`/admin/shopify-apps/${id}/relist`, {}, { preserveScroll: true });
    };

    const columns: Column<AppRow>[] = [
        {
            key: 'avatar_url',
            label: '',
            render: (row) =>
                row.avatar_url ? (
                    <img src={row.avatar_url} alt={row.name} className="w-8 h-8 rounded-lg object-cover" />
                ) : (
                    <div className="w-8 h-8 rounded-lg bg-surface-200 flex items-center justify-center text-surface-500 text-xs font-bold">
                        {row.name.charAt(0)}
                    </div>
                ),
        },
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
            key: 'ai_summary',
            label: 'AI Summary',
            render: (row) =>
                row.ai_summary ? (
                    <button
                        onClick={() => setSummaryModal({ name: row.name, text: row.ai_summary! })}
                        className="text-left text-xs text-surface-600 line-clamp-2 max-w-[200px] hover:text-surface-900 cursor-pointer"
                    >
                        {row.ai_summary.length > 80 ? row.ai_summary.slice(0, 80) + '…' : row.ai_summary}
                    </button>
                ) : (
                    <span className="text-gray-400 text-xs">—</span>
                ),
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
                                className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-shopify-50 text-shopify-700 hover:bg-shopify-100"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View
                            </Link>
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

            {summaryModal && (
                <Modal
                    open={true}
                    onClose={() => setSummaryModal(null)}
                    title={`AI Summary — ${summaryModal.name}`}
                >
                    <div className="text-sm text-surface-700 leading-relaxed whitespace-pre-wrap">
                        {summaryModal.text}
                    </div>
                </Modal>
            )}
        </AdminLayout>
    );
}
