import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import { PageProps, PaginatedResponse } from '@/types';

interface AppOption {
    id: number;
    name: string;
}

interface ReviewRow {
    id: number;
    reviewer_name: string | null;
    rating: number;
    ai_status: string;
    ai_sentiment: string | null;
    published_at: string | null;
    app: { id: number; name: string } | null;
}

interface Props extends PageProps {
    reviews: PaginatedResponse<ReviewRow>;
    apps: AppOption[];
    filters: Record<string, string>;
}

const sentimentColor = (s: string | null): 'success' | 'danger' | 'gray' => {
    if (s === 'positive') return 'success';
    if (s === 'negative') return 'danger';
    return 'gray';
};

const aiStatusColor = (s: string): 'success' | 'warning' | 'danger' | 'gray' => {
    switch (s) {
        case 'processed': return 'success';
        case 'pending': return 'warning';
        case 'error': return 'danger';
        default: return 'gray';
    }
};

export default function StoreReviewsIndex({ reviews, apps, filters }: Props) {
    const appOptions = apps.map((a) => ({ value: String(a.id), label: a.name }));

    const columns: Column<ReviewRow>[] = [
        {
            key: 'app',
            label: 'App',
            render: (row) => row.app?.name ?? <span className="text-gray-400">—</span>,
        },
        {
            key: 'reviewer_name',
            label: 'Reviewer',
            render: (row) => row.reviewer_name ?? '—',
        },
        {
            key: 'rating',
            label: 'Rating',
            render: (row) => `★ ${row.rating}`,
        },
        {
            key: 'ai_sentiment',
            label: 'Sentiment',
            render: (row) =>
                row.ai_sentiment ? (
                    <Badge color={sentimentColor(row.ai_sentiment)}>{row.ai_sentiment}</Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'ai_status',
            label: 'AI Status',
            render: (row) => (
                <Badge color={aiStatusColor(row.ai_status)}>{row.ai_status}</Badge>
            ),
        },
        {
            key: 'published_at',
            label: 'Published',
            render: (row) =>
                row.published_at ? new Date(row.published_at).toLocaleDateString() : '—',
        },
    ];

    return (
        <AdminLayout>
            <Head title="Store Reviews" />

            <div className="space-y-4">
                <h1 className="text-2xl font-bold text-gray-900">Store Reviews</h1>

                <DataTable
                    columns={columns}
                    pagination={reviews}
                    filters={[
                        {
                            key: 'shopify_app_id',
                            label: 'App',
                            type: 'select',
                            options: appOptions,
                        },
                        {
                            key: 'rating',
                            label: 'Rating',
                            type: 'select',
                            options: [1, 2, 3, 4, 5].map((r) => ({ value: String(r), label: `${r} star${r > 1 ? 's' : ''}` })),
                        },
                        {
                            key: 'ai_sentiment',
                            label: 'Sentiment',
                            type: 'select',
                            options: [
                                { value: 'positive', label: 'Positive' },
                                { value: 'neutral', label: 'Neutral' },
                                { value: 'negative', label: 'Negative' },
                            ],
                        },
                        {
                            key: 'ai_status',
                            label: 'AI Status',
                            type: 'select',
                            options: [
                                { value: 'pending', label: 'Pending' },
                                { value: 'batched', label: 'Batched' },
                                { value: 'processed', label: 'Processed' },
                                { value: 'skipped', label: 'Skipped' },
                                { value: 'error', label: 'Error' },
                            ],
                        },
                    ]}
                    currentFilters={filters}
                />
            </div>
        </AdminLayout>
    );
}
