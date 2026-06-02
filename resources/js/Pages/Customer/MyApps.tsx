import React from 'react';
import { Head } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import { PageProps } from '@/types';

interface MyApp {
    id: number;
    shopify_app_handle: string;
    name: string;
    avatar_url: string | null;
    average_rating: string | null;
    total_reviews: number;
    followed_at: string;
}

interface Props extends PageProps {
    myApps: MyApp[];
    onboarding: { needsTour: boolean };
}

const MyApps: React.FC<Props> = ({ myApps }) => {
    return (
        <CustomerLayout>
            <Head title="My Apps" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">My Apps</h1>
                {/* Search + list UI added in Task 10 */}
                <p className="text-sm text-gray-500">{myApps.length} apps</p>
            </div>
        </CustomerLayout>
    );
};

export default MyApps;
