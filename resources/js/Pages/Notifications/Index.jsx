import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ auth, notifications }) {
    const markAll = () => {
        router.patch(route('notifications.read_all'), {}, { preserveScroll: true });
    };
    const markOne = (id) => {
        router.patch(route('notifications.read', id), {}, { preserveScroll: true });
    };
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Notifications</h2>}
        >
            <Head title="Notifications" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <button
                            onClick={markAll}
                            className="px-4 py-2 bg-brand-gold text-brand-black rounded-md font-bold hover:bg-yellow-500"
                        >
                            Mark All Read
                        </button>
                    </div>
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {notifications.data.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">No notifications.</div>
                            ) : (
                                <ul className="space-y-3">
                                    {notifications.data.map((n) => (
                                        <li key={n.id} className={`p-4 rounded-lg border ${n.read_at ? 'border-gray-700 bg-gray-900' : 'border-yellow-700 bg-yellow-900/10'}`}>
                                            <div className="flex items-center justify-between">
                                                <div className="text-sm text-gray-300">{n.message}</div>
                                                {!n.read_at && (
                                                    <button
                                                        onClick={() => markOne(n.id)}
                                                        className="px-3 py-1 bg-gray-800 text-gray-200 rounded-md text-xs border border-gray-700 hover:bg-gray-700"
                                                    >
                                                        Mark Read
                                                    </button>
                                                )}
                                            </div>
                                            <div className="mt-1 text-xs text-gray-500">{new Date(n.created_at).toLocaleString()}</div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <div className="mt-4 flex items-center justify-between">
                                {notifications.links && notifications.links.map((l, idx) => (
                                    <Link
                                        key={idx}
                                        href={l.url || '#'}
                                        className={`px-3 py-1 text-xs rounded ${l.active ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'}`}
                                        dangerouslySetInnerHTML={{ __html: l.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
