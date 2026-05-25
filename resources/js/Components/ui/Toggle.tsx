import React, { useId } from 'react';

interface ToggleProps {
    label?: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
}

const Toggle: React.FC<ToggleProps> = ({
    label,
    checked,
    onChange,
    disabled = false,
}) => {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={[
                'inline-flex items-center gap-3',
                disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ].join(' ')}
        >
            <button
                id={id}
                type="button"
                role="switch"
                aria-checked={checked}
                disabled={disabled}
                onClick={() => onChange(!checked)}
                className={[
                    'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent',
                    'focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2',
                    'transition-colors duration-150',
                    checked ? 'bg-amber-500' : 'bg-gray-200',
                    disabled ? 'cursor-not-allowed' : 'cursor-pointer',
                ].join(' ')}
            >
                <span
                    aria-hidden="true"
                    className={[
                        'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0',
                        'transition-transform duration-150',
                        checked ? 'translate-x-5' : 'translate-x-0',
                    ].join(' ')}
                />
            </button>
            {label && (
                <span className="text-sm font-medium text-gray-700">{label}</span>
            )}
        </label>
    );
};

export default Toggle;
