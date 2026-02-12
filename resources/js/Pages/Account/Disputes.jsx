import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Disputes({ auth, disputes, filters }) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">My Disputes</h2>}
        >
            <Head title="My Disputes" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex space-x-4">
                            <Link
                                href={route('account.disputes.index')}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${!filters.status ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                All
                            </Link>
                            {['open','mediate','resolved','cancelled'].map(s => (
                                <Link
                                    key={s}
                                    href={route('account.disputes.index', { status: s })}
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
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Opened</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Resolved</th>
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
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.created_at ? new Date(d.created_at).toLocaleString() : '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.resolved_at ? new Date(d.resolved_at).toLocaleString() : '—'}</td>
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
