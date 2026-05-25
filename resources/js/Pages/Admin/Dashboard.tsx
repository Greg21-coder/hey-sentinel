import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, PieChart, Pie, Cell, Legend } from 'recharts';

interface Stats {
    accounts: Record<string, number>;
    total_accounts: number;
    total_users: number;
    total_apps: number;
    total_reviews: number;
    last_scraped_at: string | null;
}

interface Pipeline {
    scraped: number;
    pending: number;
    batched: number;
    processed: number;
    pain_points: number;
    unique_pain_points: number;
    progress_pct: number;
}

interface Props {
    stats: Stats;
    pipeline: Pipeline;
    ai_batches: Record<string, number>;
    sentimentBreakdown: Record<string, number>;
    reviewsPerWeek: { labels: string[]; counts: number[] };
}

const SENTIMENT_COLORS: Record<string, string> = {
    positive: '#22c55e',
    neutral: '#94a3b8',
    mixed: '#eab308',
    negative: '#ef4444',
};

const ACCOUNT_STATUS_COLORS: Record<string, string> = {
    trial: '#6366f1',
    active: '#22c55e',
    cancelled: '#ef4444',
    expired: '#94a3b8',
};

interface StatCardProps {
    label: string;
    value: string | number;
    sub?: string;
    icon: string;
    color: string;
}

function StatCard({ label, value, sub, icon, color }: StatCardProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
            <div className={`flex-shrink-0 w-12 h-12 rounded-lg ${color} flex items-center justify-center`}>
                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={icon} />
                </svg>
            </div>
            <div>
                <p className="text-sm font-medium text-gray-500">{label}</p>
                <p className="text-2xl font-bold text-gray-900">{typeof value === 'number' ? value.toLocaleString() : value}</p>
                {sub && <p className="text-xs text-gray-400 mt-0.5">{sub}</p>}
            </div>
        </div>
    );
}

interface PipelineStepProps {
    label: string;
    value: number;
    icon: string;
    color: string;
    isLast?: boolean;
}

function PipelineStep({ label, value, icon, color, isLast }: PipelineStepProps) {
    return (
        <div className="flex items-center gap-0 flex-1 min-w-0">
            <div className="flex flex-col items-center gap-1 flex-1">
                <div className={`w-14 h-14 rounded-xl ${color} flex items-center justify-center`}>
                    <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={icon} />
                    </svg>
                </div>
                <p className="text-xl font-bold text-gray-900">{value.toLocaleString()}</p>
                <p className="text-xs font-medium text-gray-500 text-center">{label}</p>
            </div>
            {!isLast && (
                <svg className="w-6 h-6 text-gray-300 flex-shrink-0 -mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
            )}
        </div>
    );
}

