import React from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

interface PlanFeature {
    feature_key: string;
    feature_value: string;
    value_type: string;
}

interface Plan {
    id: number;
    slug: string;
    name: string;
    description?: string;
    monthly_price: number;
    features: PlanFeature[];
}

interface Stats {
    apps_count: number;
    stores_count: number;
    reviews_count: number;
}

interface LandingProps {
    stats: Stats;
    plans: Plan[];
}

function formatNumber(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M+';
    if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K+';
    return String(n);
}

function featureLabel(feature: PlanFeature): string {
    const { feature_key, feature_value, value_type } = feature;
    const label = feature_key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    if (value_type === 'boolean') {
        return label;
    }
    return `${label}: ${feature_value}`;
}

function featureEnabled(feature: PlanFeature): boolean {
    if (feature.value_type === 'boolean') {
        return feature.feature_value === 'true' || feature.feature_value === '1';
    }
    return true;
}

const CheckIcon: React.FC<{ className?: string }> = ({ className = '' }) => (
    <svg
        className={className}
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
    >
        <path
            fillRule="evenodd"
            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
            clipRule="evenodd"
        />
    </svg>
);

const XIcon: React.FC<{ className?: string }> = ({ className = '' }) => (
    <svg
        className={className}
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
    >
        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
    </svg>
);

const TrackIcon: React.FC = () => (
    <svg className="h-6 w-6 text-shopify-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
);

const AiIcon: React.FC = () => (
    <svg className="h-6 w-6 text-shopify-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
    </svg>
);

const GapIcon: React.FC = () => (
    <svg className="h-6 w-6 text-shopify-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6.75v6.75" />
    </svg>
);

