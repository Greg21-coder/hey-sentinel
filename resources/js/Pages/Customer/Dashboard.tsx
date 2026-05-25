import React from 'react';
import { Head } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import SentimentTimeline from '@/Components/charts/SentimentTimeline';
import PainPointsRadar from '@/Components/charts/PainPointsRadar';
import ReviewVelocity from '@/Components/charts/ReviewVelocity';
import { PageProps } from '@/types';

interface MyApp {
    id: number;
    name: string;
    average_rating: string;
    total_reviews: number;
    ai_summary?: string;
    pivot_kind: string;
    pivot_followed_at: string;
}

interface Props extends PageProps {
    stats: {
        followed: number;
        pain_points: number;
        negative: number;
        velocity: number;
    };
    sentimentTimeline: {
        labels: string[];
        positive: number[];
        neutral: number[];
        mixed: number[];
        negative: number[];
    };
    painPointsRadar: {
        labels: string[];
        counts: number[];
    };
    reviewVelocity: {
        labels: string[];
        counts: number[];
    };
    myApps: MyApp[];
}

const StatCard: React.FC<{ label: string; value: number | string }> = ({
    label,
    value,
}) => (
    <Card>
        <div className="text-2xl font-bold text-gray-900">{value}</div>
        <div className="mt-1 text-sm text-gray-500">{label}</div>
    </Card>
);

const kindBadgeColor = (kind: string): 'primary' | 'success' | 'warning' | 'gray' => {
    switch (kind) {
        case 'competitor':
            return 'warning';
        case 'own':
            return 'success';
        default:
            return 'gray';
    }
};

const Dashboard: React.FC<Props> = ({
    stats,
    sentimentTimeline,
    painPointsRadar,
    reviewVelocity,
    myApps,
}) => {
    return (
        <CustomerLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Dashboard</h1>

                {/* Stat cards */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard label="Apps Followed" value={stats.followed} />
                    <StatCard label="Pain Points Tracked" value={stats.pain_points} />
                    <StatCard label="Negative Reviews" value={stats.negative} />
                    <StatCard label="Reviews This Week" value={stats.velocity} />
                </div>

                {/* Charts row */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card title="Sentiment Timeline">
                        <SentimentTimeline
                            labels={sentimentTimeline.labels}
                            positive={sentimentTimeline.positive}
                            neutral={sentimentTimeline.neutral}
                            mixed={sentimentTimeline.mixed}
                            negative={sentimentTimeline.negative}
                        />
                    </Card>

                    <Card title="Pain Points Radar">
                        <PainPointsRadar
                            labels={painPointsRadar.labels}
                            counts={painPointsRadar.counts}
                        />
                    </Card>
                </div>

                {/* Review velocity */}
                <Card title="Review Velocity">
                    <ReviewVelocity
                        labels={reviewVelocity.labels}
                        counts={reviewVelocity.counts}
                    />
                </Card>

                {/* My Apps table */}
                <Card title="My Apps">
                    <div className="overflow-x-auto -mx-6 -mb-5">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        App
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Rating
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Reviews
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Kind
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {myApps.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-6 py-10 text-center text-sm text-gray-400"
                                        >
                                            No apps followed yet.{' '}
                                            <a
                                                href="/customer/apps"
                                                className="text-shopify-500 hover:underline"
                                            >
                                                Browse apps
                                            </a>
                                        </td>
                                    </tr>
                                ) : (
                                    myApps.map((app) => (
                                        <tr
                                            key={app.id}
                                            className="hover:bg-gray-50"
                                        >
                                            <td className="px-6 py-3 font-medium text-gray-900">
                                                <a
                                                    href={`/customer/apps/${app.id}`}
                                                    className="hover:text-shopify-500"
                                                >
                                                    {app.name}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {Number(app.average_rating).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {Number(app.total_reviews).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge color={kindBadgeColor(app.pivot_kind)}>
                                                    {app.pivot_kind}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </CustomerLayout>
    );
};

export default Dashboard;
