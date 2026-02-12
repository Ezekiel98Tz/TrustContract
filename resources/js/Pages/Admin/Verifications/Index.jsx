import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';

export default function Index({ auth, verifications, filters }) {
    const { processing } = useForm();
    const { flash } = usePage().props;

    const review = (id, status) => {
        router.patch(route('admin.verifications.review', id), { status }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Verifications</h2>}
        >
            <Head title="Verifications" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md border border-green-800 bg-green-900/20 p-4">
                            <div className="text-sm text-green-200 font-semibold">{flash.success}</div>
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 rounded-md border border-red-800 bg-red-900/20 p-4">
                            <div className="text-sm text-red-200 font-semibold">{flash.error}</div>
                        </div>
                    )}
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex space-x-4">
                            <Link
                                href={route('admin.verifications.index')}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${!filters.status ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                All
                            </Link>
                            <Link
                                href={route('admin.verifications.index', { status: 'pending' })}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === 'pending' ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                Pending
                            </Link>
                            <Link
                                href={route('admin.verifications.index', { status: 'approved' })}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === 'approved' ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                Approved
                            </Link>
                            <Link
                                href={route('admin.verifications.index', { status: 'rejected' })}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === 'rejected' ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                Rejected
                            </Link>
                        </div>
                    </div>

                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {verifications.data.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">No verifications found.</div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">User</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Status</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Doc Type</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Document</th>
                                                <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {verifications.data.map((v) => (
                                                <tr key={v.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-white">
                                                        {v.user?.name} <span className="text-gray-500">({v.user?.email})</span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{v.status}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{v.document_type || '-'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <a
                                                            href={v.document_path ? `/storage/${v.document_path}` : '#'}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="text-brand-gold hover:text-white"
                                                        >
                                                            View
                                                        </a>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        {v.status === 'pending' ? (
                                                            <div className="flex justify-end gap-2">
                                                                <button
                                                                    onClick={() => review(v.id, 'approved')}
                                                                    disabled={processing}
                                                                    className="inline-flex items-center px-3 py-2 bg-green-800 border border-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                                                                >
                                                                    Approve
                                                                </button>
                                                                <button
                                                                    onClick={() => review(v.id, 'rejected')}
                                                                    disabled={processing}
                                                                    className="inline-flex items-center px-3 py-2 bg-red-800 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                                                >
                                                                    Reject
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-500">Reviewed</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-6 flex justify-between items-center">
                                <div className="text-sm text-gray-400">
                                    Page {verifications.current_page} of {verifications.last_page}
                                </div>
                                <div className="flex gap-2">
                                    {verifications.links.map((link, idx) => (
                                        <Link
                                            key={idx}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded-md text-sm ${link.active ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
