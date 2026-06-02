import React, { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import FlashMessages from '@/Components/ui/FlashMessages';

interface NavItem {
    href: string;
    label: string;
    icon: string; // SVG path d attribute
}

const navItems: NavItem[] = [
    {
        href: '/customer',
        label: 'Dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        href: '/customer/my-apps',
        label: 'My Apps',
        icon: 'M5 13l4 4L19 7',
    },
    {
        href: '/customer/apps',
        label: 'Browse Apps',
        icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    },
    {
        href: '/customer/saved-searches',
        label: 'Saved Searches',
        icon: 'M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z',
    },
    {
        href: '/customer/settings',
        label: 'Settings',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

const Logo: React.FC = () => (
    <a href="/customer" className="flex items-center gap-2">
        <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-shopify-500 text-white font-bold text-sm">
            H
        </span>
        <span className="text-gray-900 font-semibold text-base">HeySentinel</span>
    </a>
);

interface CustomerLayoutProps {
    children: React.ReactNode;
}

const CustomerLayout: React.FC<CustomerLayoutProps> = ({ children }) => {
    const { url, props } = usePage<PageProps>();
    const { auth } = props;

    const handleLogout = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/logout');
    };

    const isActive = (href: string) =>
        href === '/customer' ? url === '/customer' : url === href || url.startsWith(href + '/');

    const [unreadCount, setUnreadCount] = useState(0);
    const [bellOpen, setBellOpen] = useState(false);
    const [notifications, setNotifications] = useState<any[]>([]);

    useEffect(() => {
        fetch('/customer/notifications/count')
            .then(r => r.json())
            .then(d => setUnreadCount(d.count))
            .catch(() => {});
    }, [url]);

    const openBell = () => {
        if (!bellOpen) {
            fetch('/customer/notifications/recent')
                .then(r => r.json())
                .then(d => { setNotifications(d.notifications); setBellOpen(true); })
                .catch(() => {});
        } else {
            setBellOpen(false);
        }
    };

    const markAllRead = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        fetch('/customer/notifications/mark-read', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' } })
            .then(() => { setUnreadCount(0); setNotifications([]); setBellOpen(false); })
            .catch(() => {});
    };

    return (
        <div className="flex min-h-screen bg-surface-100">
            {/* Sidebar */}
            <aside className="flex flex-col w-[264px] flex-shrink-0 bg-white border-r border-gray-200">
                {/* Logo */}
                <div className="flex items-center h-16 px-5 border-b border-gray-100">
                    <Logo />
                </div>

                {/* Nav */}
                <nav className="flex-1 overflow-y-auto py-4 px-3">
                    <ul className="space-y-1">
                        {navItems.map((item) => {
                            const active = isActive(item.href);
                            return (
                                <li key={item.href}>
                                    <a
                                        href={item.href}
                                        className={[
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150',
                                            active
                                                ? 'bg-shopify-50 text-shopify-700'
                                                : 'text-surface-700 hover:bg-surface-200',
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
                </nav>

                {/* Account info + logout */}
                <div className="border-t border-gray-100 p-4">
                    {auth.user && (
                        <div className="mb-3 flex items-start justify-between">
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-gray-900 truncate">
                                    {auth.user.name}
                                </p>
                                <p className="text-xs text-gray-500 truncate">
                                    {auth.user.email}
                                </p>
                                {auth.account && (
                                    <p className="text-xs text-gray-400 truncate mt-0.5">
                                        {auth.account.name}
                                    </p>
                                )}
                            </div>
                            <div className="relative flex-shrink-0 ml-2">
                                <button onClick={openBell} className="relative p-2 text-gray-500 hover:text-gray-700">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    {unreadCount > 0 && (
                                        <span className="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold">
                                            {unreadCount > 9 ? '9+' : unreadCount}
                                        </span>
                                    )}
                                </button>
                                {bellOpen && (
                                    <div className="absolute bottom-full left-0 mb-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                                        <div className="p-3 border-b border-gray-100 flex items-center justify-between">
                                            <span className="text-sm font-semibold text-gray-900">Notifications</span>
                                            {notifications.length > 0 && (
                                                <button onClick={markAllRead} className="text-xs text-shopify-500 hover:underline">Mark all read</button>
                                            )}
                                        </div>
                                        <div className="max-h-64 overflow-y-auto">
                                            {notifications.length === 0 ? (
                                                <p className="p-4 text-sm text-gray-400 text-center">No new notifications</p>
                                            ) : (
                                                notifications.map((n: any) => (
                                                    <a key={n.id} href={`/customer/apps/${n.app_id}`} className="block px-3 py-2 hover:bg-gray-50 border-b border-gray-50">
                                                        <p className="text-sm font-medium text-gray-900">{n.app_name}</p>
                                                        <p className="text-xs text-gray-500">{n.field}: {n.old_value ?? '—'} &rarr; {n.new_value ?? '—'}</p>
                                                    </a>
                                                ))
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                    <form onSubmit={handleLogout}>
                        <button
                            type="submit"
                            className="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors duration-150"
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

export default CustomerLayout;
