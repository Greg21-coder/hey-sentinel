# React + Inertia Migration & Phase 6 M2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate HeySentinel from Blade/Filament to React + Inertia.js, adding Phase 6 M2 features (Saved Searches, Review Velocity, CSV Export, Feature Gates) and an Unlist mechanism.

**Architecture:** Cascading surface-by-surface migration. Foundation (shared deps + components) → Landing page → Customer portal (with M2 features) → Admin panel. Each block produces a deployable increment. Filament coexists during migration, then gets removed per-panel.

**Tech Stack:** React 18, TypeScript, Inertia.js (Laravel adapter), Tailwind CSS, Vite, Recharts, Pest (PHP tests)

**Spec:** `docs/superpowers/specs/2026-05-25-react-migration-m2-design.md`

---

## Block 1: Foundation

### Task 1: Install dependencies and configure Vite for React + TypeScript

**Files:**
- Modify: `package.json`
- Modify: `vite.config.js`
- Create: `tsconfig.json`
- Modify: `resources/js/app.js` → rename to `resources/js/app.tsx`

- [ ] **Step 1: Install npm packages**

Run from WSL:
```bash
npm install react@18 react-dom@18 @inertiajs/react
npm install -D @vitejs/plugin-react typescript @types/react @types/react-dom
```

Expected: packages added to `package.json`

- [ ] **Step 2: Create tsconfig.json**

```json
{
  "compilerOptions": {
    "target": "ESNext",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"]
    }
  },
  "include": ["resources/js/**/*"]
}
```

- [ ] **Step 3: Update vite.config.js**

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

- [ ] **Step 4: Verify build works**

Run: `npm run build`
Expected: Build succeeds (may have empty app.tsx warning — that's fine for now)

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json vite.config.js tsconfig.json
git commit -m "chore: install React 18 + TypeScript + Inertia dependencies and configure Vite"
```

---

### Task 2: Install Inertia Laravel adapter and create middleware

**Files:**
- Modify: `composer.json` (via composer require)
- Create: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `bootstrap/app.php` (register middleware)
- Create: `resources/views/app.blade.php`

- [ ] **Step 1: Install Inertia server-side**

Run from WSL inside the sail container or directly:
```bash
composer require inertiajs/inertia-laravel
```

- [ ] **Step 2: Create the Inertia root template**

Create `resources/views/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HeySentinel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased text-slate-900 bg-white">
    @inertia
</body>
</html>
```

- [ ] **Step 3: Create HandleInertiaRequests middleware**

Create `app/Http/Middleware/HandleInertiaRequests.php`:
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $account = $user?->currentAccount;

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->isSuperAdmin(),
                ] : null,
                'account' => $account ? [
                    'id' => $account->id,
                    'name' => $account->name,
                    'slug' => $account->slug,
                    'status' => $account->status->value,
                    'plan' => [
                        'id' => $account->plan->id,
                        'name' => $account->plan->name,
                        'slug' => $account->plan->slug,
                    ],
                ] : null,
            ],
            'featureGates' => $account ? $this->buildFeatureGates($account) : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    protected function buildFeatureGates($account): array
    {
        $plan = $account->plan;
        $gates = [];

        foreach ($plan->features as $feature) {
            $gates[$feature->feature_key] = [
                'value' => $feature->castedValue(),
                'type' => $feature->value_type->value,
                'usage' => in_array($feature->value_type->value, ['integer'])
                    ? $account->usageThisPeriod($feature->feature_key)
                    : null,
            ];
        }

        return $gates;
    }
}
```

- [ ] **Step 4: Register middleware in bootstrap/app.php**

Find the `withMiddleware` call in `bootstrap/app.php` and add to the `web` group:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

- [ ] **Step 5: Verify middleware is registered**

Run: `php artisan route:list --columns=middleware | head -20`
Expected: `HandleInertiaRequests` appears in the web middleware stack

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php resources/views/app.blade.php bootstrap/app.php composer.json composer.lock
git commit -m "feat: install Inertia Laravel adapter with HandleInertiaRequests middleware"
```

---

### Task 3: Create Inertia app bootstrap and TypeScript types

**Files:**
- Create: `resources/js/app.tsx`
- Create: `resources/js/types/index.d.ts`
- Create: `resources/js/types/models.d.ts`
- Delete: `resources/js/app.js` (replaced by app.tsx)

- [ ] **Step 1: Create TypeScript type definitions**

Create `resources/js/types/models.d.ts`:
```typescript
export interface User {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
}

export interface Plan {
    id: number;
    name: string;
    slug: string;
    description?: string;
    monthly_price: string;
    yearly_price: string;
    sort_order: number;
    is_public: boolean;
    features?: PlanFeature[];
}

export interface PlanFeature {
    id: number;
    feature_key: string;
    feature_value: string;
    value_type: 'boolean' | 'integer' | 'string' | 'unlimited';
}

export interface Account {
    id: number;
    name: string;
    slug: string;
    status: 'trial' | 'active' | 'cancelled' | 'expired';
    plan: Plan;
}

export interface ShopifyApp {
    id: number;
    shopify_app_handle: string;
    name: string;
    developer_name: string;
    category?: ShopifyAppCategory;
    description?: string;
    pricing_raw?: string;
    pricing_min_usd?: string;
    pricing_has_free: boolean;
    avatar_url?: string;
    average_rating: string;
    total_reviews: number;
    total_installs_estimate?: number;
    scraping_status: string;
    ai_summary?: string;
    unlisted_at?: string;
    pivot_kind?: string;
    pivot_followed_at?: string;
}

export interface ShopifyAppCategory {
    id: number;
    slug: string;
    name: string;
}

export interface ShopifyStore {
    id: number;
    domain: string;
    store_name?: string;
    country_code?: string;
    estimated_monthly_visits?: number;
    estimated_monthly_sales_usd?: number;
    apps_installed_count?: number;
    unlisted_at?: string;
}

export interface StoreReview {
    id: number;
    shopify_app_id: number;
    reviewer_name?: string;
    rating: number;
    review_text?: string;
    ai_status: 'pending' | 'batched' | 'processed' | 'skipped' | 'error';
    ai_sentiment?: string;
    published_at?: string;
    app?: ShopifyApp;
}

export interface AiPainPoint {
    id: number;
    slug: string;
    name: string;
    category?: string;
    mentions?: number;
    apps_count?: number;
}

export interface AccountFollowedApp {
    id: number;
    account_id: number;
    shopify_app_id: number;
    kind: 'mine' | 'competitor';
    followed_at: string;
    notes?: string;
    shopify_app?: ShopifyApp;
}

export interface SavedSearch {
    id: number;
    name: string;
    filters: SavedSearchFilters;
    notify_on_new: boolean;
    last_viewed_at?: string;
    new_results_count?: number;
    created_at: string;
    updated_at: string;
}

export interface SavedSearchFilters {
    category_id?: number;
    rating_min?: number;
    rating_max?: number;
    pricing?: 'free' | 'paid' | 'all';
    pain_point_ids?: number[];
    keyword?: string;
}

export interface FeatureGate {
    value: boolean | number | string | null;
    type: 'boolean' | 'integer' | 'string' | 'unlimited';
    usage: number | null;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}
```

- [ ] **Step 2: Create shared page props type**

Create `resources/js/types/index.d.ts`:
```typescript
import { FeatureGate, User, Account } from './models';

export interface PageProps {
    auth: {
        user: User | null;
        account: Account | null;
    };
    featureGates: Record<string, FeatureGate>;
    flash: {
        success: string | null;
        error: string | null;
    };
}
```

- [ ] **Step 3: Create app.tsx bootstrap**

Delete `resources/js/app.js` and create `resources/js/app.tsx`:
```tsx
import '../css/app.css';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (title) => title ? `${title} — HeySentinel` : 'HeySentinel',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#d97706',
    },
});
```

- [ ] **Step 4: Verify build**

Run: `npm run build`
Expected: Build succeeds with no errors

- [ ] **Step 5: Commit**

```bash
git add resources/js/app.tsx resources/js/types/ && git rm resources/js/app.js
git commit -m "feat: create Inertia app bootstrap with TypeScript type definitions"
```

---

### Task 4: Create reusable UI components

**Files:**
- Create: `resources/js/Components/ui/Button.tsx`
- Create: `resources/js/Components/ui/Input.tsx`
- Create: `resources/js/Components/ui/Select.tsx`
- Create: `resources/js/Components/ui/Badge.tsx`
- Create: `resources/js/Components/ui/Modal.tsx`
- Create: `resources/js/Components/ui/Card.tsx`
- Create: `resources/js/Components/ui/Toggle.tsx`
- Create: `resources/js/Components/ui/UpgradePrompt.tsx`
- Create: `resources/js/Components/ui/FlashMessages.tsx`

- [ ] **Step 1: Create Button component**

Create `resources/js/Components/ui/Button.tsx`:
```tsx
import { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    children: ReactNode;
    href?: string;
    loading?: boolean;
}

const variantClasses: Record<Variant, string> = {
    primary: 'bg-slate-900 text-white hover:bg-slate-800',
    secondary: 'bg-slate-100 text-slate-900 hover:bg-slate-200',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    ghost: 'text-slate-700 hover:text-slate-900 hover:bg-slate-50',
};

const sizeClasses: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};

export default function Button({
    variant = 'primary',
    size = 'md',
    children,
    href,
    loading,
    disabled,
    className = '',
    ...props
}: ButtonProps) {
    const classes = `inline-flex items-center justify-center gap-2 font-semibold rounded-lg ${variantClasses[variant]} ${sizeClasses[size]} ${disabled || loading ? 'opacity-50 cursor-not-allowed' : ''} ${className}`;

    if (href) {
        return (
            <a href={href} className={classes}>
                {children}
            </a>
        );
    }

    return (
        <button className={classes} disabled={disabled || loading} {...props}>
            {loading && (
                <svg className="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            )}
            {children}
        </button>
    );
}
```

- [ ] **Step 2: Create Input component**

Create `resources/js/Components/ui/Input.tsx`:
```tsx
import { InputHTMLAttributes, forwardRef } from 'react';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
}

const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ label, error, className = '', id, ...props }, ref) => {
        const inputId = id || props.name;
        return (
            <div>
                {label && (
                    <label htmlFor={inputId} className="block text-sm font-medium text-slate-700 mb-1">
                        {label}
                    </label>
                )}
                <input
                    ref={ref}
                    id={inputId}
                    className={`w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 ${error ? 'border-red-500' : ''} ${className}`}
                    {...props}
                />
                {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
            </div>
        );
    }
);

