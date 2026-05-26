import React from 'react';

interface CardProps {
    title?: string;
    description?: string;
    children?: React.ReactNode;
    className?: string;
}

const Card: React.FC<CardProps> = ({
    title,
    description,
    children,
    className = '',
}) => {
    return (
        <div
            className={[
                'bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden',
                className,
            ]
                .filter(Boolean)
                .join(' ')}
        >
            {(title || description) && (
                <div className="px-6 py-5 border-b border-gray-100">
                    {title && (
                        <h3 className="text-base font-semibold text-gray-900">
                            {title}
                        </h3>
                    )}
                    {description && (
                        <p className="mt-1 text-sm text-gray-500">{description}</p>
                    )}
                </div>
            )}
            {children && <div className="px-6 py-5">{children}</div>}
        </div>
    );
};

export default Card;
