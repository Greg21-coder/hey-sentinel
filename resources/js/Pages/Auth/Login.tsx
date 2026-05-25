import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Input from '@/Components/ui/Input';
import Button from '@/Components/ui/Button';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

const Login: React.FC = () => {
    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post('/login');
    }

    return (
        <PublicLayout>
            <Head title="Log in — HeySentinel" />

            <div className="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
                <div className="w-full max-w-sm">
                    <div className="text-center mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">Welcome back</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Log in to your HeySentinel account
                        </p>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                        <form onSubmit={handleSubmit} className="space-y-5">
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
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                required
                            />

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-500"
                                />
                                <label htmlFor="remember" className="text-sm text-gray-700">
                                    Remember me
                                </label>
                            </div>

                            <Button
                                type="submit"
                                variant="primary"
                                size="md"
                                loading={processing}
                                className="w-full"
                            >
                                Log in
                            </Button>
                        </form>
                    </div>

                    <p className="mt-6 text-center text-sm text-gray-600">
                        Don&apos;t have an account?{' '}
                        <a
                            href="/register"
                            className="font-medium text-amber-600 hover:text-amber-700 transition-colors duration-150"
                        >
                            Start your free trial
                        </a>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
};

export default Login;
