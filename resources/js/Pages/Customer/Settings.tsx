import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';
import { PageProps } from '@/types';

interface Props extends PageProps {
    // auth.user and auth.account come from PageProps
}

const Settings: React.FC<Props> = () => {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const account = auth.account;

    const { data, setData, put, processing, errors, reset } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/customer/settings', {
            onSuccess: () => reset('password', 'password_confirmation'),
        });
    };

    const planStatusColor = (status: string): 'success' | 'warning' | 'danger' | 'gray' => {
        switch (status) {
            case 'active':
                return 'success';
            case 'trialing':
                return 'warning';
            case 'past_due':
            case 'canceled':
                return 'danger';
            default:
                return 'gray';
        }
    };

    return (
        <CustomerLayout>
            <Head title="Settings" />

            <div className="space-y-6 max-w-2xl">
                <h1 className="text-xl font-semibold text-gray-900">Settings</h1>

                {/* Profile form */}
                <Card title="Profile" description="Update your name, email, and password.">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Input
                            label="Name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            autoComplete="name"
                        />
                        <Input
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                            autoComplete="email"
                        />
                        <Input
                            label="New Password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            autoComplete="new-password"
                            placeholder="Leave blank to keep current password"
                        />
                        <Input
                            label="Confirm New Password"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            error={errors.password_confirmation}
                            autoComplete="new-password"
                        />

                        <div className="flex justify-end">
                            <Button type="submit" loading={processing}>
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Account / plan info */}
                {account && (
                    <Card title="Account" description="Your current plan and billing status.">
                        <dl className="space-y-3 text-sm">
                            <div className="flex items-center justify-between">
                                <dt className="text-gray-500">Account</dt>
                                <dd className="font-medium text-gray-900">
                                    {account.name}
                                </dd>
                            </div>

                            {account.plan && (
                                <div className="flex items-center justify-between">
                                    <dt className="text-gray-500">Plan</dt>
                                    <dd className="font-medium text-gray-900">
                                        {account.plan.name}
                                    </dd>
                                </div>
                            )}

                            <div className="flex items-center justify-between">
                                <dt className="text-gray-500">Status</dt>
                                <dd>
                                    <Badge color={planStatusColor(account.status)}>
                                        {account.status}
                                    </Badge>
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-4 pt-4 border-t border-gray-100">
                            <Button href="/customer/settings/billing" variant="secondary" size="sm">
                                Manage Billing
                            </Button>
                        </div>
                    </Card>
                )}
            </div>
        </CustomerLayout>
    );
};

export default Settings;
