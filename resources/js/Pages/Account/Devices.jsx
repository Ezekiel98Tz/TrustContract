import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Devices({ auth, devices }) {
    const { post, processing } = useForm();
    const revoke = (id) => {
        post(route('account.devices.revoke', id));
    };
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Devices & Sessions</h2>}
        >
            <Head title="Devices" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {devices.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">No devices recorded.</div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Device</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">IP</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">First Seen</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Last Seen</th>
                                                <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {devices.map((d) => (
                                                <tr key={d.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-white">
                                                        {d.name || (d.user_agent?.slice(0, 60) || 'Unknown')}
                                                        {d.revoked_at && <span className="ml-2 text-xs text-red-400">(revoked)</span>}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{d.ip_address || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{d.first_seen_at ? new Date(d.first_seen_at).toLocaleString() : '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{d.last_seen_at ? new Date(d.last_seen_at).toLocaleString() : '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        {!d.revoked_at ? (
                                                            <button
                                                                onClick={() => revoke(d.id)}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-red-800 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                                            >
                                                                Revoke
                                                            </button>
                                                        ) : (
                                                            <span className="text-gray-500">Revoked</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
