import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Select from '@/Components/ui/Select';
import Button from '@/Components/ui/Button';
import { PageProps } from '@/types';

interface Plan {
    id: number;
    name: string;
}

interface Props extends PageProps {
    plans: Plan[];
}

export default function AccountCreate({ plans }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        status: 'trial',
        plan_id: '',
        billing_cycle: 'none',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/accounts');
    };

    return (
        <AdminLayout>
            <Head title="Create Account" />

            <div className="max-w-xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Create Account</h1>

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
                        placeholder="Auto-generated if empty"
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
                            Create Account
                        </Button>
                        <Button variant="secondary" href="/admin/accounts">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
