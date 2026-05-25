import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import DataTable, { Column } from '@/Components/tables/DataTable';
import { PageProps, PaginatedResponse } from '@/types';

interface Category {
    id: number;
    name: string;
}

interface AppDetail {
    id: number;
    name: string;
    developer_name: string;
    developer_url: string | null;
    average_rating: number;
    total_reviews: number;
    scraping_status: string;
    unlisted_at: string | null;
    last_scraped_at: string | null;
    ai_summary: string | null;
    category: Category | null;
    description: string | null;
}

interface Review {
    id: number;
    reviewer_name: string | null;
    rating: number;
    review_text: string | null;
    ai_status: string;
    ai_sentiment: string | null;
    published_at: string | null;
}

interface PainPoint {
    id: number;
    name: string;
    slug: string;
    category: string | null;
    occurrences: number;
}

interface Props extends PageProps {
    app: AppDetail;
    reviews: PaginatedResponse<Review>;
    painPoints: PainPoint[];
}

export default function ShopifyAppShow({ app, reviews, painPoints }: Props) {
    const handleUnlist = () => {
        router.post(`/admin/shopify-apps/${app.id}/unlist`);
    };

    const handleRelist = () => {
        router.post(`/admin/shopify-apps/${app.id}/relist`);
    };

    const reviewColumns: Column<Review>[] = [
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
                    <Badge
                        color={
                            row.ai_sentiment === 'positive'
                                ? 'success'
                                : row.ai_sentiment === 'negative'
                                ? 'danger'
                                : 'gray'
                        }
                    >
                        {row.ai_sentiment}
                    </Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'ai_status',
            label: 'AI Status',
            render: (row) => <Badge color="gray">{row.ai_status}</Badge>,
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
            <Head title={app.name} />

            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{app.name}</h1>
                        <p className="mt-1 text-sm text-gray-500">by {app.developer_name}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="secondary" href="/admin/shopify-apps">
                            Back
                        </Button>
                        {app.unlisted_at ? (
                            <Button variant="primary" onClick={handleRelist}>
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                Relist
                            </Button>
                        ) : (
                            <Button variant="danger" onClick={handleUnlist}>
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                Unlist
                            </Button>
                        )}
                    </div>
                </div>

                {/* App info cards */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Rating</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            ★ {Number(app.average_rating).toFixed(2)}
                        </p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Reviews</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            {app.total_reviews.toLocaleString()}
                        </p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Category</p>
                        <p className="mt-1 text-sm font-semibold text-gray-700">
                            {app.category?.name ?? '—'}
                        </p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Status</p>
                        <div className="mt-1 flex flex-wrap gap-1">
                            <Badge color="gray">{app.scraping_status}</Badge>
                            {app.unlisted_at && <Badge color="danger">Unlisted</Badge>}
                        </div>
                    </div>
                </div>

                {/* AI Summary */}
                {app.ai_summary && (
                    <Card title="AI Summary">
                        <p className="text-sm text-gray-700 leading-relaxed">{app.ai_summary}</p>
                    </Card>
                )}

                {/* Pain points */}
                {painPoints.length > 0 && (
                    <Card title="Pain Points">
                        <div className="flex flex-wrap gap-2">
                            {painPoints.map((pp) => (
                                <div
                                    key={pp.id}
                                    className="flex items-center gap-1 rounded-full bg-red-50 px-3 py-1 text-sm"
                                >
                                    <span className="font-medium text-red-700">{pp.name}</span>
                                    <span className="text-red-400">×{pp.occurrences}</span>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {/* Reviews table */}
                <Card title="Reviews">
                    <DataTable
                        columns={reviewColumns}
                        data={reviews.data}
                        meta={reviews.meta}
                        links={reviews.links}
                    />
                </Card>
            </div>
        </AdminLayout>
    );
}
