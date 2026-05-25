import React from 'react';

type BadgeColor = 'gray' | 'info' | 'success' | 'warning' | 'danger' | 'primary';

interface BadgeProps {
    color?: BadgeColor;
    children: React.ReactNode;
}

const colorClasses: Record<BadgeColor, string> = {
    gray: 'bg-gray-100 text-gray-700',
    info: 'bg-blue-100 text-blue-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-700',
    primary: 'bg-indigo-100 text-indigo-700',
};

const Badge: React.FC<BadgeProps> = ({ color = 'gray', children }) => {
    return (
        <span
            className={[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                colorClasses[color],
            ].join(' ')}
        >
            {children}
        </span>
    );
};

export default Badge;
