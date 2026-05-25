import React, { forwardRef } from 'react';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
}

const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ label, error, id, className = '', ...rest }, ref) => {
        const inputId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

        return (
            <div className="flex flex-col gap-1">
                {label && (
                    <label
                        htmlFor={inputId}
                        className="block text-sm font-medium text-gray-700"
                    >
                        {label}
                    </label>
                )}
                <input
                    ref={ref}
                    id={inputId}
                    className={[
                        'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm',
                        'focus:outline-none focus:ring-2 focus:ring-shopify-500 focus:border-shopify-500',
                        error
                            ? 'border-red-400 bg-red-50 text-red-900 placeholder-red-400'
                            : 'border-gray-300 bg-white text-gray-900 placeholder-gray-400',
                        'disabled:bg-gray-100 disabled:cursor-not-allowed',
                        className,
                    ]
                        .filter(Boolean)
                        .join(' ')}
                    {...rest}
                />
                {error && (
                    <p className="text-sm text-red-600">{error}</p>
                )}
            </div>
        );
    },
);

Input.displayName = 'Input';

export default Input;