const Landing: React.FC<LandingProps> = ({ stats, plans }) => {
    return (
        <PublicLayout>
            <Head title="HeySentinel — Know what to build next" />

            {/* ── Hero ── */}
            <section className="bg-[#303030] pt-24 pb-20 text-center px-4">
                <div className="mx-auto max-w-3xl">
                    <p className="mb-4 inline-block rounded-full border border-shopify-500/40 bg-shopify-500/10 px-4 py-1 text-sm font-medium text-shopify-300">
                        Intelligence for Shopify app builders
                    </p>
                    <h1 className="text-5xl font-bold tracking-tight text-white sm:text-6xl">
                        Stop guessing.{' '}
                        <span className="text-shopify-400">Build what's missing.</span>
                    </h1>
                    <p className="mt-6 text-lg leading-8 text-gray-300">
                        HeySentinel analyzes thousands of Shopify app reviews to surface real
                        customer pain points and market gaps — so you build features that
                        actually matter.
                    </p>
                    <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a
                            href="/register"
                            className="inline-flex items-center justify-center rounded-lg bg-shopify-500 hover:bg-shopify-600 px-8 py-3 text-base font-semibold text-white border border-shopify-500 hover:border-shopify-600 transition-colors duration-150"
                        >
                            Start free trial
                        </a>
                        <a
                            href="#how-it-works"
                            className="inline-flex items-center justify-center rounded-lg bg-transparent hover:bg-white/10 px-8 py-3 text-base font-semibold text-gray-200 border border-white/20 transition-colors duration-150"
                        >
                            See how it works
                        </a>
                    </div>
                    <p className="mt-4 text-sm text-gray-400">No credit card required</p>
                </div>
            </section>

            {/* ── Stats bar ── */}
            <section className="bg-[#252525] border-y border-white/10 py-10">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 gap-8 sm:grid-cols-4 text-center">
                        <div>
                            <p className="text-3xl font-bold text-white">
                                {formatNumber(stats.apps_count)}
                            </p>
                            <p className="mt-1 text-sm text-gray-400">Apps tracked</p>
                        </div>
                        <div>
                            <p className="text-3xl font-bold text-white">
                                {formatNumber(stats.stores_count)}
                            </p>
                            <p className="mt-1 text-sm text-gray-400">Stores indexed</p>
                        </div>
                        <div>
                            <p className="text-3xl font-bold text-white">
                                {formatNumber(stats.reviews_count)}
                            </p>
                            <p className="mt-1 text-sm text-gray-400">Reviews analyzed</p>
                        </div>
                        <div>
                            <p className="text-3xl font-bold text-white">24h</p>
                            <p className="mt-1 text-sm text-gray-400">AI insight SLA</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── Features ── */}
            <section id="features" className="bg-white py-24 px-4">
                <div className="mx-auto max-w-5xl">
                    <h2 className="text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Everything you need to stay ahead
                    </h2>
                    <p className="mt-4 text-center text-lg text-gray-500">
                        Purpose-built intelligence for Shopify app developers.
                    </p>
                    <div className="mt-16 grid gap-8 sm:grid-cols-3">
                        <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                            <div className="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-shopify-50 border border-shopify-100">
                                <TrackIcon />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900">Track competitors</h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Monitor any Shopify app in real time. Get notified when ratings
                                change, new reviews appear, or a competitor loses installs.
                            </p>
                        </div>
                        <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                            <div className="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-shopify-50 border border-shopify-100">
                                <AiIcon />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900">AI pain points</h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Our AI reads every review so you don't have to. It clusters
                                complaints into actionable pain points ranked by frequency.
                            </p>
                        </div>
                        <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                            <div className="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-shopify-50 border border-shopify-100">
                                <GapIcon />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900">Market gaps</h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Discover underserved niches where demand is high and competition
                                is weak. Find your next product before your rivals do.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── How it works ── */}
            <section id="how-it-works" className="bg-surface-100 py-24 px-4">
                <div className="mx-auto max-w-4xl">
                    <h2 className="text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        How it works
                    </h2>
                    <p className="mt-4 text-center text-lg text-gray-500">
                        From zero to insights in minutes.
                    </p>
                    <div className="mt-16 grid gap-12 sm:grid-cols-3">
                        <div className="text-center">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-shopify-500 text-white font-bold text-lg">
                                1
                            </div>
                            <h3 className="mt-4 text-base font-semibold text-gray-900">
                                Pick your competitors
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Search for any Shopify app and add it to your watchlist in
                                seconds.
                            </p>
                        </div>
                        <div className="text-center">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-shopify-500 text-white font-bold text-lg">
                                2
                            </div>
                            <h3 className="mt-4 text-base font-semibold text-gray-900">
                                AI processes every review
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Our pipeline scrapes and analyzes reviews daily, extracting
                                pain points and sentiment automatically.
                            </p>
                        </div>
                        <div className="text-center">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-shopify-500 text-white font-bold text-lg">
                                3
                            </div>
                            <h3 className="mt-4 text-base font-semibold text-gray-900">
                                Build with confidence
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Your dashboard shows exactly where the demand is. Prioritize
                                your roadmap with real evidence.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── Pricing ── */}
            <section id="pricing" className="bg-[#303030] py-24 px-4">
                <div className="mx-auto max-w-5xl">
                    <h2 className="text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Simple, transparent pricing
                    </h2>
                    <p className="mt-4 text-center text-lg text-gray-400">
                        Start free, upgrade when you're ready.
                    </p>
                    {plans.length > 0 ? (
                        <div className="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {plans.map((plan) => {
                                const isPremium = plan.slug === 'premium';
                                return (
                                    <div
                                        key={plan.id}
                                        className={[
                                            'flex flex-col rounded-2xl border p-8',
                                            isPremium
                                                ? 'border-shopify-500 bg-[#252525] shadow-lg shadow-shopify-500/10'
                                                : 'border-white/10 bg-[#252525]',
                                        ].join(' ')}
                                    >
                                        {isPremium && (
                                            <span className="mb-4 inline-block self-start rounded-full bg-shopify-500/20 border border-shopify-500/40 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-shopify-300">
                                                Most popular
                                            </span>
                                        )}
                                        <h3 className="text-xl font-bold text-white">
                                            {plan.name}
                                        </h3>
                                        {plan.description && (
                                            <p className="mt-2 text-sm text-gray-400">
                                                {plan.description}
                                            </p>
                                        )}
                                        <div className="mt-6">
                                            <span className="text-4xl font-bold text-white">
                                                ${plan.monthly_price}
                                            </span>
                                            <span className="ml-1 text-sm text-gray-400">
                                                /mo
                                            </span>
                                        </div>
                                        <ul className="mt-8 flex-1 space-y-3">
                                            {plan.features.map((feature, idx) => {
                                                const enabled = featureEnabled(feature);
                                                return (
                                                    <li key={idx} className="flex items-start gap-3">
                                                        {enabled ? (
                                                            <CheckIcon className="h-5 w-5 shrink-0 mt-0.5 text-shopify-400" />
                                                        ) : (
                                                            <XIcon className="h-5 w-5 shrink-0 mt-0.5 text-gray-600" />
                                                        )}
                                                        <span
                                                            className={[
                                                                'text-sm',
                                                                enabled ? 'text-gray-200' : 'text-gray-500',
                                                            ].join(' ')}
                                                        >
                                                            {featureLabel(feature)}
                                                        </span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                        <div className="mt-8">
                                            <a
                                                href={`/register?plan=${plan.slug}`}
                                                className={[
                                                    'block w-full rounded-lg px-4 py-3 text-center text-sm font-semibold transition-colors duration-150',
                                                    isPremium
                                                        ? 'bg-shopify-500 text-white hover:bg-shopify-600 border border-shopify-500 hover:border-shopify-600'
                                                        : 'bg-white/10 text-white hover:bg-white/20 border border-white/20',
                                                ].join(' ')}
                                            >
                                                Get started
                                            </a>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="mt-16 text-center text-gray-400">
                            Contact us for pricing.
                        </p>
                    )}
                </div>
            </section>

            {/* ── Final CTA ── */}
            <section className="bg-white py-24 px-4 text-center">
                <div className="mx-auto max-w-2xl">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Ready to know what to build next?
                    </h2>
                    <p className="mt-4 text-lg text-gray-500">
                        Join developers who ship features their customers actually want.
                    </p>
                    <div className="mt-8">
                        <a
                            href="/register"
                            className="inline-flex items-center justify-center rounded-lg bg-shopify-500 hover:bg-shopify-600 px-8 py-3 text-base font-semibold text-white border border-shopify-500 hover:border-shopify-600 transition-colors duration-150"
                        >
                            Start your free trial
                        </a>
                    </div>
                    <p className="mt-4 text-sm text-gray-400">No credit card required</p>
                </div>
            </section>

            {/* ── Footer ── */}
            <footer className="bg-[#1a1a1a] border-t border-white/10 py-8 px-4">
                <div className="mx-auto max-w-7xl flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="/" className="flex items-center gap-2">
                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-shopify-500 text-white font-bold text-sm">
                            H
                        </span>
                        <span className="text-white font-semibold text-base">HeySentinel</span>
                    </a>
                    <p className="text-sm text-gray-500">
                        &copy; {new Date().getFullYear()} HeySentinel. All rights reserved.
                    </p>
                </div>
            </footer>
        </PublicLayout>
    );
};

export default Landing;
