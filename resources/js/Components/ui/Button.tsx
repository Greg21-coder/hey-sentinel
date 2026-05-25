import React from 'react';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    loading?: boolean;
    href?: string;
}

const variantClasses: Record<Variant, string> = {
    primary:
        'bg-shopify-500 text-white hover:bg-shopify-600 border border-shopify-500 hover:border-shopify-600',
    secondary:
        'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 hover:border-gray-400',
    danger:
        'bg-red-600 text-white hover:bg-red-700 border border-red-600 hover:border-red-700',
    ghost:
        'bg-transparent text-gray-700 hover:bg-gray-100 border border-transparent',
};

const sizeClasses: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};

const Spinner: React.FC = () => (
    <svg
        className="animate-spin -ml-1 mr-2 h-4 w-4"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
        />
        <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
        />
    </svg>
);

const Button: React.FC<ButtonProps> = ({
    variant = 'primary',
    size = 'md',
    loading = false,
    href,
    children,
    className = '',
    disabled,
    ...rest
}) => {
    const baseClasses =
        'inline-flex items-center justify-center font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopify-500 disabled:opacity-50 disabled:cursor-not-allowed';

    const classes = [
        baseClasses,
        variantClasses[variant],
        sizeClasses[size],
        className,
    ]
        .filter(Boolean)
        .join(' ');

    const content = (
        <>
            {loading && <Spinner />}
            {children}
        </>
    );

    if (href) {
        return (
            <a href={href} className={classes}>
                {content}
            </a>
        );
    }

    return (
        <button
            className={classes}
            disabled={disabled || loading}
            {...rest}
        >
            {content}
        </button>
    );
};

export default Button;
