import React from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

const FlashMessages: React.FC = () => {
    const { flash } = usePage<PageProps>().props;

    if (!flash.success && !flash.error) return null;

    return (
        <div className="space-y-2 mb-4">
            {flash.success && (
                <div
                    role="alert"
                    className="flex items-start gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3"
                >
                    <svg
                        className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fillRule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                            clipRule="evenodd"
                        />
                    </svg>
                    <p className="text-sm font-medium text-green-800">
                        {flash.success}
                    </p>
                </div>
            )}
            {flash.error && (
                <div
                    role="alert"
                    className="flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3"
                >
                    <svg
                        className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fillRule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                            clipRule="evenodd"
                        />
                    </svg>
                    <p className="text-sm font-medium text-red-800">
                        {flash.error}
                    </p>
                </div>
            )}
        </div>
    );
};

export default FlashMessages;
