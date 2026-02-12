import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, disputes, filters }) {
    const { patch, processing } = useForm();
    const { flash } = usePage().props;
    const [localSuccess, setLocalSuccess] = useState(null);
    const [localError, setLocalError] = useState(null);

    const review = (id, payload) => {
        const data = { ...payload };
        if (data.status === 'mediate' && !data.mediator_id) {
            data.mediator_id = auth.user.id;
            data.mediation_notes = 'Initiated mediation';
        }
        router.patch(route('admin.disputes.review', { dispute: id }), data, {
            preserveScroll: true,
            onSuccess: () => {
                setLocalError(null);
                setLocalSuccess('Dispute updated successfully');
                setTimeout(() => setLocalSuccess(null), 3000);
            },
            onError: () => {
                setLocalSuccess(null);
                setLocalError('Failed to update dispute');
                setTimeout(() => setLocalError(null), 3000);
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Disputes</h2>}
        >
            <Head title="Disputes" />
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
                    {localSuccess && (
                        <div className="mb-4 rounded-md border border-green-800 bg-green-900/20 p-4">
                            <div className="text-sm text-green-200 font-semibold">{localSuccess}</div>
                        </div>
                    )}
                    {localError && (
                        <div className="mb-4 rounded-md border border-red-800 bg-red-900/20 p-4">
                            <div className="text-sm text-red-200 font-semibold">{localError}</div>
                        </div>
                    )}
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex space-x-4">
                            <Link
                                href={route('admin.disputes.index')}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${!filters.status ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                All
                            </Link>
                            {['open','mediate','resolved','cancelled'].map(s => (
                                <Link
                                    key={s}
                                    href={route('admin.disputes.index', { status: s })}
                                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === s ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                                >
                                    {s[0].toUpperCase() + s.slice(1)}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {disputes.data.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">No disputes found.</div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">ID</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Contract</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Initiator</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Reason</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Status</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Resolution</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Mediator</th>
                                                <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {disputes.data.map((d) => (
                                                <tr key={d.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-white">{d.id}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <Link href={route('contracts.show', d.contract?.id)} className="text-brand-gold hover:text-white">
                                                            #{d.contract?.id} {d.contract?.title || ''}
                                                        </Link>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.initiator?.name || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.reason || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.status}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.resolution || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.mediator?.name || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex justify-end gap-2">
                                                            <Link
                                                                href={route('admin.disputes.show', { dispute: d.id })}
                                                                className="inline-flex items-center px-3 py-2 bg-gray-800 border border-gray-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                                            >
                                                                View
                                                            </Link>
                                                            <button
                                                                onClick={() => review(d.id, { status: 'open' })}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-gray-800 border border-gray-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                                            >
                                                                Reopen
                                                            </button>
                                                            <button
                                                                onClick={() => review(d.id, { status: 'mediate' })}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-blue-800 border border-blue-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                                            >
                                                                Mediate
                                                            </button>
                                                            <button
                                                                onClick={() => review(d.id, { status: 'resolved', resolution: 'won' })}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-green-800 border border-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                                                            >
                                                                Resolve: Won
                                                            </button>
                                                            <button
                                                                onClick={() => review(d.id, { status: 'resolved', resolution: 'lost' })}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-red-800 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                                            >
                                                                Resolve: Lost
                                                            </button>
                                                            <button
                                                                onClick={() => review(d.id, { status: 'resolved', resolution: 'cancelled' })}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-yellow-800 border border-yellow-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700"
                                                            >
                                                                Resolve: Cancelled
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-6 flex justify-between items-center">
                                <div className="text-sm text-gray-400">
                                    Page {disputes.current_page} of {disputes.last_page}
                                </div>
                                <div className="flex gap-2">
                                    {disputes.links.map((link, idx) => (
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
