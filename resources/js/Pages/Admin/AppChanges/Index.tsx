import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import { PageProps, PaginatedResponse } from '@/types';

interface AppRef {
    id: number;
    name: string;
    shopify_app_handle: string;
}

interface ChangeRow {
    id: number;
    shopify_app_id: number;
    field: string;
    old_value: string | null;
    new_value: string | null;
    detected_at: string;
    app: AppRef | null;
}

interface Props extends PageProps {
    changes: PaginatedResponse<ChangeRow>;
    filters: Record<string, string>;
}

const fieldColor = (field: string): 'danger' | 'warning' | 'info' | 'gray' => {
    if (field.startsWith('pricing')) return 'danger';
    if (field === 'average_rating') return 'warning';
    if (field === 'total_reviews') return 'info';
    return 'gray';
};

export default function AppChangesIndex({ changes, filters }: Props) {
    const columns: Column<ChangeRow>[] = [
        {
            key: 'detected_at',
            label: 'Date',
            render: (row) => new Date(row.detected_at).toLocaleString(),
        },
        {
            key: 'app',
            label: 'App',
            render: (row) => row.app?.name ?? '—',
        },
        {
            key: 'field',
            label: 'Field',
            render: (row) => <Badge color={fieldColor(row.field)}>{row.field}</Badge>,
        },
        {
            key: 'old_value',
            label: 'Old Value',
            render: (row) => (
                <span className="text-xs text-red-600 max-w-[200px] block truncate">
                    {row.old_value ?? '—'}
                </span>
            ),
        },
        {
            key: 'new_value',
            label: 'New Value',
            render: (row) => (
                <span className="text-xs text-green-600 max-w-[200px] block truncate">
                    {row.new_value ?? '—'}
                </span>
            ),
        },
    ];

    return (
        <AdminLayout>
            <Head title="App Changes" />

            <div className="space-y-4">
                <h1 className="text-2xl font-bold text-gray-900">App Changes</h1>

                <DataTable
                    columns={columns}
                    pagination={changes}
                    filters={[
                        { key: 'search', label: 'App Name', type: 'text' },
                        {
                            key: 'field',
                            label: 'Field',
                            type: 'select',
                            options: [
                                { value: 'name', label: 'Name' },
                                { value: 'pricing_raw', label: 'Pricing' },
                                { value: 'average_rating', label: 'Rating' },
                                { value: 'total_reviews', label: 'Reviews' },
                                { value: 'developer_name', label: 'Developer' },
                                { value: 'category_name', label: 'Category' },
                            ],
                        },
                    ]}
                    currentFilters={filters}
                    emptyMessage="No changes detected yet. Changes appear after apps are re-scraped."
                />
            </div>
        </AdminLayout>
    );
}
