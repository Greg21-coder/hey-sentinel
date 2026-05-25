import React from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

interface PublicLayoutProps {
    children: React.ReactNode;
}

const Logo: React.FC = () => (
    <a href="/" className="flex items-center gap-2">
        <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 text-white font-bold text-sm">
            H
        </span>
        <span className="text-gray-900 font-semibold text-base">HeySentinel</span>
    </a>
);

const PublicLayout: React.FC<PublicLayoutProps> = ({ children }) => {
    const { auth } = usePage<PageProps>().props;
    const isLoggedIn = !!auth.user;

    return (
        <div className="min-h-screen bg-white">
            {/* Fixed top nav */}
            <header className="fixed top-0 inset-x-0 z-40 border-b border-gray-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <Logo />

                        {/* Anchor links */}
                        <nav className="hidden md:flex items-center gap-8">
                            <a
                                href="#features"
                                className="text-sm text-gray-600 hover:text-gray-900 transition-colors duration-150"
                            >
                                Features
                            </a>
                            <a
                                href="#how-it-works"
                                className="text-sm text-gray-600 hover:text-gray-900 transition-colors duration-150"
                            >
                                How it works
                            </a>
                            <a
                                href="#pricing"
                                className="text-sm text-gray-600 hover:text-gray-900 transition-colors duration-150"
                            >
                                Pricing
                            </a>
                        </nav>

                        {/* Auth buttons */}
                        <div className="flex items-center gap-3">
                            {isLoggedIn ? (
                                <a
                                    href="/customer/dashboard"
                                    className="inline-flex items-center justify-center rounded-lg bg-amber-500 hover:bg-amber-600 px-4 py-2 text-sm font-medium text-white border border-amber-500 hover:border-amber-600 transition-colors duration-150"
                                >
                                    Dashboard
                                </a>
                            ) : (
                                <>
                                    <a
                                        href="/login"
                                        className="inline-flex items-center justify-center rounded-lg bg-white hover:bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 hover:border-gray-400 transition-colors duration-150"
                                    >
                                        Login
                                    </a>
                                    <a
                                        href="/register"
                                        className="inline-flex items-center justify-center rounded-lg bg-amber-500 hover:bg-amber-600 px-4 py-2 text-sm font-medium text-white border border-amber-500 hover:border-amber-600 transition-colors duration-150"
                                    >
                                        Start free trial
                                    </a>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Content offset for fixed nav */}
            <main className="pt-16">{children}</main>
        </div>
    );
};

export default PublicLayout;
