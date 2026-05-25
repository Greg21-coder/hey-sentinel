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
    name: string;
    slug: string;
    category?: string;
    mentions: number;
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
        router.post(`/customer/export/apps/${app.id}/reviews`);
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
                                    <Badge color="primary">{app.category}</Badge>
                                )}
                            </div>
                            <p className="text-sm text-gray-500">{app.developer_name}</p>

                            <div className="flex items-center gap-4 text-sm text-gray-600 flex-wrap">
                                {app.average_rating > 0 && (
                                    <span>
                                        <span className="font-medium">
                                            {app.average_rating.toFixed(1)}
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
                                    Unfollow
                                </Button>
                            ) : (
                                <Button variant="primary" onClick={handleFollow}>
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
                            className="mt-2 text-sm font-medium text-indigo-600 hover:underline"
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
                                    key={pp.slug}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <Badge color="gray">{pp.slug}</Badge>
                                        <span className="text-sm font-medium text-gray-900">
                                            {pp.name}
                                        </span>
                                        {pp.category && (
                                            <Badge color="primary">{pp.category}</Badge>
                                        )}
                                    </div>
                                    <span className="text-sm text-gray-500">
                                        {pp.mentions} mentions
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