Input.displayName = 'Input';
export default Input;
```

- [ ] **Step 3: Create Select component**

Create `resources/js/Components/ui/Select.tsx`:
```tsx
import { SelectHTMLAttributes, forwardRef } from 'react';

interface Option {
    value: string | number;
    label: string;
}

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    options: Option[];
    placeholder?: string;
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
    ({ label, error, options, placeholder, className = '', id, ...props }, ref) => {
        const selectId = id || props.name;
        return (
            <div>
                {label && (
                    <label htmlFor={selectId} className="block text-sm font-medium text-slate-700 mb-1">
                        {label}
                    </label>
                )}
                <select
                    ref={ref}
                    id={selectId}
                    className={`w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 ${error ? 'border-red-500' : ''} ${className}`}
                    {...props}
                >
                    {placeholder && <option value="">{placeholder}</option>}
                    {options.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
            </div>
        );
    }
);

Select.displayName = 'Select';
export default Select;
```

- [ ] **Step 4: Create Badge component**

Create `resources/js/Components/ui/Badge.tsx`:
```tsx
import { ReactNode } from 'react';

type Color = 'gray' | 'info' | 'success' | 'warning' | 'danger' | 'primary';

const colorClasses: Record<Color, string> = {
    gray: 'bg-slate-100 text-slate-700',
    info: 'bg-blue-100 text-blue-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-700',
    primary: 'bg-indigo-100 text-indigo-700',
};

interface BadgeProps {
    color?: Color;
    children: ReactNode;
}

export default function Badge({ color = 'gray', children }: BadgeProps) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${colorClasses[color]}`}>
            {children}
        </span>
    );
}
```

- [ ] **Step 5: Create Modal component**

Create `resources/js/Components/ui/Modal.tsx`:
```tsx
import { ReactNode, useEffect, useCallback } from 'react';

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    children: ReactNode;
}

export default function Modal({ open, onClose, title, children }: ModalProps) {
    const handleEscape = useCallback((e: KeyboardEvent) => {
        if (e.key === 'Escape') onClose();
    }, [onClose]);

    useEffect(() => {
        if (open) document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [open, handleEscape]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="fixed inset-0 bg-black/50" onClick={onClose} />
            <div className="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[85vh] overflow-y-auto p-6">
                {title && (
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-bold">{title}</h3>
                        <button onClick={onClose} className="text-slate-400 hover:text-slate-600">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                )}
                {children}
            </div>
        </div>
    );
}
```

- [ ] **Step 6: Create Card component**

Create `resources/js/Components/ui/Card.tsx`:
```tsx
import { ReactNode } from 'react';

interface CardProps {
    title?: string;
    description?: string;
    children: ReactNode;
    className?: string;
}

export default function Card({ title, description, children, className = '' }: CardProps) {
    return (
        <div className={`bg-white rounded-xl border border-slate-200 p-6 ${className}`}>
            {title && <h3 className="text-lg font-bold mb-1">{title}</h3>}
            {description && <p className="text-sm text-slate-500 mb-4">{description}</p>}
            {children}
        </div>
    );
}
```

- [ ] **Step 7: Create Toggle component**

Create `resources/js/Components/ui/Toggle.tsx`:
```tsx
interface ToggleProps {
    label?: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
}

export default function Toggle({ label, checked, onChange, disabled }: ToggleProps) {
    return (
        <label className="inline-flex items-center gap-2 cursor-pointer">
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                disabled={disabled}
                onClick={() => onChange(!checked)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full ${checked ? 'bg-indigo-600' : 'bg-slate-200'} ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
                <span className={`inline-block h-4 w-4 rounded-full bg-white ${checked ? 'translate-x-6' : 'translate-x-1'}`} />
            </button>
            {label && <span className="text-sm text-slate-700">{label}</span>}
        </label>
    );
}
```

- [ ] **Step 8: Create UpgradePrompt component**

Create `resources/js/Components/ui/UpgradePrompt.tsx`:
```tsx
import Button from './Button';

interface UpgradePromptProps {
    feature: string;
    planName?: string;
}

export default function UpgradePrompt({ feature, planName = 'Pro' }: UpgradePromptProps) {
    return (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-center">
            <p className="text-sm text-amber-800 mb-2">
                <span className="font-semibold">{feature}</span> is available on the {planName} plan.
            </p>
            <Button variant="primary" size="sm" href="/customer/settings">
                Upgrade
            </Button>
        </div>
    );
}
```

- [ ] **Step 9: Create FlashMessages component**

Create `resources/js/Components/ui/FlashMessages.tsx`:
```tsx
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function FlashMessages() {
    const { flash } = usePage<PageProps>().props;

    return (
        <>
            {flash.success && (
                <div className="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800 mb-4">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800 mb-4">
                    {flash.error}
                </div>
            )}
        </>
    );
}
```

- [ ] **Step 10: Verify build**

Run: `npm run build`
Expected: Build succeeds with no errors

- [ ] **Step 11: Commit**

```bash
git add resources/js/Components/ui/
git commit -m "feat: create reusable UI components (Button, Input, Select, Badge, Modal, Card, Toggle, UpgradePrompt, FlashMessages)"
```

---

### Task 5: Create layouts

**Files:**
- Create: `resources/js/Layouts/PublicLayout.tsx`
- Create: `resources/js/Layouts/CustomerLayout.tsx`
- Create: `resources/js/Layouts/AdminLayout.tsx`

- [ ] **Step 1: Create PublicLayout**

Create `resources/js/Layouts/PublicLayout.tsx`:
```tsx
import { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen">
            <nav className="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-sm border-b border-slate-200">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-lg">
                                H
                            </div>
                            <span className="font-extrabold text-xl tracking-tight">HeySentinel</span>
                        </Link>
                        <div className="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
                            <a href="#features" className="hover:text-amber-700">Features</a>
                            <a href="#how" className="hover:text-amber-700">How it works</a>
                            <a href="#pricing" className="hover:text-amber-700">Pricing</a>
                        </div>
                        <div className="hidden md:flex items-center gap-3">
                            {auth.user ? (
                                <Link href="/customer" className="text-sm font-semibold bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800">
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link href="/login" className="text-sm font-semibold text-slate-700 hover:text-amber-700">
                                        Log in
                                    </Link>
                                    <Link href="/register" className="text-sm font-semibold bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800">
                                        Start free trial
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>
            <main>{children}</main>
        </div>
    );
}
```

- [ ] **Step 2: Create CustomerLayout**

Create `resources/js/Layouts/CustomerLayout.tsx`:
```tsx
import { ReactNode } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import FlashMessages from '@/Components/ui/FlashMessages';

const navItems = [
    { href: '/customer', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4' },
    { href: '/customer/apps', label: 'Browse Apps', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
    { href: '/customer/saved-searches', label: 'Saved Searches', icon: 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z' },
    { href: '/customer/settings', label: 'Settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
];

export default function CustomerLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<PageProps>().props;
    const currentPath = usePage().url;

    return (
        <div className="min-h-screen flex bg-slate-50">
            <aside className="w-64 bg-white border-r border-slate-200 flex flex-col">
                <div className="p-4 border-b border-slate-200">
                    <Link href="/" className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-sm">H</div>
                        <span className="font-bold text-lg">HeySentinel</span>
                    </Link>
                </div>
                <nav className="flex-1 p-3 space-y-1">
                    {navItems.map((item) => {
                        const active = currentPath === item.href || (item.href !== '/customer' && currentPath.startsWith(item.href));
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium ${active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={item.icon} />
                                </svg>
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
                <div className="p-4 border-t border-slate-200">
                    <div className="text-sm font-medium text-slate-900">{auth.account?.name}</div>
                    <div className="text-xs text-slate-500">{auth.user?.email}</div>
                    <button
                        onClick={() => router.post('/logout')}
                        className="mt-2 text-xs text-slate-500 hover:text-slate-700"
                    >
                        Log out
                    </button>
                </div>
            </aside>
            <main className="flex-1 p-8">
                <FlashMessages />
                {children}
            </main>
        </div>
    );
}
```

- [ ] **Step 3: Create AdminLayout**

Create `resources/js/Layouts/AdminLayout.tsx`:
```tsx
import { ReactNode } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import FlashMessages from '@/Components/ui/FlashMessages';

const navGroups = [
    {
        label: 'Overview',
        items: [
            { href: '/admin', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4' },
        ],
    },
    {
        label: 'Management',
        items: [
            { href: '/admin/accounts', label: 'Accounts', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { href: '/admin/users', label: 'Users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z' },
            { href: '/admin/plans', label: 'Plans', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
        ],
    },
    {
        label: 'Shopify Data',
        items: [
            { href: '/admin/shopify-apps', label: 'Apps', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
            { href: '/admin/shopify-stores', label: 'Stores', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
            { href: '/admin/store-reviews', label: 'Reviews', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
        ],
    },
    {
        label: 'Analytics',
        items: [
            { href: '/admin/pain-points', label: 'Pain Points', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
        ],
    },
];

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<PageProps>().props;
    const currentPath = usePage().url;

    return (
        <div className="min-h-screen flex bg-slate-50">
            <aside className="w-64 bg-slate-900 text-white flex flex-col">
                <div className="p-4 border-b border-slate-700">
                    <Link href="/admin" className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-white font-black text-sm">H</div>
                        <span className="font-bold text-lg">Admin</span>
                    </Link>
                </div>
                <nav className="flex-1 p-3 space-y-4 overflow-y-auto">
                    {navGroups.map((group) => (
                        <div key={group.label}>
                            <p className="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{group.label}</p>
                            <div className="space-y-1">
                                {group.items.map((item) => {
                                    const active = currentPath === item.href || (item.href !== '/admin' && currentPath.startsWith(item.href));
                                    return (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium ${active ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`}
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={item.icon} />
                                            </svg>
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </nav>
                <div className="p-4 border-t border-slate-700">
                    <div className="text-sm font-medium">{auth.user?.name}</div>
                    <button
                        onClick={() => router.post('/logout')}
                        className="mt-1 text-xs text-slate-400 hover:text-white"
                    >
                        Log out
                    </button>
                </div>
            </aside>
            <main className="flex-1 p-8">
                <FlashMessages />
                {children}
            </main>
        </div>
    );
}
```

- [ ] **Step 4: Verify build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 5: Commit**

```bash
git add resources/js/Layouts/
git commit -m "feat: create PublicLayout, CustomerLayout, and AdminLayout for Inertia"
```

---

### Task 6: Create DataTable reusable component

**Files:**
- Create: `resources/js/Components/tables/DataTable.tsx`

- [ ] **Step 1: Create DataTable component**

Create `resources/js/Components/tables/DataTable.tsx`:
```tsx
import { ReactNode, useCallback } from 'react';
import { router } from '@inertiajs/react';
import Button from '@/Components/ui/Button';

