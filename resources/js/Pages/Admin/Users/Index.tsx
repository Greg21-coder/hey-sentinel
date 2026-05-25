import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Button from '@/Components/ui/Button';
import Modal from '@/Components/ui/Modal';
import { PageProps, PaginatedResponse } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    accounts_count: number;
    last_login_at: string | null;
    created_at: string;
    is_super_admin: boolean;
}

interface Props extends PageProps {
    users: PaginatedResponse<UserRow>;
}

export default function UsersIndex({ users }: Props) {
    const { props } = usePage<PageProps>();
    const currentUserId = props.auth.user?.id;
    const [deleteTarget, setDeleteTarget] = useState<UserRow | null>(null);

    const columns: Column<UserRow>[] = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        {
            key: 'accounts_count',
            label: 'Accounts',
            render: (row) => String(row.accounts_count),
        },
        {
            key: 'last_login_at',
            label: 'Last Login',
            render: (row) =>
                row.last_login_at ? new Date(row.last_login_at).toLocaleDateString() : '—',
        },
        {
            key: 'created_at',
            label: 'Joined',
            render: (row) => new Date(row.created_at).toLocaleDateString(),
        },
    ];

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/admin/users/${deleteTarget.id}`, {
            onFinish: () => setDeleteTarget(null),
        });
    };

    return (
        <AdminLayout>
            <Head title="Users" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Users</h1>
                    <Button href="/admin/users/create">New User</Button>
                </div>

                <DataTable
                    columns={columns}
                    data={users.data}
                    meta={users.meta}
                    links={users.links}
                    rowActions={(row) => (
                        <div className="flex items-center gap-2 justify-end">
                            <Link
                                href={`/admin/users/${row.id}/edit`}
                                className="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Edit
                            </Link>
                            {row.id !== currentUserId && (
                                <button
                                    onClick={() => setDeleteTarget(row)}
                                    className="text-sm text-red-600 hover:text-red-800"
                                >
                                    Delete
                                </button>
                            )}
                        </div>
                    )}
                />
            </div>

            <Modal
                open={deleteTarget !== null}
                onClose={() => setDeleteTarget(null)}
                title="Delete User"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to permanently delete{' '}
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
