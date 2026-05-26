import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Input from '@/Components/ui/Input';
import Select from '@/Components/ui/Select';
import Button from '@/Components/ui/Button';
import Toggle from '@/Components/ui/Toggle';
import Modal from '@/Components/ui/Modal';
import { PageProps } from '@/types';

interface PlanFeature {
    id: number;
    feature_key: string;
    feature_value: string;
    value_type: string;
}

interface PlanData {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    monthly_price: number;
    yearly_price: number;
    sort_order: number;
    status: string;
    is_public: boolean;
    features: PlanFeature[];
}

interface Props extends PageProps {
    plan: PlanData;
    features: PlanFeature[];
}

export default function PlanEdit({ plan, features }: Props) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [editingFeature, setEditingFeature] = useState<PlanFeature | null>(null);
    const [newFeature, setNewFeature] = useState({ feature_key: '', feature_value: '', value_type: 'boolean' });

    const { data, setData, put, processing, errors } = useForm({
        slug: plan.slug,
        name: plan.name,
        description: plan.description ?? '',
        monthly_price: String(plan.monthly_price),
        yearly_price: String(plan.yearly_price),
        sort_order: String(plan.sort_order),
        status: plan.status,
        is_public: plan.is_public,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/plans/${plan.id}`);
    };

    const handleDelete = () => {
        router.delete(`/admin/plans/${plan.id}`, {
            onFinish: () => setShowDeleteModal(false),
        });
    };

    const handleAddFeature = () => {
        router.post(`/admin/plans/${plan.id}/features`, newFeature, {
            preserveState: true,
            onSuccess: () => setNewFeature({ feature_key: '', feature_value: '', value_type: 'boolean' }),
        });
    };

    const handleUpdateFeature = () => {
        if (!editingFeature) return;
        router.put(`/admin/plans/${plan.id}/features/${editingFeature.id}`, editingFeature, {
            preserveState: true,
            onSuccess: () => setEditingFeature(null),
        });
    };

    const handleDeleteFeature = (featureId: number) => {
        router.delete(`/admin/plans/${plan.id}/features/${featureId}`, {
            preserveState: true,
        });
    };

    const valueTypeOptions = [
        { value: 'boolean', label: 'Boolean' },
        { value: 'integer', label: 'Integer' },
        { value: 'string', label: 'String' },
        { value: 'unlimited', label: 'Unlimited' },
    ];

    return (
        <AdminLayout>
            <Head title={`Edit ${plan.name}`} />

            <div className="max-w-2xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Edit Plan</h1>

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
                            className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-shopify-500"
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
                            Save Changes
                        </Button>
                        <Button variant="secondary" href="/admin/plans">
                            Cancel
                        </Button>
                    </div>
                </form>

                {/* Features inline table */}
                <div className="border-t border-gray-200 pt-6 space-y-3">
                    <h2 className="text-lg font-semibold text-gray-800">Plan Features</h2>

                    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Key</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Value</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {features.map((feat) => (
                                    <tr key={feat.id} className="hover:bg-gray-50">
                                        {editingFeature?.id === feat.id ? (
                                            <>
                                                <td className="px-4 py-2">
                                                    <Input
                                                        value={editingFeature.feature_key}
                                                        onChange={(e) => setEditingFeature({ ...editingFeature, feature_key: e.target.value })}
                                                    />
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Input
                                                        value={editingFeature.feature_value}
                                                        onChange={(e) => setEditingFeature({ ...editingFeature, feature_value: e.target.value })}
                                                    />
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Select
                                                        value={editingFeature.value_type}
                                                        onChange={(e) => setEditingFeature({ ...editingFeature, value_type: e.target.value })}
                                                        options={valueTypeOptions}
                                                    />
                                                </td>
                                                <td className="px-4 py-2 text-right whitespace-nowrap">
                                                    <button onClick={handleUpdateFeature} className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-shopify-50 text-shopify-700 hover:bg-shopify-100 mr-2">Save</button>
                                                    <button onClick={() => setEditingFeature(null)} className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-surface-200 text-surface-700 hover:bg-surface-300">Cancel</button>
                                                </td>
                                            </>
                                        ) : (
                                            <>
                                                <td className="px-4 py-3 text-gray-700">{feat.feature_key}</td>
                                                <td className="px-4 py-3 text-gray-700">{feat.feature_value}</td>
                                                <td className="px-4 py-3 text-gray-500">{feat.value_type}</td>
                                                <td className="px-4 py-3 text-right whitespace-nowrap">
                                                    <button onClick={() => setEditingFeature(feat)} className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-shopify-50 text-shopify-700 hover:bg-shopify-100 mr-2">Edit</button>
                                                    <button onClick={() => handleDeleteFeature(feat.id)} className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100">Remove</button>
                                                </td>
                                            </>
                                        )}
                                    </tr>
                                ))}
                                {/* Add new feature row */}
                                <tr className="bg-gray-50">
                                    <td className="px-4 py-2">
                                        <Input
                                            placeholder="feature_key"
                                            value={newFeature.feature_key}
                                            onChange={(e) => setNewFeature({ ...newFeature, feature_key: e.target.value })}
                                        />
                                    </td>
                                    <td className="px-4 py-2">
                                        <Input
                                            placeholder="value"
                                            value={newFeature.feature_value}
                                            onChange={(e) => setNewFeature({ ...newFeature, feature_value: e.target.value })}
                                        />
                                    </td>
                                    <td className="px-4 py-2">
                                        <Select
                                            value={newFeature.value_type}
                                            onChange={(e) => setNewFeature({ ...newFeature, value_type: e.target.value })}
                                            options={valueTypeOptions}
                                        />
                                    </td>
                                    <td className="px-4 py-2 text-right">
                                        <Button size="sm" onClick={handleAddFeature}>Add</Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="border-t border-gray-200 pt-6">
                    <Button variant="danger" onClick={() => setShowDeleteModal(true)}>
                        Delete Plan
                    </Button>
                </div>
            </div>

            <Modal
                open={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title="Delete Plan"
            >
                <p className="text-sm text-gray-600">
                    Are you sure you want to delete{' '}
                    <span className="font-semibold">{plan.name}</span>?
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
