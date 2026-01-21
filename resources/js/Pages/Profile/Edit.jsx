import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import StarRating from '@/components/StarRating';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, reputation }) {
    const user = usePage().props.auth.user;
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-brand-gold">Profile</h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-brand-black p-4 shadow sm:rounded-lg sm:p-8 border border-gray-800">
                        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-bold text-brand-gold uppercase tracking-wide">Reputation</h3>
                                <p className="text-sm text-gray-400">{user.name}</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <StarRating value={reputation?.avg || 0} readOnly size={22} />
                                <span className="text-sm text-gray-300">{reputation?.avg ?? '-'} / 5</span>
                                <span className="text-sm text-gray-500">({reputation?.count ?? 0} reviews)</span>
                            </div>
                        </div>
                        {reputation?.recent?.length > 0 ? (
                            <ul className="mt-4 space-y-3">
                                {reputation.recent.map((rv) => (
                                    <li key={rv.id} className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                        <div className="flex items-center justify-between">
                                            <div className="text-sm text-gray-400">By {rv.reviewer?.name ?? 'User'}</div>
                                            <StarRating value={rv.rating} readOnly size={18} />
                                        </div>
                                        {rv.comment && <p className="mt-2 text-gray-200 text-sm">{rv.comment}</p>}
                                        <div className="mt-2 text-xs text-gray-500">{new Date(rv.created_at).toLocaleString()}</div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-4 text-sm text-gray-500">No reviews yet.</p>
                        )}
                    </div>
                    <div className="bg-brand-black p-4 shadow sm:rounded-lg sm:p-8 border border-gray-800">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="bg-brand-black p-4 shadow sm:rounded-lg sm:p-8 border border-gray-800">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-brand-black p-4 shadow sm:rounded-lg sm:p-8 border border-gray-800">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
