import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ auth, user, contracts, stats }) {
    const { post } = useForm();

    const toneClass = (tone) => {
        if (tone === 'success') return 'bg-green-900 text-green-200 border-green-700';
        if (tone === 'info') return 'bg-blue-900 text-blue-200 border-blue-700';
        if (tone === 'danger') return 'bg-red-900 text-red-200 border-red-700';
        if (tone === 'warning') return 'bg-yellow-900 text-yellow-200 border-yellow-700';
        return 'bg-gray-800 text-gray-200 border-gray-700';
    };

    const verificationLabel = (value) => {
        if (value === 'verified') return 'Verified';
        if (value === 'unverified') return 'Unverified';
        if (value === 'pending') return 'Pending';
        return 'Unknown';
    };

    const toggleVerification = () => {
        if (user.verification_status === 'verified') {
            if (confirm('Are you sure you want to unverify this user?')) {
                post(route('admin.users.unverify', user.id));
            }
        } else {
            post(route('admin.users.verify', user.id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-brand-gold">User Details: {user.name}</h2>
                    <Link href={route('admin.users.index')} className="text-sm text-gray-400 hover:text-white">
                        &larr; Back to Users
                    </Link>
                </div>
            }
        >
            <Head title={`User: ${user.name}`} />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    
                    {/* User Overview Card */}
                    <div className="bg-brand-black overflow-hidden shadow-sm sm:rounded-lg border border-gray-800">
                        <div className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-2xl font-bold text-brand-gold">{user.name}</h3>
                                    <p className="text-gray-400">{user.email}</p>
                                    <div className="mt-2 flex space-x-2">
                                        <span className="px-3 py-1 bg-gray-800 text-gray-300 text-xs rounded-full font-bold uppercase tracking-wide border border-gray-700">
                                            {user.role}
                                        </span>
                                        <span
                                            title="Advanced: enhanced checks • Standard: ID and address verified • Basic: email, phone, country • Unverified"
                                            className={`px-3 py-1 text-xs rounded-full font-bold uppercase tracking-wide border
                                            ${user.verification_status === 'verified' 
                                                ? 'bg-green-900 text-green-200 border-green-700' 
                                                : 'bg-yellow-900 text-yellow-200 border-yellow-700'}`}>
                                            {verificationLabel(user.verification_status)}{user.verification_level && user.verification_level !== 'none' ? ` • ${user.verification_level}` : ''}
                                        </span>
                                    </div>
                                </div>
                                
                                {/* Important Actions */}
                                <div className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                    <h4 className="text-sm font-bold text-gray-400 uppercase mb-3">Important Actions</h4>
                                    <button
                                        onClick={toggleVerification}
                                        className={`w-full px-4 py-2 rounded-md font-bold shadow transition border
                                            ${user.verification_status === 'verified' 
                                                ? 'bg-red-900 text-red-200 border-red-700 hover:bg-red-800' 
                                                : 'bg-brand-gold text-brand-black border-brand-gold hover:bg-yellow-500'}`}
                                    >
                                        {user.verification_status === 'verified' ? 'Revoke Verification' : 'Verify User'}
                                    </button>
                                </div>
                            </div>

                            {/* Stats */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                                <div className="bg-gray-900 p-4 rounded-md border border-gray-800">
                                    <div className="text-sm text-gray-400">Total Contracts</div>
                                    <div className="text-2xl font-bold text-white">{stats.total_contracts}</div>
                                </div>
                                <div className="bg-gray-900 p-4 rounded-md border border-gray-800">
                                    <div className="text-sm text-gray-400">Active Contracts</div>
                                    <div className="text-2xl font-bold text-brand-gold">{stats.active_contracts}</div>
                                </div>
                                <div className="bg-gray-900 p-4 rounded-md border border-gray-800">
                                    <div className="text-sm text-gray-400">Completed</div>
                                    <div className="text-2xl font-bold text-gray-200">{stats.completed_contracts}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Contracts Activity */}
                    <div className="bg-brand-black overflow-hidden shadow-sm sm:rounded-lg border border-gray-800">
                        <div className="px-6 py-4 border-b border-gray-800 bg-gray-900">
                            <h3 className="text-lg font-bold text-brand-gold">Contract Activity</h3>
                        </div>
                        <div className="p-6">
                            {contracts.length === 0 ? (
                                <p className="text-gray-500 text-center py-4">No contract activity found.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Title</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Counterparty</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {contracts.map((contract) => {
                                                const isBuyer = contract.buyer_id === user.id;
                                                return (
                                                    <tr key={contract.id} className="hover:bg-gray-900 transition-colors">
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{contract.id}</td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                                            {contract.title}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {isBuyer ? 'Buyer' : 'Seller'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {isBuyer ? contract.seller?.name : contract.buyer?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full border
                                                                ${toneClass(contract.status_tone)}`}>
                                                                {contract.status_label}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {new Date(contract.created_at).toLocaleDateString()}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
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