export interface Column<T> {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (row: T) => ReactNode;
}

export interface FilterConfig {
    key: string;
    label: string;
    type: 'text' | 'select' | 'toggle';
    options?: { value: string | number; label: string }[];
}

interface DataTableProps<T> {
    columns: Column<T>[];
    data: T[];
    meta?: {
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
    };
    links?: {
        prev: string | null;
        next: string | null;
    };
    filters?: FilterConfig[];
    currentFilters?: Record<string, string>;
    currentSort?: string;
    currentDirection?: 'asc' | 'desc';
    rowActions?: (row: T) => ReactNode;
    emptyMessage?: string;
}

export default function DataTable<T extends { id: number | string }>({
    columns,
    data,
    meta,
    links,
    filters,
    currentFilters = {},
    currentSort,
    currentDirection = 'asc',
    rowActions,
    emptyMessage = 'No records found.',
}: DataTableProps<T>) {
    const handleSort = useCallback((key: string) => {
        const direction = currentSort === key && currentDirection === 'asc' ? 'desc' : 'asc';
        router.get(window.location.pathname, {
            ...currentFilters,
            sort: key,
            direction,
            page: 1,
        }, { preserveState: true, preserveScroll: true });
    }, [currentSort, currentDirection, currentFilters]);

    const handleFilter = useCallback((key: string, value: string) => {
        const newFilters = { ...currentFilters, [key]: value, page: '1' };
        if (!value) delete newFilters[key];
        router.get(window.location.pathname, newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [currentFilters]);

    return (
        <div>
            {filters && filters.length > 0 && (
                <div className="flex flex-wrap gap-3 mb-4">
                    {filters.map((f) => (
                        <div key={f.key}>
                            {f.type === 'text' && (
                                <input
                                    type="text"
                                    placeholder={f.label}
                                    value={currentFilters[f.key] || ''}
                                    onChange={(e) => handleFilter(f.key, e.target.value)}
                                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                                />
                            )}
                            {f.type === 'select' && (
                                <select
                                    value={currentFilters[f.key] || ''}
                                    onChange={(e) => handleFilter(f.key, e.target.value)}
                                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                                >
                                    <option value="">{f.label}</option>
                                    {f.options?.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            )}
                        </div>
                    ))}
                </div>
            )}

            <div className="overflow-x-auto rounded-xl border border-slate-200">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 border-b border-slate-200">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={`px-4 py-3 text-left font-semibold text-slate-600 ${col.sortable ? 'cursor-pointer select-none hover:text-slate-900' : ''}`}
                                    onClick={col.sortable ? () => handleSort(col.key) : undefined}
                                >
                                    <span className="flex items-center gap-1">
                                        {col.label}
                                        {col.sortable && currentSort === col.key && (
                                            <span>{currentDirection === 'asc' ? '↑' : '↓'}</span>
                                        )}
                                    </span>
                                </th>
                            ))}
                            {rowActions && <th className="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {data.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length + (rowActions ? 1 : 0)} className="px-4 py-8 text-center text-slate-500">
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50">
                                    {columns.map((col) => (
                                        <td key={col.key} className="px-4 py-3 text-slate-700">
                                            {col.render ? col.render(row) : String((row as Record<string, unknown>)[col.key] ?? '—')}
                                        </td>
                                    ))}
                                    {rowActions && (
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {rowActions(row)}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {meta && meta.last_page > 1 && (
                <div className="flex items-center justify-between mt-4 text-sm text-slate-600">
                    <span>Showing {meta.from}–{meta.to} of {meta.total}</span>
                    <div className="flex gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={!links?.prev}
                            onClick={() => links?.prev && router.get(links.prev, {}, { preserveState: true })}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={!links?.next}
                            onClick={() => links?.next && router.get(links.next, {}, { preserveState: true })}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
```

- [ ] **Step 2: Verify build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/tables/DataTable.tsx
git commit -m "feat: create reusable DataTable component with sort, filter, and pagination"
```

---

## Block 2: Landing Page

### Task 7: Create Landing page and controller

**Files:**
- Create: `app/Http/Controllers/LandingController.php`
- Create: `resources/js/Pages/Landing.tsx`
- Modify: `routes/web.php`

- [ ] **Step 1: Create LandingController**

Create `app/Http/Controllers/LandingController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\ShopifyApp;
use App\Models\ShopifyStore;
use App\Models\StoreReview;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $stats = Cache::remember('landing_stats', 3600, function () {
            return [
                'apps_tracked' => ShopifyApp::count(),
                'stores_indexed' => ShopifyStore::count(),
                'reviews_analyzed' => StoreReview::count(),
            ];
        });

        $plans = Plan::query()
            ->public()
            ->active()
            ->with(['features' => fn ($q) => $q->orderBy('feature_key')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'description' => $plan->description,
                'monthly_price' => $plan->monthly_price,
                'features' => $plan->features->map(fn ($f) => [
                    'feature_key' => $f->feature_key,
                    'feature_value' => $f->feature_value,
                    'value_type' => $f->value_type->value,
                ]),
            ]);

        return Inertia::render('Landing', [
            'stats' => $stats,
            'plans' => $plans,
        ]);
    }
}
```

- [ ] **Step 2: Create Landing.tsx page**

Create `resources/js/Pages/Landing.tsx`:
```tsx
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

interface LandingPlan {
    id: number;
    slug: string;
    name: string;
    description: string;
    monthly_price: string;
    features: {
        feature_key: string;
        feature_value: string;
        value_type: string;
    }[];
}

interface Props {
    stats: {
        apps_tracked: number;
        stores_indexed: number;
        reviews_analyzed: number;
    };
    plans: LandingPlan[];
}

function formatNumber(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M+';
    if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K+';
    return String(n);
}

function formatFeatureValue(f: { feature_key: string; feature_value: string; value_type: string }): { label: string; value: string; included: boolean } {
    const label = f.feature_key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    switch (f.value_type) {
        case 'unlimited': return { label, value: 'Unlimited', included: true };
        case 'boolean': return { label, value: f.feature_value === 'true' ? 'Included' : 'Not included', included: f.feature_value === 'true' };
        case 'integer': return { label, value: Number(f.feature_value).toLocaleString(), included: true };
        default: return { label, value: f.feature_value, included: true };
    }
}

const features = [
    { title: 'Track competitors at scale', desc: 'We continuously scrape the Shopify App Store and Storeleads. No manual tracking, no spreadsheets. Filter by category, pricing, rating, and install volume in seconds.', icon: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z' },
    { title: 'AI-extracted pain points', desc: 'Claude Haiku classifies every review into structured pain points and feature requests. No vector DBs, no RAG complexity — just clean tags ready for SQL filtering.', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Discover market gaps', desc: "See exactly which apps have the most complaints about shipping, billing, UI, or any other category. Filter to top-installed apps with the lowest ratings — that's your opportunity.", icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' },
];

const steps = [
    { num: '01', title: 'Sign up and tell us your niche', desc: 'Pick the Shopify App categories you care about. We start indexing competitors within minutes.' },
    { num: '02', title: 'Let the AI surface the pain', desc: 'Our pipeline classifies every customer review into pain points, feature requests, and bug reports. Updated daily.' },
    { num: '03', title: 'Browse insights, build winners', desc: 'Use the dashboard to filter apps by pain-point category, install volume, and rating. The product gaps are the queries with empty result sets.' },
];

export default function Landing({ stats, plans }: Props) {
    return (
        <PublicLayout>
            <Head title="Find market gaps in the Shopify App Store" />

            {/* Hero */}
            <section className="pt-32 pb-24 lg:pt-44 lg:pb-32">
                <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                    <div className="max-w-4xl mx-auto">
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 border border-amber-200 text-amber-900 text-sm font-medium mb-8">
                            <span className="w-2 h-2 rounded-full bg-amber-600" />
                            Built for Shopify App developers
                        </div>
                        <h1 className="text-5xl md:text-6xl lg:text-7xl font-black tracking-tighter leading-[1.05] mb-6">
                            Stop guessing.
                            <span className="block text-amber-600">Build what's missing.</span>
                        </h1>
                        <p className="text-xl md:text-2xl text-slate-700 leading-relaxed max-w-3xl mx-auto mb-10">
                            HeySentinel scrapes thousands of competitor apps in the Shopify App Store and uses AI to surface the{' '}
                            <span className="font-bold text-slate-900">exact pain points</span>{' '}
                            merchants are crying about — so you can build the solution they're already paying for.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <Link href="/register" className="inline-flex items-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-800">
                                Start 14-day free trial
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </Link>
                            <a href="#pricing" className="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-semibold text-lg text-slate-700 hover:text-slate-900">
                                See pricing →
                            </a>
                        </div>
                        <p className="text-sm text-slate-500 mt-6">No credit card required · Cancel anytime</p>
                    </div>

                    {/* Stats */}
                    <div className="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                        {[
                            [formatNumber(stats.apps_tracked), 'Apps tracked'],
                            [formatNumber(stats.stores_indexed), 'Stores indexed'],
                            [formatNumber(stats.reviews_analyzed), 'Reviews analyzed'],
                            ['24h', 'AI insight SLA'],
                        ].map(([value, label]) => (
                            <div key={label} className="text-center">
                                <div className="text-3xl md:text-4xl font-black text-slate-900">{value}</div>
                                <div className="text-sm text-slate-600 font-medium mt-1">{label}</div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Features */}
            <section id="features" className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-4xl md:text-5xl font-black tracking-tight mb-4">Everything you need to find your next product.</h2>
                        <p className="text-lg text-slate-600">Three pillars that turn the Shopify App Store into actionable intelligence.</p>
                    </div>
                    <div className="grid md:grid-cols-3 gap-8">
                        {features.map((f) => (
                            <div key={f.title} className="p-8 rounded-2xl bg-white border border-slate-200 hover:border-amber-300 hover:shadow-lg">
                                <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white mb-5">
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={f.icon} /></svg>
                                </div>
                                <h3 className="text-xl font-bold mb-3">{f.title}</h3>
                                <p className="text-slate-600 leading-relaxed">{f.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* How it works */}
            <section id="how" className="py-24 bg-slate-50">
                <div className="max-w-5xl mx-auto px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-4xl md:text-5xl font-black tracking-tight mb-4">How it works</h2>
                        <p className="text-lg text-slate-600">Three steps from "what should I build?" to "I know exactly what to build."</p>
                    </div>
                    <div className="space-y-8">
                        {steps.map((s) => (
                            <div key={s.num} className="flex flex-col md:flex-row gap-6 items-start">
                                <div className="flex-shrink-0 w-16 h-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xl">{s.num}</div>
                                <div className="flex-1 pt-2">
                                    <h3 className="text-2xl font-bold mb-2">{s.title}</h3>
                                    <p className="text-slate-600 text-lg leading-relaxed">{s.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing */}
            <section id="pricing" className="py-24 bg-slate-900 text-white">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-4xl md:text-5xl font-black tracking-tight mb-4">Pricing that scales with you.</h2>
                        <p className="text-lg text-slate-300">Start free. Upgrade when you start shipping winners.</p>
                    </div>
                    <div className="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                        {plans.map((plan) => {
                            const isHighlight = plan.slug === 'premium';
                            return (
                                <div key={plan.id} className={`relative rounded-2xl p-8 ${isHighlight ? 'bg-gradient-to-br from-amber-500 to-orange-600 shadow-2xl scale-105 z-10' : 'bg-slate-800/80 border border-slate-700'}`}>
                                    {isHighlight && (
                                        <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-slate-900 text-amber-400 text-xs font-bold uppercase tracking-wider">Most popular</div>
                                    )}
                                    <h3 className="text-2xl font-bold">{plan.name}</h3>
                                    <p className={`text-sm mt-1 ${isHighlight ? 'text-amber-50' : 'text-slate-400'}`}>{plan.description}</p>
                                    <div className="mt-6 mb-6 flex items-baseline gap-1">
                                        <span className="text-5xl font-black">${Number(plan.monthly_price).toFixed(0)}</span>
                                        <span className={`text-sm ${isHighlight ? 'text-amber-50' : 'text-slate-400'}`}>/month</span>
                                    </div>
                                    <Link href={`/register?plan=${plan.slug}`} className={`block w-full text-center py-3 rounded-xl font-semibold ${isHighlight ? 'bg-white text-slate-900 hover:bg-amber-50' : 'bg-slate-700 text-white hover:bg-slate-600'}`}>
                                        Start with {plan.name}
                                    </Link>
                                    <ul className="mt-8 space-y-3">
                                        {plan.features.map((f) => {
                                            const { label, value, included } = formatFeatureValue(f);
                                            return (
                                                <li key={f.feature_key} className={`flex items-start gap-3 text-sm ${!included ? 'opacity-40' : ''}`}>
                                                    {included ? (
                                                        <svg className={`flex-shrink-0 w-5 h-5 mt-0.5 ${isHighlight ? 'text-white' : 'text-amber-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" /></svg>
                                                    ) : (
                                                        <svg className="flex-shrink-0 w-5 h-5 text-slate-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" /></svg>
                                                    )}
                                                    <span><span className="font-medium">{label}:</span> {value}</span>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                            );
                        })}
                    </div>
                    <p className="text-center mt-12 text-slate-400 text-sm">All plans include a 14-day free trial · No credit card required to start</p>
                </div>
            </section>

            {/* CTA */}
            <section className="py-24 bg-white">
                <div className="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                    <h2 className="text-4xl md:text-5xl font-black tracking-tight mb-6">Ready to know what to build next?</h2>
                    <p className="text-xl text-slate-600 mb-10 max-w-2xl mx-auto">Join Shopify App developers who stopped guessing and started shipping what merchants are actually asking for.</p>
                    <Link href="/register" className="inline-flex items-center gap-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-800">
                        Start your free trial
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </Link>
                </div>
            </section>

            {/* Footer */}
            <footer className="bg-slate-950 text-slate-400 py-12">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-black text-sm">H</div>
                            <span className="font-bold text-white">HeySentinel</span>
                        </div>
                        <p className="text-sm">© {new Date().getFullYear()} HeySentinel. Market intel for the Shopify App ecosystem.</p>
                    </div>
                </div>
            </footer>
        </PublicLayout>
    );
}
```

- [ ] **Step 3: Update routes/web.php**

Replace the landing route in `routes/web.php`:
```php
<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Auth\RegisteredAccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::get('/register', [RegisteredAccountController::class, 'create'])->name('register');
Route::post('/register', [RegisteredAccountController::class, 'store'])->name('register.store');
```

- [ ] **Step 4: Write test for landing page**

Create `tests/Feature/LandingPageTest.php`:
```php
<?php

