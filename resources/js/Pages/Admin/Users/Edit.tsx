import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Button from '@/Components/ui/Button';
import Toggle from '@/Components/ui/Toggle';
import Modal from '@/Components/ui/Modal';
import { PageProps } from '@/types';

interface UserData {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
}

interface Props extends PageProps {
    user: UserData;
}

export default function UserEdit({ user }: Props) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        is_super_admin: user.is_super_admin,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    const handleDelete = () => {
        router.delete(`/admin/users/${user.id}`, {
            onFinish: () => setShowDeleteModal(false),
        });
    };

    return (
        <AdminLayout>
            <Head title={`Edit ${user.name}`} />

            <div className="max-w-xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Edit User</h1>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <Input
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={errors.email}
                        required
                    />
                    <Input
                        label="New Password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                        placeholder="Leave blank to keep current"
                    />
                    <div className="flex items-center gap-3">
                        <Toggle
                            checked={data.is_super_admin}
                            onChange={(val) => setData('is_super_admin', val)}
                        />
                        <span className="text-sm font-medium text-gray-700">Super Admin</span>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" loading={processing}>
                            Save Changes
                        </Button>
                        <Button variant="secondary" href="/admin/users">
                            Cancel
                        </Button>
                    </div>
                </form>

                <div className="border-t border-gray-200 pt-6">
                    <Button variant="danger" onClick={() => setShowDeleteModal(true)}>
                        Delete User
                    </Button>
                </div>
            </div>

            <Modal
                open={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title="Delete User"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to permanently delete{' '}
                    <span className="font-semibold">{user.name}</span>?
                </p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
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
