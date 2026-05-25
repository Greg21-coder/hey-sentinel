import React, { forwardRef } from 'react';

interface SelectOption {
    value: string | number;
    label: string;
}

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    options: SelectOption[];
    placeholder?: string;
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
    ({ label, error, options, placeholder, id, className = '', ...rest }, ref) => {
        const selectId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

        return (
            <div className="flex flex-col gap-1">
                {label && (
                    <label
                        htmlFor={selectId}
                        className="block text-sm font-medium text-gray-700"
                    >
                        {label}
                    </label>
                )}
                <select
                    ref={ref}
                    id={selectId}
                    className={[
                        'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm',
                        'focus:outline-none focus:ring-2 focus:ring-shopify-500 focus:border-shopify-500',
                        error
                            ? 'border-red-400 bg-red-50 text-red-900'
                            : 'border-gray-300 bg-white text-gray-900',
                        'disabled:bg-gray-100 disabled:cursor-not-allowed',
                        className,
                    ]
                        .filter(Boolean)
                        .join(' ')}
                    {...rest}
                >
                    {placeholder && (
                        <option value="">{placeholder}</option>
                    )}
                    {options.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                {error && (
                    <p className="text-sm text-red-600">{error}</p>
                )}
            </div>
        );
    },
);

Select.displayName = 'Select';

export default Select;
