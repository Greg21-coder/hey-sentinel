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
                                className="text-sm text-shopify-500 hover:text-shopify-700"
                            >
                                Edit
                            </Link>
                            <button
                                onClick={() => setDeleteTarget(row)}
                                className="text-sm text-red-600 hover:text-red-800"
                            >
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
