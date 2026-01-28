import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Sessions({ auth, sessions }) {
    const { delete: destroy, post, processing, data, setData } = useForm({ current_password: '' });

    const deleteSession = (id) => {
        destroy(route('account.sessions.destroy', id));
    };

    const logoutOthers = (e) => {
        e.preventDefault();
        post(route('account.sessions.destroy_others'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Active Sessions</h2>}
        >
            <Head title="Sessions" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="flex items-center justify-between mb-4">
                                <form onSubmit={logoutOthers} className="flex items-center gap-2">
                                    <input
                                        type="password"
                                        value={data.current_password}
                                        onChange={(e) => setData('current_password', e.target.value)}
                                        placeholder="Current password"
                                        className="px-3 py-2 rounded-md bg-gray-800 border border-gray-700 text-sm text-gray-200"
                                        required
                                    />
                                    <button
                                        type="submit"
                                        disabled={processing || !data.current_password}
                                        className="inline-flex items-center px-3 py-2 bg-red-800 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                    >
                                        Log Out Other Sessions
                                    </button>
                                </form>
                            </div>
                            {sessions.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">No active sessions.</div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">IP</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Agent</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Last Activity</th>
                                                <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {sessions.map((s) => (
                                                <tr key={s.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{s.ip_address || '—'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-white">
                                                        {s.user_agent?.slice(0, 80) || 'Unknown'}
                                                        {s.is_current && <span className="ml-2 text-xs text-green-400">(current)</span>}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                        {s.last_activity ? new Date(s.last_activity * 1000).toLocaleString() : '—'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        {!s.is_current ? (
                                                            <button
                                                                onClick={() => deleteSession(s.id)}
                                                                disabled={processing}
                                                                className="inline-flex items-center px-3 py-2 bg-red-800 border border-red-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                                            >
                                                                Log Out
                                                            </button>
                                                        ) : (
                                                            <span className="text-gray-500">Active</span>
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
