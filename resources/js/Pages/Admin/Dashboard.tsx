import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/ui/Card';
import { PageProps } from '@/types';

interface DashboardStats {
    accounts: Record<string, number>;
    total_accounts: number;
    total_users: number;
    total_apps: number;
    total_processed: number;
    total_pending: number;
    ai_batches: Record<string, number>;
    last_scraped_at: string | null;
}

interface Props extends PageProps {
    stats: DashboardStats;
}

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

export default function Dashboard({ stats }: Props) {
    const accountStatuses = ['trial', 'active', 'past_due', 'suspended', 'cancelled'];

    const formatDate = (dt: string | null) =>
        dt ? new Date(dt).toLocaleString() : '—';

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

                {/* Top-level stats */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard label="Total Accounts" value={stats.total_accounts} />
                    <StatCard label="Total Users" value={stats.total_users} />
                    <StatCard label="Total Apps" value={stats.total_apps} />
                    <StatCard
                        label="Last Scraped"
                        value={formatDate(stats.last_scraped_at)}
                    />
                </div>

                {/* Accounts by status */}
                <Card title="Accounts by Status">
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        {accountStatuses.map((status) => (
                            <div key={status} className="text-center">
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats.accounts[status] ?? 0}
                                </p>
                                <p className="mt-1 text-sm capitalize text-gray-500">
                                    {status.replace('_', ' ')}
                                </p>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Reviews pipeline */}
                <div className="grid grid-cols-2 gap-4">
                    <StatCard
                        label="Reviews Processed"
                        value={stats.total_processed}
                        sub="AI analysis complete"
                    />
                    <StatCard
                        label="Reviews Pending"
                        value={stats.total_pending}
                        sub="Awaiting AI processing"
                    />
                </div>

                {/* AI Batches */}
                {Object.keys(stats.ai_batches).length > 0 && (
                    <Card title="AI Batches by Status">
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {Object.entries(stats.ai_batches).map(([status, count]) => (
                                <div key={status} className="text-center">
                                    <p className="text-2xl font-bold text-gray-900">{count}</p>
                                    <p className="mt-1 text-sm capitalize text-gray-500">{status}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
