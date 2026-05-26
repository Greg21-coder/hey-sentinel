import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Button from '@/Components/ui/Button';
import Toggle from '@/Components/ui/Toggle';
import { PageProps } from '@/types';

export default function UserCreate(_props: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        is_super_admin: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/users');
    };

    return (
        <AdminLayout>
            <Head title="Create User" />

            <div className="max-w-xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Create User</h1>

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
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                        required
                    />
                    <div className="flex items-center gap-3">
                        <Toggle
                            checked={data.is_super_admin}
                            onChange={(val) => setData('is_super_admin', val)}
                        />
                        <span className="text-sm font-medium text-gray-700">Super Admin</span>
                    </div>
                    {errors.is_super_admin && (
                        <p className="text-sm text-red-600">{errors.is_super_admin}</p>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" loading={processing}>
                            Create User
                        </Button>
                        <Button variant="secondary" href="/admin/users">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
