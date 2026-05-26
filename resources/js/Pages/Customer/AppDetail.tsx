import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import UpgradePrompt from '@/Components/ui/UpgradePrompt';
import DataTable, { Column } from '@/Components/tables/DataTable';
import { PageProps } from '@/types';
import { ShopifyApp, StoreReview, PaginatedResponse } from '@/types/models';

interface PainPoint {
    id: number;
    name: string;
    category?: string;
    mention_count: number;
}

interface Props extends PageProps {
    app: ShopifyApp;
    isFollowed: boolean;
    reviews: PaginatedResponse<StoreReview>;
    painPoints: PainPoint[];
}

const sentimentColor = (
    sentiment?: string,
): 'success' | 'warning' | 'danger' | 'gray' => {
    switch (sentiment) {
        case 'positive':
            return 'success';
        case 'mixed':
            return 'warning';
        case 'negative':
            return 'danger';
        default:
            return 'gray';
    }
};

const ratingColor = (rating: number): 'success' | 'warning' | 'danger' | 'gray' => {
    if (rating >= 4) return 'success';
    if (rating >= 3) return 'warning';
    if (rating > 0) return 'danger';
    return 'gray';
};

const AppDetail: React.FC<Props> = ({ app, isFollowed, reviews, painPoints }) => {
    const { featureGates } = usePage<PageProps>().props;
    const canExport = featureGates?.export_csv?.value === true;

    const [summaryExpanded, setSummaryExpanded] = useState(false);

    const handleFollow = () => {
        router.post(`/customer/apps/${app.id}/follow`, {}, { preserveScroll: true });
    };

    const handleUnfollow = () => {
        router.delete(`/customer/apps/${app.id}/follow`, { preserveScroll: true });
    };

    const handleExportReviews = () => {
        router.post(`/customer/export/reviews/${app.id}`);
    };

    const reviewColumns: Column<StoreReview>[] = [
        {
            key: 'reviewer_name',
            label: 'Reviewer',
            render: (row) => row.reviewer_name ?? <span className="text-gray-400">Anonymous</span>,
        },
        {
            key: 'rating',
            label: 'Rating',
            sortable: true,
            render: (row) => (
                <Badge color={ratingColor(row.rating)}>{row.rating} ★</Badge>
            ),
        },
        {
            key: 'ai_sentiment',
            label: 'Sentiment',
            render: (row) =>
                row.ai_sentiment ? (
                    <Badge color={sentimentColor(row.ai_sentiment)}>
                        {row.ai_sentiment}
                    </Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'review_text',
            label: 'Review',
            render: (row) => {
                const text = row.review_text ?? '';
                return (
                    <span className="text-gray-700 line-clamp-2 max-w-xs">
                        {text.length > 120 ? text.slice(0, 120) + '…' : text}
                    </span>
                );
            },
        },
        {
            key: 'published_at',
            label: 'Published',
            sortable: true,
            render: (row) =>
                row.published_at
                    ? new Date(row.published_at).toLocaleDateString()
                    : <span className="text-gray-400">—</span>,
        },
    ];

    return (
        <CustomerLayout>
            <Head title={app.name} />

            <div className="space-y-6">
                {/* App header */}
                <Card>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-2">
                            <div className="flex items-center gap-3 flex-wrap">
                                <h1 className="text-xl font-semibold text-gray-900">
                                    {app.name}
                                </h1>
                                {app.category && (
                                    <Badge color="primary">{typeof app.category === 'object' ? app.category.name : app.category}</Badge>
                                )}
                            </div>
                            <p className="text-sm text-gray-500">{app.developer_name}</p>

                            <div className="flex items-center gap-4 text-sm text-gray-600 flex-wrap">
                                {Number(app.average_rating) > 0 && (
                                    <span>
                                        <span className="font-medium">
                                            {Number(app.average_rating).toFixed(1)}
                                        </span>{' '}
                                        ★
                                    </span>
                                )}
                                <span>
                                    <span className="font-medium">
                                        {app.total_reviews.toLocaleString()}
                                    </span>{' '}
                                    reviews
                                </span>
                                {app.pricing_has_free && (
                                    <Badge color="success">Free plan</Badge>
                                )}
                                {app.pricing_min_usd && (
                                    <span>From ${app.pricing_min_usd}/mo</span>
                                )}
                            </div>
                        </div>

                        <div className="flex-shrink-0">
                            {isFollowed ? (
                                <Button variant="secondary" onClick={handleUnfollow}>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    Unfollow
                                </Button>
                            ) : (
                                <Button variant="primary" onClick={handleFollow}>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Follow
                                </Button>
                            )}
                        </div>
                    </div>
                </Card>

                {/* AI Summary */}
                {app.ai_summary && (
                    <Card title="AI Summary">
                        <div
                            className={
                                summaryExpanded
                                    ? 'text-sm text-gray-700'
                                    : 'text-sm text-gray-700 line-clamp-3'
                            }
                        >
                            {app.ai_summary}
                        </div>
                        <button
                            onClick={() => setSummaryExpanded((v) => !v)}
                            className="mt-2 text-sm font-medium text-shopify-500 hover:underline"
                        >
                            {summaryExpanded ? 'Show less' : 'Read more'}
                        </button>
                    </Card>
                )}

                {/* Pain Points */}
                {painPoints.length > 0 && (
                    <Card title="Pain Points">
                        <ul className="divide-y divide-gray-100">
                            {painPoints.map((pp) => (
                                <li
                                    key={pp.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium text-gray-900">
                                            {pp.name}
                                        </span>
                                        {pp.category && (
                                            <Badge color="primary">{pp.category}</Badge>
                                        )}
                                    </div>
                                    <span className="text-sm text-gray-500">
                                        {pp.mention_count} mentions
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}

                {/* Reviews */}
                <Card title="Reviews">
                    <div className="space-y-4">
                        <div className="flex justify-end">
                            {canExport ? (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={handleExportReviews}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Export CSV
                                </Button>
                            ) : (
                                <UpgradePrompt feature="Review CSV Export" />
                            )}
                        </div>

                        <DataTable
                            columns={reviewColumns}
                            data={reviews.data}
                            meta={reviews.meta}
                            links={reviews.links}
                            emptyMessage="No reviews found for this app."
                        />
                    </div>
                </Card>
            </div>
        </CustomerLayout>
    );
};

export default AppDetail;
