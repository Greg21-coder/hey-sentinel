import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Input from '@/Components/ui/Input';
import Button from '@/Components/ui/Button';

interface PlanOption {
    id: number;
    slug: string;
    name: string;
    monthly_price: number;
}

interface RegisterProps {
    preselectedPlan?: string | null;
    plans: PlanOption[];
}

interface RegisterForm {
    name: string;
    company_name: string;
    email: string;
    password: string;
    password_confirmation: string;
    plan_slug: string;
}

const Register: React.FC<RegisterProps> = ({ preselectedPlan, plans }) => {
    const defaultPlan = preselectedPlan ?? plans[0]?.slug ?? '';

    const { data, setData, post, processing, errors } = useForm<RegisterForm>({
        name: '',
        company_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        plan_slug: defaultPlan,
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post('/register');
    }

    return (
        <PublicLayout>
            <Head title="Create account — HeySentinel" />

            <div className="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
                <div className="w-full max-w-md">
                    <div className="text-center mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">Create your account</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            14-day free trial. No credit card required.
                        </p>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <Input
                                label="Full name"
                                type="text"
                                id="name"
                                autoComplete="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />

                            <Input
                                label="Company name"
                                type="text"
                                id="company_name"
                                autoComplete="organization"
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                                error={errors.company_name}
                                required
                            />

                            <Input
                                label="Email address"
                                type="email"
                                id="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                required
                            />

                            <Input
                                label="Password"
                                type="password"
                                id="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                required
                            />

                            <Input
                                label="Confirm password"
                                type="password"
                                id="password_confirmation"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData('password_confirmation', e.target.value)
                                }
                                error={errors.password_confirmation}
                                required
                            />

                            {plans.length > 0 && (
                                <div className="flex flex-col gap-1">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Select a plan
                                    </p>
                                    {errors.plan_slug && (
                                        <p className="text-sm text-red-600">{errors.plan_slug}</p>
                                    )}
                                    <div className="mt-1 space-y-2">
                                        {plans.map((plan) => (
                                            <label
                                                key={plan.id}
                                                className={[
                                                    'flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition-colors duration-150',
                                                    data.plan_slug === plan.slug
                                                        ? 'border-amber-400 bg-amber-50'
                                                        : 'border-gray-200 bg-white hover:bg-gray-50',
                                                ].join(' ')}
                                            >
                                                <input
                                                    type="radio"
                                                    name="plan_slug"
                                                    value={plan.slug}
                                                    checked={data.plan_slug === plan.slug}
                                                    onChange={() => setData('plan_slug', plan.slug)}
                                                    className="h-4 w-4 text-amber-500 border-gray-300 focus:ring-amber-500"
                                                />
                                                <span className="flex-1 text-sm font-medium text-gray-900">
                                                    {plan.name}
                                                </span>
                                                <span className="text-sm text-gray-500">
                                                    ${plan.monthly_price}/mo
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <Button
                                type="submit"
                                variant="primary"
                                size="md"
                                loading={processing}
                                className="w-full"
                            >
                                Create account
                            </Button>
                        </form>
                    </div>

                    <p className="mt-6 text-center text-sm text-gray-600">
                        Already have an account?{' '}
                        <a
                            href="/login"
                            className="font-medium text-amber-600 hover:text-amber-700 transition-colors duration-150"
                        >
                            Log in
                        </a>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
};

export default Register;
