import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import { PageProps, PaginatedResponse } from '@/types';

interface RunRow {
    id: number;
    source: string;
    status: string;
    triggered_by: string;
    apps_found: number;
    apps_new: number;
    apps_existing: number;
    apps_limit: number | null;
    error_message: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
}

interface LastRun {
    id: number;
    apps_new: number;
    created_at: string;
}

interface Props extends PageProps {
    runs: PaginatedResponse<RunRow>;
    stats: {
        total_apps: number;
        pending_scraping: number;
        last_run: LastRun | null;
    };
    hasRunning: boolean;
}

const statusColor = (status: string): 'gray' | 'info' | 'success' | 'danger' => {
    switch (status) {
        case 'running': return 'info';
        case 'completed': return 'success';
        case 'failed': return 'danger';
        default: return 'gray';
    }
};

const triggerColor = (trigger: string): 'primary' | 'warning' => {
    return trigger === 'manual' ? 'primary' : 'warning';
};

function formatDuration(started: string | null, completed: string | null): string {
    if (!started || !completed) return '—';
    const ms = new Date(completed).getTime() - new Date(started).getTime();
    if (ms < 1000) return `${ms}ms`;
    const secs = Math.round(ms / 1000);
    if (secs < 60) return `${secs}s`;
    const mins = Math.floor(secs / 60);
    const remSecs = secs % 60;
    return `${mins}m ${remSecs}s`;
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

export default function DiscoveryIndex({ stats, runs, hasRunning }: Props) {
    const handleRun = () => {
        router.post('/admin/discovery/run', {}, { preserveScroll: true });
    };

    const columns: Column<RunRow>[] = [
        {
            key: 'created_at',
            label: 'Date',
            render: (row) => new Date(row.created_at).toLocaleString(),
        },
        { key: 'source', label: 'Source' },
        {
            key: 'triggered_by',
            label: 'Trigger',
            render: (row) => <Badge color={triggerColor(row.triggered_by)}>{row.triggered_by}</Badge>,
        },
        {
            key: 'apps_found',
            label: 'Found',
            render: (row) => row.apps_found.toLocaleString(),
        },
        {
            key: 'apps_new',
            label: 'New',
            render: (row) => (
                <span className={row.apps_new > 0 ? 'font-semibold text-green-700' : ''}>
                    {row.apps_new.toLocaleString()}
                </span>
            ),
        },
        {
            key: 'apps_existing',
            label: 'Existing',
            render: (row) => row.apps_existing.toLocaleString(),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <Badge color={statusColor(row.status)}>{row.status}</Badge>,
        },
        {
            key: 'duration',
            label: 'Duration',
            render: (row) => formatDuration(row.started_at, row.completed_at),
        },
    ];

    return (
        <AdminLayout>
            <Head title="Discovery" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">App Discovery</h1>
                    <Button onClick={handleRun} disabled={hasRunning}>
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        {hasRunning ? 'Discovery Running…' : 'Run Discovery Now'}
                    </Button>
                </div>

                {/* Stats cards */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-medium text-gray-500">Total Apps</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            {stats.total_apps.toLocaleString()}
                        </p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-medium text-gray-500">Pending Scraping</p>
                        <p className="mt-1 text-2xl font-bold text-amber-600">
                            {stats.pending_scraping.toLocaleString()}
                        </p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-medium text-gray-500">Last Discovery</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">
                            {stats.last_run
                                ? timeAgo(stats.last_run.created_at)
                                : 'Never'}
                        </p>
                        {stats.last_run && (
                            <p className="text-sm text-gray-500">
                                {stats.last_run.apps_new} new apps
                            </p>
                        )}
                    </div>
                </div>

                {/* History table */}
                <DataTable
                    columns={columns}
                    data={runs.data}
                    meta={runs.meta}
                    links={runs.links}
                    emptyMessage="No discovery runs yet. Click 'Run Discovery Now' to start."
                />
            </div>
        </AdminLayout>
    );
}
