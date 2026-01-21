import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';

export default function PersonalInformation({ completion, status }) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, post, errors, processing, recentlySuccessful } =
        useForm({
            phone: user.phone ?? '',
            country: user.country ?? '',
            address_line1: user.address_line1 ?? '',
            address_line2: user.address_line2 ?? '',
            city: user.city ?? '',
            state: user.state ?? '',
            postal_code: user.postal_code ?? '',
            date_of_birth: user.date_of_birth ?? '',
            document: null,
            two_factor_enabled: user.two_factor_enabled ?? false,
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route('account.personal-information.update'));
    };

    const percent = completion?.percent ?? 0;

    return (
        <AuthenticatedLayout
            user={user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Personal Information</h2>}
        >
            <Head title="Personal Information" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="mb-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-bold text-brand-gold">Complete your identity</h3>
                                        <p className="text-sm text-gray-400">
                                            Provide accurate personal information to unlock safer, higher-value deals.
                                        </p>
                                    </div>
                                    <div className="w-48">
                                        <div className="text-xs text-gray-400 mb-1">Profile completeness</div>
                                        <div className="h-2 bg-gray-800 rounded-full overflow-hidden">
                                            <div
                                                className="h-2 bg-brand-gold"
                                                style={{ width: `${percent}%` }}
                                            />
                                        </div>
                                        <div className="mt-1 text-right text-xs text-gray-500">{percent}%</div>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="phone" value="Phone" />
                                        <TextInput
                                            id="phone"
                                            className="mt-1 block w-full"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            autoComplete="tel"
                                        />
                                        <InputError className="mt-2" message={errors.phone} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="date_of_birth" value="Date of Birth" />
                                        <TextInput
                                            id="date_of_birth"
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={data.date_of_birth}
                                            onChange={(e) => setData('date_of_birth', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.date_of_birth} />
                                    </div>
                                </div>

                                <div className="rounded-md border border-gray-800 bg-gray-900 p-4">
                                    <label className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={data.two_factor_enabled}
                                            onChange={(e) => setData('two_factor_enabled', e.target.checked)}
                                            className="h-4 w-4 rounded border-gray-700 bg-gray-800 text-brand-gold focus:ring-brand-gold"
                                        />
                                        <span className="text-sm text-gray-300">
                                            Enable two-factor authentication (email code on login)
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <InputLabel htmlFor="country" value="Country" />
                                    <TextInput
                                        id="country"
                                        className="mt-1 block w-full"
                                        value={data.country}
                                        onChange={(e) => setData('country', e.target.value)}
                                        autoComplete="country-name"
                                    />
                                    <InputError className="mt-2" message={errors.country} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="address_line1" value="Address line 1" />
                                    <TextInput
                                        id="address_line1"
                                        className="mt-1 block w-full"
                                        value={data.address_line1}
                                        onChange={(e) => setData('address_line1', e.target.value)}
                                        autoComplete="address-line1"
                                    />
                                    <InputError className="mt-2" message={errors.address_line1} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="address_line2" value="Address line 2" />
                                    <TextInput
                                        id="address_line2"
                                        className="mt-1 block w-full"
                                        value={data.address_line2}
                                        onChange={(e) => setData('address_line2', e.target.value)}
                                        autoComplete="address-line2"
                                    />
                                    <InputError className="mt-2" message={errors.address_line2} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <InputLabel htmlFor="city" value="City" />
                                        <TextInput
                                            id="city"
                                            className="mt-1 block w-full"
                                            value={data.city}
                                            onChange={(e) => setData('city', e.target.value)}
                                            autoComplete="address-level2"
                                        />
                                        <InputError className="mt-2" message={errors.city} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="state" value="State / Region" />
                                        <TextInput
                                            id="state"
                                            className="mt-1 block w-full"
                                            value={data.state}
                                            onChange={(e) => setData('state', e.target.value)}
                                            autoComplete="address-level1"
                                        />
                                        <InputError className="mt-2" message={errors.state} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="postal_code" value="Postal code" />
                                        <TextInput
                                            id="postal_code"
                                            className="mt-1 block w-full"
                                            value={data.postal_code}
                                            onChange={(e) => setData('postal_code', e.target.value)}
                                            autoComplete="postal-code"
                                        />
                                        <InputError className="mt-2" message={errors.postal_code} />
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>Save</PrimaryButton>
                                    <Transition
                                        show={recentlySuccessful || status === 'saved'}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-gray-600">Saved.</p>
                                    </Transition>
                                </div>
                            </form>

                            <div className="mt-8 border-t border-gray-800 pt-6">
                                <h4 className="text-brand-gold font-bold mb-2">Verification</h4>
                                <p className="text-sm text-gray-400">
                                    To reach higher verification levels, submit your ID document for review and keep your information up to date.
                                </p>
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        post(route('account.personal-information.submit-id'));
                                    }}
                                    className="mt-3 flex items-center gap-3"
                                >
                                    <input
                                        id="document"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        onChange={(e) => setData('document', e.target.files[0])}
                                        className="block w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-800 file:text-brand-gold hover:file:bg-gray-700"
                                    />
                                    <PrimaryButton disabled={processing || !data.document}>
                                        Submit
                                    </PrimaryButton>
                                    <InputError className="mt-2" message={errors.document} />
                                </form>
                                {(recentlySuccessful || status === 'verification-submitted') && (
                                    <div className="mt-2 text-sm font-medium text-green-600">Verification submitted.</div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
