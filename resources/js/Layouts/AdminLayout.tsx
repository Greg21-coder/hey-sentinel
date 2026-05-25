import React from 'react';
import { usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import FlashMessages from '@/Components/ui/FlashMessages';

interface NavItem {
    href: string;
    label: string;
    icon: string; // SVG path d attribute
}

interface NavSection {
    label: string;
    items: NavItem[];
}

const navSections: NavSection[] = [
    {
        label: 'Overview',
        items: [
            {
                href: '/admin',
                label: 'Dashboard',
                icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            },
        ],
    },
    {
        label: 'Management',
        items: [
            {
                href: '/admin/accounts',
                label: 'Accounts',
                icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            },
            {
                href: '/admin/users',
                label: 'Users',
                icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            },
            {
                href: '/admin/plans',
                label: 'Plans',
                icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            },
        ],
    },
    {
        label: 'Shopify Data',
        items: [
            {
                href: '/admin/shopify-apps',
                label: 'Apps',
                icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            },
            {
                href: '/admin/shopify-stores',
                label: 'Stores',
                icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            },
            {
                href: '/admin/store-reviews',
                label: 'Reviews',
                icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
            },
        ],
    },
    {
        label: 'Analytics',
        items: [
            {
                href: '/admin/pain-points',
                label: 'Pain Points',
                icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            },
        ],
    },
];

const Logo: React.FC = () => (
    <a href="/admin" className="flex items-center gap-2">
        <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">
            H
        </span>
        <span className="text-white font-semibold text-base">HeySentinel</span>
    </a>
);

interface AdminLayoutProps {
    children: React.ReactNode;
}

const AdminLayout: React.FC<AdminLayoutProps> = ({ children }) => {
    const { url, props } = usePage<PageProps>();
    const { auth } = props;

    const handleLogout = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/logout');
    };

    const isActive = (href: string) =>
        url === href || url.startsWith(href + '/');

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <aside className="flex flex-col w-[264px] flex-shrink-0 bg-slate-900">
                {/* Logo */}
                <div className="flex items-center h-16 px-5 border-b border-slate-800">
                    <Logo />
                </div>

                {/* Nav */}
                <nav className="flex-1 overflow-y-auto py-4 px-3">
                    {navSections.map((section) => (
                        <div key={section.label} className="mb-5">
                            <p className="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                {section.label}
                            </p>
                            <ul className="space-y-0.5">
                                {section.items.map((item) => {
                                    const active = isActive(item.href);
                                    return (
                                        <li key={item.href}>
                                            <a
                                                href={item.href}
                                                className={[
                                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150',
                                                    active
                                                        ? 'bg-slate-700 text-white'
                                                        : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                                                ].join(' ')}
                                            >
                                                <svg
                                                    className="h-5 w-5 flex-shrink-0"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    strokeWidth={1.5}
                                                    viewBox="0 0 24 24"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d={item.icon}
                                                    />
                                                </svg>
                                                {item.label}
                                            </a>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>

                {/* Account info + logout */}
                <div className="border-t border-slate-800 p-4">
                    {auth.user && (
                        <div className="mb-3">
                            <p className="text-xs font-medium text-slate-200 truncate">
                                {auth.user.name}
                            </p>
                            <p className="text-xs text-slate-400 truncate">
                                {auth.user.email}
                            </p>
                        </div>
                    )}
                    <form onSubmit={handleLogout}>
                        <button
                            type="submit"
                            className="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors duration-150"
                        >
                            <svg
                                className="h-4 w-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth={1.5}
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                />
                            </svg>
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col min-w-0">
                <main className="flex-1 p-6 overflow-y-auto">
                    <FlashMessages />
                    {children}
                </main>
            </div>
        </div>
    );
};

export default AdminLayout;
