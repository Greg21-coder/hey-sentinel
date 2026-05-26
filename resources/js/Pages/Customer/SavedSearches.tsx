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
                                className="flex items-center gap-0.5 text-xs text-gray-400 hover:text-gray-600"
                            >
                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
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
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Load
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={handleDelete}
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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
                        className="text-sm font-medium text-shopify-500 hover:underline"
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
                                className="text-shopify-500 hover:underline"
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
