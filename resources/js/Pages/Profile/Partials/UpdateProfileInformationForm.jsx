import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name ?? '',
            email: user.email ?? '',
            phone: user.phone ?? '',
            country: user.country ?? '',
            address_line1: user.address_line1 ?? '',
            address_line2: user.address_line2 ?? '',
            city: user.city ?? '',
            state: user.state ?? '',
            postal_code: user.postal_code ?? '',
            date_of_birth: user.date_of_birth ?? '',
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profile Information
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                        <InputError className="mt-2" message={errors.email} />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="phone" value="Phone (for verification)" />

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
                            onChange={(e) =>
                                setData('date_of_birth', e.target.value)
                            }
                        />

                        <InputError
                            className="mt-2"
                            message={errors.date_of_birth}
                        />
                    </div>
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
                        onChange={(e) =>
                            setData('address_line1', e.target.value)
                        }
                        autoComplete="address-line1"
                    />

                    <InputError
                        className="mt-2"
                        message={errors.address_line1}
                    />
                </div>

                <div>
                    <InputLabel htmlFor="address_line2" value="Address line 2" />

                    <TextInput
                        id="address_line2"
                        className="mt-1 block w-full"
                        value={data.address_line2}
                        onChange={(e) =>
                            setData('address_line2', e.target.value)
                        }
                        autoComplete="address-line2"
                    />

                    <InputError
                        className="mt-2"
                        message={errors.address_line2}
                    />
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
                            onChange={(e) =>
                                setData('postal_code', e.target.value)
                            }
                            autoComplete="postal-code"
                        />

                        <InputError
                            className="mt-2"
                            message={errors.postal_code}
                        />
                    </div>
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