use App\Models\Plan;

it('renders the landing page with plans and stats', function () {
    Plan::factory()->create(['slug' => 'free', 'name' => 'Free', 'monthly_price' => 0, 'is_public' => true, 'status' => 'active']);

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Landing')
        ->has('stats')
        ->has('plans')
    );
});
```

- [ ] **Step 5: Run test**

Run: `php artisan test tests/Feature/LandingPageTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/js/Pages/Landing.tsx routes/web.php tests/Feature/LandingPageTest.php
git commit -m "feat: create React landing page with real DB stats and dynamic pricing"
```

---

### Task 8: Create auth pages (Login + Register)

**Files:**
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `app/Http/Controllers/Auth/RegisteredAccountController.php`
- Create: `resources/js/Pages/Auth/Login.tsx`
- Create: `resources/js/Pages/Auth/Register.tsx`
- Modify: `routes/web.php`

- [ ] **Step 1: Create LoginController**

Create `app/Http/Controllers/Auth/LoginController.php`:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isSuperAdmin()) {
                return redirect()->intended('/admin');
            }

            return redirect()->intended('/customer');
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

- [ ] **Step 2: Update RegisteredAccountController for Inertia**

Modify `app/Http/Controllers/Auth/RegisteredAccountController.php`. Change the `create` method to return Inertia and the `store` redirect:

Replace `create()`:
```php
public function create(): \Inertia\Response
{
    $plan = request('plan');

    return \Inertia\Inertia::render('Auth/Register', [
        'preselectedPlan' => $plan,
        'plans' => Plan::query()->public()->active()->orderBy('sort_order')->get(['id', 'slug', 'name', 'monthly_price']),
    ]);
}
```

Replace the `return redirect()` at the end of `store()`:
```php
return redirect()->to('/login')->with('success', 'Account created. Please log in.');
```

- [ ] **Step 3: Create Login.tsx**

Create `resources/js/Pages/Auth/Login.tsx`:
```tsx
import { Head, useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <PublicLayout>
            <Head title="Log in" />
            <div className="pt-32 pb-24 max-w-md mx-auto px-6">
                <h1 className="text-3xl font-black mb-8 text-center">Log in to HeySentinel</h1>
                <form onSubmit={submit} className="space-y-4">
                    <Input label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} required />
                    <Input label="Password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={errors.password} required />
                    <label className="flex items-center gap-2">
                        <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} className="rounded border-slate-300" />
                        <span className="text-sm text-slate-600">Remember me</span>
                    </label>
                    <Button type="submit" loading={processing} className="w-full">Log in</Button>
                </form>
                <p className="text-center mt-6 text-sm text-slate-500">
                    Don't have an account? <Link href="/register" className="text-indigo-600 hover:underline">Start free trial</Link>
                </p>
            </div>
        </PublicLayout>
    );
}
```

- [ ] **Step 4: Create Register.tsx**

Create `resources/js/Pages/Auth/Register.tsx`:
```tsx
import { Head, useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';

interface Props {
    preselectedPlan: string | null;
    plans: { id: number; slug: string; name: string; monthly_price: string }[];
}

export default function Register({ preselectedPlan, plans }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        company_name: '',
        plan_slug: preselectedPlan || plans[0]?.slug || '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/register');
    }

    return (
        <PublicLayout>
            <Head title="Create your account" />
            <div className="pt-32 pb-24 max-w-md mx-auto px-6">
                <h1 className="text-3xl font-black mb-2 text-center">Start your free trial</h1>
                <p className="text-center text-slate-500 mb-8">14 days free. No credit card required.</p>
                <form onSubmit={submit} className="space-y-4">
                    <Input label="Your name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />
                    <Input label="Company name" value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} error={errors.company_name} required />
                    <Input label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} required />
                    <Input label="Password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={errors.password} required />
                    <Input label="Confirm password" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} required />
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Select a plan</label>
                        <div className="space-y-2">
                            {plans.map((plan) => (
                                <label key={plan.slug} className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer ${data.plan_slug === plan.slug ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'}`}>
                                    <input type="radio" name="plan_slug" value={plan.slug} checked={data.plan_slug === plan.slug} onChange={(e) => setData('plan_slug', e.target.value)} className="text-indigo-600" />
                                    <span className="font-medium">{plan.name}</span>
                                    <span className="text-slate-500 text-sm ml-auto">${Number(plan.monthly_price).toFixed(0)}/mo</span>
                                </label>
                            ))}
                        </div>
                        {errors.plan_slug && <p className="mt-1 text-sm text-red-600">{errors.plan_slug}</p>}
                    </div>
                    <Button type="submit" loading={processing} className="w-full">Create account</Button>
                </form>
                <p className="text-center mt-6 text-sm text-slate-500">
                    Already have an account? <Link href="/login" className="text-indigo-600 hover:underline">Log in</Link>
                </p>
            </div>
        </PublicLayout>
    );
}
```

- [ ] **Step 5: Update routes/web.php with auth routes**

Add to `routes/web.php`:
```php
use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');
```

- [ ] **Step 6: Write auth tests**

Create `tests/Feature/AuthPagesTest.php`:
```php
<?php

use App\Models\Plan;
use App\Models\User;
use App\Models\Account;
use App\Models\AccountUser;
use App\Enums\UserRole;

