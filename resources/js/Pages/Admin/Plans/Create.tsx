import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Select from '@/Components/ui/Select';
import Button from '@/Components/ui/Button';
import Toggle from '@/Components/ui/Toggle';
import { PageProps } from '@/types';

export default function PlanCreate(_props: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        slug: '',
        name: '',
        description: '',
        monthly_price: '0',
        yearly_price: '0',
        sort_order: '0',
        status: 'draft',
        is_public: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/plans');
    };

    return (
        <AdminLayout>
            <Head title="Create Plan" />

            <div className="max-w-xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Create Plan</h1>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Slug"
                        value={data.slug}
                        onChange={(e) => setData('slug', e.target.value)}
                        error={errors.slug}
                        required
                    />
                    <Input
                        label="Name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                            rows={3}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </div>
                    <Input
                        label="Monthly Price ($)"
                        type="number"
                        min="0"
                        step="0.01"
                        value={data.monthly_price}
                        onChange={(e) => setData('monthly_price', e.target.value)}
                        error={errors.monthly_price}
                    />
                    <Input
                        label="Yearly Price ($)"
                        type="number"
                        min="0"
                        step="0.01"
                        value={data.yearly_price}
                        onChange={(e) => setData('yearly_price', e.target.value)}
                        error={errors.yearly_price}
                    />
                    <Input
                        label="Sort Order"
                        type="number"
                        min="0"
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', e.target.value)}
                        error={errors.sort_order}
                    />
                    <Select
                        label="Status"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        error={errors.status}
                        options={[
                            { value: 'draft', label: 'Draft' },
                            { value: 'active', label: 'Active' },
                            { value: 'deprecated', label: 'Deprecated' },
                        ]}
                    />
                    <div className="flex items-center gap-3">
                        <Toggle
                            checked={data.is_public}
                            onChange={(val) => setData('is_public', val)}
                        />
                        <span className="text-sm font-medium text-gray-700">Publicly visible</span>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" loading={processing}>
                            Create Plan
                        </Button>
                        <Button variant="secondary" href="/admin/plans">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
