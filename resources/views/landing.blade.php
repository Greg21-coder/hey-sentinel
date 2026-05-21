@extends('layouts.public')

@section('title', 'HeySentinel — Find market gaps in the Shopify App Store')

@section('content')
<div class="min-h-screen overflow-x-hidden">

    {{-- ========== NAV ========== --}}
    <nav x-data="{ open: false }" class="fixed top-0 left-0 right-0 z-50 glass border-b border-amber-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="#" class="flex items-center gap-2 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-lg shadow-md group-hover:scale-110 transition-transform">
                        H
                    </div>
                    <span class="font-extrabold text-xl tracking-tight">HeySentinel</span>
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
                    <a href="#features" class="hover:text-amber-700 transition">Features</a>
                    <a href="#how" class="hover:text-amber-700 transition">How it works</a>
                    <a href="#pricing" class="hover:text-amber-700 transition">Pricing</a>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    <a href="/admin/login" class="text-sm font-semibold text-slate-700 hover:text-amber-700 transition">
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 transition shadow-sm">
                        Start free trial
                    </a>
                </div>

                <button @click="open = !open" class="md:hidden p-2 text-slate-700">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="open" x-cloak x-transition class="md:hidden py-4 border-t border-amber-100">
                <div class="flex flex-col gap-3 text-sm font-medium">
                    <a href="#features" @click="open=false" class="text-slate-700 hover:text-amber-700">Features</a>
                    <a href="#how" @click="open=false" class="text-slate-700 hover:text-amber-700">How it works</a>
                    <a href="#pricing" @click="open=false" class="text-slate-700 hover:text-amber-700">Pricing</a>
                    <hr class="border-amber-100 my-2">
                    <a href="/admin/login" class="text-slate-700 hover:text-amber-700">Log in</a>
                    <a href="{{ route('register') }}" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-center">Start free trial</a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ========== HERO ========== --}}
    <section class="relative pt-32 pb-24 lg:pt-44 lg:pb-32 overflow-hidden">
        <div class="absolute inset-0 gradient-bg opacity-30"></div>
        <div class="absolute top-20 -left-20 w-96 h-96 bg-amber-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
        <div class="absolute top-40 -right-20 w-96 h-96 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse" style="animation-delay: 1s;"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 border border-amber-200 text-amber-900 text-sm font-medium mb-8 animate-fade-in">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-600"></span>
                    </span>
                    Built for Shopify App developers
                </div>

                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black tracking-tighter leading-[1.05] mb-6 animate-fade-in-up">
                    Stop guessing.
                    <span class="block bg-gradient-to-r from-amber-600 via-orange-500 to-amber-600 bg-clip-text text-transparent">
                        Build what's missing.
                    </span>
                </h1>

                <p class="text-xl md:text-2xl text-slate-700 leading-relaxed max-w-3xl mx-auto mb-10 animate-fade-in-up" style="animation-delay: 0.15s; opacity: 0;">
                    HeySentinel scrapes thousands of competitor apps in the Shopify App Store and uses AI to surface the
                    <span class="font-bold text-slate-900">exact pain points</span>
                    merchants are crying about — so you can build the solution they're already paying for.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center animate-fade-in-up" style="animation-delay: 0.3s; opacity: 0;">
                    <a href="{{ route('register') }}" class="group relative inline-flex items-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-800 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                        Start 14-day free trial
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#pricing" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-semibold text-lg text-slate-700 hover:text-slate-900 transition">
                        See pricing →
                    </a>
                </div>

                <p class="text-sm text-slate-500 mt-6 animate-fade-in-up" style="animation-delay: 0.45s; opacity: 0;">
                    No credit card required · Cancel anytime
                </p>
            </div>

            {{-- Stat bar --}}
            <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto animate-fade-in" style="animation-delay: 0.6s; opacity: 0;">
                @foreach ([
                    ['8,000+', 'Apps tracked'],
                    ['3M+', 'Stores indexed'],
                    ['50M+', 'Reviews analyzed'],
                    ['24h', 'AI insight SLA'],
                ] as [$value, $label])
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-black text-slate-900">{{ $value }}</div>
                    <div class="text-sm text-slate-600 font-medium mt-1">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========== FEATURES ========== --}}
    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-4">
                    Everything you need to find your next product.
                </h2>
                <p class="text-lg text-slate-600">
                    Three pillars that turn the Shopify App Store into actionable intelligence.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach ([
                    ['icon' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z', 'title' => 'Track competitors at scale', 'desc' => 'We continuously scrape the Shopify App Store and Storeleads. No manual tracking, no spreadsheets. Filter by category, pricing, rating, and install volume in seconds.'],
                    ['icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'title' => 'AI-extracted pain points', 'desc' => 'Claude Haiku classifies every review into structured pain points and feature requests. No vector DBs, no RAG complexity — just clean tags ready for SQL filtering.'],
                    ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'title' => 'Discover market gaps', 'desc' => 'See exactly which apps have the most complaints about shipping, billing, UI, or any other category. Filter to top-installed apps with the lowest ratings — that\'s your opportunity.'],
                ] as $i => $feature)
                <div class="group relative p-8 rounded-2xl bg-gradient-to-br from-white to-amber-50/40 border border-amber-100 hover:border-amber-300 hover:shadow-xl transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white mb-5 shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">{{ $feature['title'] }}</h3>
                    <p class="text-slate-600 leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========== HOW ========== --}}
    <section id="how" class="py-24 bg-gradient-to-b from-amber-50/30 to-white">
        <div class="max-w-5xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-4">How it works</h2>
                <p class="text-lg text-slate-600">Three steps from "what should I build?" to "I know exactly what to build."</p>
            </div>

            <div class="space-y-8">
                @foreach ([
                    ['01', 'Sign up and tell us your niche', 'Pick the Shopify App categories you care about. We start indexing competitors within minutes.'],
                    ['02', 'Let the AI surface the pain', 'Our pipeline classifies every customer review into pain points, feature requests, and bug reports. Updated daily.'],
                    ['03', 'Browse insights, build winners', 'Use the dashboard to filter apps by pain-point category, install volume, and rating. The product gaps are the queries with empty result sets.'],
                ] as [$num, $title, $desc])
                <div class="flex flex-col md:flex-row gap-6 items-start group">
                    <div class="flex-shrink-0 w-16 h-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xl shadow-lg group-hover:bg-amber-600 transition-colors">
                        {{ $num }}
                    </div>
                    <div class="flex-1 pt-2">
                        <h3 class="text-2xl font-bold mb-2">{{ $title }}</h3>
                        <p class="text-slate-600 text-lg leading-relaxed">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========== PRICING ========== --}}
    <section id="pricing" class="py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-amber-500 rounded-full mix-blend-screen filter blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-orange-500 rounded-full mix-blend-screen filter blur-3xl opacity-20"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-4">
                    Pricing that scales with you.
                </h2>
                <p class="text-lg text-slate-300">
                    Start free. Upgrade when you start shipping winners.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach ($plans as $plan)
                    @php
                        $isHighlight = $plan->slug === 'premium';
                        $price = (float) $plan->monthly_price;
                    @endphp
                    <div class="relative rounded-2xl p-8 {{ $isHighlight ? 'bg-gradient-to-br from-amber-500 to-orange-600 shadow-2xl scale-105 z-10' : 'bg-slate-800/80 border border-slate-700' }}">
                        @if ($isHighlight)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-slate-900 text-amber-400 text-xs font-bold uppercase tracking-wider">
                                Most popular
                            </div>
                        @endif

                        <h3 class="text-2xl font-bold {{ $isHighlight ? 'text-white' : 'text-white' }}">{{ $plan->name }}</h3>
                        <p class="text-sm mt-1 {{ $isHighlight ? 'text-amber-50' : 'text-slate-400' }}">{{ $plan->description }}</p>

                        <div class="mt-6 mb-6 flex items-baseline gap-1">
                            <span class="text-5xl font-black {{ $isHighlight ? 'text-white' : 'text-white' }}">${{ number_format($price, 0) }}</span>
                            <span class="text-sm {{ $isHighlight ? 'text-amber-50' : 'text-slate-400' }}">/month</span>
                        </div>

                        <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="block w-full text-center py-3 rounded-xl font-semibold transition {{ $isHighlight ? 'bg-white text-slate-900 hover:bg-amber-50' : 'bg-slate-700 text-white hover:bg-slate-600' }}">
                            Start with {{ $plan->name }}
                        </a>

                        <ul class="mt-8 space-y-3">
                            @foreach ($plan->features as $feature)
                                @php
                                    $displayValue = match ($feature->value_type->value) {
                                        'unlimited' => 'Unlimited',
                                        'boolean' => $feature->feature_value === 'true' ? 'Included' : 'Not included',
                                        'integer' => number_format((int) $feature->feature_value),
                                        default => $feature->feature_value,
                                    };
                                    $label = ucwords(str_replace('_', ' ', $feature->feature_key));
                                    $isIncluded = $feature->value_type->value !== 'boolean' || $feature->feature_value === 'true';
                                @endphp
                                <li class="flex items-start gap-3 text-sm {{ $isIncluded ? '' : 'opacity-40' }}">
                                    @if ($isIncluded)
                                        <svg class="flex-shrink-0 w-5 h-5 {{ $isHighlight ? 'text-white' : 'text-amber-400' }} mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="flex-shrink-0 w-5 h-5 text-slate-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                    <div class="{{ $isHighlight ? 'text-white' : 'text-slate-200' }}">
                                        <span class="font-medium">{{ $label }}:</span>
                                        <span>{{ $displayValue }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <p class="text-center mt-12 text-slate-400 text-sm">
                All plans include a 14-day free trial · No credit card required to start
            </p>
        </div>
    </section>

    {{-- ========== CTA ========== --}}
    <section class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-6">
                Ready to know what to build next?
            </h2>
            <p class="text-xl text-slate-600 mb-10 max-w-2xl mx-auto">
                Join Shopify App developers who stopped guessing and started shipping what merchants are actually asking for.
            </p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-800 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                Start your free trial
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>

    {{-- ========== FOOTER ========== --}}
    <footer class="bg-slate-950 text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-sm">H</div>
                    <span class="font-bold text-white">HeySentinel</span>
                </div>
                <p class="text-sm">© {{ date('Y') }} HeySentinel. Market intel for the Shopify App ecosystem.</p>
            </div>
        </div>
    </footer>

</div>
@endsection
