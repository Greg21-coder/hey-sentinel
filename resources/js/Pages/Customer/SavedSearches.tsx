import React, { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';
import Toggle from '@/Components/ui/Toggle';
import UpgradePrompt from '@/Components/ui/UpgradePrompt';
import { PageProps } from '@/types';
import { SavedSearch } from '@/types/models';

interface Props extends PageProps {
    savedSearches: SavedSearch[];
}

const SavedSearchCard: React.FC<{ search: SavedSearch }> = ({ search }) => {
    const [editing, setEditing] = useState(false);
    const { data, setData, put, processing } = useForm({
        name: search.name,
    });

    const handleLoadSearch = () => {
        const params = new URLSearchParams();
        const f = search.filters;
        if (f.category_id) params.set('category', String(f.category_id));
        if (f.rating_min) params.set('rating_min', String(f.rating_min));
        if (f.pricing) params.set('pricing', f.pricing);
        if (f.keyword) params.set('keyword', f.keyword);
        router.visit(`/customer/apps?${params.toString()}`);
    };

    const handleToggleNotify = (checked: boolean) => {
        router.put(
            `/customer/saved-searches/${search.id}`,
            { notify_on_new: checked },
            { preserveScroll: true },
        );
    };

    const handleDelete = () => {
        if (!confirm(`Delete saved search "${search.name}"?`)) return;
        router.delete(`/customer/saved-searches/${search.id}`, {
            preserveScroll: true,
        });
    };

    const handleRename = () => {
        put(`/customer/saved-searches/${search.id}`, {
            onSuccess: () => setEditing(false),
        });
    };

    const filters = search.filters;
    const filterBadges: { label: string; value: string }[] = [];
    if (filters.category_id)
        filterBadges.push({ label: 'Category', value: String(filters.category_id) });
    if (filters.rating_min)
        filterBadges.push({ label: 'Min Rating', value: String(filters.rating_min) });
    if (filters.pricing)
        filterBadges.push({ label: 'Pricing', value: filters.pricing });
    if (filters.keyword)
        filterBadges.push({ label: 'Keyword', value: filters.keyword });
    if (filters.pain_point_ids?.length)
        filterBadges.push({
            label: 'Pain Points',
            value: `${filters.pain_point_ids.length} selected`,
        });

    return (
        <Card>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-2 flex-1 min-w-0">
                    {editing ? (
                        <div className="flex items-center gap-2">
                            <Input
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="max-w-xs"
                            />
                            <Button
                                variant="primary"
                                size="sm"
                                onClick={handleRename}
                                loading={processing}
                                disabled={!data.name.trim()}
                            >
                                Save
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setEditing(false);
                                    setData('name', search.name);
                                }}
                            >
                                Cancel
                            </Button>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            <span className="font-medium text-gray-900">
                                {search.name}
                            </span>
                            <button
                                onClick={() => setEditing(true)}
                                className="text-xs text-gray-400 hover:text-gray-600"
                            >
                                Rename
                            </button>
                        </div>
                    )}

                    {filterBadges.length > 0 && (
                        <div className="flex flex-wrap gap-1.5">
                            {filterBadges.map((badge) => (
                                <Badge key={badge.label} color="gray">
                                    {badge.label}: {badge.value}
                                </Badge>
                            ))}
                        </div>
                    )}

                    {search.new_results_count !== undefined &&
                        search.new_results_count > 0 && (
                            <Badge color="primary">
                                {search.new_results_count} new results
                            </Badge>
                        )}

                    <p className="text-xs text-gray-400">
                        Saved{' '}
                        {new Date(search.created_at).toLocaleDateString()}
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:items-end flex-shrink-0">
                    <Toggle
                        label="Notify on new"
                        checked={search.notify_on_new}
                        onChange={handleToggleNotify}
                    />
                    <div className="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={handleLoadSearch}
                        >
                            Load
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={handleDelete}
                        >
                            Delete
                        </Button>
                    </div>
                </div>
            </div>
        </Card>
    );
};

const SavedSearches: React.FC<Props> = ({ savedSearches }) => {
    const { featureGates } = usePage<PageProps>().props;
    const savedSearchGate = featureGates?.saved_searches;
    const atLimit =
        savedSearchGate &&
        typeof savedSearchGate.value === 'number' &&
        savedSearches.length >= savedSearchGate.value;

    return (
        <CustomerLayout>
            <Head title="Saved Searches" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">
                        Saved Searches
                    </h1>
                    <a
                        href="/customer/apps"
                        className="text-sm font-medium text-indigo-600 hover:underline"
                    >
                        Browse apps to save a search
                    </a>
                </div>

                {atLimit && (
                    <UpgradePrompt feature="More saved searches" />
                )}

                {savedSearches.length === 0 ? (
                    <Card>
                        <div className="py-10 text-center text-sm text-gray-400">
                            No saved searches yet.{' '}
                            <a
                                href="/customer/apps"
                                className="text-indigo-600 hover:underline"
                            >
                                Browse apps
                            </a>{' '}
                            and save a search to get notified of new results.
                        </div>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {savedSearches.map((search) => (
                            <SavedSearchCard key={search.id} search={search} />
                        ))}
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
};

export default SavedSearches;
