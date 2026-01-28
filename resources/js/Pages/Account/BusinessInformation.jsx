import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';

export default function BusinessInformation({ auth, business, verifications, status, countries }) {
    const user = usePage().props.auth.user;
    const { data, setData, patch, post, errors, processing, recentlySuccessful } =
        useForm({
            company_name: business?.company_name ?? '',
            registration_number: business?.registration_number ?? '',
            jurisdiction: business?.jurisdiction ?? '',
            tax_id: business?.tax_id ?? '',
            lei: business?.lei ?? '',
            address_line1: business?.address_line1 ?? '',
            address_line2: business?.address_line2 ?? '',
            city: business?.city ?? '',
            state: business?.state ?? '',
            postal_code: business?.postal_code ?? '',
            document_type: 'business_registration',
            document: null,
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route('account.business-information.update'));
    };

    return (
        <AuthenticatedLayout
            user={user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Business Verification</h2>}
        >
            <Head title="Business Verification" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="mb-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-bold text-brand-gold">Company information</h3>
                                        <p className="text-sm text-gray-400">
                                            Provide your company details to begin verification.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <InputLabel htmlFor="company_name" value="Company Name" />
                                    <TextInput id="company_name" className="mt-1 block w-full" value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} />
                                    <InputError className="mt-2" message={errors.company_name} />
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="registration_number" value="Registration Number" />
                                        <TextInput id="registration_number" className="mt-1 block w-full" value={data.registration_number} onChange={(e) => setData('registration_number', e.target.value)} />
                                        <InputError className="mt-2" message={errors.registration_number} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="jurisdiction" value="Jurisdiction" />
                                        <select
                                            id="jurisdiction"
                                            className="mt-1 block w-full rounded-md bg-gray-900 border-gray-800 text-gray-200"
                                            value={data.jurisdiction}
                                            onChange={(e) => setData('jurisdiction', e.target.value)}
                                        >
                                            <option value="">Select country</option>
                                            {countries && Object.entries(countries).map(([code, name]) => (
                                                <option key={code} value={code}>{name}</option>
                                            ))}
                                        </select>
                                        <InputError className="mt-2" message={errors.jurisdiction} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="tax_id" value="Tax ID" />
                                        <TextInput id="tax_id" className="mt-1 block w-full" value={data.tax_id} onChange={(e) => setData('tax_id', e.target.value)} />
                                        <InputError className="mt-2" message={errors.tax_id} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="lei" value="LEI (optional)" />
                                        <TextInput id="lei" className="mt-1 block w-full" value={data.lei} onChange={(e) => setData('lei', e.target.value)} />
                                        <InputError className="mt-2" message={errors.lei} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <InputLabel htmlFor="address_line1" value="Address line 1" />
                                        <TextInput id="address_line1" className="mt-1 block w-full" value={data.address_line1} onChange={(e) => setData('address_line1', e.target.value)} />
                                        <InputError className="mt-2" message={errors.address_line1} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="city" value="City" />
                                        <TextInput id="city" className="mt-1 block w-full" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                                        <InputError className="mt-2" message={errors.city} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="postal_code" value="Postal code" />
                                        <TextInput id="postal_code" className="mt-1 block w-full" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} />
                                        <InputError className="mt-2" message={errors.postal_code} />
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>Save</PrimaryButton>
                                    <Transition show={recentlySuccessful || status === 'saved'} enter="transition ease-in-out" enterFrom="opacity-0" leave="transition ease-in-out" leaveTo="opacity-0">
                                        <p className="text-sm text-gray-600">Saved.</p>
                                    </Transition>
                                </div>
                            </form>

                            <div className="mt-8 border-t border-gray-800 pt-6">
                                <h4 className="text-brand-gold font-bold mb-2">Submit verification documents</h4>
                                <p className="text-sm text-gray-400">
                                    Upload your business registration, license or tax certificate. Clear, recent documents are required.
                                </p>
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        post(route('account.business-information.submit-document'));
                                    }}
                                    className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end"
                                >
                                    <div>
                                        <InputLabel htmlFor="document_type" value="Document Type" />
                                        <select
                                            id="document_type"
                                            className="mt-1 block w-full rounded-md bg-gray-900 border-gray-800 text-gray-200"
                                            value={data.document_type}
                                            onChange={(e) => setData('document_type', e.target.value)}
                                        >
                                            <option value="business_registration">Business Registration</option>
                                            <option value="business_license">Business License</option>
                                            <option value="tax_certificate">Tax Certificate</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="document" value="Document" />
                                        <input
                                            id="document"
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            onChange={(e) => setData('document', e.target.files[0])}
                                            className="mt-1 block w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-800 file:text-brand-gold hover:file:bg-gray-700"
                                        />
                                        <InputError className="mt-2" message={errors.document} />
                                    </div>
                                    <PrimaryButton disabled={processing || !data.document}>Submit</PrimaryButton>
                                </form>
                                {(recentlySuccessful || status === 'verification-submitted') && (
                                    <div className="mt-2 text-sm font-medium text-green-600">Verification submitted.</div>
                                )}
                            </div>

                            <div className="mt-8 border-t border-gray-800 pt-6">
                                <h4 className="text-brand-gold font-bold mb-2">Verification status</h4>
                                <div
                                    title="Advanced: enhanced checks • Standard: ID and address verified • Basic: email, phone, country • Unverified"
                                    className="text-sm text-gray-400">
                                    {business?.verification_status === 'verified' ? 'Verified' : business?.verification_status === 'pending' ? 'Pending review' : 'Unverified'}
                                    {business?.verification_level ? ` • ${business.verification_level}` : ''}
                                </div>
                                <div className="mt-3">
                                    {verifications?.data?.length ? (
                                        <ul className="space-y-2">
                                            {verifications.data.map((v) => (
                                                <li key={v.id} className="bg-gray-900 p-3 rounded-lg border border-gray-700 text-sm text-gray-200">
                                                    <div className="flex items-center justify-between">
                                                        <span>{v.document_type}</span>
                                                        <span className="text-gray-400">{v.status}</span>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <div className="text-sm text-gray-500">No submissions yet.</div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
