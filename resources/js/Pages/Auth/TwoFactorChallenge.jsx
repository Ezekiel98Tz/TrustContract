import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge({ auth, status }) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('twofactor.verify'));
    };

    const resend = (e) => {
        e.preventDefault();
        post(route('twofactor.send'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Two-Factor Verification</h2>}
        >
            <Head title="Two-Factor Verification" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <p className="text-sm text-gray-400 mb-4">
                                We sent a 6-digit code to your email. Enter it below to complete your login.
                            </p>
                            {status === 'code-sent' && (
                                <div className="mb-4 text-sm font-medium text-green-600">
                                    Code sent. Check your email.
                                </div>
                            )}
                            {status === 'invalid-code' && (
                                <div className="mb-4 text-sm font-medium text-red-600">
                                    Invalid or expired code. Request a new code and try again.
                                </div>
                            )}
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <InputLabel htmlFor="code" value="6-digit code" />
                                    <TextInput
                                        id="code"
                                        className="mt-1 block w-full"
                                        value={data.code}
                                        maxLength={6}
                                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, '').slice(0, 6))}
                                        required
                                        autoComplete="one-time-code"
                                    />
                                    <InputError className="mt-2" message={errors.code} />
                                </div>
                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing || data.code.length !== 6}>Verify</PrimaryButton>
                                    <button
                                        onClick={resend}
                                        type="button"
                                        className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-brand-gold uppercase tracking-widest hover:bg-gray-700"
                                    >
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