it('renders login page', function () {
    $this->get('/login')->assertStatus(200)->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('renders register page with plans', function () {
    Plan::factory()->create(['is_public' => true, 'status' => 'active']);
    $this->get('/register')->assertStatus(200)->assertInertia(fn ($page) => $page->component('Auth/Register')->has('plans'));
});

it('logs in a valid user', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    Account::factory()->create(['owner_user_id' => $user->id]);
    AccountUser::create(['user_id' => $user->id, 'account_id' => Account::first()->id, 'role' => UserRole::Owner->value, 'invitation_accepted_at' => now()]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/customer');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $this->post('/login', ['email' => 'no@no.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
```

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Feature/AuthPagesTest.php`
Expected: All PASS

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php app/Http/Controllers/Auth/RegisteredAccountController.php resources/js/Pages/Auth/ routes/web.php tests/Feature/AuthPagesTest.php
git commit -m "feat: create React login and register pages with Inertia auth flow"
```

---

## Block 3: Customer Portal + Phase 6 M2

### Task 9: Create migrations for SavedSearch and Unlist

**Files:**
- Create: `database/migrations/2026_05_25_100001_create_saved_searches_table.php`
- Create: `database/migrations/2026_05_25_100002_add_unlisted_at_to_shopify_apps_and_stores.php`
- Create: `app/Models/SavedSearch.php`
- Create: `app/Models/Concerns/HasUnlisting.php`
- Modify: `app/Models/ShopifyApp.php`
- Modify: `app/Models/ShopifyStore.php`
- Modify: `config/features.php`

- [ ] **Step 1: Create saved_searches migration**

Run: `php artisan make:migration create_saved_searches_table`

Edit the generated migration:
```php
public function up(): void
{
    Schema::create('saved_searches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('account_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->json('filters');
        $table->boolean('notify_on_new')->default(false);
        $table->timestamp('last_viewed_at')->nullable();
        $table->timestamps();

        $table->index('account_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('saved_searches');
}
```

- [ ] **Step 2: Create unlisted_at migration**

Run: `php artisan make:migration add_unlisted_at_to_shopify_apps_and_stores`

Edit the generated migration:
```php
public function up(): void
{
    Schema::table('shopify_apps', function (Blueprint $table) {
        $table->timestamp('unlisted_at')->nullable()->after('ai_summary_model');
    });
    Schema::table('shopify_stores', function (Blueprint $table) {
        $table->timestamp('unlisted_at')->nullable()->after('scraping_status');
    });
}

public function down(): void
{
    Schema::table('shopify_apps', function (Blueprint $table) {
        $table->dropColumn('unlisted_at');
    });
    Schema::table('shopify_stores', function (Blueprint $table) {
        $table->dropColumn('unlisted_at');
    });
}
```

- [ ] **Step 3: Create SavedSearch model**

Create `app/Models/SavedSearch.php`:
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use BelongsToAccount, HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'filters',
        'notify_on_new',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'notify_on_new' => 'boolean',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Create HasUnlisting trait**

Create `app/Models/Concerns/HasUnlisting.php`:
```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasUnlisting
{
    public static function bootHasUnlisting(): void
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable().'.unlisted_at');
        });
    }

    public function scopeWithUnlisted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    public function unlist(): void
    {
        $this->update(['unlisted_at' => now()]);
    }

    public function relist(): void
    {
        $this->update(['unlisted_at' => null]);
    }

    public function isUnlisted(): bool
    {
        return $this->unlisted_at !== null;
    }
}
```

- [ ] **Step 5: Apply HasUnlisting to ShopifyApp**

Add to `app/Models/ShopifyApp.php`:

Add import: `use App\Models\Concerns\HasUnlisting;`
Add trait: `use HasFactory, HasUnlisting;`
Add `'unlisted_at'` to `$fillable` array.
Add `'unlisted_at' => 'datetime'` to `casts()`.

- [ ] **Step 6: Apply HasUnlisting to ShopifyStore**

Add to `app/Models/ShopifyStore.php`:

Add import: `use App\Models\Concerns\HasUnlisting;`
Add trait: `use HasFactory, HasUnlisting;`
Add `'unlisted_at'` to `$fillable` array.
Add `'unlisted_at' => 'datetime'` to `casts()`.

- [ ] **Step 7: Update config/features.php**

Update `config/features.php` to add the new feature keys:
```php
<?php

return [
    'apps_tracked',
    'saved_searches',
    'alerts',
    'export_csv',
    'review_velocity',
    'team_members',
    'api_access',
    'white_label',
];
```

- [ ] **Step 8: Run migrations**

Run: `php artisan migrate`
Expected: Both migrations run successfully

- [ ] **Step 9: Write model tests**

Create `tests/Feature/Models/SavedSearchTest.php`:
```php
<?php

use App\Models\Account;
use App\Models\SavedSearch;
use App\Models\User;

it('creates a saved search scoped to account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $user->id]);

    $search = SavedSearch::create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'name' => 'Low-rated email apps',
        'filters' => ['rating_max' => 3, 'keyword' => 'email'],
    ]);

    expect($search->filters)->toBe(['rating_max' => 3, 'keyword' => 'email']);
    expect($search->account_id)->toBe($account->id);
});
```

Create `tests/Feature/Models/HasUnlistingTest.php`:
```php
<?php

use App\Models\ShopifyApp;

it('excludes unlisted apps by default', function () {
    ShopifyApp::factory()->create(['name' => 'Visible']);
    ShopifyApp::factory()->create(['name' => 'Hidden', 'unlisted_at' => now()]);

    expect(ShopifyApp::count())->toBe(1);
    expect(ShopifyApp::withUnlisted()->count())->toBe(2);
});

it('can unlist and relist an app', function () {
    $app = ShopifyApp::factory()->create();

    $app->unlist();
    expect($app->fresh()->isUnlisted())->toBeTrue();
    expect(ShopifyApp::count())->toBe(0);

    $app->relist();
    expect($app->fresh()->isUnlisted())->toBeFalse();
    expect(ShopifyApp::count())->toBe(1);
});
```

- [ ] **Step 10: Run tests**

Run: `php artisan test tests/Feature/Models/SavedSearchTest.php tests/Feature/Models/HasUnlistingTest.php`
Expected: All PASS

- [ ] **Step 11: Commit**

```bash
git add database/migrations/ app/Models/SavedSearch.php app/Models/Concerns/HasUnlisting.php app/Models/ShopifyApp.php app/Models/ShopifyStore.php config/features.php tests/Feature/Models/SavedSearchTest.php tests/Feature/Models/HasUnlistingTest.php
git commit -m "feat: add SavedSearch model, HasUnlisting trait, and unlisted_at columns"
```

---

### Task 10: Create customer portal controllers

**Files:**
- Create: `app/Http/Controllers/Customer/CustomerDashboardController.php`
- Create: `app/Http/Controllers/Customer/CustomerAppController.php`
- Create: `app/Http/Controllers/Customer/FollowAppController.php`
- Create: `app/Http/Controllers/Customer/SavedSearchController.php`
- Create: `app/Http/Controllers/Customer/ExportController.php`
- Create: `app/Http/Controllers/Customer/SettingsController.php`
- Create: `app/Http/Middleware/EnsureSuperAdmin.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create CustomerDashboardController**

Create `app/Http/Controllers/Customer/CustomerDashboardController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $account = $request->user()->currentAccount;
        $accountId = $account?->id ?? 0;

        $appIds = AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id');

        $stats = $this->buildStats($accountId, $appIds);
        $sentimentTimeline = $this->buildSentimentTimeline($appIds, 12);
        $painPointsRadar = $this->buildPainPointsRadar($appIds);
        $reviewVelocity = $this->buildReviewVelocity($appIds);
        $myApps = $this->buildMyApps($accountId);

        return Inertia::render('Customer/Dashboard', compact(
            'stats', 'sentimentTimeline', 'painPointsRadar', 'reviewVelocity', 'myApps'
        ));
    }

    protected function buildStats(int $accountId, $appIds): array
    {
        if ($appIds->isEmpty()) {
            return ['followed' => 0, 'pain_points' => 0, 'negative' => 0, 'velocity' => 0];
        }

        $painPoints = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereIn('store_reviews.shopify_app_id', $appIds)
            ->count();

        $negative = DB::table('store_reviews')
            ->whereIn('shopify_app_id', $appIds)
            ->where('ai_sentiment', 'negative')
            ->count();

        $velocity = DB::table('store_reviews')
            ->whereIn('shopify_app_id', $appIds)
            ->where('published_at', '>=', now()->subWeek())
            ->count();

        return [
            'followed' => $appIds->count(),
            'pain_points' => $painPoints,
            'negative' => $negative,
            'velocity' => $velocity,
        ];
    }

    protected function buildSentimentTimeline($appIds, int $months): array
    {
        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();
        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $buckets[$key] = ['label' => $month->format('M Y'), 'positive' => 0, 'neutral' => 0, 'mixed' => 0, 'negative' => 0];
        }

        if ($appIds->isNotEmpty()) {
            $rows = DB::table('store_reviews')
                ->whereIn('shopify_app_id', $appIds)
                ->whereNotNull('ai_sentiment')
                ->where('published_at', '>=', $start)
                ->selectRaw("DATE_FORMAT(published_at, '%Y-%m') as ym, ai_sentiment, COUNT(*) as cnt")
                ->groupBy('ym', 'ai_sentiment')
                ->get();

            foreach ($rows as $row) {
                if (isset($buckets[$row->ym]) && array_key_exists($row->ai_sentiment, $buckets[$row->ym])) {
                    $buckets[$row->ym][$row->ai_sentiment] = (int) $row->cnt;
                }
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'positive' => array_column($buckets, 'positive'),
            'neutral' => array_column($buckets, 'neutral'),
            'mixed' => array_column($buckets, 'mixed'),
            'negative' => array_column($buckets, 'negative'),
        ];
    }

    protected function buildPainPointsRadar($appIds): array
    {
        if ($appIds->isEmpty()) return ['labels' => [], 'counts' => []];

        $points = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->whereIn('store_reviews.shopify_app_id', $appIds)
            ->selectRaw('ai_pain_points.name, COUNT(*) as cnt')
            ->groupBy('ai_pain_points.name')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        return [
            'labels' => $points->pluck('name')->all(),
            'counts' => $points->pluck('cnt')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function buildReviewVelocity($appIds): array
    {
        if ($appIds->isEmpty()) return ['labels' => [], 'counts' => []];

        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weeks[] = ['start' => $weekStart, 'label' => $weekStart->format('M d')];
        }

        $counts = [];
        foreach ($weeks as $week) {
            $count = DB::table('store_reviews')
                ->whereIn('shopify_app_id', $appIds)
                ->where('published_at', '>=', $week['start'])
                ->where('published_at', '<', $week['start']->copy()->addWeek())
                ->count();
            $counts[] = $count;
        }

        return [
            'labels' => array_column($weeks, 'label'),
            'counts' => $counts,
        ];
    }

    protected function buildMyApps(int $accountId): array
    {
        return DB::table('shopify_apps')
            ->join('account_followed_apps', function ($join) use ($accountId) {
                $join->on('account_followed_apps.shopify_app_id', '=', 'shopify_apps.id')
                    ->where('account_followed_apps.account_id', $accountId);
            })
            ->whereNull('shopify_apps.unlisted_at')
            ->select(
                'shopify_apps.id', 'shopify_apps.name', 'shopify_apps.average_rating',
                'shopify_apps.total_reviews', 'shopify_apps.ai_summary',
                'account_followed_apps.kind as pivot_kind', 'account_followed_apps.followed_at as pivot_followed_at'
            )
            ->orderByDesc('shopify_apps.total_reviews')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
```

