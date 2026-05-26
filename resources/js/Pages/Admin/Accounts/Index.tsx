import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import Modal from '@/Components/ui/Modal';
import { PageProps, PaginatedResponse } from '@/types';

interface Owner {
    id: number;
    name: string;
    email: string;
}

interface Plan {
    id: number;
    name: string;
}

interface Account {
    id: number;
    name: string;
    slug: string;
    status: string;
    billing_cycle: string;
    users_count: number;
    created_at: string;
    owner: Owner | null;
    plan: Plan | null;
}

interface Props extends PageProps {
    accounts: PaginatedResponse<Account>;
    filters: Record<string, string>;
}

const statusColor = (status: string): 'success' | 'info' | 'danger' | 'warning' | 'gray' => {
    switch (status) {
        case 'active': return 'success';
        case 'trial': return 'info';
        case 'cancelled': return 'danger';
        case 'suspended': return 'warning';
        default: return 'gray';
    }
};

export default function AccountsIndex({ accounts, filters }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<Account | null>(null);

    const columns: Column<Account>[] = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'slug', label: 'Slug' },
        {
            key: 'owner',
            label: 'Owner',
            render: (row) => row.owner?.name ?? <span className="text-gray-400">—</span>,
        },
        {
            key: 'plan',
            label: 'Plan',
            render: (row) => row.plan?.name ?? <span className="text-gray-400">—</span>,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <Badge color={statusColor(row.status)}>{row.status.replace('_', ' ')}</Badge>
            ),
        },
        {
            key: 'users_count',
            label: 'Users',
            render: (row) => String(row.users_count),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (row) => new Date(row.created_at).toLocaleDateString(),
        },
    ];

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/admin/accounts/${deleteTarget.id}`, {
            onFinish: () => setDeleteTarget(null),
        });
    };

    return (
        <AdminLayout>
            <Head title="Accounts" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Accounts</h1>
                    <Button href="/admin/accounts/create">New Account</Button>
                </div>

                <DataTable
                    columns={columns}
                    data={accounts.data}
                    meta={accounts.meta}
                    links={accounts.links}
                    filters={[
                        {
                            key: 'status',
                            label: 'Status',
                            type: 'select',
                            options: [
                                { value: 'trial', label: 'Trial' },
                                { value: 'active', label: 'Active' },
                                { value: 'past_due', label: 'Past Due' },
                                { value: 'suspended', label: 'Suspended' },
                                { value: 'cancelled', label: 'Cancelled' },
                            ],
                        },
                        { key: 'search', label: 'Search', type: 'text' },
                    ]}
                    currentFilters={filters}
                    rowActions={(row) => (
                        <div className="flex items-center gap-2 justify-end">
                            <Link
                                href={`/admin/accounts/${row.id}/edit`}
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
                title="Delete Account"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to delete{' '}
                    <span className="font-semibold">{deleteTarget?.name}</span>? This action cannot be undone.
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
