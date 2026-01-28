import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function Dashboard({ auth, stats }) {
    const { trust } = usePage().props;
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Trust / Phase 2 status */}
                    <div className="mb-6">
                        {(!auth.user.email_verified_at || !auth.user.phone || !auth.user.country) && (
                            <div className="rounded-md border border-yellow-700 bg-yellow-900/20 p-4">
                                <div className="text-sm text-yellow-200 font-semibold">
                                    Complete Personal Information
                                </div>
                                <div className="mt-1 text-sm text-gray-200">
                                    Verify email and provide phone + country to unlock contract creation/signing.
                                </div>
                                <div className="mt-2">
                                    <Link
                                        href={route('account.personal-information.edit')}
                                        className="inline-flex items-center px-3 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500"
                                    >
                                        Go to Personal Information
                                    </Link>
                                </div>
                            </div>
                        )}
                        {trust?.completion?.percent !== undefined && (
                            <div className="mt-4 rounded-md border border-gray-800 bg-brand-black p-4">
                                <div className="text-xs font-semibold uppercase tracking-wider text-gray-400">Profile completeness</div>
                                <div className="mt-2 flex items-center justify-between">
                                    <div className="w-full mr-4 h-2 rounded bg-gray-700 overflow-hidden">
                                        <div
                                            style={{ width: `${Math.max(0, Math.min(100, Math.round(trust.completion.percent)))}%` }}
                                            className={`h-2 ${trust.completion.percent >= 80 ? 'bg-green-500' : trust.completion.percent >= 50 ? 'bg-yellow-500' : 'bg-red-500'}`}
                                        />
                                    </div>
                                    <div className="text-sm text-gray-200 font-semibold">{Math.round(trust.completion.percent)}%</div>
                                </div>
                            </div>
                        )}
                    </div>
                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-6">
                        <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg p-6 border border-gray-800">
                            <div className="text-gray-400 text-sm uppercase tracking-wider">Active Contracts</div>
                            <div className="text-3xl font-bold text-brand-gold">{stats.active}</div>
                        </div>
                        <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg p-6 border border-gray-800">
                            <div className="text-gray-400 text-sm uppercase tracking-wider">Completed Contracts</div>
                            <div className="text-3xl font-bold text-white">{stats.completed}</div>
                        </div>
                        <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg p-6 border border-gray-800">
                            <div className="text-gray-400 text-sm uppercase tracking-wider">Total Contracts</div>
                            <div className="text-3xl font-bold text-white">{stats.total}</div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-bold text-brand-gold uppercase tracking-wide">Quick Actions</h3>
                            </div>
                            <div className="flex gap-4">
                                <Link
                                    href={route('contracts.create')}
                                    className="inline-flex items-center px-6 py-3 bg-brand-gold border border-transparent rounded-md font-bold text-sm text-brand-black uppercase tracking-widest hover:bg-yellow-500 focus:bg-yellow-500 active:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 transition ease-in-out duration-150 shadow-md transform hover:-translate-y-0.5"
                                >
                                    New Contract
                                </Link>
                                <Link
                                    href={route('contracts.index')}
                                    className="inline-flex items-center px-6 py-3 bg-gray-800 border border-gray-700 rounded-md font-semibold text-sm text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    View All Contracts
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