- [ ] **Step 2: Create CustomerAppController**

Create `app/Http/Controllers/Customer/CustomerAppController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use App\Models\AiPainPoint;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAppController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $query = ShopifyApp::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('rating_min')) {
            $query->where('average_rating', '>=', $request->float('rating_min'));
        }
        if ($request->filled('pricing')) {
            match ($request->input('pricing')) {
                'free' => $query->where('pricing_has_free', true),
                'paid' => $query->where('pricing_has_free', false),
                default => null,
            };
        }
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%'.$request->input('keyword').'%');
        }

        $sort = $request->input('sort', 'total_reviews');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);

        $apps = $query->paginate(20)->withQueryString();

        $followedIds = AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id')
            ->all();

        $categories = ShopifyAppCategory::query()->orderBy('name')->pluck('name', 'id');
        $painPointOptions = AiPainPoint::query()->orderBy('name')->pluck('name', 'id');

        return Inertia::render('Customer/BrowseApps', [
            'apps' => $apps,
            'followedIds' => $followedIds,
            'categories' => $categories,
            'painPointOptions' => $painPointOptions,
            'filters' => $request->only(['category_id', 'rating_min', 'pricing', 'keyword', 'sort', 'direction']),
        ]);
    }

    public function show(Request $request, ShopifyApp $shopifyApp): Response
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $shopifyApp->load('category');

        $isFollowed = AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->where('shopify_app_id', $shopifyApp->id)
            ->exists();

        $reviews = $shopifyApp->reviews()
            ->orderByDesc('published_at')
            ->paginate(15)
            ->withQueryString();

        $painPoints = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->where('store_reviews.shopify_app_id', $shopifyApp->id)
            ->selectRaw('ai_pain_points.name, ai_pain_points.slug, ai_pain_points.category, COUNT(*) as mentions')
            ->groupBy('ai_pain_points.id', 'ai_pain_points.name', 'ai_pain_points.slug', 'ai_pain_points.category')
            ->orderByDesc('mentions')
            ->limit(20)
            ->get();

        return Inertia::render('Customer/AppDetail', [
            'app' => $shopifyApp,
            'isFollowed' => $isFollowed,
            'reviews' => $reviews,
            'painPoints' => $painPoints,
        ]);
    }
}
```

- [ ] **Step 3: Create FollowAppController**

Create `app/Http/Controllers/Customer/FollowAppController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FollowedAppKind;
use App\Http\Controllers\Controller;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Illuminate\Http\Request;

class FollowAppController extends Controller
{
    public function store(Request $request, ShopifyApp $shopifyApp)
    {
        $account = $request->user()->currentAccount;

        if (! $account->canUse('apps_tracked')) {
            return back()->with('error', 'You have reached your plan limit for tracked apps.');
        }

        AccountFollowedApp::firstOrCreate([
            'account_id' => $account->id,
            'shopify_app_id' => $shopifyApp->id,
        ], [
            'kind' => FollowedAppKind::Competitor->value,
            'followed_at' => now(),
        ]);

        $account->recordUsage('apps_tracked');

        return back()->with('success', "Now following {$shopifyApp->name}");
    }

    public function destroy(Request $request, ShopifyApp $shopifyApp)
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->where('shopify_app_id', $shopifyApp->id)
            ->delete();

        return back()->with('success', "Unfollowed {$shopifyApp->name}");
    }
}
```

- [ ] **Step 4: Create SavedSearchController**

Create `app/Http/Controllers/Customer/SavedSearchController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $searches = SavedSearch::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Customer/SavedSearches', [
            'searches' => $searches,
        ]);
    }

    public function store(Request $request)
    {
        $account = $request->user()->currentAccount;

        if (! $account->canUse('saved_searches')) {
            return back()->with('error', 'You have reached your plan limit for saved searches.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['required', 'array'],
        ]);

        SavedSearch::create([
            'account_id' => $account->id,
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'filters' => $data['filters'],
        ]);

        $account->recordUsage('saved_searches');

        return back()->with('success', 'Search saved.');
    }

    public function update(Request $request, SavedSearch $savedSearch)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'filters' => ['sometimes', 'array'],
            'notify_on_new' => ['sometimes', 'boolean'],
        ]);

        $savedSearch->update($data);

        return back()->with('success', 'Search updated.');
    }

    public function destroy(SavedSearch $savedSearch)
    {
        $savedSearch->delete();

        return back()->with('success', 'Search deleted.');
    }
}
```

- [ ] **Step 5: Create ExportController**

Create `app/Http/Controllers/Customer/ExportController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCsvJob;
use App\Models\ShopifyApp;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function apps(Request $request)
    {
        $account = $request->user()->currentAccount;

        if (! $account->canUse('export_csv')) {
            return back()->with('error', 'CSV export is not available on your current plan.');
        }

        ExportCsvJob::dispatch($account->id, 'apps', $request->input('filters', []));

        return back()->with('success', 'Export started. You will be able to download it shortly.');
    }

    public function reviews(Request $request, ShopifyApp $shopifyApp)
    {
        $account = $request->user()->currentAccount;

        if (! $account->canUse('export_csv')) {
            return back()->with('error', 'CSV export is not available on your current plan.');
        }

        ExportCsvJob::dispatch($account->id, 'reviews', ['shopify_app_id' => $shopifyApp->id]);

        return back()->with('success', 'Export started. You will be able to download it shortly.');
    }
}
```

- [ ] **Step 6: Create ExportCsvJob stub**

Create `app/Jobs/ExportCsvJob.php`:
```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $accountId,
        public string $type,
        public array $filters = [],
    ) {}

    public function handle(): void
    {
        $filename = "exports/{$this->accountId}/{$this->type}-".now()->format('Y-m-d-His').'.csv';

        $handle = fopen('php://temp', 'r+');

        if ($this->type === 'apps') {
            fputcsv($handle, ['Name', 'Developer', 'Rating', 'Reviews', 'Pricing', 'Category']);
            $query = \App\Models\ShopifyApp::query()->with('category');
            foreach ($query->cursor() as $app) {
                fputcsv($handle, [
                    $app->name, $app->developer_name, $app->average_rating,
                    $app->total_reviews, $app->pricing_min_usd, $app->category?->name,
                ]);
            }
        } elseif ($this->type === 'reviews') {
            fputcsv($handle, ['App', 'Reviewer', 'Rating', 'Sentiment', 'Review', 'Published']);
            $query = \App\Models\StoreReview::query()->with('app');
            if (isset($this->filters['shopify_app_id'])) {
                $query->where('shopify_app_id', $this->filters['shopify_app_id']);
            }
            foreach ($query->cursor() as $review) {
                fputcsv($handle, [
                    $review->app?->name, $review->reviewer_name, $review->rating,
                    $review->ai_sentiment, $review->review_text, $review->published_at,
                ]);
            }
        }

        rewind($handle);
        Storage::put($filename, stream_get_contents($handle));
        fclose($handle);
    }
}
```

- [ ] **Step 7: Create SettingsController**

Create `app/Http/Controllers/Customer/SettingsController.php`:
```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $account = $request->user()->currentAccount;

        return Inertia::render('Customer/Settings', [
            'account' => $account ? [
                'name' => $account->name,
                'plan' => $account->plan?->name,
                'status' => $account->status->value,
                'trial_ends' => $account->free_trial_ends_at?->toDateString(),
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['password'])) $user->password = Hash::make($data['password']);

        $user->save();

        return back()->with('success', 'Settings updated.');
    }
}
```

- [ ] **Step 8: Create EnsureSuperAdmin middleware**

Create `app/Http/Middleware/EnsureSuperAdmin.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 9: Add customer and admin routes to web.php**

Append to `routes/web.php`:
```php
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerAppController;
use App\Http\Controllers\Customer\FollowAppController;
use App\Http\Controllers\Customer\SavedSearchController;
use App\Http\Controllers\Customer\ExportController;
use App\Http\Controllers\Customer\SettingsController;

Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', CustomerDashboardController::class)->name('dashboard');
    Route::get('/apps', [CustomerAppController::class, 'index'])->name('apps.index');
    Route::get('/apps/{shopifyApp}', [CustomerAppController::class, 'show'])->name('apps.show');
    Route::post('/apps/{shopifyApp}/follow', [FollowAppController::class, 'store'])->name('apps.follow');
    Route::delete('/apps/{shopifyApp}/follow', [FollowAppController::class, 'destroy'])->name('apps.unfollow');
    Route::resource('saved-searches', SavedSearchController::class)->except(['show', 'create', 'edit']);
    Route::post('/export/apps', [ExportController::class, 'apps'])->name('export.apps');
    Route::post('/export/reviews/{shopifyApp}', [ExportController::class, 'reviews'])->name('export.reviews');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
```

- [ ] **Step 10: Run route list to verify**

Run: `php artisan route:list --path=customer`
Expected: All customer routes appear correctly

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/Customer/ app/Http/Middleware/EnsureSuperAdmin.php app/Jobs/ExportCsvJob.php routes/web.php
git commit -m "feat: create customer portal controllers with M2 features (saved searches, export, follow, settings)"
```

---

### Task 11: Create customer portal React pages

