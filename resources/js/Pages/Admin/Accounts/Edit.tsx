import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Select from '@/Components/ui/Select';
import Button from '@/Components/ui/Button';
import Modal from '@/Components/ui/Modal';
import { PageProps } from '@/types';

interface Owner {
    id: number;
    name: string;
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
    plan_id: number | null;
    owner: Owner | null;
    plan: Plan | null;
}

interface Props extends PageProps {
    account: Account;
    plans: Plan[];
}

export default function AccountEdit({ account, plans }: Props) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        name: account.name,
        slug: account.slug,
        status: account.status,
        plan_id: account.plan_id ? String(account.plan_id) : '',
        billing_cycle: account.billing_cycle,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/accounts/${account.id}`);
    };

    const handleDelete = () => {
        router.delete(`/admin/accounts/${account.id}`, {
            onFinish: () => setShowDeleteModal(false),
        });
    };

    return (
        <AdminLayout>
            <Head title={`Edit ${account.name}`} />

            <div className="max-w-xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Edit Account</h1>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <Input
                        label="Slug"
                        value={data.slug}
                        onChange={(e) => setData('slug', e.target.value)}
                        error={errors.slug}
                    />
                    <Select
                        label="Status"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        error={errors.status}
                        options={[
                            { value: 'trial', label: 'Trial' },
                            { value: 'active', label: 'Active' },
                            { value: 'past_due', label: 'Past Due' },
                            { value: 'suspended', label: 'Suspended' },
                            { value: 'cancelled', label: 'Cancelled' },
                        ]}
                    />
                    <Select
                        label="Plan"
                        value={data.plan_id}
                        onChange={(e) => setData('plan_id', e.target.value)}
                        error={errors.plan_id}
                        placeholder="No plan"
                        options={plans.map((p) => ({ value: p.id, label: p.name }))}
                    />
                    <Select
                        label="Billing Cycle"
                        value={data.billing_cycle}
                        onChange={(e) => setData('billing_cycle', e.target.value)}
                        error={errors.billing_cycle}
                        options={[
                            { value: 'monthly', label: 'Monthly' },
                            { value: 'yearly', label: 'Yearly' },
                            { value: 'none', label: 'None' },
                        ]}
                    />

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" loading={processing}>
                            Save Changes
                        </Button>
                        <Button variant="secondary" href="/admin/accounts">
                            Cancel
                        </Button>
                    </div>
                </form>

                <div className="border-t border-gray-200 pt-6">
                    <Button variant="danger" onClick={() => setShowDeleteModal(true)}>
                        Delete Account
                    </Button>
                </div>
            </div>

            <Modal
                open={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title="Delete Account"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to delete{' '}
                    <span className="font-semibold">{account.name}</span>? This action cannot be undone.
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
