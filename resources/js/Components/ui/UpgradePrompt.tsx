import React from 'react';
import Button from './Button';

interface UpgradePromptProps {
    feature: string;
    planName?: string;
}

const UpgradePrompt: React.FC<UpgradePromptProps> = ({
    feature,
    planName = 'Pro',
}) => {
    return (
        <div className="rounded-lg bg-amber-50 border border-amber-200 p-4 flex items-start gap-4">
            <div className="flex-shrink-0">
                <svg
                    className="h-5 w-5 text-amber-500 mt-0.5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fillRule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clipRule="evenodd"
                    />
                </svg>
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-amber-800">
                    {feature} is available on the {planName} plan
                </p>
                <p className="mt-1 text-sm text-amber-700">
                    Upgrade your plan to unlock this feature and more.
                </p>
            </div>
            <div className="flex-shrink-0">
                <Button
                    href="/customer/settings"
                    variant="primary"
                    size="sm"
                >
                    Upgrade
                </Button>
            </div>
        </div>
    );
};

export default UpgradePrompt;