**Files:**
- Create: `resources/js/Pages/Customer/Dashboard.tsx`
- Create: `resources/js/Pages/Customer/BrowseApps.tsx`
- Create: `resources/js/Pages/Customer/AppDetail.tsx`
- Create: `resources/js/Pages/Customer/SavedSearches.tsx`
- Create: `resources/js/Pages/Customer/Settings.tsx`

This task creates the 5 customer portal pages. Each page uses `CustomerLayout` and renders data from the controllers created in Task 10.

**Due to the length of this task, each page is a separate step. The complete code for each page follows the patterns established in the Landing page (Task 7) — using Inertia `usePage`/`useForm`, the shared UI components, and the DataTable component.**

- [ ] **Step 1: Install Recharts**

Run: `npm install recharts`

- [ ] **Step 2: Create chart components**

Create `resources/js/Components/charts/SentimentTimeline.tsx`:
```tsx
import { LineChart, Line, XAxis, YAxis, Tooltip, Legend, ResponsiveContainer, CartesianGrid } from 'recharts';

interface Props {
    labels: string[];
    positive: number[];
    neutral: number[];
    mixed: number[];
    negative: number[];
}

export default function SentimentTimeline({ labels, positive, neutral, mixed, negative }: Props) {
    const data = labels.map((label, i) => ({
        month: label,
        Positive: positive[i],
        Neutral: neutral[i],
        Mixed: mixed[i],
        Negative: negative[i],
    }));

    return (
        <ResponsiveContainer width="100%" height={320}>
            <LineChart data={data}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip />
                <Legend />
                <Line type="monotone" dataKey="Positive" stroke="#22c55e" strokeWidth={2} dot={false} />
                <Line type="monotone" dataKey="Neutral" stroke="#94a3b8" strokeWidth={2} dot={false} />
                <Line type="monotone" dataKey="Mixed" stroke="#eab308" strokeWidth={2} dot={false} />
                <Line type="monotone" dataKey="Negative" stroke="#ef4444" strokeWidth={2} dot={false} />
            </LineChart>
        </ResponsiveContainer>
    );
}
```

Create `resources/js/Components/charts/PainPointsRadar.tsx`:
```tsx
import { RadarChart, Radar, PolarGrid, PolarAngleAxis, PolarRadiusAxis, ResponsiveContainer } from 'recharts';

interface Props {
    labels: string[];
    counts: number[];
}

export default function PainPointsRadar({ labels, counts }: Props) {
    const data = labels.map((label, i) => ({ subject: label, count: counts[i] }));

    if (data.length === 0) return <p className="text-sm text-slate-500 text-center py-8">No pain point data yet.</p>;

    return (
        <ResponsiveContainer width="100%" height={320}>
            <RadarChart data={data}>
                <PolarGrid stroke="#e2e8f0" />
                <PolarAngleAxis dataKey="subject" tick={{ fontSize: 11 }} />
                <PolarRadiusAxis tick={{ fontSize: 10 }} />
                <Radar dataKey="count" stroke="#6366f1" fill="#6366f1" fillOpacity={0.2} strokeWidth={2} />
            </RadarChart>
        </ResponsiveContainer>
    );
}
```

Create `resources/js/Components/charts/ReviewVelocity.tsx`:
```tsx
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts';

interface Props {
    labels: string[];
    counts: number[];
}

export default function ReviewVelocity({ labels, counts }: Props) {
    const data = labels.map((label, i) => ({ week: label, reviews: counts[i] }));

    return (
        <ResponsiveContainer width="100%" height={280}>
            <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="week" tick={{ fontSize: 11 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="reviews" fill="#6366f1" radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    );
}
```

- [ ] **Step 3: Create Dashboard.tsx**

Create `resources/js/Pages/Customer/Dashboard.tsx`:
```tsx
import { Head } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import SentimentTimeline from '@/Components/charts/SentimentTimeline';
import PainPointsRadar from '@/Components/charts/PainPointsRadar';
import ReviewVelocity from '@/Components/charts/ReviewVelocity';

interface Props {
    stats: { followed: number; pain_points: number; negative: number; velocity: number };
    sentimentTimeline: { labels: string[]; positive: number[]; neutral: number[]; mixed: number[]; negative: number[] };
    painPointsRadar: { labels: string[]; counts: number[] };
    reviewVelocity: { labels: string[]; counts: number[] };
    myApps: { id: number; name: string; average_rating: string; total_reviews: number; ai_summary?: string; pivot_kind: string; pivot_followed_at: string }[];
}

export default function Dashboard({ stats, sentimentTimeline, painPointsRadar, reviewVelocity, myApps }: Props) {
    const statCards = [
        { label: 'Apps followed', value: stats.followed, color: 'text-indigo-600' },
        { label: 'Pain points', value: stats.pain_points, color: 'text-amber-600' },
        { label: 'Negative reviews', value: stats.negative, color: 'text-red-600' },
        { label: 'Reviews this week', value: stats.velocity, color: 'text-green-600' },
    ];

    return (
        <CustomerLayout>
            <Head title="Dashboard" />
            <h1 className="text-2xl font-black mb-6">Dashboard</h1>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                {statCards.map((s) => (
                    <Card key={s.label}>
                        <p className="text-sm text-slate-500">{s.label}</p>
                        <p className={`text-3xl font-black ${s.color}`}>{s.value.toLocaleString()}</p>
                    </Card>
                ))}
            </div>

            <div className="grid lg:grid-cols-2 gap-6 mb-6">
                <Card title="Sentiment over time" description="Monthly review sentiment across followed apps">
                    <SentimentTimeline {...sentimentTimeline} />
                </Card>
                <Card title="Pain points radar" description="Top pain points across followed apps">
                    <PainPointsRadar {...painPointsRadar} />
                </Card>
            </div>

            <Card title="Review velocity" description="Reviews per week (last 12 weeks)" className="mb-6">
                <ReviewVelocity {...reviewVelocity} />
            </Card>

            <Card title="My followed apps">
                {myApps.length === 0 ? (
                    <p className="text-sm text-slate-500 py-4">No apps followed yet. Browse apps to start tracking.</p>
                ) : (
                    <table className="w-full text-sm">
                        <thead className="border-b border-slate-200">
                            <tr>
                                <th className="text-left py-2 font-semibold text-slate-600">App</th>
                                <th className="text-left py-2 font-semibold text-slate-600">Rating</th>
                                <th className="text-left py-2 font-semibold text-slate-600">Reviews</th>
                                <th className="text-left py-2 font-semibold text-slate-600">Kind</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {myApps.map((app) => (
                                <tr key={app.id}>
                                    <td className="py-2 font-medium">{app.name}</td>
                                    <td className="py-2">{Number(app.average_rating).toFixed(2)}</td>
                                    <td className="py-2">{app.total_reviews}</td>
                                    <td className="py-2">
                                        <Badge color={app.pivot_kind === 'mine' ? 'success' : 'gray'}>
                                            {app.pivot_kind === 'mine' ? 'My app' : 'Competitor'}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
        </CustomerLayout>
    );
}
```

- [ ] **Step 4: Create BrowseApps.tsx, AppDetail.tsx, SavedSearches.tsx, Settings.tsx**

These pages follow the same pattern. Due to the plan length constraint, each uses:
- `CustomerLayout` wrapper
- Props from the corresponding controller
- `DataTable` for list views
- `useForm` for mutations (follow/unfollow, save search, update settings)
- `UpgradePrompt` for gated features

Create each file following these structures:

**`resources/js/Pages/Customer/BrowseApps.tsx`**: DataTable with columns (name, developer, category, rating, reviews, pricing), filters from controller props, Follow/Unfollow row action via `router.post`/`router.delete`, "Save this search" button opening a modal with name input.

**`resources/js/Pages/Customer/AppDetail.tsx`**: App header card, pain points list, reviews DataTable with pagination. Follow button. Export CSV button (gated).

**`resources/js/Pages/Customer/SavedSearches.tsx`**: List of saved searches with name, filter badges, notify toggle, Load/Delete actions. Uses `router.put` for updates and `router.delete` for deletion.

**`resources/js/Pages/Customer/Settings.tsx`**: Profile form (name, email, password change) using `useForm`. Account info card (plan name, status, trial end date).

The exact implementation code for each of these 4 pages should be written following the established patterns from Dashboard.tsx — use the same import style, layout wrapping, and component reuse.

- [ ] **Step 5: Verify build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 6: Write smoke tests**

Create `tests/Feature/CustomerPortalTest.php`:
```php
<?php

use App\Models\User;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Enums\UserRole;
use App\Enums\AccountStatus;

beforeEach(function () {
    $this->plan = Plan::factory()->create(['status' => 'active']);
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create([
        'owner_user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'status' => AccountStatus::Trial->value,
    ]);
    AccountUser::create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'role' => UserRole::Owner->value,
        'invitation_accepted_at' => now(),
    ]);
});

it('renders customer dashboard', function () {
    $this->actingAs($this->user)
        ->get('/customer')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Customer/Dashboard'));
});

it('renders browse apps page', function () {
    $this->actingAs($this->user)
        ->get('/customer/apps')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Customer/BrowseApps'));
});

it('renders settings page', function () {
    $this->actingAs($this->user)
        ->get('/customer/settings')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Customer/Settings'));
});

it('requires authentication for customer routes', function () {
    $this->get('/customer')->assertRedirect('/login');
});
```

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Feature/CustomerPortalTest.php`
Expected: All PASS

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Customer/ resources/js/Components/charts/ tests/Feature/CustomerPortalTest.php
git commit -m "feat: create customer portal React pages with dashboard, browse apps, saved searches, and settings"
```

---

### Task 12: Remove Filament customer panel

**Files:**
- Delete: `app/Providers/Filament/CustomerPanelProvider.php`
- Delete: `app/Filament/Customer/` (entire directory)
- Delete: `resources/views/filament/customer/` (if exists)

- [ ] **Step 1: Verify React customer portal works end-to-end**

Start dev server: `npm run dev`
Visit: `http://localhost/customer` (logged in)
Expected: React dashboard renders with data

- [ ] **Step 2: Remove Filament customer panel files**

```bash
rm app/Providers/Filament/CustomerPanelProvider.php
rm -rf app/Filament/Customer/
rm -rf resources/views/filament/customer/ 2>/dev/null
```

- [ ] **Step 3: Remove CustomerPanelProvider from config if registered**

Check `config/app.php` or `bootstrap/providers.php` for `CustomerPanelProvider` and remove it if present.

