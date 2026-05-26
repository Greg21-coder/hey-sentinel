import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import Modal from '@/Components/ui/Modal';
import Input from '@/Components/ui/Input';
import UpgradePrompt from '@/Components/ui/UpgradePrompt';
import DataTable, { Column, FilterConfig } from '@/Components/tables/DataTable';
import { PageProps } from '@/types';
import { ShopifyApp, PaginatedResponse } from '@/types/models';

interface Props extends PageProps {
    apps: PaginatedResponse<ShopifyApp>;
    followedIds: number[];
    categories: { id: number; name: string }[];
    painPointOptions: { id: number; name: string }[];
    filters: Record<string, string>;
}

const ratingColor = (rating: number): 'success' | 'warning' | 'danger' | 'gray' => {
    if (rating >= 4) return 'success';
    if (rating >= 3) return 'warning';
    if (rating > 0) return 'danger';
    return 'gray';
};

const BrowseApps: React.FC<Props> = ({
    apps,
    followedIds,
    categories,
    painPointOptions,
    filters,
}) => {
    const { featureGates } = usePage<PageProps>().props;
    const canExport = featureGates?.export_csv?.value === true;

    const [saveModalOpen, setSaveModalOpen] = useState(false);
    const [searchName, setSearchName] = useState('');
    const [savingSearch, setSavingSearch] = useState(false);
    const [summaryModal, setSummaryModal] = useState<{ name: string; text: string } | null>(null);

    const followedSet = new Set(followedIds);

    const columns: Column<ShopifyApp>[] = [
        {
            key: 'avatar_url',
            label: '',
            render: (row) =>
                row.avatar_url ? (
                    <img src={row.avatar_url} alt={row.name} className="w-8 h-8 rounded-lg object-cover" />
                ) : (
                    <div className="w-8 h-8 rounded-lg bg-surface-200 flex items-center justify-center text-surface-500 text-xs font-bold">
                        {row.name.charAt(0)}
                    </div>
                ),
        },
        {
            key: 'name',
            label: 'App',
            sortable: true,
            render: (row) => (
                <a
                    href={`/customer/apps/${row.id}`}
                    className="font-medium text-shopify-500 hover:underline"
                >
                    {row.name}
                </a>
            ),
        },
        {
            key: 'developer_name',
            label: 'Developer',
            sortable: true,
        },
        {
            key: 'category',
            label: 'Category',
            render: (row) =>
                row.category ? (
                    <Badge color="gray">{typeof row.category === 'object' ? row.category.name : row.category}</Badge>
                ) : (
                    <span className="text-gray-400">—</span>
                ),
        },
        {
            key: 'average_rating',
            label: 'Rating',
            sortable: true,
            render: (row) => {
                const rating = Number(row.average_rating);
                return (
                    <Badge color={ratingColor(rating)}>
                        {rating > 0 ? rating.toFixed(1) : '—'}
                    </Badge>
                );
            },
        },
        {
            key: 'total_reviews',
            label: 'Reviews',
            sortable: true,
            render: (row) => row.total_reviews.toLocaleString(),
        },
        {
            key: 'ai_summary',
            label: 'AI Summary',
            render: (row) =>
                row.ai_summary ? (
                    <button
                        onClick={() => setSummaryModal({ name: row.name, text: row.ai_summary! })}
                        className="text-left text-xs text-surface-600 line-clamp-2 max-w-[200px] hover:text-surface-900 cursor-pointer"
                    >
                        {row.ai_summary.length > 80 ? row.ai_summary.slice(0, 80) + '…' : row.ai_summary}
                    </button>
                ) : (
                    <span className="text-gray-400 text-xs">—</span>
                ),
        },
        {
            key: 'pricing_min_usd',
            label: 'Pricing',
            render: (row) => {
                if (row.pricing_has_free) return <Badge color="success">Free</Badge>;
                if (row.pricing_min_usd) return `$${row.pricing_min_usd}/mo`;
                return <span className="text-gray-400">—</span>;
            },
        },
    ];

    const categoryOptions = categories.map((cat) => ({
        value: String(cat.id),
        label: cat.name,
    }));

    const tableFilters: FilterConfig[] = [
        {
            key: 'category',
            label: 'Category',
            type: 'select',
            options: categoryOptions,
        },
        {
            key: 'rating_min',
            label: 'Min Rating',
            type: 'text',
        },
        {
            key: 'pricing',
            label: 'Pricing',
            type: 'select',
            options: [
                { value: 'free', label: 'Free' },
                { value: 'paid', label: 'Paid' },
            ],
        },
        {
            key: 'keyword',
            label: 'Keyword',
            type: 'text',
        },
    ];

    const handleFollow = (app: ShopifyApp) => {
        router.post(`/customer/apps/${app.id}/follow`, {}, { preserveScroll: true });
    };

    const handleUnfollow = (app: ShopifyApp) => {
        router.delete(`/customer/apps/${app.id}/follow`, { preserveScroll: true });
    };

    const handleSaveSearch = () => {
        if (!searchName.trim()) return;
        setSavingSearch(true);
        router.post(
            '/customer/saved-searches',
            { name: searchName, filters },
            {
                onFinish: () => {
                    setSavingSearch(false);
                    setSaveModalOpen(false);
                    setSearchName('');
                },
            },
        );
    };

    const handleExport = () => {
        router.post('/customer/export/apps', filters);
    };

    return (
        <CustomerLayout>
            <Head title="Browse Apps" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Browse Apps</h1>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => setSaveModalOpen(true)}
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                            Save this search
                        </Button>

                        {canExport ? (
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={handleExport}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Export CSV
                            </Button>
                        ) : (
                            <UpgradePrompt feature="CSV Export" />
                        )}
                    </div>
                </div>

                <Card>
                    <DataTable
                        columns={columns}
                        pagination={apps}
                        filters={tableFilters}
                        currentFilters={filters}
                        rowActions={(row) =>
                            followedSet.has(row.id) ? (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => handleUnfollow(row)}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    Unfollow
                                </Button>
                            ) : (
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={() => handleFollow(row)}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Follow
                                </Button>
                            )
                        }
                        emptyMessage="No apps found matching your filters."
                    />
                </Card>
            </div>

            {/* Save search modal */}
            <Modal
                open={saveModalOpen}
                onClose={() => setSaveModalOpen(false)}
                title="Save Search"
            >
                <div className="space-y-4">
                    <Input
                        label="Search name"
                        value={searchName}
                        onChange={(e) => setSearchName(e.target.value)}
                        placeholder="e.g. Free review apps 4+ stars"
                    />
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => setSaveModalOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            onClick={handleSaveSearch}
                            loading={savingSearch}
                            disabled={!searchName.trim()}
                        >
                            Save
                        </Button>
                    </div>
                </div>
            </Modal>

            {summaryModal && (
                <Modal
                    open={true}
                    onClose={() => setSummaryModal(null)}
                    title={`AI Summary — ${summaryModal.name}`}
                >
                    <div className="text-sm text-surface-700 leading-relaxed whitespace-pre-wrap">
                        {summaryModal.text}
                    </div>
                </Modal>
            )}
        </CustomerLayout>
    );
};

export default BrowseApps;
