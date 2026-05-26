import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import Modal from '@/Components/ui/Modal';
import { PageProps, PaginatedResponse } from '@/types';

interface PlanRow {
    id: number;
    name: string;
    slug: string;
    monthly_price: number;
    yearly_price: number;
    status: string;
    is_public: boolean;
    accounts_count: number;
    deleted_at: string | null;
}

interface Props extends PageProps {
    plans: PaginatedResponse<PlanRow>;
}

const statusColor = (status: string): 'success' | 'warning' | 'gray' => {
    switch (status) {
        case 'active': return 'success';
        case 'draft': return 'warning';
        default: return 'gray';
    }
};

export default function PlansIndex({ plans }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<PlanRow | null>(null);

    const columns: Column<PlanRow>[] = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'slug', label: 'Slug' },
        {
            key: 'monthly_price',
            label: 'Monthly Price',
            render: (row) => `$${Number(row.monthly_price).toFixed(2)}`,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <Badge color={statusColor(row.status)}>{row.status}</Badge>
            ),
        },
        {
            key: 'accounts_count',
            label: 'Accounts',
            render: (row) => String(row.accounts_count),
        },
    ];

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/admin/plans/${deleteTarget.id}`, {
            onFinish: () => setDeleteTarget(null),
        });
    };

    return (
        <AdminLayout>
            <Head title="Plans" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Plans</h1>
                    <Button href="/admin/plans/create">New Plan</Button>
                </div>

                <DataTable
                    columns={columns}
                    data={plans.data}
                    meta={plans.meta}
                    links={plans.links}
                    rowActions={(row) => (
                        <div className="flex items-center gap-2 justify-end">
                            <Link
                                href={`/admin/plans/${row.id}/edit`}
                                className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-shopify-50 text-shopify-700 hover:bg-shopify-100"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                Edit
                            </Link>
                            <button
                                onClick={() => setDeleteTarget(row)}
                                className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Delete
                            </button>
                        </div>
                    )}
                />
            </div>

            <Modal
                open={deleteTarget !== null}
                onClose={() => setDeleteTarget(null)}
                title="Delete Plan"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to delete{' '}
                    <span className="font-semibold">{deleteTarget?.name}</span>?
                </p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setDeleteTarget(null)}>
                        Cancel
                    </Button>
                    <Button variant="danger" onClick={handleDelete}>
                        Delete
                    </Button>
                </div>
            </Modal>
        </AdminLayout>
    );
}
