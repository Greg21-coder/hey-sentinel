import React, { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';
import Badge from '@/Components/ui/Badge';
import { startMyAppsTour } from '@/Lib/onboardingTour';
import { PageProps } from '@/types';

interface MyApp {
    id: number;
    shopify_app_handle: string;
    name: string;
    avatar_url: string | null;
    average_rating: string | null;
    total_reviews: number;
    scraped_reviews_count: number;
    reviews_sync_started_at: string | null;
    followed_at: string;
}

type SyncState = 'syncing' | 'failed' | 'healthy';

function appSyncState(app: MyApp): SyncState {
    const scraped = app.scraped_reviews_count ?? 0;
    const total = app.total_reviews ?? 0;
    if (scraped > 0 || total === 0) return 'healthy';
    if (app.reviews_sync_started_at === null) return 'failed';

    const startedMs = new Date(app.reviews_sync_started_at).getTime();
    const ageMs = Date.now() - startedMs;
    return ageMs < 5 * 60 * 1000 ? 'syncing' : 'failed';
}

interface SearchResult {
    id: number;
    shopify_app_handle: string;
    name: string;
    avatar_url: string | null;
    scraping_status: 'scraped' | 'pending' | 'error';
    average_rating: string | null;
    total_reviews: number;
}

interface Props extends PageProps {
    myApps: MyApp[];
    onboarding: { needsTour: boolean };
}

const SHOPIFY_URL_RE = /^https:\/\/apps\.shopify\.com\/[a-z0-9][a-z0-9\-]*\/?$/;

const MyApps: React.FC<Props> = ({ myApps, onboarding }) => {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [searching, setSearching] = useState(false);
    const [submitting, setSubmitting] = useState<string | null>(null);
    const debounceRef = useRef<number | null>(null);

    useEffect(() => {
        if (!onboarding.needsTour) return;
        if (window.localStorage.getItem('heysentinel.tourSeen') === '1') return;

        const t = window.setTimeout(() => {
            startMyAppsTour().catch(() => {});
            window.localStorage.setItem('heysentinel.tourSeen', '1');
        }, 400);

        return () => window.clearTimeout(t);
    }, [onboarding.needsTour]);

    useEffect(() => {
        if (debounceRef.current) window.clearTimeout(debounceRef.current);

        const trimmed = query.trim();
        if (trimmed.length < 2) {
            setResults([]);
            setSearching(false);
            return;
        }

        setSearching(true);
        debounceRef.current = window.setTimeout(() => {
            fetch(`/customer/my-apps/search?q=${encodeURIComponent(trimmed)}`)
                .then(r => r.json())
                .then(d => setResults(d.results ?? []))
                .catch(() => setResults([]))
                .finally(() => setSearching(false));
        }, 250);
    }, [query]);

    const submitHandle = (handle: string) => {
        if (submitting) return;
        setSubmitting(handle);
        router.post(
            '/customer/my-apps',
            { handle },
            {
                onFinish: () => setSubmitting(null),
                preserveScroll: false,
            },
        );
    };

    const submitUrl = (url: string) => {
        if (submitting) return;
        setSubmitting(url);
        router.post(
            '/customer/my-apps',
            { url },
            {
                onFinish: () => setSubmitting(null),
                preserveScroll: false,
            },
        );
    };

    const trimmed = query.trim();
    const looksLikeShopifyUrl = SHOPIFY_URL_RE.test(trimmed);
    const showUrlFallback = trimmed.length >= 2 && results.length === 0 && !searching && looksLikeShopifyUrl;

    return (
        <CustomerLayout>
            <Head title="My Apps" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">My Apps</h1>
                    {onboarding.needsTour && (
                        <Button variant="secondary" size="sm" onClick={() => startMyAppsTour()}>
                            Take the tour
                        </Button>
                    )}
                </div>

                <Card title="Find your app">
                    <div className="space-y-3" data-tour="search">
                        <Input
                            label="Search by name or paste a Shopify URL"
                            value={query}
                            onChange={e => setQuery(e.target.value)}
                            placeholder="e.g. Klaviyo or https://apps.shopify.com/klaviyo-email-marketing"
                        />

                        {searching && (
                            <p className="text-xs text-gray-500">Searching...</p>
                        )}

                        {results.length > 0 && (
                            <ul className="divide-y divide-gray-100 rounded-md border border-gray-200 bg-white" data-tour="results">
                                {results.map(r => (
                                    <li key={r.id} className="flex items-center justify-between px-3 py-2">
                                        <div className="flex items-center gap-3 min-w-0">
                                            {r.avatar_url ? (
                                                <img src={r.avatar_url} alt="" className="h-8 w-8 rounded-md object-cover" />
                                            ) : (
                                                <div className="h-8 w-8 rounded-md bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">
                                                    {(r.name || r.shopify_app_handle).charAt(0).toUpperCase()}
                                                </div>
                                            )}
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate">{r.name || r.shopify_app_handle}</p>
                                                <p className="text-xs text-gray-500 truncate">{r.shopify_app_handle}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {r.scraping_status === 'scraped' ? (
                                                <Badge color="success">In catalogue</Badge>
                                            ) : (
                                                <Badge color="gray">Needs import</Badge>
                                            )}
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                onClick={() => submitHandle(r.shopify_app_handle)}
                                                loading={submitting === r.shopify_app_handle}
                                            >
                                                Add as mine
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {showUrlFallback && (
                            <div className="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-3">
                                <p className="text-xs text-gray-600 mb-2">Not in our catalogue yet. Import it from Shopify?</p>
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={() => submitUrl(trimmed)}
                                    loading={submitting === trimmed}
                                >
                                    Import from URL
                                </Button>
                            </div>
                        )}

                        {trimmed.length >= 2 && results.length === 0 && !searching && !looksLikeShopifyUrl && (
                            <p className="text-xs text-gray-500">
                                No matches. Paste a Shopify URL (https://apps.shopify.com/&lt;handle&gt;) to import a new app.
                            </p>
                        )}
                    </div>
                </Card>

                <Card title="Your apps">
                    <div className="overflow-x-auto -mx-6 -mb-5" data-tour="my-list">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">App</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Rating</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reviews</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {myApps.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-10 text-center text-sm text-gray-400">
                                            No apps yet. Use the search above to add your first.
                                        </td>
                                    </tr>
                                ) : (
                                    myApps.map(app => (
                                        <tr key={app.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-3 font-medium text-gray-900">
                                                <a href={`/customer/apps/${app.id}`} className="flex items-center gap-3 hover:text-shopify-500">
                                                    {app.avatar_url ? (
                                                        <img src={app.avatar_url} alt="" className="h-8 w-8 rounded-md object-cover flex-shrink-0" />
                                                    ) : (
                                                        <div className="h-8 w-8 rounded-md bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500 flex-shrink-0">
                                                            {(app.name || app.shopify_app_handle).charAt(0).toUpperCase()}
                                                        </div>
                                                    )}
                                                    <span className="truncate">{app.name}</span>
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {app.average_rating != null ? `★ ${Number(app.average_rating).toFixed(2)}` : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {(app.total_reviews ?? 0).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => router.delete(`/customer/apps/${app.id}/follow`, { preserveScroll: true })}
                                                >
                                                    Remove
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </CustomerLayout>
    );
};

export default MyApps;