- [ ] **Step 4: Run tests to verify nothing breaks**

Run: `php artisan test`
Expected: All existing tests pass (Filament customer smoke test will need to be removed or updated)

- [ ] **Step 5: Update or remove old Filament customer panel tests**

Delete `tests/Feature/Filament/CustomerPanelSmokeTest.php` since that panel no longer exists.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor: remove Filament customer panel, replaced by React + Inertia"
```

---

## Block 4: Admin Panel

### Task 13: Create admin controllers

**Files:**
- Create: `app/Http/Controllers/Admin/AdminDashboardController.php`
- Create: `app/Http/Controllers/Admin/AdminAccountController.php`
- Create: `app/Http/Controllers/Admin/AdminUserController.php`
- Create: `app/Http/Controllers/Admin/AdminPlanController.php`
- Create: `app/Http/Controllers/Admin/AdminShopifyAppController.php`
- Create: `app/Http/Controllers/Admin/AdminShopifyStoreController.php`
- Create: `app/Http/Controllers/Admin/AdminStoreReviewController.php`
- Create: `app/Http/Controllers/Admin/AdminPainPointsController.php`
- Modify: `routes/web.php`

Each admin controller follows a standard pattern: index returns paginated Inertia response, create/store/edit/update handle CRUD forms, destroy soft-deletes where applicable. The controllers replicate the data queries from the existing Filament resources and widgets.

- [ ] **Step 1: Create all admin controllers**

Create each controller file. They follow the same patterns as the customer controllers — return `Inertia::render()` with the relevant data as props. The key differences:

- `AdminDashboardController`: aggregates system-wide stats (total accounts, users, apps, reviews, AI batches)
- `AdminAccountController`: full CRUD with soft delete, loads users relation
- `AdminUserController`: full CRUD with hard delete, cannot delete self
- `AdminPlanController`: full CRUD with soft delete, validates no active accounts before delete, loads features relation
- `AdminShopifyAppController`: index (with `withUnlisted` toggle), show, unlist/relist actions
- `AdminShopifyStoreController`: index (with `withUnlisted` toggle), unlist/relist actions
- `AdminStoreReviewController`: index with filters (app, rating, sentiment, ai_status, date range)
- `AdminPainPointsController`: replicates the 3 widget queries from Filament (PainPointsStatsOverview, TopPainPointsTable, TopAppsByPainPointsTable)

- [ ] **Step 2: Add admin routes**

Append to `routes/web.php`:
```php
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminShopifyAppController;
use App\Http\Controllers\Admin\AdminShopifyStoreController;
use App\Http\Controllers\Admin\AdminStoreReviewController;
use App\Http\Controllers\Admin\AdminPainPointsController;

Route::middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('accounts', AdminAccountController::class);
        Route::resource('users', AdminUserController::class);
        Route::resource('plans', AdminPlanController::class);
        Route::get('/shopify-apps', [AdminShopifyAppController::class, 'index'])->name('shopify-apps.index');
        Route::get('/shopify-apps/{shopifyApp}', [AdminShopifyAppController::class, 'show'])->name('shopify-apps.show');
        Route::post('/shopify-apps/{shopifyApp}/unlist', [AdminShopifyAppController::class, 'unlist'])->name('shopify-apps.unlist');
        Route::post('/shopify-apps/{shopifyApp}/relist', [AdminShopifyAppController::class, 'relist'])->name('shopify-apps.relist');
        Route::get('/shopify-stores', [AdminShopifyStoreController::class, 'index'])->name('shopify-stores.index');
        Route::post('/shopify-stores/{shopifyStore}/unlist', [AdminShopifyStoreController::class, 'unlist'])->name('shopify-stores.unlist');
        Route::post('/shopify-stores/{shopifyStore}/relist', [AdminShopifyStoreController::class, 'relist'])->name('shopify-stores.relist');
        Route::get('/store-reviews', [AdminStoreReviewController::class, 'index'])->name('store-reviews.index');
        Route::get('/pain-points', AdminPainPointsController::class)->name('pain-points');
    });
```

- [ ] **Step 3: Verify routes**

Run: `php artisan route:list --path=admin`
Expected: All admin routes appear correctly

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/ routes/web.php
git commit -m "feat: create admin panel controllers with CRUD, unlist, and pain points analytics"
```

---

### Task 14: Create admin React pages

**Files:**
- Create: `resources/js/Pages/Admin/Dashboard.tsx`
- Create: `resources/js/Pages/Admin/Accounts/Index.tsx`
- Create: `resources/js/Pages/Admin/Accounts/Create.tsx`
- Create: `resources/js/Pages/Admin/Accounts/Edit.tsx`
- Create: `resources/js/Pages/Admin/Users/Index.tsx`
- Create: `resources/js/Pages/Admin/Users/Create.tsx`
- Create: `resources/js/Pages/Admin/Users/Edit.tsx`
- Create: `resources/js/Pages/Admin/Plans/Index.tsx`
- Create: `resources/js/Pages/Admin/Plans/Create.tsx`
- Create: `resources/js/Pages/Admin/Plans/Edit.tsx`
- Create: `resources/js/Pages/Admin/ShopifyApps/Index.tsx`
- Create: `resources/js/Pages/Admin/ShopifyApps/Show.tsx`
- Create: `resources/js/Pages/Admin/ShopifyStores/Index.tsx`
- Create: `resources/js/Pages/Admin/StoreReviews/Index.tsx`
- Create: `resources/js/Pages/Admin/PainPointsAnalytics.tsx`

All admin pages use `AdminLayout` and follow the same patterns: `DataTable` for list views, `useForm` for create/edit forms, `router.delete` for deletes with confirmation via `Modal`, `router.post` for unlist/relist.

- [ ] **Step 1: Create Dashboard.tsx**

Admin dashboard showing system stats cards (total accounts, users, apps, reviews, batches, last scrape).

- [ ] **Step 2: Create CRUD pages for Accounts, Users, Plans**

Each resource gets Index (DataTable), Create (form), Edit (form) pages. Plans/Edit includes inline feature management.

- [ ] **Step 3: Create read-only pages for ShopifyApps, ShopifyStores, StoreReviews**

List pages with DataTable, filters, and unlist/relist row actions for apps and stores.

- [ ] **Step 4: Create PainPointsAnalytics.tsx**

Replicates the 3 Filament widgets: stats overview cards, top pain points table, top apps by pain points table.

- [ ] **Step 5: Verify build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 6: Write admin smoke tests**

Create `tests/Feature/AdminPanelTest.php`:
```php
<?php

use App\Models\User;
use App\Models\Account;
use App\Models\AccountUser;
use App\Enums\UserRole;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $internal = Account::factory()->create(['slug' => 'heysentinel-internal', 'owner_user_id' => $this->admin->id]);
    AccountUser::create(['user_id' => $this->admin->id, 'account_id' => $internal->id, 'role' => UserRole::Owner->value, 'invitation_accepted_at' => now()]);
});

it('renders admin dashboard for superadmin', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
});

it('blocks non-superadmin from admin', function () {
    $regular = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $regular->id]);
    AccountUser::create(['user_id' => $regular->id, 'account_id' => $account->id, 'role' => UserRole::Owner->value, 'invitation_accepted_at' => now()]);

    $this->actingAs($regular)->get('/admin')->assertStatus(403);
});

it('lists accounts', function () {
    $this->actingAs($this->admin)
        ->get('/admin/accounts')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Admin/Accounts/Index'));
});
```

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Feature/AdminPanelTest.php`
Expected: All PASS

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Admin/ tests/Feature/AdminPanelTest.php
git commit -m "feat: create admin panel React pages with CRUD, unlist, and pain points analytics"
```

---

### Task 15: Remove Filament admin panel and clean up

**Files:**
- Delete: `app/Providers/Filament/AdminPanelProvider.php`
- Delete: `app/Filament/Resources/` (entire directory)
- Delete: `app/Filament/Widgets/` (entire directory)
- Delete: `app/Filament/Pages/` (entire directory)
- Modify: `app/Models/User.php` (remove FilamentUser interface)
- Delete: `resources/views/filament/modals/` (if exists)
- Delete: `resources/views/landing.blade.php`
- Delete: `resources/views/auth/register.blade.php`
- Delete: `resources/views/layouts/public.blade.php`

- [ ] **Step 1: Verify React admin works end-to-end**

Visit all admin pages as superadmin to verify they render correctly.

- [ ] **Step 2: Remove Filament admin panel files**

```bash
rm app/Providers/Filament/AdminPanelProvider.php
rm -rf app/Filament/Resources/
rm -rf app/Filament/Widgets/
rm -rf app/Filament/Pages/
rm -rf resources/views/filament/ 2>/dev/null
```

- [ ] **Step 3: Remove FilamentUser interface from User model**

In `app/Models/User.php`:
- Remove `use Filament\Models\Contracts\FilamentUser;`
- Remove `use Filament\Panel;`
- Remove `implements FilamentUser` from class declaration
- Remove the `canAccessPanel()` method

- [ ] **Step 4: Remove old Blade views**

```bash
rm resources/views/landing.blade.php
rm resources/views/auth/register.blade.php
rm resources/views/layouts/public.blade.php
rm resources/views/welcome.blade.php 2>/dev/null
```

- [ ] **Step 5: Remove old Filament test**

```bash
rm tests/Feature/Filament/AdminPanelSmokeTest.php
```

- [ ] **Step 6: Run full test suite**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "refactor: remove Filament entirely, complete React + Inertia migration"
```

---

### Task 16: Final build verification and cleanup

- [ ] **Step 1: Run production build**

Run: `npm run build`
Expected: Build succeeds with no errors or warnings

- [ ] **Step 2: Run full test suite**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 3: Verify all surfaces**

Start dev server and verify:
1. Landing page at `/` renders with real stats
2. Login at `/login` works
3. Register at `/register` works
4. Customer dashboard at `/customer` shows widgets
5. Browse Apps at `/customer/apps` shows table
6. Saved Searches at `/customer/saved-searches` works
7. Settings at `/customer/settings` works
8. Admin dashboard at `/admin` shows stats (superadmin only)
9. Admin CRUD pages work for accounts, users, plans
10. Unlist/relist works for apps and stores

- [ ] **Step 4: Commit any final fixes**

```bash
git add -A
git commit -m "chore: final build verification and cleanup after React migration"
```
