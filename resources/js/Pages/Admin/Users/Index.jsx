import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, users, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.users.index'), { search: searchTerm }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">User Management</h2>}
        >
            <Head title="User Management" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Filters & Actions */}
                    <div className="flex justify-between items-center mb-6">
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search users..."
                                className="rounded-md border-gray-700 bg-gray-900 text-white shadow-sm focus:border-brand-gold focus:ring-brand-gold"
                            />
                            <button
                                type="submit"
                                className="px-4 py-2 bg-brand-gold text-brand-black rounded-md font-bold hover:bg-yellow-500 transition"
                            >
                                Search
                            </button>
                        </form>
                    </div>

                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-800">
                                    <thead className="bg-gray-900">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Email</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Role</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-bold text-brand-gold uppercase tracking-wider">Joined</th>
                                            <th className="px-6 py-3 text-right text-xs font-bold text-brand-gold uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-brand-black divide-y divide-gray-800">
                                        {users.data.map((user) => (
                                            <tr key={user.id} className="hover:bg-gray-900 transition-colors duration-150">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-white">{user.name}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-400">{user.email}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        ${user.role === 'Admin' ? 'bg-gray-700 text-brand-gold border border-brand-gold' : 
                                                          user.role === 'Buyer' ? 'bg-blue-900 text-blue-200' : 
                                                          'bg-green-900 text-green-200'}`}>
                                                        {user.role}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        ${user.verification_status === 'verified' ? 'bg-green-900 text-green-200' : 
                                                          'bg-yellow-900 text-yellow-200'}`}>
                                                        {user.verification_status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                    {new Date(user.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <Link
                                                        href={route('admin.users.show', user.id)}
                                                        className="text-brand-gold hover:text-white font-bold hover:underline"
                                                    >
                                                        View Details
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            
                            {/* Pagination */}
                            {users.links && users.links.length > 3 && (
                                <div className="mt-6 flex justify-center">
                                    {users.links.map((link, key) => (
                                        <Link
                                            key={key}
                                            href={link.url || '#'}
                                            className={`mx-1 px-4 py-2 border rounded-md text-sm font-medium transition-colors duration-150
                                                ${link.active 
                                                    ? 'bg-brand-gold text-brand-black border-brand-gold' 
                                                    : 'bg-gray-900 text-gray-400 border-gray-700 hover:bg-gray-800'
                                                } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