export default function Dashboard({ stats, pipeline, ai_batches, sentimentBreakdown, reviewsPerWeek }: Props) {
    const formatDate = (dt: string | null) =>
        dt ? new Date(dt).toLocaleString() : 'Never';

    const sentimentData = Object.entries(sentimentBreakdown).map(([name, value]) => ({
        name: name.charAt(0).toUpperCase() + name.slice(1),
        value,
    }));

    const reviewsChartData = reviewsPerWeek.labels.map((label, i) => ({
        week: label,
        reviews: reviewsPerWeek.counts[i],
    }));

    const accountData = Object.entries(stats.accounts).map(([status, count]) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' '),
        value: Number(count),
    }));

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p className="text-sm text-gray-500">
                        Last scraped: <span className="font-medium">{formatDate(stats.last_scraped_at)}</span>
                    </p>
                </div>

                {/* Top-level stats with icons */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        label="Accounts"
                        value={stats.total_accounts}
                        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        color="bg-indigo-500"
                    />
                    <StatCard
                        label="Users"
                        value={stats.total_users}
                        icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        color="bg-blue-500"
                    />
                    <StatCard
                        label="Apps Tracked"
                        value={stats.total_apps}
                        icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        color="bg-amber-500"
                    />
                    <StatCard
                        label="Reviews Collected"
                        value={stats.total_reviews}
                        icon="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        color="bg-green-500"
                    />
                </div>

                {/* Pipeline visualization */}
                <Card title="Data Pipeline" description="End-to-end flow from scraping to insights">
                    <div className="flex items-start justify-between py-4">
                        <PipelineStep
                            label="Scraped"
                            value={pipeline.scraped}
                            icon="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            color="bg-slate-600"
                        />
                        <PipelineStep
                            label="Pending"
                            value={pipeline.pending}
                            icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            color="bg-amber-500"
                        />
                        <PipelineStep
                            label="In Batch"
                            value={pipeline.batched}
                            icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            color="bg-blue-500"
                        />
                        <PipelineStep
                            label="Processed"
                            value={pipeline.processed}
                            icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            color="bg-green-500"
                        />
                        <PipelineStep
                            label="Pain Points"
                            value={pipeline.pain_points}
                            icon="M13 10V3L4 14h7v7l9-11h-7z"
                            color="bg-red-500"
                            isLast
                        />
                    </div>
                    <div className="mt-2 flex items-center gap-3">
                        <div className="flex-1 bg-gray-200 rounded-full h-2">
                            <div
                                className="bg-green-500 h-2 rounded-full"
                                style={{ width: `${pipeline.progress_pct}%` }}
                            />
                        </div>
                        <span className="text-sm font-medium text-gray-600">{pipeline.progress_pct}% processed</span>
                        <Badge color="info">{pipeline.unique_pain_points} unique pain points</Badge>
                    </div>
                </Card>

                {/* Charts row */}
                <div className="grid lg:grid-cols-2 gap-6">
                    {/* Reviews per week */}
                    <Card title="Review Intake" description="Reviews collected per week (last 12 weeks)">
                        <ResponsiveContainer width="100%" height={260}>
                            <BarChart data={reviewsChartData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                <XAxis dataKey="week" tick={{ fontSize: 11 }} />
                                <YAxis tick={{ fontSize: 12 }} />
                                <Tooltip />
                                <Bar dataKey="reviews" fill="#6366f1" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </Card>

                    {/* Sentiment breakdown */}
                    <Card title="Sentiment Breakdown" description="AI classification of all processed reviews">
                        {sentimentData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={260}>
                                <PieChart>
                                    <Pie
                                        data={sentimentData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={60}
                                        outerRadius={100}
                                        paddingAngle={2}
                                        dataKey="value"
                                    >
                                        {sentimentData.map((entry) => (
                                            <Cell
                                                key={entry.name}
                                                fill={SENTIMENT_COLORS[entry.name.toLowerCase()] || '#94a3b8'}
                                            />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-10">No sentiment data yet.</p>
                        )}
                    </Card>
                </div>

                {/* Bottom row: accounts + AI batches */}
                <div className="grid lg:grid-cols-2 gap-6">
                    {/* Accounts by status */}
                    <Card title="Accounts by Status">
                        {accountData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart data={accountData} layout="vertical">
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                    <XAxis type="number" tick={{ fontSize: 12 }} />
                                    <YAxis dataKey="name" type="category" tick={{ fontSize: 12 }} width={80} />
                                    <Tooltip />
                                    <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                                        {accountData.map((entry) => (
                                            <Cell
                                                key={entry.name}
                                                fill={ACCOUNT_STATUS_COLORS[entry.name.toLowerCase()] || '#94a3b8'}
                                            />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-10">No accounts yet.</p>
                        )}
                    </Card>

                    {/* AI Batches */}
                    <Card title="AI Batch Status">
                        {Object.keys(ai_batches).length > 0 ? (
                            <div className="grid grid-cols-2 gap-4">
                                {Object.entries(ai_batches).map(([status, count]) => {
                                    const color = status === 'completed' ? 'bg-green-500' :
                                                  status === 'pending' ? 'bg-amber-500' :
                                                  status === 'failed' ? 'bg-red-500' : 'bg-slate-400';
                                    return (
                                        <div key={status} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                            <div className={`w-3 h-3 rounded-full ${color}`} />
                                            <div>
                                                <p className="text-lg font-bold text-gray-900">{Number(count)}</p>
                                                <p className="text-xs text-gray-500 capitalize">{status}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-10">No AI batches yet.</p>
                        )}
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
