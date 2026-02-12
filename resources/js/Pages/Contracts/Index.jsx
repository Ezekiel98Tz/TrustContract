import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ auth, contracts, filters }) {
    const toneClass = (tone) => {
        if (tone === 'success') return 'bg-green-900 text-green-200';
        if (tone === 'info') return 'bg-blue-900 text-blue-200';
        if (tone === 'danger') return 'bg-red-900 text-red-200';
        if (tone === 'warning') return 'bg-yellow-900 text-yellow-200';
        return 'bg-gray-800 text-gray-200';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">My Contracts</h2>}
        >
            <Head title="My Contracts" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex space-x-4">
                            <Link
                                href={route('contracts.index')}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${!filters.status ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                All
                            </Link>
                            <Link
                                href={route('contracts.index', { status: 'draft' })}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === 'draft' ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                Drafts
                            </Link>
                            <Link
                                href={route('contracts.index', { status: 'signed' })}
                                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${filters.status === 'signed' ? 'bg-brand-gold text-brand-black' : 'text-gray-400 hover:text-brand-gold'}`}
                            >
                                Signed
                            </Link>
                        </div>
                        <Link
                            href={route('contracts.create')}
                            className="inline-flex items-center px-4 py-2 bg-brand-gold border border-transparent rounded-md font-bold text-xs text-brand-black uppercase tracking-widest hover:bg-yellow-500 focus:bg-yellow-500 active:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Create Contract
                        </Link>
                    </div>

                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {contracts.data.length === 0 ? (
                                <div className="text-center py-10 text-gray-500">
                                    No contracts found.
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-800">
                                        <thead className="bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Title</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Role</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Counterparty</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Amount</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Status</th>
                                                <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Date</th>
                                                <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-brand-black divide-y divide-gray-800">
                                            {contracts.data.map((contract) => {
                                                const isBuyer = contract.buyer_id === auth.user.id;
                                                return (
                                                    <tr key={contract.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                                            {contract.title}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {isBuyer ? 'Buyer' : 'Seller'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {isBuyer ? contract.seller?.name : contract.buyer?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {(contract.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: contract.currency || 'USD' })}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                ${toneClass(contract.status_tone)}`}>
                                                                {contract.status_label}
                                                            </span>
                                                            {contract.disputes_active > 0 && (
                                                                <span className="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-900 text-orange-200 border border-orange-800" title="Active disputes on this contract">
                                                                    Disputes: {contract.disputes_active}
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                            {new Date(contract.created_at).toLocaleDateString()}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <Link href={route('contracts.show', contract.id)} className="text-brand-gold hover:text-white transition-colors duration-150">
                                                                View
                                                            </Link>
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
