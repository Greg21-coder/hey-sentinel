import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import DataTable, { Column } from '@/Components/tables/DataTable';
import { PageProps, PaginatedResponse } from '@/types';
import {
    BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer, Cell,
} from 'recharts';

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
    filters: Record<string, string>;
    charts: {
        sentimentBreakdown: Record<string, number>;
        ratingDistribution: Record<string, number>;
        reviewTimeline: Record<string, number>;
    };
}

const SENTIMENT_COLORS: Record<string, string> = {
    positive: '#008060',
    neutral: '#64748b',
    mixed: '#ffc453',
    negative: '#d72c0d',
};

const RATING_COLORS: Record<number, string> = {
    1: '#d72c0d',
    2: '#e8590c',
    3: '#ffc453',
    4: '#7bc47f',
    5: '#008060',
};

export default function ShopifyAppShow({ app, reviews, painPoints, filters, charts }: Props) {
    const handleUnlist = () => {
        router.post(`/admin/shopify-apps/${app.id}/unlist`);
    };

    const handleRelist = () => {
        router.post(`/admin/shopify-apps/${app.id}/relist`);
    };

    const sentimentData = Object.entries(charts.sentimentBreakdown).map(([key, value]) => ({
        name: key.charAt(0).toUpperCase() + key.slice(1),
        value,
        fill: SENTIMENT_COLORS[key] ?? '#94a3b8',
    }));

    const ratingData = [1, 2, 3, 4, 5].map((r) => ({
        name: `${r}★`,
        value: charts.ratingDistribution[String(r)] ?? 0,
        fill: RATING_COLORS[r],
    }));

    const timelineData = Object.entries(charts.reviewTimeline).map(([month, count]) => ({
        month,
        reviews: count,
    }));

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
            key: 'review_text',
            label: 'Review',
            render: (row) => (
                <span className="text-xs text-gray-600 line-clamp-2 max-w-[300px] block">
                    {row.review_text ? (row.review_text.length > 120 ? row.review_text.slice(0, 120) + '…' : row.review_text) : '—'}
                </span>
            ),
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
                                : row.ai_sentiment === 'mixed'
                                ? 'warning'
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
                {/* Header */}
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
                            <Button variant="primary" onClick={handleRelist}>Relist</Button>
                        ) : (
                            <Button variant="danger" onClick={handleUnlist}>Unlist</Button>
                        )}
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Rating</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            {app.average_rating != null ? `★ ${Number(app.average_rating).toFixed(2)}` : '—'}
                        </p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <p className="text-sm font-medium text-gray-500">Reviews</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            {(app.total_reviews ?? 0).toLocaleString()}
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

                {/* Growth Intelligence Charts */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card title="Sentiment Breakdown">
                        {sentimentData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart data={sentimentData} layout="vertical" margin={{ left: 60, right: 16 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" horizontal={false} />
                                    <XAxis type="number" tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} />
                                    <YAxis type="category" dataKey="name" tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} width={60} />
                                    <Tooltip contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: '12px' }} />
                                    <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                                        {sentimentData.map((entry, i) => (
                                            <Cell key={i} fill={entry.fill} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 py-8 text-center">No sentiment data yet</p>
                        )}
                    </Card>

                    <Card title="Rating Distribution">
                        <ResponsiveContainer width="100%" height={200}>
                            <BarChart data={ratingData} margin={{ top: 8, right: 16, left: 0, bottom: 8 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                                <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} />
                                <YAxis tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} />
                                <Tooltip contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: '12px' }} />
                                <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                    {ratingData.map((entry, i) => (
                                        <Cell key={i} fill={entry.fill} />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </Card>

                    <Card title="Review Timeline (12m)">
                        {timelineData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={200}>
                                <LineChart data={timelineData} margin={{ top: 8, right: 16, left: 0, bottom: 8 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                    <XAxis dataKey="month" tick={{ fontSize: 10, fill: '#6b7280' }} axisLine={false} tickLine={false} />
                                    <YAxis tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} />
                                    <Tooltip contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: '12px' }} />
                                    <Line type="monotone" dataKey="reviews" stroke="#008060" strokeWidth={2} dot={{ r: 3, fill: '#008060' }} />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 py-8 text-center">No timeline data yet</p>
                        )}
                    </Card>
                </div>

                {/* AI Summary */}
                {app.ai_summary && (
                    <Card title="AI Summary">
                        <p className="text-sm text-gray-700 leading-relaxed">{app.ai_summary}</p>
                    </Card>
                )}

                {/* Pain Points */}
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

                {/* Reviews Table with Filters */}
                <Card title="Reviews">
                    <DataTable
                        columns={reviewColumns}
                        pagination={reviews}
                        filters={[
                            {
                                key: 'rating',
                                label: 'Rating',
                                type: 'select',
                                options: [
                                    { value: '1', label: '1 Star' },
                                    { value: '2', label: '2 Stars' },
                                    { value: '3', label: '3 Stars' },
                                    { value: '4', label: '4 Stars' },
                                    { value: '5', label: '5 Stars' },
                                ],
                            },
                            {
                                key: 'sentiment',
                                label: 'Sentiment',
                                type: 'select',
                                options: [
                                    { value: 'positive', label: 'Positive' },
                                    { value: 'neutral', label: 'Neutral' },
                                    { value: 'mixed', label: 'Mixed' },
                                    { value: 'negative', label: 'Negative' },
                                ],
                            },
                        ]}
                        currentFilters={filters}
                        emptyMessage="No reviews found."
                    />
                </Card>
            </div>
        </AdminLayout>
    );
}
