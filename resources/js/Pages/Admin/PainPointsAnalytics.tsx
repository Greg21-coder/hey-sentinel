import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import DataTable, { Column } from '@/Components/tables/DataTable';
import { PageProps } from '@/types';

interface PainPointsStats {
    reviews_processed: number;
    reviews_pending: number;
    pain_point_links: number;
    apps_analyzed: number;
    progress_pct: number;
    avg_per_review: number;
}

interface PainPointRow {
    id: number;
    name: string;
    slug: string;
    category: string | null;
    occurrences: number;
    apps_affected: number;
    avg_confidence: number | null;
    high_severity_count: number;
}

interface TopAppRow {
    id: number;
    name: string;
    developer_name: string;
    average_rating: number;
    total_reviews: number;
    processed_reviews: number;
    pain_point_count: number;
    top_pain_point: string | null;
    negative_pct: number | null;
}

interface Props extends PageProps {
    stats: PainPointsStats;
    topPainPoints: PainPointRow[];
    topApps: TopAppRow[];
}

const categoryColor = (cat: string | null): 'danger' | 'warning' | 'info' | 'primary' | 'success' | 'gray' => {
    switch (cat) {
        case 'pricing': return 'danger';
        case 'support': return 'warning';
        case 'performance': return 'info';
        case 'ux': return 'primary';
        case 'reliability': return 'danger';
        case 'features': return 'success';
        default: return 'gray';
    }
};

const StatCard: React.FC<{ label: string; value: string | number; sub?: string }> = ({
    label,
    value,
    sub,
}) => (
    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <p className="text-sm font-medium text-gray-500">{label}</p>
        <p className="mt-1 text-3xl font-bold text-gray-900">{value}</p>
        {sub && <p className="mt-1 text-sm text-gray-400">{sub}</p>}
    </div>
);

export default function PainPointsAnalytics({ stats, topPainPoints, topApps }: Props) {
    const painPointColumns: Column<PainPointRow>[] = [
        { key: 'name', label: 'Pain Point', sortable: true },
        {
            key: 'slug',
            label: 'Slug',
            render: (row) => <Badge color="info">{row.slug}</Badge>,
        },
        {
            key: 'category',
            label: 'Category',
            render: (row) =>
                row.category ? (
                    <Badge color={categoryColor(row.category)}>{row.category}</Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'occurrences',
            label: 'Occurrences',
            sortable: true,
            render: (row) => row.occurrences.toLocaleString(),
        },
        {
            key: 'apps_affected',
            label: 'Apps Affected',
            sortable: true,
            render: (row) => row.apps_affected.toLocaleString(),
        },
        {
            key: 'avg_confidence',
            label: 'Avg Confidence',
            render: (row) =>
                row.avg_confidence != null
                    ? `${Math.round(Number(row.avg_confidence) * 100)}%`
                    : '—',
        },
        {
            key: 'high_severity_count',
            label: 'High Severity',
            render: (row) => (
                <Badge color="danger">{row.high_severity_count}</Badge>
            ),
        },
    ];

    const topAppsColumns: Column<TopAppRow>[] = [
        { key: 'name', label: 'App', sortable: true },
        { key: 'developer_name', label: 'Developer' },
        {
            key: 'average_rating',
            label: '★ Rating',
            render: (row) => `★ ${Number(row.average_rating).toFixed(2)}`,
        },
        {
            key: 'total_reviews',
            label: 'Reviews',
            render: (row) => row.total_reviews.toLocaleString(),
        },
        {
            key: 'processed_reviews',
            label: 'Analyzed',
            render: (row) => row.processed_reviews.toLocaleString(),
        },
        {
            key: 'pain_point_count',
            label: 'Pain Signals',
            render: (row) => <Badge color="warning">{row.pain_point_count}</Badge>,
        },
        {
            key: 'top_pain_point',
            label: 'Top Pain Point',
            render: (row) =>
                row.top_pain_point ? (
                    <Badge color="danger">{row.top_pain_point}</Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'negative_pct',
            label: 'Negative %',
            render: (row) =>
                row.negative_pct != null ? `${row.negative_pct}%` : '—',
        },
    ];

    return (
        <AdminLayout>
            <Head title="Pain Points Analytics" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Pain Points Analytics</h1>

                {/* Stats cards */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard
                        label="Reviews Processed"
                        value={stats.reviews_processed.toLocaleString()}
                        sub={`${stats.progress_pct}% complete`}
                    />
                    <StatCard
                        label="Pending in Queue"
                        value={stats.reviews_pending.toLocaleString()}
                        sub="Awaiting AI extraction"
                    />
                    <StatCard
                        label="Pain Points Extracted"
                        value={stats.pain_point_links.toLocaleString()}
                        sub={`Avg ${stats.avg_per_review} per review`}
                    />
                    <StatCard
                        label="Apps with AI Signals"
                        value={stats.apps_analyzed.toLocaleString()}
                        sub="Coverage across catalog"
                    />
                </div>

                {/* Top Pain Points */}
                <Card title="Top Pain Points (across all reviews)">
                    <DataTable
                        columns={painPointColumns}
                        data={topPainPoints}
                        emptyMessage="No pain points extracted yet."
                    />
                </Card>

                {/* Top Apps by Pain Points */}
                <Card title="Apps Ranked by Pain Point Signal">
                    <DataTable
                        columns={topAppsColumns}
                        data={topApps}
                        emptyMessage="No apps with pain point signals yet."
                    />
                </Card>
            </div>
        </AdminLayout>
    );
}
