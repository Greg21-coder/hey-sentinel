@extends('layouts.public')

@section('title', 'Create your HeySentinel account')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 via-white to-orange-50 relative overflow-hidden">

    {{-- decorative blobs --}}
    <div class="absolute top-20 -left-20 w-96 h-96 bg-amber-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
    <div class="absolute bottom-20 -right-20 w-96 h-96 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <a href="/" class="flex items-center gap-2 group mb-8 justify-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-lg shadow-md group-hover:scale-110 transition-transform">
                    H
                </div>
                <span class="font-extrabold text-2xl tracking-tight">HeySentinel</span>
            </a>

            <div class="glass rounded-2xl shadow-xl border border-amber-100 p-8">
                <h1 class="text-2xl font-black tracking-tight text-center mb-1">Start your 14-day trial</h1>
                <p class="text-sm text-slate-600 text-center mb-6">No credit card required</p>

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-800">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Your name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none">
                    </div>

                    <div>
                        <label for="company_name" class="block text-sm font-semibold text-slate-700 mb-1">Company / workspace name</label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" required minlength="8"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none">
                        <p class="text-xs text-slate-500 mt-1">Minimum 8 characters</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none">
                    </div>

                    <div>
                        <label for="plan_slug" class="block text-sm font-semibold text-slate-700 mb-1">Plan</label>
                        <select name="plan_slug" id="plan_slug" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition outline-none bg-white">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->slug }}" {{ ($preselectedPlan ?? old('plan_slug', 'free')) === $plan->slug ? 'selected' : '' }}>
                                    {{ $plan->name }} — ${{ number_format((float) $plan->monthly_price, 0) }}/mo
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full bg-slate-900 text-white py-3 rounded-xl font-semibold hover:bg-slate-800 transition shadow-md hover:shadow-lg hover:-translate-y-0.5">
                        Create account
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    Already have an account?
                    <a href="/admin/login" class="font-semibold text-amber-700 hover:text-amber-900 transition">Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
