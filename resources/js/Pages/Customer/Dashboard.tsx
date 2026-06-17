import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import SentimentTimeline from '@/Components/charts/SentimentTimeline';
import PainPointsRadar from '@/Components/charts/PainPointsRadar';
import ReviewVelocity from '@/Components/charts/ReviewVelocity';
import KindFilterChip from '@/Components/KindFilterChip';
import { PageProps } from '@/types';

interface MyApp {
    id: number;
    name: string;
    average_rating: string;
    total_reviews: number;
    ai_summary?: string;
    kind: 'mine' | 'competitor';
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
    kind: 'all' | 'mine' | 'competitor';
    changeFeed: {
        id: number;
        app_name: string;
        app_id: number;
        field: string;
        old_value: string | null;
        new_value: string | null;
        detected_at: string;
    }[];
    onboarding: {
        needsTour: boolean;
    };
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
        case 'mine':
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
    kind,
    changeFeed,
    onboarding,
}) => {
    const [tourDismissed, setTourDismissed] = useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem('heysentinel.tourDismissed') === '1';
    });

    const showBanner = onboarding.needsTour && !tourDismissed;

    const dismissBanner = () => {
        window.localStorage.setItem('heysentinel.tourDismissed', '1');
        setTourDismissed(true);
    };

    return (
        <CustomerLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Dashboard</h1>

                {showBanner && (
                    <div className="flex items-center justify-between rounded-lg border border-shopify-200 bg-shopify-50 px-4 py-3">
                        <div className="flex items-start gap-3">
                            <svg className="mt-0.5 h-5 w-5 flex-shrink-0 text-shopify-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p className="text-sm font-medium text-shopify-900">Set up your apps</p>
                                <p className="text-xs text-shopify-700">Pick the Shopify apps you own so HeySentinel can track them on your dashboard.</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <a
                                href="/customer/my-apps"
                                className="rounded-md bg-shopify-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-shopify-600"
                            >
                                Quick setup
                            </a>
                            <button
                                type="button"
                                onClick={dismissBanner}
                                className="rounded-md px-2 py-1.5 text-xs font-medium text-shopify-700 hover:bg-shopify-100"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                )}

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
                    <div className="mb-3">
                        <KindFilterChip value={kind} partialKey="myApps" />
                    </div>
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
                                                {app.kind === 'mine'
                                                    ? <span className="ml-1.5 rounded bg-green-100 text-green-800 px-1.5 py-0.5 text-[10px]">Mine</span>
                                                    : <span className="ml-1.5 rounded bg-gray-100 text-gray-800 px-1.5 py-0.5 text-[10px]">Competitor</span>}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {app.average_rating != null ? `★ ${Number(app.average_rating).toFixed(2)}` : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {(app.total_reviews ?? 0).toLocaleString()}
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

                {changeFeed.length > 0 && (
                    <Card title="Recent Changes">
                        <div className="divide-y divide-gray-100 -mx-6 -mb-5">
                            {changeFeed.map((change) => (
                                <a key={change.id} href={`/customer/apps/${change.app_id}`} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{change.app_name}</p>
                                        <p className="text-xs text-gray-500">
                                            <span className="font-medium">{change.field}</span>: {change.old_value ?? '—'} &rarr; {change.new_value ?? '—'}
                                        </p>
                                    </div>
                                    <span className="text-xs text-gray-400">{new Date(change.detected_at).toLocaleDateString()}</span>
                                </a>
                            ))}
                        </div>
                    </Card>
                )}
            </div>
        </CustomerLayout>
    );
};

export default Dashboard;
